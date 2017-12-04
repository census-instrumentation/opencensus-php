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

/*
 * This is the implementation of the OpenCensus\Trace\Ext\Annoation class. The PHP
 * equivalent is:
 *
 * namespace OpenCensus\Trace\Ext;
 *
 * class Annoation {
 * }
 */

#include "php_opencensus.h"
#include "opencensus_trace_annotation.h"

zend_class_entry* opencensus_trace_annotation_ce = NULL;

/**
 * Returns an allocated initialized pointer to a opencensus_trace_annotation_t
 * struct.
 *
 * Note that you will have to call opencensus_trace_annotation_span_free
 * yourself when it's time to clean up the memory.
 */
opencensus_trace_annotation_t *opencensus_trace_annotation_alloc()
{
    opencensus_trace_annotation_t *annotation = emalloc(sizeof(opencensus_trace_annotation_t));
    annotation->time_event.type = OPENCENSUS_TRACE_TIME_EVENT_ANNOTATION;
    annotation->description = NULL;
    array_init(&annotation->options);
    return annotation;
}

void opencensus_trace_annotation_free(opencensus_trace_annotation_t *annotation)
{
    if (annotation->description) {
        zend_string_release(annotation->description);
    }
    if (Z_TYPE(annotation->options) != IS_NULL) {
        ZVAL_PTR_DTOR(&annotation->options);
    }
}
