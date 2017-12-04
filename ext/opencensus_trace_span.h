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

#ifndef PHP_OPENCENSUS_TRACE_SPAN_H
#define PHP_OPENCENSUS_TRACE_SPAN_H 1

#include "php.h"

extern zend_class_entry* opencensus_trace_span_ce;

// TraceSpan struct
typedef struct opencensus_trace_span_t {
    zend_string *name;
    zend_string *span_id;
    double start;
    double stop;
    struct opencensus_trace_span_t *parent;
    zval stackTrace;

    // zend_string* => zval*
    HashTable *attributes;

    // list of time events
    HashTable *time_events;

    // list of links
    HashTable *links;
} opencensus_trace_span_t;

int opencensus_trace_span_add_attribute(opencensus_trace_span_t *span, zend_string *k, zend_string *v);
int opencensus_trace_span_add_attribute_str(opencensus_trace_span_t *span, char *k, zend_string *v);
int opencensus_trace_span_add_annotation(opencensus_trace_span_t *span, zend_string *description, zval *options);
int opencensus_trace_span_add_link(opencensus_trace_span_t *span, zend_string *trace_id, zend_string *span_id, zval *options);
int opencensus_trace_span_add_message_event(opencensus_trace_span_t *span, zend_string *type, zend_string *id, zval *options);
int opencensus_trace_span_apply_span_options(opencensus_trace_span_t *span, zval *span_options);
opencensus_trace_span_t *opencensus_trace_span_alloc();
void opencensus_trace_span_free(opencensus_trace_span_t *span);
int opencensus_trace_span_minit(INIT_FUNC_ARGS);
int opencensus_trace_span_to_zval(opencensus_trace_span_t *span, zval *zv);

#endif /* PHP_OPENCENSUS_TRACE_SPAN_H */
