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
 * This is the implementation of the OpenCensus\Trace\SpanContext class. The PHP
 * equivalent is:
 *
 * namespace OpenCensus\Trace;
 *
 * class Context {
 *   protected $traceId;
 *   protected $spanId;
 *
 *   public function __construct(array $contextOptions)
 *   {
 *     foreach ($contextOptions as $k => $v) {
 *       $this->__set($k, $v);
 *     }
 *   }
 *
 *   public function spanId()
 *   {
 *     return $this->spanId;
 *   }
 * *
 *   public function traceId()
 *   {
 *     return $this->traceId;
 *   }
 * }
 */

#include "opencensus_trace_context.h"

zend_class_entry* opencensus_trace_context_ce = NULL;

ZEND_BEGIN_ARG_INFO_EX(arginfo_OpenCensusTraceContext_construct, 0, 0, 1)
	ZEND_ARG_ARRAY_INFO(0, contextOptions, 0)
ZEND_END_ARG_INFO();

/**
 * Initializer for OpenCensus\Trace\SpanContext
 *
 * @param array $contextOptions
 */
static PHP_METHOD(OpenCensusTraceContext, __construct) {
    zval *v;
    zend_string *k;
    HashTable *context_options;

    if (zend_parse_parameters(ZEND_NUM_ARGS(), "h", &context_options) == FAILURE) {
        return;
    }

    ZEND_HASH_FOREACH_STR_KEY_VAL(context_options, k, v) {
        zend_update_property(opencensus_trace_context_ce, getThis(), ZSTR_VAL(k), strlen(ZSTR_VAL(k)), v);
    } ZEND_HASH_FOREACH_END();
}

/**
 * Fetch the span id
 *
 * @return string
 */
static PHP_METHOD(OpenCensusTraceContext, spanId) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_context_ce, getThis(), "spanId", sizeof("spanId") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the trace id
 *
 * @return string
 */
static PHP_METHOD(OpenCensusTraceContext, traceId) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_context_ce, getThis(), "traceId", sizeof("traceId") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/* Declare method entries for the OpenCensus\Trace\SpanContext class */
static zend_function_entry opencensus_trace_context_methods[] = {
    PHP_ME(OpenCensusTraceContext, __construct, arginfo_OpenCensusTraceContext_construct, ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
    PHP_ME(OpenCensusTraceContext, spanId, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceContext, traceId, NULL, ZEND_ACC_PUBLIC)
    PHP_FE_END
};

/* Module init handler for registering the OpenCensus\Trace\SpanContext class */
int opencensus_trace_context_minit(INIT_FUNC_ARGS) {
    zend_class_entry ce;

    INIT_CLASS_ENTRY(ce, "OpenCensus\\Trace\\Ext\\SpanContext", opencensus_trace_context_methods);
    opencensus_trace_context_ce = zend_register_internal_class(&ce);

    zend_declare_property_null(opencensus_trace_context_ce, "spanId", sizeof("spanId") - 1, ZEND_ACC_PROTECTED);
    zend_declare_property_null(opencensus_trace_context_ce, "traceId", sizeof("traceId") - 1, ZEND_ACC_PROTECTED);

    return SUCCESS;
}
