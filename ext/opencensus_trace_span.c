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
 * This is the implementation of the OpenCensus\Trace\Span class. The PHP
 * equivalent is:
 *
 * namespace OpenCensus\Trace;
 *
 * class Span {
 *   const SPAN_KIND_UNKNOWN = 0;
 *   const SPAN_KIND_CLIENT = 1;
 *   const SPAN_KIND_SERVER = 2;
 *   const SPAN_KIND_PRODUCER = 3;
 *   const SPAN_KIND_CONSUMER = 4;
 *
 *   protected $name = "unknown";
 *   protected $spanId;
 *   protected $parentSpanId;
 *   protected $startTime;
 *   protected $endTime;
 *   protected $labels;
 *   protected $kind;
 *
 *   public function __construct(array $spanOptions)
 *   {
 *     foreach ($spanOptions as $k => $v) {
 *       $this->__set($k, $v);
 *     }
 *   }
 *
 *   public function name()
 *   {
 *     return $this->name;
 *   }
 *
 *   public function spanId()
 *   {
 *     return $this->spanId;
 *   }
 *
 *   public function parentSpanId()
 *   {
 *     return $this->parentSpanId;
 *   }
 *
 *   public function startTime()
 *   {
 *     return $this->startTime;
 *   }
 *
 *   public function endTime()
 *   {
 *     return $this->endTime;
 *   }
 *
 *   public function labels()
 *   {
 *     return $this->labels;
 *   }
 *
 *   public function kind()
 *   {
 *     return $this->kind;
 *    }
 * }
 */

#include "opencensus_trace_span.h"

zend_class_entry* opencensus_trace_span_ce = NULL;

ZEND_BEGIN_ARG_INFO_EX(arginfo_OpenCensusTraceSpan_construct, 0, 0, 1)
	ZEND_ARG_ARRAY_INFO(0, spanOptions, 0)
ZEND_END_ARG_INFO();

/**
 * Initializer for OpenCensus\Trace\Span
 *
 * @param array $spanOptions
 */
static PHP_METHOD(OpenCensusTraceSpan, __construct) {
    zval *zval_span_options, *v;
    ulong idx;
    zend_string *k;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "a", &zval_span_options) == FAILURE) {
        return;
    }

    zend_array *span_options = Z_ARR_P(zval_span_options);
    ZEND_HASH_FOREACH_KEY_VAL(span_options, idx, k, v) {
        zend_update_property(opencensus_trace_span_ce, getThis(), ZSTR_VAL(k), strlen(ZSTR_VAL(k)), v);
    } ZEND_HASH_FOREACH_END();
}

/**
 * Fetch the span name
 *
 * @return string
 */
static PHP_METHOD(OpenCensusTraceSpan, name) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "name", sizeof("name") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the span id
 *
 * @return string
 */
static PHP_METHOD(OpenCensusTraceSpan, spanId) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "spanId", sizeof("spanId") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the parent span id
 *
 * @return string
 */
static PHP_METHOD(OpenCensusTraceSpan, parentSpanId) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "parentSpanId", sizeof("parentSpanId") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the labels for the span
 *
 * @return array
 */
static PHP_METHOD(OpenCensusTraceSpan, labels) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "labels", sizeof("labels") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the start time
 *
 * @return float
 */
static PHP_METHOD(OpenCensusTraceSpan, startTime) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "startTime", sizeof("startTime") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the end time
 *
 * @return float
 */
static PHP_METHOD(OpenCensusTraceSpan, endTime) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "endTime", sizeof("endTime") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the backtrace from the moment the span was started
 *
 * @return array
 */
static PHP_METHOD(OpenCensusTraceSpan, backtrace) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "backtrace", sizeof("backtrace") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the span kind
 *
 * @return int
 */
