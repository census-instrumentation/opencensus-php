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
#include "Zend/zend_builtin_functions.h"
#include "Zend/zend_exceptions.h"
#include "standard/php_math.h"
#include "standard/php_rand.h"

/**
 * True globals for storing the original zend_execute_ex and
 * zend_execute_internal function pointers
 */
static void (*opencensus_original_zend_execute_ex) (zend_execute_data *execute_data);
static void (*opencensus_original_zend_execute_internal) (zend_execute_data *execute_data, zval *return_value);

void opencensus_trace_minit() {
    /**
     * Save original zend execute functions and use our own to instrument
     * function calls
     */
    opencensus_original_zend_execute_ex = zend_execute_ex;
    zend_execute_ex = opencensus_trace_execute_ex;

    opencensus_original_zend_execute_internal = zend_execute_internal;
    zend_execute_internal = opencensus_trace_execute_internal;
}

void opencensus_trace_mshutdown() {
    /* Put the original zend execute function back */
    zend_execute_ex = opencensus_original_zend_execute_ex;
    zend_execute_internal = opencensus_original_zend_execute_internal;
}

/**
 * Fetch the spanId zend_string value from the provided array.
 * Note that the returned zend_string must be released by the caller.
 */
static zend_string *span_id_from_options(HashTable *options)
{
    zval *val;
    zend_string *str = NULL;
    if (options == NULL) {
        return NULL;
    }

    val = zend_hash_str_find(options, "spanId", strlen("spanId"));
    if (val == NULL) {
        return NULL;
    }

    switch (Z_TYPE_P(val)) {
        case IS_STRING:
            str = zval_get_string(val);
            break;
        case IS_LONG:
            str = _php_math_longtobase(val, 16);
            break;
    }

    if (str == NULL) {
        php_error_docref(NULL, E_WARNING, "Provided spanId should be a hex string");
        return NULL;
    }

    return str;
}

/*Fetch the span struct for the spanId from the provided array. */
static opencensus_trace_span_t *span_from_options(zval *options)
{
    zend_string *span_id = NULL;
    opencensus_trace_span_t *span = NULL;

    if (options == NULL) {
        return NULL;
    }

    span_id = span_id_from_options(Z_ARR_P(options));
    if (span_id != NULL) {
        span = (opencensus_trace_span_t *)zend_hash_find_ptr(OPENCENSUS_G(spans), span_id);
        zend_string_release(span_id);
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
    zval *options = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "SS|a", &k, &v, &options) == FAILURE) {
        RETURN_FALSE;
    }

    span = span_from_options(options);
    if (span == NULL) {
        span = OPENCENSUS_G(current_span);
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
    zend_string *description;
    opencensus_trace_span_t *span;
    zval *options = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "S|a", &description, &options) == FAILURE) {
        RETURN_FALSE;
    }

    span = span_from_options(options);
    if (span == NULL) {
        span = OPENCENSUS_G(current_span);
    }

    if (span == NULL) {
        RETURN_FALSE;
    }

    if (opencensus_trace_span_add_annotation(span, description, options) == SUCCESS) {
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
    zend_string *trace_id, *span_id;
    opencensus_trace_span_t *span;
    zval *options = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "SS|a", &trace_id, &span_id, &options) == FAILURE) {
        RETURN_FALSE;
    }

    span = span_from_options(options);
    if (span == NULL) {
        span = OPENCENSUS_G(current_span);
    }

    if (span == NULL) {
        RETURN_FALSE;
    }

    if (opencensus_trace_span_add_link(span, trace_id, span_id, options) == SUCCESS) {
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
    zend_string *type, *id;
    opencensus_trace_span_t *span;
    zval *options = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "SS|a", &type, &id, &options) == FAILURE) {
        RETURN_FALSE;
    }

    span = span_from_options(options);
    if (span == NULL) {
        span = OPENCENSUS_G(current_span);
    }

    if (span == NULL) {
        RETURN_FALSE;
    }

    if (opencensus_trace_span_add_message_event(span, type, id, options) == SUCCESS) {
        RETURN_TRUE;
    }

    RETURN_FALSE;
}

