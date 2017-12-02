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

#include "php_opencensus.h"
#include "opencensus_trace.h"
#include "opencensus_trace_span.h"
#include "opencensus_trace_context.h"
#include "Zend/zend_compile.h"
#include "Zend/zend_closures.h"
#include "zend_extensions.h"
#include "standard/php_math.h"

#if PHP_VERSION_ID < 70100
#include "standard/php_rand.h"
#endif

#ifdef _WIN32
#include "win32/time.h"
#else
#include <sys/time.h>
#endif

/**
 * True globals for storing the original zend_execute_ex and
 * zend_execute_internal function pointers
 */
void (*original_zend_execute_ex) (zend_execute_data *execute_data);
void (*original_zend_execute_internal) (zend_execute_data *execute_data, zval *return_value);

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

ZEND_GET_MODULE(opencensus)

/**
 * Return the current version of the opencensus extension
 *
 * @return string
 */
PHP_FUNCTION(opencensus_version)
{
    RETURN_STRING(PHP_OPENCENSUS_VERSION);
}

static zend_string *span_id_from_options(HashTable *options)
{
    zval *val;
    if (options == NULL) {
        return NULL;
    }

    if (val = zend_hash_str_find(options, "spanId", strlen("spanId"))) {
        return Z_STR_P(val);
    } else {
        return NULL;
    }
}

static opencensus_trace_span_t *span_from_options(HashTable *options)
{
    zend_string *span_id = NULL;
    opencensus_trace_span_t *span = NULL;

    if (options == NULL) {
        return NULL;
    }

    if (span_id = span_id_from_options(options)) {
        span = (opencensus_trace_span_t *)zend_hash_find_ptr(OPENCENSUS_TRACE_G(spans), span_id);
    }

    return span;
}

/**
 * Add a attribute to the current trace span
 *
 * @param string $key
 * @param string $value
 * @return bool
 */
PHP_FUNCTION(opencensus_trace_add_attribute)
{
    zend_string *k, *v;
    opencensus_trace_span_t *span;
    HashTable *options = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "SS|h", &k, &v, &options) == FAILURE) {
        RETURN_FALSE;
    }

    span = span_from_options(options);
    if (span == NULL) {
        span = OPENCENSUS_TRACE_G(current_span);
    }

    if (span == NULL) {
        RETURN_FALSE;
    }

    if (opencensus_trace_span_add_attribute(span, k, v) == SUCCESS) {
        RETURN_TRUE;
    }

    RETURN_FALSE;
}

/**
 * Add an annotation to the current trace span
 *
 * @param string $description
 * @param array $options
 * @return bool
 */
PHP_FUNCTION(opencensus_trace_add_annotation)
{
    zend_string *k, *v;
    opencensus_trace_span_t *span;
    HashTable *options = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "S|h", &k, &v, &options) == FAILURE) {
        RETURN_FALSE;
    }

    span = span_from_options(options);
    if (span == NULL) {
        span = OPENCENSUS_TRACE_G(current_span);
    }

    if (span == NULL) {
        RETURN_FALSE;
    }

    if (opencensus_trace_span_add_attribute(span, k, v) == SUCCESS) {
        RETURN_TRUE;
    }

    RETURN_FALSE;
}

/**
 * Add a link to the current trace span
 *
 * @param string $traceId
 * @param string $spanId
 * @param array $options
 * @return bool
 */
PHP_FUNCTION(opencensus_trace_add_link)
{
    zend_string *k, *v;
    opencensus_trace_span_t *span;
    HashTable *options = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "SS|h", &k, &v, &options) == FAILURE) {
        RETURN_FALSE;
    }

    span = span_from_options(options);
    if (span == NULL) {
        span = OPENCENSUS_TRACE_G(current_span);
    }

    if (span == NULL) {
        RETURN_FALSE;
    }

    if (opencensus_trace_span_add_attribute(span, k, v) == SUCCESS) {
        RETURN_TRUE;
    }

    RETURN_FALSE;
}

