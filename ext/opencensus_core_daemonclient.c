/*
 * Copyright 2018 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

#ifndef PHP_WIN32

#include <pthread.h>
#include <unistd.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <stdbool.h>
#include <stdatomic.h>
#include "zend_interfaces.h"
#include "zend_types.h"
#include "php_opencensus.h"
#include "opencensus_core_daemonclient.h"
#include "varint.h"

const char protocol_version = 1;

#define varint_max_len 10

#define swap_uint64_t(x) \
    ((uint64_t)((((uint64_t)(x) & 0xff00000000000000ULL) >> 56) | \
                (((uint64_t)(x) & 0x00ff000000000000ULL) >> 40) | \
                (((uint64_t)(x) & 0x0000ff0000000000ULL) >> 24) | \
                (((uint64_t)(x) & 0x000000ff00000000ULL) >>  8) | \
                (((uint64_t)(x) & 0x00000000ff000000ULL) <<  8) | \
                (((uint64_t)(x) & 0x0000000000ff0000ULL) << 24) | \
                (((uint64_t)(x) & 0x000000000000ff00ULL) << 40) | \
                (((uint64_t)(x) & 0x00000000000000ffULL) << 56)))

#define swap_uint32_t(x) \
    ((uint32_t)((((uint32_t)(x) & 0xff000000) >> 24) | \
                (((uint32_t)(x) & 0x00ff0000) >>  8) | \
                (((uint32_t)(x) & 0x0000ff00) <<  8) | \
                (((uint32_t)(x) & 0x000000ff) << 24)))

typedef struct {
	char *data;
	size_t len;
	size_t cap;
} daemon_msg;

typedef enum msg_type {
	// PHP lifecycle events (1-19)
	MSG_PROC_INIT             = 1,
	MSG_PROC_SHUTDOWN,
	MSG_REQ_INIT,
	MSG_REQ_SHUTDOWN,
	// trace types (20-39)
	MSG_TRACE_EXPORT          = 20,
	// stats types (40-...)
	MSG_MEASURE_CREATE        = 40,
	MSG_VIEW_REPORTING_PERIOD,
	MSG_VIEW_REGISTER,
	MSG_VIEW_UNREGISTER,
	MSG_STATS_RECORD
} msg_type;

typedef struct node {
	struct node *next;
	daemon_msg  header;
	daemon_msg  msg;
} node;

typedef struct daemonclient {
	atomic_bool        enabled;
	atomic_int         seq_nr;
	int                sockfd;
	char               pid[varint_max_len];
	size_t             pid_len;
	struct sockaddr_un addr;
	node               *head;
	node               *tail;
	pthread_t          thread_id;
	pthread_mutex_t    mu;
	pthread_cond_t     has_data;

} daemonclient;

/* true process global */
static daemonclient *mdc = NULL;

static inline int check_buffer(daemon_msg *msg, size_t min_req_size)
{
	min_req_size += msg->len;
	if (min_req_size > msg->cap) {
		msg->data = realloc(msg->data, min_req_size + 1024);
		if (!msg->data) {
			return false;
		}
		msg->cap = min_req_size + 1024;
	}
	return true;
}

static inline void msg_destroy(daemon_msg *msg)
{
	if (msg->cap > 0) {
		free(msg->data);
		msg->cap = 0;
		msg->len = 0;
	}
}

static void *clear_msg_list(node **head)
{
	node *old;
	node *n = *head;
	*head = NULL;

	while (n != NULL) {
		old = n;
		n = n->next;
		msg_destroy(&old->header);
		msg_destroy(&old->msg);
		free(old);
	}

	return NULL;
}

static inline int encode_double(daemon_msg *msg, double d)
{
	if (!check_buffer(msg, 8)) {
		return false;
	}


#if SIZEOF_ZEND_LONG < 8
	union f32 {
		float f;
		uint32_t i;
	} m = { (float) d };
#ifndef WORDS_BIGENDIAN
	m.i = swap_uint32_t(m.i);
#endif // WORDS_BIGENDIAN
	memset(msg->data+msg->len, 0, 8);
	memcpy(msg->data+msg->len+2, &m.f, sizeof(float));
#else // SIZEOF_ZEND_LONG
	union d64 {
		double d;
		uint64_t i;
	} m = { d };
#ifndef WORDS_BIGENDIAN
	m.i = swap_uint64_t(m.i);
#endif // WORDS_BIGENDIAN
	memcpy(msg->data+msg->len, &m.d, sizeof(double));
#endif // SIZEOF_ZEND_LONG
	msg->len += 8;
	return true;
}

