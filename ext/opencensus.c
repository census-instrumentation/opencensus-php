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
#include "opencensus_trace.h"
#include "opencensus_core_daemonclient.h"
#include "ext/standard/info.h"

ZEND_DECLARE_MODULE_GLOBALS(opencensus)

/* {{{ arginfo */
ZEND_BEGIN_ARG_INFO_EX(arginfo_opencensus_core_send_to_daemon, 0, 0, 2)
	ZEND_ARG_TYPE_INFO(0, msgType, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, msgData, IS_STRING, 0)
ZEND_END_ARG_INFO()

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
/* }}} */

static PHP_MINFO_FUNCTION(opencensus);
static PHP_GINIT_FUNCTION(opencensus);
static PHP_GSHUTDOWN_FUNCTION(opencensus);
static PHP_MINIT_FUNCTION(opencensus);
static PHP_MSHUTDOWN_FUNCTION(opencensus);
static PHP_RINIT_FUNCTION(opencensus);
static PHP_RSHUTDOWN_FUNCTION(opencensus);

PHP_FUNCTION(opencensus_version);

/* {{{ opencensus_functions[]
 */
static zend_function_entry opencensus_functions[] = {
    PHP_FE(opencensus_version, NULL)
	PHP_FE(opencensus_core_send_to_daemonclient, arginfo_opencensus_core_send_to_daemon)
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
/* }}} */

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
	PHP_MODULE_GLOBALS(opencensus),
	PHP_GINIT(opencensus),
	PHP_GSHUTDOWN(opencensus),
	NULL,
    STANDARD_MODULE_PROPERTIES_EX
};

#ifdef COMPILE_DL_OPENCENSUS
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
#endif
ZEND_GET_MODULE(opencensus)
#endif

PHP_INI_BEGIN()
PHP_INI_ENTRY(opencensus_client_path, opencensus_client_path_val, PHP_INI_ALL, NULL)
PHP_INI_END()

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(opencensus)
{
    php_info_print_table_start();
    php_info_print_table_row(2, "OpenCensus support", "enabled");
    php_info_print_table_row(2, "OpenCensus module version", PHP_OPENCENSUS_VERSION);
    php_info_print_table_end();

    DISPLAY_INI_ENTRIES();
}
/* }}} */

/* {{{ PHP_GINIT_FUNCTION
 */
PHP_GINIT_FUNCTION(opencensus)
{
#if defined(COMPILE_DL_OPENCENSUS) && defined(ZTS)
	ZEND_TSRMLS_CACHE_UPDATE()
#endif
    opencensus_trace_ginit();
}
/* }}} */

/* {{{ PHP_GSHUTDOWN_FUNCTION
 */
PHP_GSHUTDOWN_FUNCTION(opencensus)
{
	opencensus_trace_gshutdown();
}
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(opencensus)
{
#if defined(COMPILE_DL_OPENCENSUS) && defined(ZTS)
	ZEND_TSRMLS_CACHE_UPDATE()
#endif
	REGISTER_INI_ENTRIES();

#ifndef PHP_WIN32
	/* daemonclient currently not available for WIN32 */
	opencensus_core_daemonclient_minit();
	opencensus_core_daemonclient_forker();
#endif
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
	opencensus_core_daemonclient_mshutdown(SHUTDOWN_FUNC_ARGS_PASSTHRU);

	UNREGISTER_INI_ENTRIES();

	return SUCCESS;
}
/* }}} */

/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(opencensus)
{
	opencensus_trace_rinit();
	opencensus_core_daemonclient_rinit();
    return SUCCESS;
}

/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(opencensus)
{
	opencensus_core_daemonclient_rshutdown();
	opencensus_trace_rshutdown();

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
