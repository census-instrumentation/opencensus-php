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
 * This is the implementation of the OpenCensus\Trace\Ext\Link class. The PHP
 * equivalent is:
 *
 * namespace OpenCensus\Trace\Ext;
 *
 * class Link {
 *   protected $traceId;
 *   protected $spanId;
 *   protected $options;
 *
 *   public function traceId()
 *   {
 *     return $this->traceId;
 *   }
 *
 *   public function spanId()
 *   {
 *     return $this->spanId;
 *   }
 *
 *   public function options()
 *   {
 *     return $this->options;
 *   }
 * }
 */

#include "php_opencensus.h"
#include "opencensus_trace_link.h"
#include "Zend/zend_alloc.h"

zend_class_entry* opencensus_trace_link_ce = NULL;

/**
 * Fetch the link traceId
 *
 * @return string
 */
static PHP_METHOD(OpenCensusTraceLink, traceId) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_link_ce, getThis(), "traceId", sizeof("traceId") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the link spanId
 *
 * @return string
 */
static PHP_METHOD(OpenCensusTraceLink, spanId) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_link_ce, getThis(), "spanId", sizeof("spanId") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the link options
 *
 * @return float
 */
static PHP_METHOD(OpenCensusTraceLink, options) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_link_ce, getThis(), "options", sizeof("options") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/* Declare method entries for the OpenCensus\Trace\Ext\Link class */
static zend_function_entry opencensus_trace_link_methods[] = {
    PHP_ME(OpenCensusTraceLink, traceId, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceLink, spanId, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceLink, options, NULL, ZEND_ACC_PUBLIC)
    PHP_FE_END
};

/* Module init handler for registering the OpenCensus\Trace\Ext\Link class */
int opencensus_trace_link_minit(INIT_FUNC_ARGS)
{
    zend_class_entry ce;

    INIT_CLASS_ENTRY(ce, "OpenCensus\\Trace\\Ext\\Link", opencensus_trace_link_methods);
    opencensus_trace_link_ce = zend_register_internal_class(&ce);

    zend_declare_property_null(opencensus_trace_link_ce, "traceId", sizeof("traceId") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_link_ce, "spanId", sizeof("spanId") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_link_ce, "options", sizeof("options") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);

    return SUCCESS;
}

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
        zval_dtor(&link->options);
    }
    efree(link);
}

int opencensus_trace_link_to_zval(opencensus_trace_link_t *link, zval *zv)
{
    object_init_ex(zv, opencensus_trace_link_ce);
    zend_update_property_str(opencensus_trace_link_ce, zv, "traceId", sizeof("traceId") - 1, link->trace_id);
    zend_update_property_str(opencensus_trace_link_ce, zv, "spanId", sizeof("spanId") - 1, link->span_id);
    zend_update_property(opencensus_trace_link_ce, zv, "options", sizeof("options") - 1, &link->options);
    return SUCCESS;
}