static void opencensus_copy_args(zend_execute_data *execute_data, zval **args, int *ret_num_args)
{
    int i, num_args = ZEND_CALL_NUM_ARGS(execute_data), has_scope = 0;
    zval *arguments = emalloc((num_args + 1) * sizeof(zval));
    *args = arguments;

    if (getThis() != NULL) {
        has_scope = 1;
        ZVAL_COPY(&arguments[0], getThis());
    }

    for (i = 0; i < num_args; i++) {
        ZVAL_COPY(&arguments[i + has_scope], ZEND_CALL_VAR_NUM(execute_data, i));
    }
    *ret_num_args = num_args + has_scope;
}

static void opencensus_free_args(zval *args, int num_args)
{
    int i;
    for (i = 0; i < num_args; i++) {
        zval_dtor(&args[i]);
    }
    efree(args);
}

/**
 * Call the provided callback with the provided parameters to the traced
 * function. The callback must return an array or an E_WARNING is raised.
 */
static int opencensus_trace_call_user_function_callback(zval *args, int num_args, zend_execute_data *execute_data, opencensus_trace_span_t *span, zval *callback, zval *callback_result TSRMLS_DC)
{
    if (call_user_function_ex(EG(function_table), NULL, callback, callback_result, num_args, args, 0, NULL) != SUCCESS) {
        return FAILURE;
    }

    if (EG(exception) != NULL) {
        php_error_docref(NULL, E_WARNING, "Exception in trace callback");
        zend_clear_exception();
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
static opencensus_trace_span_t *opencensus_trace_begin(zend_string *name, zend_execute_data *execute_data, zend_string *span_id TSRMLS_DC)
{
    opencensus_trace_span_t *span = opencensus_trace_span_alloc();

    zend_fetch_debug_backtrace(&span->stackTrace, 1, DEBUG_BACKTRACE_IGNORE_ARGS, 0);

    span->start = opencensus_now();
    span->name = zend_string_copy(name);
    if (span_id != NULL) {
        span->span_id = zend_string_copy(span_id);
    } else {
        span->span_id = generate_span_id();
    }

    if (OPENCENSUS_G(current_span)) {
        span->parent = OPENCENSUS_G(current_span);
    }

    OPENCENSUS_G(current_span) = span;

    /* add the span to the list of spans */
    zend_hash_add_ptr(OPENCENSUS_G(spans), span->span_id, span);

    return span;
}

/**
 * Finish the current trace span. Set the new current trace span to this span's
 * parent if there is one.
 */
static int opencensus_trace_finish()
{
    opencensus_trace_span_t *span = OPENCENSUS_G(current_span);

    if (!span) {
        return FAILURE;
    }

    /* set current time for now */
    span->stop = opencensus_now();

    OPENCENSUS_G(current_span) = span->parent;

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
        span = opencensus_trace_begin(function_name, execute_data, NULL TSRMLS_CC);
        opencensus_trace_span_apply_span_options(span, &default_span_options);
        zval_dtor(&default_span_options);
    } else {
        span_id = span_id_from_options(Z_ARR_P(span_options));
        span = opencensus_trace_begin(function_name, execute_data, span_id TSRMLS_CC);
        if (span_id != NULL) {
            zend_string_release(span_id);
        }
        opencensus_trace_span_apply_span_options(span, span_options);
    }

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

void span_dtor(zval *zv)
{
    opencensus_trace_span_t *span = Z_PTR_P(zv);
    opencensus_trace_span_free(span);
    ZVAL_PTR_DTOR(zv);
}

/**
 * Reset the list of spans and free any allocated memory used.
 * If reset is set, reallocate request globals so we can start capturing spans.
 */
void opencensus_trace_clear(int reset TSRMLS_DC)
{
    /* free the hashtable */
    zend_hash_destroy(OPENCENSUS_G(spans));
    FREE_HASHTABLE(OPENCENSUS_G(spans));

    /* reallocate and setup the hashtable for captured spans */
    if (reset) {
        ALLOC_HASHTABLE(OPENCENSUS_G(spans));
        zend_hash_init(OPENCENSUS_G(spans), 16, NULL, span_dtor, 0);
    }

    OPENCENSUS_G(current_span) = NULL;
    if (OPENCENSUS_G(trace_id)) {
        zend_string_release(OPENCENSUS_G(trace_id));
        OPENCENSUS_G(trace_id) = NULL;
    }

    if (OPENCENSUS_G(trace_parent_span_id)) {
        zend_string_release(OPENCENSUS_G(trace_parent_span_id));
        OPENCENSUS_G(trace_parent_span_id) = NULL;
    }
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
    zend_string *trace_id = NULL, *parent_span_id = NULL;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "S|S", &trace_id, &parent_span_id) == FAILURE) {
        RETURN_FALSE;
    }

    OPENCENSUS_G(trace_id) = zend_string_copy(trace_id);
    if (parent_span_id) {
        OPENCENSUS_G(trace_parent_span_id) = zend_string_copy(parent_span_id);
    }

    RETURN_TRUE;
}

