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
#include "opencensus_trace_link.h"

zend_class_entry* opencensus_trace_link_ce = NULL;

/**
 * Returns an allocated initialized pointer to a opencensus_trace_link_t
 * struct.
 *
 * Note that you will have to call opencensus_trace_link_span_free
 * yourself when it's time to clean up the memory.
 */
opencensus_trace_link_t *opencensus_trace_link_alloc()
{
    opencensus_trace_link_t *link = emalloc(sizeof(opencensus_trace_link_t));
    link->trace_id = NULL;
    link->span_id = NULL;
    array_init(&link->options);
    return link;
}

void opencensus_trace_link_free(opencensus_trace_link_t *link)
{
    if (link->trace_id) {
        zend_string_release(link->trace_id);
    }
    if (link->span_id) {
        zend_string_release(link->span_id);
    }
    if (Z_TYPE(link->options) != IS_NULL) {
        ZVAL_PTR_DTOR(&link->options);
    }
}
