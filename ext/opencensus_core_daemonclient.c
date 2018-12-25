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
#include <sys/socket.h>
#include <sys/un.h>
#include <stdatomic.h>
#include "php_opencensus.h"
#include "opencensus_core_daemonclient.h"
#include "varint.h"

#define varint_max_len 10

#if SIZEOF_ZEND_LONG > 4
#define swap_long(x) \
    ((uint64_t)((((uint64_t)(x) & 0xff00000000000000ULL) >> 56) | \
                (((uint64_t)(x) & 0x00ff000000000000ULL) >> 40) | \
                (((uint64_t)(x) & 0x0000ff0000000000ULL) >> 24) | \
                (((uint64_t)(x) & 0x000000ff00000000ULL) >>  8) | \
                (((uint64_t)(x) & 0x00000000ff000000ULL) <<  8) | \
                (((uint64_t)(x) & 0x0000000000ff0000ULL) << 24) | \
                (((uint64_t)(x) & 0x000000000000ff00ULL) << 40) | \
                (((uint64_t)(x) & 0x00000000000000ffULL) << 56)))
#else
#define swap_long(x) \
    ((uint32_t)((((uint32_t)(x) & 0xff000000) >> 24) | \
                (((uint32_t)(x) & 0x00ff0000) >>  8) | \
                (((uint32_t)(x) & 0x0000ff00) <<  8) | \
                (((uint32_t)(x) & 0x000000ff) << 24)))
#endif


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
	daemon_msg msg;
} node;

typedef struct daemon_client {
	atomic_bool disabled;
	atomic_int seq_nr;
	int sockfd;
	char pid[varint_max_len];
	size_t pid_len;
	struct sockaddr_un addr;
	pthread_t pt_process;
	pthread_mutex_t mu;
	node *head;
	node *tail;
	pthread_cond_t has_data;

} daemon_client;

/* true process global */
static daemon_client *dc;

static void *process(void *arg);
static int send_msg(daemon_client *dc, msg_type type, char *msg, size_t len);
static void *clear_msg_list(void *arg);
inline void encode_double(daemon_msg *msg, double d);
inline int encode_uint64(daemon_msg *msg, uint64_t i);
inline int encode_string(daemon_msg *msg, char *str, size_t len);

daemon_client *daemon_client_create(char *socket_path)
{
	int len;
	daemon_client *dc = malloc(sizeof(daemon_client));


	if ((dc->sockfd = socket(AF_UNIX, SOCK_STREAM, 0)) == -1) {
		free(dc);
		return NULL;
	}

	dc->addr.sun_family = AF_UNIX;
	strncpy(dc->addr.sun_path, socket_path, sizeof(dc->addr.sun_path));
	len = strlen(dc->addr.sun_path) + sizeof(dc->addr.sun_family);

	if (connect(dc->sockfd, (struct sockaddr *)&dc->addr, len) == -1) {
		free(dc);
		return NULL;
	}

	pthread_cond_init(&dc->has_data, NULL);
	pthread_create(&dc->pt_process, NULL, process, (void *)dc);

	dc->pid_len = uvarint_encode(dc->pid, varint_max_len, getpid());
	atomic_init(&dc->disabled, false);
	atomic_init(&dc->seq_nr, 1);

	return dc;
}

void daemon_client_destroy(daemon_client *dc)
{
	if (dc != NULL) {
		atomic_store(&dc->disabled, true);
		pthread_cond_signal(&dc->has_data);
		pthread_join(dc->pt_process, NULL);
		pthread_cond_destroy (&dc->has_data);
		close(dc->sockfd);
		clear_msg_list(&dc->head);
		free(dc);
	}
}