static PHP_METHOD(OpenCensusTraceSpan, kind) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "kind", sizeof("kind") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/* Declare method entries for the OpenCensus\Trace\Span class */
static zend_function_entry opencensus_trace_span_methods[] = {
    PHP_ME(OpenCensusTraceSpan, __construct, arginfo_OpenCensusTraceSpan_construct, ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
    PHP_ME(OpenCensusTraceSpan, name, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, spanId, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, parentSpanId, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, labels, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, startTime, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, endTime, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, backtrace, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, kind, NULL, ZEND_ACC_PUBLIC)
    PHP_FE_END
};

#define REGISTER_TRACE_SPAN_CONSTANT(id) zend_declare_class_constant_long(opencensus_trace_span_ce, "SPAN_" #id, sizeof("SPAN_" #id) - 1, OPENCENSUS_TRACE_SPAN_##id);

/* Module init handler for registering the OpenCensus\Trace\Span class */
int opencensus_trace_span_minit(INIT_FUNC_ARGS) {
    zend_class_entry ce;

    INIT_CLASS_ENTRY(ce, "OpenCensus\\Trace\\Span", opencensus_trace_span_methods);
    opencensus_trace_span_ce = zend_register_internal_class(&ce);

    zend_declare_property_string(
      opencensus_trace_span_ce, "name", sizeof("name") - 1, "unknown", ZEND_ACC_PROTECTED TSRMLS_CC
    );
    zend_declare_property_null(opencensus_trace_span_ce, "spanId", sizeof("spanId") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "parentSpanId", sizeof("parentSpanId") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "startTime", sizeof("startTime") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "endTime", sizeof("endTime") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "kind", sizeof("kind") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "labels", sizeof("labels") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "backtrace", sizeof("backtrace") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);

    REGISTER_TRACE_SPAN_CONSTANT(KIND_UNKNOWN);
    REGISTER_TRACE_SPAN_CONSTANT(KIND_CLIENT);
    REGISTER_TRACE_SPAN_CONSTANT(KIND_SERVER);
    REGISTER_TRACE_SPAN_CONSTANT(KIND_PRODUCER);
    REGISTER_TRACE_SPAN_CONSTANT(KIND_CONSUMER);

    return SUCCESS;
}

/**
 * Returns an allocated initialized pointer to a opencensus_trace_span_t struct
 * Note that you will have to call opencensus_trace_span_free yourself when
 * it's time to clean up the memory
 */
opencensus_trace_span_t *opencensus_trace_span_alloc()
{
    opencensus_trace_span_t *span = emalloc(sizeof(opencensus_trace_span_t));
    span->name = NULL;
    span->parent = NULL;
    span->span_id = 0;
    span->start = 0;
    span->stop = 0;
    ALLOC_HASHTABLE(span->labels);
    zend_hash_init(span->labels, 4, NULL, ZVAL_PTR_DTOR, 0);
    return span;
}

/**
 * Frees the memory allocated for this opencensus_trace_span_t struct and any
 * other allocated objects. For every call to opencensus_trace_span_alloc(),
 * we should be calling opencensus_trace_span_free()
 */
void opencensus_trace_span_free(opencensus_trace_span_t *span)
{
    /* clear any allocated labels */
    FREE_HASHTABLE(span->labels);
    if (span->name) {
        zend_string_release(span->name);
    }

    /* free the trace span */
    efree(span);
}

/* Add a label to the trace span struct */
int opencensus_trace_span_add_label(opencensus_trace_span_t *span, zend_string *k, zend_string *v)
{
    /* put the string value into a zval and save it in the HashTable */
    zval zv;
    ZVAL_STRING(&zv, ZSTR_VAL(v));

    if (zend_hash_update(span->labels, zend_string_copy(k), &zv) == NULL) {
        return FAILURE;
    } else {
        return SUCCESS;
    }
}

/* Add a single label to the provided trace span struct */
int opencensus_trace_span_add_label_str(opencensus_trace_span_t *span, char *k, zend_string *v)
{
    return opencensus_trace_span_add_label(span, zend_string_init(k, strlen(k), 0), v);
}

/* Update the provided span with the provided zval (array) of span options */
int opencensus_trace_span_apply_span_options(opencensus_trace_span_t *span, zval *span_options)
{
    zend_string *k;
    zval *v;

    ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARR_P(span_options), k, v) {
        if (strcmp(ZSTR_VAL(k), "labels") == 0) {
            zend_hash_merge(span->labels, Z_ARRVAL_P(v), zval_add_ref, 0);
        } else if (strcmp(ZSTR_VAL(k), "startTime") == 0) {
            span->start = Z_DVAL_P(v);
        } else if (strcmp(ZSTR_VAL(k), "name") == 0) {
            span->name = zend_string_copy(Z_STR_P(v));
        } else if (strcmp(ZSTR_VAL(k), "kind") == 0) {
            span->kind = Z_LVAL_P(v);
        }
    } ZEND_HASH_FOREACH_END();
    return SUCCESS;
}
