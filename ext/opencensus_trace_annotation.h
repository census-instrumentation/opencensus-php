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

#ifndef PHP_OPENCENSUS_TRACE_ANNOTATION_H
#define PHP_OPENCENSUS_TRACE_ANNOTATION_H 1

#include "php.h"
#include "opencensus_trace_time_event.h"

extern zend_class_entry* opencensus_trace_annotation_ce;

typedef struct opencensus_trace_annotation_t {
    opencensus_trace_time_event_t time_event;
    zend_string *description;
    zval options;
} opencensus_trace_annotation_t;

opencensus_trace_annotation_t *opencensus_trace_annotation_alloc();
void opencensus_trace_annotation_free(opencensus_trace_annotation_t *annotation);
int opencensus_trace_annotation_minit(INIT_FUNC_ARGS);
int opencensus_trace_annotation_to_zval(opencensus_trace_annotation_t *annotation, zval *zv);

#endif /* PHP_OPENCENSUS_TRACE_ANNOTATION_H */