static void *process(void *arg)
{
	node *n;
	daemon_client *dc = (daemon_client *)arg;

	while (!atomic_load(&dc->disabled)) {
		pthread_mutex_lock(&dc->mu);

		while (dc->head == NULL && !atomic_load(&dc->disabled)) {
			pthread_cond_wait(&dc->has_data, &dc->mu);
		}
		n = dc->head;
		dc->head = NULL;
		dc->tail = NULL;

		pthread_mutex_unlock(&dc->mu);

		while (n != NULL) {
			int remainder = n->msg.len;
			char *data    = n->msg.data;

			while (remainder > 0) {
				int sent = send(dc->sockfd, data, remainder, 0);
				if (sent == -1) {
					// error while sending data...
					atomic_store(&dc->disabled, true);
					php_error_docref(NULL, E_ERROR, "%s", "unix_socket error: unable to send OpenCensus data");
					clear_msg_list(&n);
					return NULL;
				}
				data      += sent;
				remainder -= sent;
			}

			node *old = n;
			n = n->next;
			free(old);
		}
	}

	return NULL;
}

static void *clear_msg_list(void *arg)
{
	node *msg = *((node **) arg);
	node *old;

	while (msg != NULL) {
		old = msg;
		msg = msg->next;
		free(old);
	}

	return NULL;
}

//static int create_measure(daemon_client *dc, )

static int send_msg(daemon_client *dc, msg_type type, char *msg, size_t msg_len)
{
	if (atomic_load(&dc->disabled)) {
		return false;
	}

	node *n = malloc(sizeof(node));
	// allocate enough memory for entire payload (header+data payload)
	n->msg.data = malloc(msg_len + 80);
	n->msg.cap  = msg_len+80;

	// write SOM (start of message)
	memset(n->msg.data, 0, 4);
	n->msg.len = 4;

	// msg_type
	*(n->msg.data + n->msg.len) = type;
	n->msg.len++;

	// seq_nr
	if (!encode_uint64(&n->msg, atomic_fetch_add(&dc->seq_nr, 1))) {
		free(n->msg.data);
		free(n);
		return false;
	}

	// process id
	memcpy(n->msg.data+n->msg.len, &dc->pid, dc->pid_len);
	n->msg.len += dc->pid_len;

	// thread id (0 if not in ZTS mode)
#ifdef ZTS
	if (!encode_uint64(&n->msg, (unsigned long long)pthread_self())) {
		free(n->msg.data);
		free(n);
		return false;
	}
#else
	*(n->msg.data+n->msg.len) = 0;
	n->msg.len++;
#endif

	// start time
	encode_double(&n->msg, opencensus_now());

	// add msg payload
	encode_string(&n->msg, msg, msg_len);

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

inline void encode_double(daemon_msg *msg, double d)
{
#ifndef WORDS_BIGENDIAN
	d = swap_long(d);
#endif // WORDS_BIGENDIAN

#if SIZEOF_ZEND_LONG > 4
	memcpy(msg->data, (const void *)&d, 8);
#else // SIZEOF_ZEND_LONG
	memset(msg->data, 0, 8);
	memcpy(msg->data+2, d, 4);
#endif // SIZEOF_ZEND_LONG
	msg->len += 8;
}


inline int encode_uint64(daemon_msg *msg, uint64_t i)
{
	size_t min_req_size = msg->len + varint_max_len + 1024;

	if (min_req_size > msg->cap) {
		msg->data = realloc(msg->data, min_req_size);
		if (!msg->data) {
			return false;
		}
		msg->cap = min_req_size;
	}

	size_t ilen = uvarint_encode(msg->data+msg->len, varint_max_len, i);
	if (ilen <= 0) {
		return false;
	}
	msg->cap = min_req_size;

	return true;
}

inline int encode_string(daemon_msg *msg, char *str, size_t len)
{
	size_t min_req_size = msg->len + len + 1024;

	if (min_req_size > msg->cap) {
		msg->data = realloc(msg->data, min_req_size);
		if (!msg->data) {
			return false;
		}
		msg->cap = min_req_size;
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


void opencensus_core_daemonclient_minit(INIT_FUNC_ARGS)
{
	dc = daemon_client_create(INI_STR(opencensus_client_path));
	// TODO: send MSG_PROC_INIT
}

void opencensus_core_daemonclient_mshutdown(SHUTDOWN_FUNC_ARGS)
{
	// TODO: send MSG_PROC_SHUTDOWN
	daemon_client_destroy(dc);
}

void opencensus_core_daemonclient_rinit()
{
	// TODO: send MSG_REQ_INIT
}

void opencensus_core_daemonclient_rshutdown()
{
	// TODO: send MSG_REQ_SHUTDOWN
}