/**
 * Add a message event to the current trace span
 *
 * @param string $type
 * @param string $id
 * @param array $options
 * @return bool
 */
PHP_FUNCTION(opencensus_trace_add_message_event)
{
    zend_string *k, *v;
    opencensus_trace_span_t *span;
    HashTable *options = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "SS|h", &k, &v, &options) == FAILURE) {
        RETURN_FALSE;
    }

    span = span_from_options(options);
    if (span == NULL) {
        span = OPENCENSUS_TRACE_G(current_span);
    }

    if (span == NULL) {
        RETURN_FALSE;
    }

    if (opencensus_trace_span_add_attribute(span, k, v) == SUCCESS) {
        RETURN_TRUE;
    }

    RETURN_FALSE;
}

/* Return the current timestamp as a double */
static double opencensus_now()
{
    struct timeval tv;
    gettimeofday(&tv, NULL);

    return (double) (tv.tv_sec + tv.tv_usec / 1000000.00);
}

/**
 * Call the provided callback with the provided parameters to the traced
 * function. The callback must return an array or an E_WARNING is raised.
 */
static int opencensus_trace_call_user_function_callback(zend_execute_data *execute_data, opencensus_trace_span_t *span, zval *callback, zval *callback_result TSRMLS_DC)
{
    int i, num_args = EX_NUM_ARGS(), has_scope = 0;
    zval *args = emalloc((num_args + 1) * sizeof(zval));

    if (getThis() == NULL) {
        ZVAL_NULL(&args[0]);
    } else {
        has_scope = 1;
        ZVAL_ZVAL(&args[0], getThis(), 0, 1);
    }

    for (i = 0; i < num_args; i++) {
        ZVAL_ZVAL(&args[i + has_scope], EX_VAR_NUM(i), 0, 1);
    }

    if (call_user_function_ex(EG(function_table), NULL, callback, callback_result, num_args + has_scope, args, 0, NULL) != SUCCESS) {
        efree(args);
        return FAILURE;
    }
    efree(args);

    if (EG(exception) != NULL) {
        return FAILURE;
    }

    if (Z_TYPE_P(callback_result) != IS_ARRAY) {
        /* only raise the warning if the closure succeeded */
        php_error_docref(NULL, E_WARNING, "Trace callback should return array");
        return FAILURE;
    }

    return SUCCESS;
}

/**
 * Handle the callback for the traced method depending on the type
 * - if the zval is an associative array, then assume it's the trace span initialization
 *   options
 * - if the zval is an array that looks like a callable, then assume it's a callable
 * - if the zval is a Closure, then execute the closure and take the results as
 *   the trace span initialization options
 */
static void opencensus_trace_execute_callback(opencensus_trace_span_t *span, zend_execute_data *execute_data, zval *span_options TSRMLS_DC)
{
    zend_string *callback_name;
    if (zend_is_callable(span_options, 0, &callback_name)) {
        zval callback_result;
        if (opencensus_trace_call_user_function_callback(execute_data, span, span_options, &callback_result TSRMLS_CC) == SUCCESS) {
            opencensus_trace_span_apply_span_options(span, &callback_result);
        }
        zend_string_release(callback_name);
    } else if (Z_TYPE_P(span_options) == IS_ARRAY) {
        opencensus_trace_span_apply_span_options(span, span_options);
    }
}

/**
 * Force the random span id to be positive. php_mt_rand() generates 32 bits
 * of randomness. On 32-bit systems, we must cast to an unsigned int before
 * bitshifting to force a positive number. We're ok to lose on bit of
 * randomness because previous versions of mt_rand only generated 31 bits.
 */
static zend_string *generate_span_id()
{
    zval zv;
#if PHP_VERSION_ID < 70100
    if (!BG(mt_rand_is_seeded)) {
        php_mt_srand(GENERATE_SEED());
    }
#endif

    ZVAL_LONG(&zv, ((uint32_t) php_mt_rand()) >> 1);
    return _php_math_longtobase(&zv, 16);
}

