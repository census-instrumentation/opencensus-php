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

// Trace functions
PHP_FUNCTION(opencensus_trace_function);
PHP_FUNCTION(opencensus_trace_method);
PHP_FUNCTION(opencensus_trace_list);
PHP_FUNCTION(opencensus_trace_begin);
PHP_FUNCTION(opencensus_trace_finish);
PHP_FUNCTION(opencensus_trace_clear);
PHP_FUNCTION(opencensus_trace_set_context);
PHP_FUNCTION(opencensus_trace_context);
PHP_FUNCTION(opencensus_trace_add_label);
PHP_FUNCTION(opencensus_trace_add_root_label);

// Extension lifecycle hooks
int opencensus_minit(INIT_FUNC_ARGS);
int opencensus_rinit(TSRMLS_D);
int opencensus_rshutdown(TSRMLS_D);

#endif /* PHP_OPENCENSUS_TRACE_H */
