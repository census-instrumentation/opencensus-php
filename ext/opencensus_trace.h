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

#ifndef PHP_OPENCENSUS_TRACE_H
#define PHP_OPENCENSUS_TRACE_H 1

#include "opencensus_trace_span.h"
#include "opencensus_trace_context.h"
#include "opencensus_trace_message_event.h"
#include "opencensus_trace_annotation.h"
#include "opencensus_trace_link.h"

// Trace functions
PHP_FUNCTION(opencensus_trace_function);
PHP_FUNCTION(opencensus_trace_method);
PHP_FUNCTION(opencensus_trace_list);
PHP_FUNCTION(opencensus_trace_begin);
PHP_FUNCTION(opencensus_trace_finish);
PHP_FUNCTION(opencensus_trace_clear);
PHP_FUNCTION(opencensus_trace_set_context);
PHP_FUNCTION(opencensus_trace_context);
PHP_FUNCTION(opencensus_trace_add_attribute);
PHP_FUNCTION(opencensus_trace_add_annotation);
PHP_FUNCTION(opencensus_trace_add_link);
PHP_FUNCTION(opencensus_trace_add_message_event);

void span_dtor(zval *zv);
void opencensus_trace_minit();
void opencensus_trace_mshutdown();
void opencensus_trace_clear(int reset TSRMLS_DC);
void opencensus_trace_execute_ex (zend_execute_data *execute_data TSRMLS_DC);
void opencensus_trace_execute_internal(INTERNAL_FUNCTION_PARAMETERS);


#endif /* PHP_OPENCENSUS_TRACE_H */
