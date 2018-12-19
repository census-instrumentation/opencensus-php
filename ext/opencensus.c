/*
 * Copyright 2018 OpenCensus Authors
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

#include "php_opencensus.h"

extern void span_dtor(zval *zv);

/**
 * True globals for storing the original zend_execute_ex and
 * zend_execute_internal function pointers
 */
static void (*opencensus_original_zend_execute_ex) (zend_execute_data *execute_data);
static void (*opencensus_original_zend_execute_internal) (zend_execute_data *execute_data, zval *return_value);

/* Constructor used for creating the opencensus globals */
static void php_opencensus_globals_ctor(void *pDest TSRMLS_DC)
{
    zend_opencensus_globals *opencensus_global = (zend_opencensus_globals *) pDest;
}

ZEND_DECLARE_MODULE_GLOBALS(opencensus)

ZEND_BEGIN_ARG_INFO_EX(arginfo_opencensus_trace_function, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, functionName, IS_STRING, 0)
    ZEND_ARG_INFO(0, handler)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_opencensus_trace_method, 0, 0, 2)
    ZEND_ARG_TYPE_INFO(0, className, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, methodName, IS_STRING, 0)
    ZEND_ARG_INFO(0, handler)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_opencensus_trace_begin, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, spanName, IS_STRING, 0)
    ZEND_ARG_ARRAY_INFO(0, spanOptions, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_opencensus_trace_set_context, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, traceId, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, parentSpanId, IS_STRING, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_opencensus_trace_add_attribute, 0, 0, 2)
    ZEND_ARG_TYPE_INFO(0, key, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, value, IS_STRING, 0)
    ZEND_ARG_ARRAY_INFO(0, options, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_opencensus_trace_add_annotation, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, description, IS_STRING, 0)
    ZEND_ARG_ARRAY_INFO(0, options, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_opencensus_trace_add_link, 0, 0, 2)
    ZEND_ARG_TYPE_INFO(0, traceId, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, spanId, IS_STRING, 0)
    ZEND_ARG_ARRAY_INFO(0, options, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_opencensus_trace_add_message_event, 0, 0, 2)
    ZEND_ARG_TYPE_INFO(0, type, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
    ZEND_ARG_ARRAY_INFO(0, options, 0)
ZEND_END_ARG_INFO()

/* List of functions provided by this extension */
static zend_function_entry opencensus_functions[] = {
    PHP_FE(opencensus_version, NULL)
    PHP_FE(opencensus_trace_function, arginfo_opencensus_trace_function)
    PHP_FE(opencensus_trace_method, arginfo_opencensus_trace_method)
    PHP_FE(opencensus_trace_list, NULL)
    PHP_FE(opencensus_trace_begin, arginfo_opencensus_trace_begin)
    PHP_FE(opencensus_trace_finish, NULL)
    PHP_FE(opencensus_trace_clear, NULL)
    PHP_FE(opencensus_trace_set_context, arginfo_opencensus_trace_set_context)
    PHP_FE(opencensus_trace_context, NULL)
    PHP_FE(opencensus_trace_add_attribute, arginfo_opencensus_trace_add_attribute)
    PHP_FE(opencensus_trace_add_annotation, arginfo_opencensus_trace_add_annotation)
    PHP_FE(opencensus_trace_add_link, arginfo_opencensus_trace_add_link)
    PHP_FE(opencensus_trace_add_message_event, arginfo_opencensus_trace_add_message_event)
    PHP_FE_END
};

PHP_MINFO_FUNCTION(opencensus)
{
    php_info_print_table_start();
    php_info_print_table_row(2, "OpenCensus support", "enabled");
    php_info_print_table_row(2, "OpenCensus module version", PHP_OPENCENSUS_VERSION);
    php_info_print_table_end();
    DISPLAY_INI_ENTRIES();
}

/* Registers the lifecycle hooks for this extension */
zend_module_entry opencensus_module_entry = {
    STANDARD_MODULE_HEADER,
    PHP_OPENCENSUS_EXTNAME,
    opencensus_functions,
    PHP_MINIT(opencensus),
    PHP_MSHUTDOWN(opencensus),
    PHP_RINIT(opencensus),
    PHP_RSHUTDOWN(opencensus),
    PHP_MINFO(opencensus),
    PHP_OPENCENSUS_VERSION,
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_OPENCENSUS
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
#endif
ZEND_GET_MODULE(opencensus)
#endif

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(opencensus)
{
    /* allocate global request variables */
#ifdef ZTS
    ts_allocate_id(&opencensus_globals_id, sizeof(zend_opencensus_globals), php_opencensus_globals_ctor, NULL);
#else
    php_opencensus_globals_ctor(&php_opencensus_globals_ctor);
#endif

    /**
     * Save original zend execute functions and use our own to instrument
     * function calls
     */
    opencensus_original_zend_execute_ex = zend_execute_ex;
    zend_execute_ex = opencensus_trace_execute_ex;

    opencensus_original_zend_execute_internal = zend_execute_internal;
    zend_execute_internal = opencensus_trace_execute_internal;

    opencensus_trace_span_minit(INIT_FUNC_ARGS_PASSTHRU);
    opencensus_trace_context_minit(INIT_FUNC_ARGS_PASSTHRU);
    opencensus_trace_annotation_minit(INIT_FUNC_ARGS_PASSTHRU);
    opencensus_trace_link_minit(INIT_FUNC_ARGS_PASSTHRU);
    opencensus_trace_message_event_minit(INIT_FUNC_ARGS_PASSTHRU);

    return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(opencensus)
{
    /* Put the original zend execute function back */
    zend_execute_ex = opencensus_original_zend_execute_ex;
    zend_execute_internal = opencensus_original_zend_execute_internal;

    return SUCCESS;
}
/* }}} */

/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(opencensus)
{
    /* initialize storage for user traced functions - per request basis */
    ALLOC_HASHTABLE(OPENCENSUS_G(user_traced_functions));
    zend_hash_init(OPENCENSUS_G(user_traced_functions), 16, NULL, ZVAL_PTR_DTOR, 0);

    /* initialize storage for recorded spans - per request basis */
    ALLOC_HASHTABLE(OPENCENSUS_G(spans));
    zend_hash_init(OPENCENSUS_G(spans), 16, NULL, span_dtor, 0);

    OPENCENSUS_G(current_span) = NULL;
    OPENCENSUS_G(trace_id) = NULL;
    OPENCENSUS_G(trace_parent_span_id) = NULL;

    return SUCCESS;
}

/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(opencensus)
{
    opencensus_trace_clear(0 TSRMLS_CC);

    /* cleanup user_traced_functions zvals that we copied when registing */
    zend_hash_destroy(OPENCENSUS_G(user_traced_functions));
    FREE_HASHTABLE(OPENCENSUS_G(user_traced_functions));

    return SUCCESS;
}
/* }}} */

/**
 * Return the current version of the opencensus extension
 *
 * @return string
 */
PHP_FUNCTION(opencensus_version)
{
    RETURN_STRING(PHP_OPENCENSUS_VERSION);
}

/* Return the current timestamp as a double */
double opencensus_now()
{
    struct timeval tv;
    gettimeofday(&tv, NULL);

    return (double) (tv.tv_sec + tv.tv_usec / 1000000.00);
}