/**
 * Start a new trace span. Inherit the parent span id from the current trace
 * context
 */
static opencensus_trace_span_t *opencensus_trace_begin(zend_string *function_name, zend_execute_data *execute_data, zend_string *span_id TSRMLS_DC)
{
    opencensus_trace_span_t *span = opencensus_trace_span_alloc();

    zend_fetch_debug_backtrace(&span->stackTrace, 1, DEBUG_BACKTRACE_IGNORE_ARGS, 0);

    span->start = opencensus_now();
    span->name = zend_string_copy(function_name);
    if (span_id) {
        span->span_id = zend_string_copy(span_id);
    } else {
        span->span_id = generate_span_id();
    }

    if (OPENCENSUS_TRACE_G(current_span)) {
        span->parent = OPENCENSUS_TRACE_G(current_span);
    }

    OPENCENSUS_TRACE_G(current_span) = span;
    zval ptr;
    ZVAL_PTR(&ptr, span);

    /* add the span to the list of spans */
    // printf("inserting span with span id: %s\n", ZSTR_VAL(span->span_id));
    zend_hash_add(OPENCENSUS_TRACE_G(spans), span->span_id, &ptr);

    return span;
}

/**
 * Finish the current trace span. Set the new current trace span to this span's
 * parent if there is one.
 */
static int opencensus_trace_finish()
{
    opencensus_trace_span_t *span = OPENCENSUS_TRACE_G(current_span);

    if (!span) {
        return FAILURE;
    }

    /* set current time for now */
    span->stop = opencensus_now();

    OPENCENSUS_TRACE_G(current_span) = span->parent;

    return SUCCESS;
}

/**
 * Given a class name and a function name, return a new string that represents
 * the function name. Note that this zend_string should be released when
 * finished.
 */
static zend_string *opencensus_trace_generate_class_name(zend_string *class_name, zend_string *function_name)
{
    int len = class_name->len + function_name->len + 2;
    zend_string *result = zend_string_alloc(len, 0);

    strcpy(ZSTR_VAL(result), class_name->val);
    strcat(ZSTR_VAL(result), "::");
    strcat(ZSTR_VAL(result), function_name->val);
    return result;
}

/* Prepend the name of the scope class to the function name */
static zend_string *opencensus_trace_add_scope_name(zend_string *function_name, zend_class_entry *scope)
{
    zend_string *result;
    if (!function_name) {
        return NULL;
    }

    if (scope) {
        result = opencensus_trace_generate_class_name(scope->name, function_name);
    } else {
        result = zend_string_copy(function_name);
    }
    return result;
}

/**
 * Start a new trace span
 *
 * @param string $spanName
 * @param array $spanOptions
 * @return bool
 */
PHP_FUNCTION(opencensus_trace_begin)
{
    zend_string *function_name, *span_id;
    zval *span_options = NULL, default_span_options;
    opencensus_trace_span_t *span;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "S|a", &function_name, &span_options) == FAILURE) {
        RETURN_FALSE;
    }

    if (span_options == NULL) {
        array_init(&default_span_options);
        span_options = &default_span_options;
    }

    span_id = span_id_from_options(Z_ARR_P(span_options));
    span = opencensus_trace_begin(function_name, execute_data, span_id TSRMLS_CC);
    opencensus_trace_execute_callback(span, execute_data, span_options TSRMLS_CC);
    RETURN_TRUE;
}

/**
 * Finish the current trace span
 *
 * @return bool
 */
PHP_FUNCTION(opencensus_trace_finish)
{
    if (opencensus_trace_finish() == SUCCESS) {
        RETURN_TRUE;
    }
    RETURN_FALSE;
}

/**
 * Reset the list of spans and free any allocated memory used.
 * If reset is set, reallocate request globals so we can start capturing spans.
 */
