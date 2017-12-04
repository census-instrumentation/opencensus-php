/*
 * Copyright 2017 OpenCensus Authors
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

#ifndef PHP_OPENCENSUS_TRACE_MESSAGE_EVENT_H
#define PHP_OPENCENSUS_TRACE_MESSAGE_EVENT_H 1

#include "php.h"
#include "opencensus_trace_time_event.h"

extern zend_class_entry* opencensus_trace_message_event_ce;

typedef struct opencensus_trace_message_event_t {
    opencensus_trace_time_event_t time_event;
    zend_string *type;
    zend_string *id;
    zval options;
} opencensus_trace_message_event_t;

opencensus_trace_message_event_t *opencensus_trace_message_event_alloc();
void opencensus_trace_message_event_free(opencensus_trace_message_event_t *message_event);
int opencensus_trace_message_event_minit(INIT_FUNC_ARGS);
int opencensus_trace_message_event_to_zval(opencensus_trace_message_event_t *message_event, zval *zv);

#endif /* PHP_OPENCENSUS_TRACE_MESSAGE_EVENT_H */
