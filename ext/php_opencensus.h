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

#ifndef PHP_OPENCENSUS_H
#define PHP_OPENCENSUS_H

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "opencensus_trace.h"
#include "opencensus_core_daemonclient.h"

#ifdef PHP_WIN32
#include "win32/time.h"
#else
#include <sys/time.h>
#endif

#define PHP_OPENCENSUS_VERSION "0.3.0"
#define PHP_OPENCENSUS_EXTNAME "opencensus"

extern zend_module_entry opencensus_module_entry;
#define phpext_opencensus_ptr &opencensus_module_entry

ZEND_BEGIN_MODULE_GLOBALS(opencensus)
    // map of functions we're tracing to callbacks
    HashTable *user_traced_functions;

    // Trace context
    opencensus_trace_span_t *current_span;
    zend_string *trace_id;
    zend_string *trace_parent_span_id;

    // List of collected spans
    HashTable *spans;
ZEND_END_MODULE_GLOBALS(opencensus)

ZEND_EXTERN_MODULE_GLOBALS(opencensus)
#define OPENCENSUS_G(v) ZEND_MODULE_GLOBALS_ACCESSOR(opencensus, v)

double opencensus_now();

#endif /* PHP_OPENCENSUS_H */