/**
 * Return the current trace context
 *
 * @return OpenCensus\Trace\SpanContext
 */
PHP_FUNCTION(opencensus_trace_context)
{
    opencensus_trace_span_t *span = OPENCENSUS_G(current_span);
    object_init_ex(return_value, opencensus_trace_context_ce);

    if (span) {
        zend_update_property_str(opencensus_trace_context_ce, return_value, "spanId", sizeof("spanId") - 1, span->span_id);
    } else if (OPENCENSUS_G(trace_parent_span_id)) {
        zend_update_property_str(opencensus_trace_context_ce, return_value, "spanId", sizeof("spanId") - 1, OPENCENSUS_G(trace_parent_span_id));
    }
    if (OPENCENSUS_G(trace_id)) {
        zend_update_property_str(opencensus_trace_context_ce, return_value, "traceId", sizeof("traceId") - 1, OPENCENSUS_G(trace_id));
    }
}

/**
 * This method replaces the internal zend_execute_ex method used to dispatch
 * calls to user space code. The original zend_execute_ex method is moved to
 * opencensus_original_zend_execute_ex
 */
void opencensus_trace_execute_ex (zend_execute_data *execute_data TSRMLS_DC) {
    zend_string *function_name = opencensus_trace_add_scope_name(
        EG(current_execute_data)->func->common.function_name,
        EG(current_execute_data)->func->common.scope
    );
    zval *trace_handler;
    opencensus_trace_span_t *span;
    zend_string *callback_name = NULL;

    /* Some functions have no names - just execute them */
    if (function_name == NULL) {
        opencensus_original_zend_execute_ex(execute_data TSRMLS_CC);
        return;
    }

    trace_handler = zend_hash_find(OPENCENSUS_G(user_traced_functions), function_name);

    /* Function is not registered for execution - continue normal execution */
    if (trace_handler == NULL) {
        opencensus_original_zend_execute_ex(execute_data TSRMLS_CC);
        zend_string_release(function_name);
        return;
    }

    span = opencensus_trace_begin(function_name, execute_data, NULL TSRMLS_CC);
    zend_string_release(function_name);

    if (zend_is_callable(trace_handler, 0, &callback_name)) {
        /* Registered handler is callable - execute the callback */
        zval callback_result, *args;
        int num_args;
        opencensus_copy_args(execute_data, &args, &num_args);
        opencensus_original_zend_execute_ex(execute_data TSRMLS_CC);
        if (opencensus_trace_call_user_function_callback(args, num_args, execute_data, span, trace_handler, &callback_result TSRMLS_CC) == SUCCESS) {
            opencensus_trace_span_apply_span_options(span, &callback_result);
        }
        opencensus_free_args(args, num_args);
        zval_dtor(&callback_result);
    } else {
        /* Registered handler is span options array */
        opencensus_original_zend_execute_ex(execute_data TSRMLS_CC);
        if (Z_TYPE_P(trace_handler) == IS_ARRAY) {
            opencensus_trace_span_apply_span_options(span, trace_handler);
        }
    }
    zend_string_release(callback_name);
    opencensus_trace_finish();
}