static inline int encode_uint64(daemon_msg *msg, uint64_t i)
{
	if (!check_buffer(msg, varint_max_len)) {
		return false;
	}

	size_t ilen = uvarint_encode(msg->data+msg->len, varint_max_len, i);
	if (ilen <= 0) {
		return false;
	}
	msg->len += ilen;

	return true;
}

static inline int encode_string(daemon_msg *msg, char *str, size_t len)
{
	if (!check_buffer(msg, varint_max_len + len)) {
		return false;
	}

	size_t ilen = uvarint_encode(msg->data+msg->len, varint_max_len, len);
	if (ilen <= 0) {
		return false;
	}
	msg->len += ilen;

	strncpy(msg->data+msg->len, str, len);
	msg->len += len;

	return true;
}

static inline int write_msg(daemonclient *dc, daemon_msg msg)
{
	while (msg.len > 0) {
		int sent = send(dc->sockfd, msg.data, msg.len, 0);
		if (sent == -1) {
			return false;
		}
		msg.data += sent;
		msg.len  -= sent;
	}
	return true;
}

static void *process(void *arg)
{
	node *n, *old;
	daemonclient *dc = (daemonclient *)arg;

	while (atomic_load(&dc->enabled)) {
		pthread_mutex_lock(&dc->mu);

		while (dc->head == NULL && atomic_load(&dc->enabled)) {
			pthread_cond_wait(&dc->has_data, &dc->mu);
		}

		n = dc->head;
		dc->head = NULL;
		dc->tail = NULL;

		pthread_mutex_unlock(&dc->mu);

		while (n != NULL) {
			if (!write_msg(dc, n->header) || !write_msg(dc, n->msg)) {
				atomic_store(&dc->enabled, false);
				clear_msg_list(&n);
				return NULL;
			}

			old = n;
			n = n->next;
			msg_destroy(&old->header);
			msg_destroy(&old->msg);
			free(old);
		}
	}

	return NULL;
}

static int send_msg(daemonclient *dc, msg_type type, daemon_msg *msg)
{
	if (!atomic_load(&dc->enabled)) {
		msg_destroy(msg);
		return false;
	}

	daemon_msg header = { malloc(80), 0, 80 };

	// write SOM (start of message)
	memset(header.data, 0, 4);
	header.len = 4;

	// msg_type
	if (!encode_uint64(&header, type)) {
		msg_destroy(&header);
		msg_destroy(msg);
		return false;
	}

	// seq_nr
	if (!encode_uint64(&header, atomic_fetch_add(&dc->seq_nr, 1))) {
		msg_destroy(&header);
		msg_destroy(msg);
		return false;
	}

	// process id
	memcpy(header.data+header.len, &dc->pid, dc->pid_len);
	header.len += dc->pid_len;

	// thread id (0 if not in ZTS mode)
#ifdef ZTS
	if (!encode_uint64(&header, (unsigned long long)pthread_self())) {
		msg_destroy(&header);
		msg_destroy(msg);
		return false;
	}
#else
	*(header.data+header.len) = 0;
	header.len++;
#endif

	// start time
	if (!encode_double(&header, opencensus_now())) {
		msg_destroy(&header);
		msg_destroy(msg);
		return false;
	}

	if (!encode_uint64(&header, msg->len)) {
		msg_destroy(&header);
		msg_destroy(msg);
		return false;
	}

	// create our node to hand off our message to the processing thread
	node *n = malloc(sizeof(node));
	n->next   = NULL;
	n->header = header;
	n->msg    = *msg;
	// empty out msg (node has taken responsibility over the payload)
	msg->data = NULL;
	msg->len  = 0;
	msg->cap  = 0;

	pthread_mutex_lock(&dc->mu);

	if (dc->tail != NULL) {
		dc->tail->next = n;
	} else {
		dc->head = n;
	}
	dc->tail = n;

	pthread_mutex_unlock(&dc->mu);

	pthread_cond_signal(&dc->has_data);

	return true;
}