static void opencensus_trace_clear(int reset TSRMLS_DC)
{
    opencensus_trace_span_t *span;

    /* free memory for all captured spans */
    ZEND_HASH_FOREACH_PTR(OPENCENSUS_TRACE_G(spans), span) {
        opencensus_trace_span_free(span);
    } ZEND_HASH_FOREACH_END();

    /* free the hashtable */
    FREE_HASHTABLE(OPENCENSUS_TRACE_G(spans));

    /* reallocate and setup the hashtable for captured spans */
    if (reset) {
        ALLOC_HASHTABLE(OPENCENSUS_TRACE_G(spans));
        zend_hash_init(OPENCENSUS_TRACE_G(spans), 16, NULL, ZVAL_PTR_DTOR, 0);
    }

    OPENCENSUS_TRACE_G(current_span) = NULL;
    OPENCENSUS_TRACE_G(trace_id) = NULL;
    OPENCENSUS_TRACE_G(trace_parent_span_id) = 0;
}

/**
 * Reset the list of spans
 *
 * @return bool
 */
PHP_FUNCTION(opencensus_trace_clear)
{
    opencensus_trace_clear(1 TSRMLS_CC);
    RETURN_TRUE;
}

/**
 * Set the initial trace context
 *
 * @param string $traceId
 * @param string $parentSpanId
 */
PHP_FUNCTION(opencensus_trace_set_context)
{
    zend_string *trace_id, *parent_span_id;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "S|S", &trace_id, &parent_span_id) == FAILURE) {
        RETURN_FALSE;
    }

    OPENCENSUS_TRACE_G(trace_id) = zend_string_copy(trace_id);
    OPENCENSUS_TRACE_G(trace_parent_span_id) = zend_string_copy(parent_span_id);

    RETURN_TRUE;
}

/**
 * Return the current trace context
 *
 * @return OpenCensus\Trace\SpanContext
 */
PHP_FUNCTION(opencensus_trace_context)
{
    opencensus_trace_span_t *span = OPENCENSUS_TRACE_G(current_span);
    object_init_ex(return_value, opencensus_trace_context_ce);

    if (span) {
        zend_update_property_str(opencensus_trace_context_ce, return_value, "spanId", sizeof("spanId") - 1, span->span_id);
    } else if (OPENCENSUS_TRACE_G(trace_parent_span_id)) {
        zend_update_property_str(opencensus_trace_context_ce, return_value, "spanId", sizeof("spanId") - 1, OPENCENSUS_TRACE_G(trace_parent_span_id));
    }
    if (OPENCENSUS_TRACE_G(trace_id)) {
        zend_update_property_str(opencensus_trace_context_ce, return_value, "traceId", sizeof("traceId") - 1, OPENCENSUS_TRACE_G(trace_id));
    }
}

/**
 * This method replaces the internal zend_execute_ex method used to dispatch
 * calls to user space code. The original zend_execute_ex method is moved to
 * original_zend_execute_ex
 */
void opencensus_trace_execute_ex (zend_execute_data *execute_data TSRMLS_DC) {
    zend_string *function_name = opencensus_trace_add_scope_name(
        EG(current_execute_data)->func->common.function_name,
        EG(current_execute_data)->func->common.scope
    );
    zval *trace_handler;
    opencensus_trace_span_t *span;

    if (function_name) {
        trace_handler = zend_hash_find(OPENCENSUS_TRACE_G(user_traced_functions), function_name);

        if (trace_handler != NULL) {
            span = opencensus_trace_begin(function_name, execute_data, NULL TSRMLS_CC);
            original_zend_execute_ex(execute_data TSRMLS_CC);
            opencensus_trace_execute_callback(span, execute_data, trace_handler TSRMLS_CC);
            opencensus_trace_finish();
        } else {
            original_zend_execute_ex(execute_data TSRMLS_CC);
        }
        zend_string_release(function_name);
    } else {
        original_zend_execute_ex(execute_data TSRMLS_CC);
    }
}