/**
 * This method resumes the internal function execution.
 */
static void resume_execute_internal(INTERNAL_FUNCTION_PARAMETERS)
{
    if (opencensus_original_zend_execute_internal) {
        opencensus_original_zend_execute_internal(INTERNAL_FUNCTION_PARAM_PASSTHRU);
    } else {
        execute_data->func->internal_function.handler(INTERNAL_FUNCTION_PARAM_PASSTHRU);
    }
}

/**
 * This method replaces the internal zend_execute_internal method used to
 * dispatch calls to internal code. The original zend_execute_internal method
 * is moved to opencensus_original_zend_execute_internal
 */
void opencensus_trace_execute_internal(INTERNAL_FUNCTION_PARAMETERS)
{
    zend_string *function_name = opencensus_trace_add_scope_name(
        execute_data->func->internal_function.function_name,
        execute_data->func->internal_function.scope
    );
    zval *trace_handler;
    opencensus_trace_span_t *span;
    zend_string *callback_name = NULL;

    /* Some functions have no names - just execute them */
    if (function_name == NULL) {
        resume_execute_internal(INTERNAL_FUNCTION_PARAM_PASSTHRU);
        return;
    }

    trace_handler = zend_hash_find(OPENCENSUS_G(user_traced_functions), function_name);

    /* Function is not registered for execution - continue normal execution */
    if (trace_handler == NULL) {
        resume_execute_internal(INTERNAL_FUNCTION_PARAM_PASSTHRU);
        zend_string_release(function_name);
        return;
    }

    span = opencensus_trace_begin(function_name, execute_data, NULL TSRMLS_CC);
    zend_string_release(function_name);

    if (zend_is_callable(trace_handler, 0, &callback_name)) {
        /* Registered handler is callable - execute the callback */
        zval callback_result, *args;
        int num_args;
        opencensus_copy_args(execute_data, &args, &num_args);
        resume_execute_internal(INTERNAL_FUNCTION_PARAM_PASSTHRU);
        if (opencensus_trace_call_user_function_callback(args, num_args, execute_data, span, trace_handler, &callback_result TSRMLS_CC) == SUCCESS) {
            opencensus_trace_span_apply_span_options(span, &callback_result);
        }
        opencensus_free_args(args, num_args);
        zval_dtor(&callback_result);
    } else {
        /* Registered handler is span options array */
        resume_execute_internal(INTERNAL_FUNCTION_PARAM_PASSTHRU);
        if (Z_TYPE_P(trace_handler) == IS_ARRAY) {
            opencensus_trace_span_apply_span_options(span, trace_handler);
        }
    }
    zend_string_release(callback_name);
    opencensus_trace_finish();
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
    zval *handler = NULL;
    zval h;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "S|z", &function_name, &handler) == FAILURE) {
        RETURN_FALSE;
    }

    if (handler == NULL) {
        ZVAL_LONG(&h, 1);
        handler = &h;
    } else {
        ZVAL_COPY(&h, handler);
    }

    zend_hash_update(OPENCENSUS_G(user_traced_functions), function_name, &h);
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
    zval *handler = NULL;
    zval h;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "SS|z", &class_name, &function_name, &handler) == FAILURE) {
        RETURN_FALSE;
    }

    if (handler == NULL) {
        ZVAL_LONG(&h, 1);
        handler = &h;
    } else {
        ZVAL_COPY(&h, handler);
    }

    key = opencensus_trace_generate_class_name(class_name, function_name);
    zend_hash_update(OPENCENSUS_G(user_traced_functions), key, &h);
    zend_string_release(key);

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

    array_init(return_value);

    ZEND_HASH_FOREACH_PTR(OPENCENSUS_G(spans), trace_span) {
        zval span;
        opencensus_trace_span_to_zval(trace_span, &span);
        add_next_index_zval(return_value, &span TSRMLS_CC);
    } ZEND_HASH_FOREACH_END();
}