#endif

daemonclient *daemonclient_create(char *socket_path)
{
	daemonclient *dc = malloc(sizeof(daemonclient));
	pthread_mutex_init(&dc->mu, NULL);
	pthread_cond_init(&dc->has_data, NULL);
	dc->thread_id = 0;
	dc->head = NULL;
	dc->tail = NULL;

	if ((dc->sockfd = socket(AF_UNIX, SOCK_STREAM, 0)) == -1) {
		return dc;
	}

	dc->addr.sun_family = AF_UNIX;
	strncpy(dc->addr.sun_path, socket_path, sizeof(dc->addr.sun_path));

	if (connect(dc->sockfd, (struct sockaddr *)&dc->addr, SUN_LEN(&dc->addr)) == -1) {
		return dc;
	}

	atomic_init(&dc->enabled, true);

	if (pthread_create(&dc->thread_id, NULL, process, dc) != 0) {
		atomic_init(&dc->enabled, false);
		return dc;
	}

	dc->pid_len = uvarint_encode(dc->pid, varint_max_len, getpid());
	atomic_init(&dc->seq_nr, 1);

	return dc;
}

void daemonclient_destroy(daemonclient *dc)
{
	if (dc != NULL) {
		atomic_store(&dc->enabled, false);
		pthread_cond_signal(&dc->has_data);
		if (dc->thread_id > 0) {
			pthread_join(dc->thread_id, NULL);
		}
		pthread_cond_destroy(&dc->has_data);
		pthread_mutex_destroy(&dc->mu);
		close(dc->sockfd);
		clear_msg_list(&dc->head);
		free(dc);
		dc = NULL;
	}
}

PHP_FUNCTION(opencensus_core_send_to_daemonclient)
{
	uint64_t    msg_type;
	zend_string *msg_data;

	if (zend_parse_parameters(ZEND_NUM_ARGS(), "lS", &msg_type, &msg_data) == FAILURE) {
		return;
	}

	daemon_msg msg = { malloc(ZSTR_LEN(msg_data)), ZSTR_LEN(msg_data), ZSTR_LEN(msg_data) };
	memcpy(msg.data, ZSTR_VAL(msg_data), ZSTR_LEN(msg_data));

	if (!send_msg(mdc, msg_type, &msg)) {
		RETURN_FALSE;
	}

	RETURN_TRUE;
}

void opencensus_core_daemonclient_minit(INIT_FUNC_ARGS)
{
	mdc = daemonclient_create(INI_STR(opencensus_client_path));

	daemon_msg msg = { NULL, 0, 0 } ;
	encode_uint64(&msg, protocol_version);
	encode_string(&msg, PHP_VERSION, strlen(PHP_VERSION));
	encode_string(&msg, ZEND_VERSION, strlen(ZEND_VERSION));
	send_msg(mdc, MSG_PROC_INIT, &msg);
}

void opencensus_core_daemonclient_mshutdown(SHUTDOWN_FUNC_ARGS)
{
	daemon_msg msg = { NULL, 0, 0 } ;
	send_msg(mdc, MSG_PROC_SHUTDOWN, &msg);

	daemonclient_destroy(mdc);
}

void opencensus_core_daemonclient_rinit()
{
	daemon_msg msg = { NULL, 0, 0 } ;
	encode_uint64(&msg, protocol_version);
	encode_string(&msg, PHP_VERSION, strlen(PHP_VERSION));
	encode_string(&msg, ZEND_VERSION, strlen(ZEND_VERSION));
	send_msg(mdc, MSG_REQ_INIT, &msg);
}

void opencensus_core_daemonclient_rshutdown()
{
	daemon_msg msg = { NULL, 0, 0 } ;
	send_msg(mdc, MSG_REQ_SHUTDOWN, &msg);
}