/**
 * This method resumes the internal function execution.
 */
static void resume_execute_internal(INTERNAL_FUNCTION_PARAMETERS)
{
    if (original_zend_execute_internal) {
        original_zend_execute_internal(INTERNAL_FUNCTION_PARAM_PASSTHRU);
    } else {
        execute_data->func->internal_function.handler(INTERNAL_FUNCTION_PARAM_PASSTHRU);
    }
}

/**
 * This method replaces the internal zend_execute_internal method used to
 * dispatch calls to internal code. The original zend_execute_internal method
 * is moved to original_zend_execute_internal
 */
void opencensus_trace_execute_internal(INTERNAL_FUNCTION_PARAMETERS)
{
    zend_string *function_name = opencensus_trace_add_scope_name(
        execute_data->func->internal_function.function_name,
        execute_data->func->internal_function.scope
    );
    zval *trace_handler;
    opencensus_trace_span_t *span;

    if (function_name) {
        trace_handler = zend_hash_find(OPENCENSUS_TRACE_G(user_traced_functions), function_name);

        if (trace_handler) {
            span = opencensus_trace_begin(function_name, execute_data, NULL TSRMLS_CC);
            resume_execute_internal(INTERNAL_FUNCTION_PARAM_PASSTHRU);
            opencensus_trace_execute_callback(span, execute_data, trace_handler TSRMLS_CC);
            opencensus_trace_finish();
        } else {
            resume_execute_internal(INTERNAL_FUNCTION_PARAM_PASSTHRU);
        }
        /* zend_string_release(function_name); */
    } else {
        resume_execute_internal(INTERNAL_FUNCTION_PARAM_PASSTHRU);
    }
}

/**
 * Register the provided function for tracing.
 *
 * @param string $functionName
 * @param array|callable $handler
 * @return bool
 */
PHP_FUNCTION(opencensus_trace_function)
{
    zend_string *function_name;
    zval *handler = NULL, *copy;
    zval h;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "S|z", &function_name, &handler) == FAILURE) {
        RETURN_FALSE;
    }

    if (handler == NULL) {
        ZVAL_LONG(&h, 1);
        handler = &h;
    }

    /* Note: these is freed in the RSHUTDOWN via opencensus_trace_clear */
    PHP_OPENCENSUS_MAKE_STD_ZVAL(copy);
    ZVAL_ZVAL(copy, handler, 1, 0);

    zend_hash_update(OPENCENSUS_TRACE_G(user_traced_functions), function_name, copy);
    RETURN_TRUE;
}

/**
 * Register the provided function for tracing.
 *
 * @param string $className
 * @param string $methodName
 * @param array|callable $handler
 * @return bool
 */
PHP_FUNCTION(opencensus_trace_method)
{
    zend_string *class_name, *function_name, *key;
    zval *handler = NULL, *copy;
    zval h;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "SS|z", &class_name, &function_name, &handler) == FAILURE) {
        RETURN_FALSE;
    }

    if (handler == NULL) {
        ZVAL_LONG(&h, 1);
        handler = &h;
    }

    /* Note: these is freed in the RSHUTDOWN via opencensus_trace_clear */
    PHP_OPENCENSUS_MAKE_STD_ZVAL(copy);
    ZVAL_ZVAL(copy, handler, 1, 0);

    key = opencensus_trace_generate_class_name(class_name, function_name);
    zend_hash_update(OPENCENSUS_TRACE_G(user_traced_functions), key, handler);

    RETURN_FALSE;
}

/**
 * Return the collected list of trace spans that have been collected for this
 * request
 *
 * @return OpenCensus\Trace\Span[]
 */
PHP_FUNCTION(opencensus_trace_list)
{
    opencensus_trace_span_t *trace_span;
    zval attribute, span;

    array_init(return_value);

    ZEND_HASH_FOREACH_PTR(OPENCENSUS_TRACE_G(spans), trace_span) {
        object_init_ex(&span, opencensus_trace_span_ce);
        zend_update_property_str(opencensus_trace_span_ce, &span, "spanId", sizeof("spanId") - 1, trace_span->span_id);
        if (trace_span->parent) {
            zend_update_property_str(opencensus_trace_span_ce, &span, "parentSpanId", sizeof("parentSpanId") - 1, trace_span->parent->span_id);
        } else if (OPENCENSUS_TRACE_G(trace_parent_span_id)) {
            zend_update_property_str(opencensus_trace_span_ce, &span, "parentSpanId", sizeof("parentSpanId") - 1, OPENCENSUS_TRACE_G(trace_parent_span_id));
        }
        zend_update_property_str(opencensus_trace_span_ce, &span, "name", sizeof("name") - 1, trace_span->name);
        zend_update_property_double(opencensus_trace_span_ce, &span, "startTime", sizeof("startTime") - 1, trace_span->start);
        zend_update_property_double(opencensus_trace_span_ce, &span, "endTime", sizeof("endTime") - 1, trace_span->stop);

        ZVAL_ARR(&attribute, trace_span->attributes);
        zend_update_property(opencensus_trace_span_ce, &span, "attributes", sizeof("attributes") - 1, &attribute);

        zend_update_property(opencensus_trace_span_ce, &span, "stackTrace", sizeof("stackTrace") - 1, &trace_span->stackTrace);

        add_next_index_zval(return_value, &span);
    } ZEND_HASH_FOREACH_END();
}

/* Constructor used for creating the opencensus globals */
static void php_opencensus_globals_ctor(void *pDest TSRMLS_DC)
{
    zend_opencensus_globals *opencensus_global = (zend_opencensus_globals *) pDest;
}

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
    original_zend_execute_ex = zend_execute_ex;
    zend_execute_ex = opencensus_trace_execute_ex;

    original_zend_execute_internal = zend_execute_internal;
    zend_execute_internal = opencensus_trace_execute_internal;

    opencensus_trace_span_minit(INIT_FUNC_ARGS_PASSTHRU);
    opencensus_trace_context_minit(INIT_FUNC_ARGS_PASSTHRU);

    return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(opencensus)
{
    /* Put the original zend execute function back */
    zend_execute_ex = original_zend_execute_ex;
    zend_execute_internal = original_zend_execute_internal;

    return SUCCESS;
}
/* }}} */

/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(opencensus)
{
    /* initialize storage for user traced functions - per request basis */
    ALLOC_HASHTABLE(OPENCENSUS_TRACE_G(user_traced_functions));
    zend_hash_init(OPENCENSUS_TRACE_G(user_traced_functions), 16, NULL, ZVAL_PTR_DTOR, 0);

    /* initialize storage for recorded spans - per request basis */
    ALLOC_HASHTABLE(OPENCENSUS_TRACE_G(spans));
    zend_hash_init(OPENCENSUS_TRACE_G(spans), 16, NULL, ZVAL_PTR_DTOR, 0);

    OPENCENSUS_TRACE_G(current_span) = NULL;
    OPENCENSUS_TRACE_G(trace_id) = NULL;
    OPENCENSUS_TRACE_G(trace_parent_span_id) = 0;

    return SUCCESS;
}

/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(opencensus)
{
    zval *handler;

    opencensus_trace_clear(0 TSRMLS_CC);

    /* cleanup user_traced_functions zvals that we copied when registing */
    ZEND_HASH_FOREACH_VAL(OPENCENSUS_TRACE_G(user_traced_functions), handler) {
        PHP_OPENCENSUS_FREE_STD_ZVAL(handler);
    } ZEND_HASH_FOREACH_END();
    FREE_HASHTABLE(OPENCENSUS_TRACE_G(user_traced_functions));

    return SUCCESS;
}
/* }}} */
