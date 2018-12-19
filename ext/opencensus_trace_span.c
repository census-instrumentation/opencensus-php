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
 * namespace OpenCensus\Trace\Ext;
 *
 * class Span {
 *   protected $name = "unknown";
 *   protected $spanId;
 *   protected $parentSpanId;
 *   protected $startTime;
 *   protected $endTime;
 *   protected $attributes;
 *   protected $stackTrace;
 *   protected $links;
 *   protected $timeEvents;
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
 *   public function attributes()
 *   {
 *     return $this->attributes;
 *   }
 *
 *   public function stackTrace()
 *   {
 *     return $this->stackTrace;
 *   }
 *
 *   public function links()
 *   {
 *     return $this->links;
 *   }
 *
 *   public function timeEvents()
 *   {
 *     return $this->timeEvents;
 *   }
 *
 *   public function kind()
 *   {
 *     return $this->kind;
 *   }
 * }
 */

#include "php_opencensus.h"
#include "opencensus_trace_span.h"
#include "opencensus_trace_annotation.h"
#include "opencensus_trace_link.h"
#include "opencensus_trace_message_event.h"
#include "Zend/zend_alloc.h"
#include "Zend/zend_variables.h"

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

static PHP_METHOD(OpenCensusTraceSpan, __destruct) {
    zval val, *zv;
    zv = zend_read_property(opencensus_trace_span_ce, getThis(), "attributes", sizeof("attributes") - 1, 0, &val TSRMLS_CC);
    zval_dtor(zv);
    zv = zend_read_property(opencensus_trace_span_ce, getThis(), "links", sizeof("links") - 1, 0, &val TSRMLS_CC);
    zval_dtor(zv);
    zv = zend_read_property(opencensus_trace_span_ce, getThis(), "timeEvents", sizeof("timeEvents") - 1, 0, &val TSRMLS_CC);
    zval_dtor(zv);
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
 * Fetch the attributes for the span
 *
 * @return array
 */
static PHP_METHOD(OpenCensusTraceSpan, attributes) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "attributes", sizeof("attributes") - 1, 1, &rv);
    if (ZVAL_IS_NULL(val)) {
        array_init(return_value);
        return;
    }

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the links for the span
 *
 * @return array
 */
static PHP_METHOD(OpenCensusTraceSpan, links) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "links", sizeof("links") - 1, 1, &rv);
    if (ZVAL_IS_NULL(val)) {
        array_init(return_value);
        return;
    }

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the time events for the span
 *
 * @return array
 */
static PHP_METHOD(OpenCensusTraceSpan, timeEvents) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "timeEvents", sizeof("timeEvents") - 1, 1, &rv);
    if (ZVAL_IS_NULL(val)) {
        array_init(return_value);
        return;
    }

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
 * Fetch the stackTrace from the moment the span was started
 *
 * @return array
 */
static PHP_METHOD(OpenCensusTraceSpan, stackTrace) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "stackTrace", sizeof("stackTrace") - 1, 1, &rv);
    if (ZVAL_IS_NULL(val)) {
        array_init(return_value);
        return;
    }

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the span kind
 *
 * @return string
 */
static PHP_METHOD(OpenCensusTraceSpan, kind) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "kind", sizeof("kind") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the sameProcessAsParentSpan attribute of the span.
 *
 * @return bool
 */
static PHP_METHOD(OpenCensusTraceSpan, sameProcessAsParentSpan) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_span_ce, getThis(), "sameProcessAsParentSpan", sizeof("sameProcessAsParentSpan") - 1, 1, &rv);
    switch (Z_TYPE_P(val)) {
        case IS_FALSE:
            RETURN_FALSE;
        case IS_TRUE:
        default:
            RETURN_TRUE
    }
}

/* Declare method entries for the OpenCensus\Trace\Span class */
static zend_function_entry opencensus_trace_span_methods[] = {
    PHP_ME(OpenCensusTraceSpan, __construct, arginfo_OpenCensusTraceSpan_construct, ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
    PHP_ME(OpenCensusTraceSpan, __destruct, NULL, ZEND_ACC_PUBLIC | ZEND_ACC_DTOR)
    PHP_ME(OpenCensusTraceSpan, name, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, spanId, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, parentSpanId, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, attributes, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, startTime, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, endTime, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, stackTrace, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, links, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, timeEvents, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, kind, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceSpan, sameProcessAsParentSpan, NULL, ZEND_ACC_PUBLIC)
    PHP_FE_END
};

/* Module init handler for registering the OpenCensus\Trace\Span class */
int opencensus_trace_span_minit(INIT_FUNC_ARGS) {
    zend_class_entry ce;

    INIT_CLASS_ENTRY(ce, "OpenCensus\\Trace\\Ext\\Span", opencensus_trace_span_methods);
    opencensus_trace_span_ce = zend_register_internal_class(&ce);

    zend_declare_property_string(
      opencensus_trace_span_ce, "name", sizeof("name") - 1, "unknown", ZEND_ACC_PROTECTED TSRMLS_CC
    );
    zend_declare_property_null(opencensus_trace_span_ce, "spanId", sizeof("spanId") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "parentSpanId", sizeof("parentSpanId") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "startTime", sizeof("startTime") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "endTime", sizeof("endTime") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "attributes", sizeof("attributes") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "stackTrace", sizeof("stackTrace") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "links", sizeof("links") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "timeEvents", sizeof("timeEvents") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "kind", sizeof("kind") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_span_ce, "sameProcessAsParentSpan", sizeof("sameProcessAsParentSpan") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);

    return SUCCESS;
}

static void annotation_dtor(zval *zv)
{
    opencensus_trace_annotation_t *annotation = (opencensus_trace_annotation_t *)Z_PTR_P(zv);
    opencensus_trace_annotation_free(annotation);
    ZVAL_PTR_DTOR(zv);
}


static void link_dtor(zval *zv)
{
    opencensus_trace_link_t *link = (opencensus_trace_link_t *)Z_PTR_P(zv);
    opencensus_trace_link_free(link);
    ZVAL_PTR_DTOR(zv);
}

static void message_event_dtor(zval *zv)
{
    opencensus_trace_message_event_t *message_event = (opencensus_trace_message_event_t *)Z_PTR_P(zv);
    opencensus_trace_message_event_free(message_event);
    ZVAL_PTR_DTOR(zv);
}

static void time_event_dtor(zval *zv)
{
    opencensus_trace_time_event_t *time_event = (opencensus_trace_time_event_t *)Z_PTR_P(zv);
    if (time_event->type == OPENCENSUS_TRACE_TIME_EVENT_ANNOTATION) {
        annotation_dtor(zv);
    } else if (time_event->type == OPENCENSUS_TRACE_TIME_EVENT_MESSAGE_EVENT) {
        message_event_dtor(zv);
    } else {
        ZVAL_PTR_DTOR(zv);
    }
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
    span->span_id = NULL;
    span->kind = zend_string_init(
        OPENCENSUS_TRACE_SPAN_KIND_UNSPECIFIED,
        strlen(OPENCENSUS_TRACE_SPAN_KIND_UNSPECIFIED),
        0
    );
    span->start = 0;
    span->stop = 0;
    span->same_process_as_parent_span = 1;
    ALLOC_HASHTABLE(span->attributes);
    zend_hash_init(span->attributes, 4, NULL, ZVAL_PTR_DTOR, 0);

    ALLOC_HASHTABLE(span->time_events);
    zend_hash_init(span->time_events, 4, NULL, time_event_dtor, 0);

    ALLOC_HASHTABLE(span->links);
    zend_hash_init(span->links, 4, NULL, link_dtor, 0);
    return span;
}

/**
 * Frees the memory allocated for this opencensus_trace_span_t struct and any
 * other allocated objects. For every call to opencensus_trace_span_alloc(),
 * we should be calling opencensus_trace_span_free()
 */
void opencensus_trace_span_free(opencensus_trace_span_t *span)
{
    /* clear any allocated attributes */
    zend_hash_destroy(span->links);
    FREE_HASHTABLE(span->links);
    zend_hash_destroy(span->time_events);
    FREE_HASHTABLE(span->time_events);
    zend_hash_destroy(span->attributes);
    FREE_HASHTABLE(span->attributes);
    if (span->name) {
        zend_string_release(span->name);
    }
    if (span->span_id) {
        zend_string_release(span->span_id);
    }
    if (span->kind) {
        zend_string_release(span->kind);
    }

    zval_dtor(&span->stackTrace);

    /* free the trace span */
    efree(span);
}

/* Add a attribute to the trace span struct */
int opencensus_trace_span_add_attribute(opencensus_trace_span_t *span, zend_string *k, zend_string *v)
{
    /* put the string value into a zval and save it in the HashTable */
    zval zv;
    ZVAL_STRING(&zv, ZSTR_VAL(v));

    if (zend_hash_update(span->attributes, k, &zv) == NULL) {
        return FAILURE;
    } else {
        return SUCCESS;
    }
}

/* Add an annotation to the trace span struct */
int opencensus_trace_span_add_annotation(opencensus_trace_span_t *span, zend_string *description, zval *options)
{
    opencensus_trace_annotation_t *annotation = opencensus_trace_annotation_alloc();
    annotation->time_event.time = opencensus_now();
    annotation->description = zend_string_copy(description);
    if (options != NULL) {
        zend_hash_merge(Z_ARR(annotation->options), Z_ARR_P(options), zval_add_ref, 1);
    }

    zend_hash_next_index_insert_ptr(span->time_events, annotation);
    return SUCCESS;
}

/* Add a link to the trace span struct */
int opencensus_trace_span_add_link(opencensus_trace_span_t *span, zend_string *trace_id, zend_string *span_id, zval *options)
{
    opencensus_trace_link_t *link = opencensus_trace_link_alloc();
    link->trace_id = zend_string_copy(trace_id);
    link->span_id = zend_string_copy(span_id);
    if (options != NULL) {
        zend_hash_merge(Z_ARR(link->options), Z_ARR_P(options), zval_add_ref, 1);
    }

    zend_hash_next_index_insert_ptr(span->links, link);
    return FAILURE;
}

/* Add a message event to the trace span struct */
int opencensus_trace_span_add_message_event(opencensus_trace_span_t *span, zend_string *type, zend_string *id, zval *options)
{
    opencensus_trace_message_event_t *message_event = opencensus_trace_message_event_alloc();
    message_event->time_event.time = opencensus_now();
    message_event->type = zend_string_copy(type);
    message_event->id = zend_string_copy(id);
    if (options != NULL) {
        zend_hash_merge(Z_ARR(message_event->options), Z_ARR_P(options), zval_add_ref, 1);
    }

    zend_hash_next_index_insert_ptr(span->time_events, message_event);
    return SUCCESS;
}

/* Add a single attribute to the provided trace span struct */
int opencensus_trace_span_add_attribute_str(opencensus_trace_span_t *span, char *k, zend_string *v)
{
    return opencensus_trace_span_add_attribute(span, zend_string_init(k, strlen(k), 0), v);
}

/* Update the provided span with the provided zval (array) of span options */
int opencensus_trace_span_apply_span_options(opencensus_trace_span_t *span, zval *span_options)
{
    zend_string *k;
    zval *v;

    ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARR_P(span_options), k, v) {
        if (strcmp(ZSTR_VAL(k), "attributes") == 0) {
            zend_hash_merge(span->attributes, Z_ARRVAL_P(v), zval_add_ref, 0);
        } else if (strcmp(ZSTR_VAL(k), "startTime") == 0) {
            switch (Z_TYPE_P(v)) {
                case IS_NULL:
                    break;
                case IS_LONG:
                case IS_DOUBLE:
                    span->start = zval_get_double(v);
                    break;
                default:
                    php_error_docref(NULL, E_WARNING, "Provided startTime should be a float");
                    break;
            }
        } else if (strcmp(ZSTR_VAL(k), "name") == 0) {
            if (!Z_ISNULL_P(v)) {
                if (span->name) {
                    zend_string_release(span->name);
                }
                span->name = zval_get_string(v);
            } else {
                php_error_docref(NULL, E_WARNING, "Provided name should be a string");
            }
        } else if (strcmp(ZSTR_VAL(k), "kind") == 0) {
            if (Z_TYPE_P(v) == IS_STRING) {
                if (span->kind) {
                    zend_string_release(span->kind);
                }
                span->kind = zval_get_string(v);
            } else {
                php_error_docref(NULL, E_WARNING, "Provided kind should be a string");
            }
        } else if (strcmp(ZSTR_VAL(k), "sameProcessAsParentSpan") == 0) {
            span->same_process_as_parent_span = zend_is_true(v);
        } else if (strcmp(ZSTR_VAL(k), "stackTrace") == 0) {
            if (Z_TYPE_P(v) == IS_ARRAY) {
                if (!Z_ISNULL(span->stackTrace)) {
                    zval_dtor(&span->stackTrace);
                }
                ZVAL_COPY(&span->stackTrace, v);
            } else {
                php_error_docref(NULL, E_WARNING, "Provided stackTrace should be an array");
            }
        }
    } ZEND_HASH_FOREACH_END();
    return SUCCESS;
}

static int opencensus_trace_time_event_to_zval(opencensus_trace_time_event_t *time_event, zval *zv)
{
    if (time_event->type == OPENCENSUS_TRACE_TIME_EVENT_ANNOTATION) {
        return opencensus_trace_annotation_to_zval((opencensus_trace_annotation_t *) time_event, zv);
    } else if (time_event->type == OPENCENSUS_TRACE_TIME_EVENT_MESSAGE_EVENT) {
        return opencensus_trace_message_event_to_zval((opencensus_trace_message_event_t *) time_event, zv);
    } else {
        ZVAL_NULL(zv);
    }
    return SUCCESS;
}

static int opencensus_trace_update_time_events(opencensus_trace_span_t *span, zval *return_value)
{
    opencensus_trace_time_event_t *event;
    ZEND_HASH_FOREACH_PTR(span->time_events, event) {
        zval zv;
        opencensus_trace_time_event_to_zval(event, &zv);
        add_next_index_zval(return_value, &zv);
    } ZEND_HASH_FOREACH_END();
    return SUCCESS;
}

static int opencensus_trace_update_links(opencensus_trace_span_t *span, zval *return_value)
{
    opencensus_trace_link_t *link;
    ZEND_HASH_FOREACH_PTR(span->links, link) {
        zval zv;
        opencensus_trace_link_to_zval(link, &zv);
        add_next_index_zval(return_value, &zv);
    } ZEND_HASH_FOREACH_END();
    return SUCCESS;
}

/* Fill the provided span with the provided data from the internal span representation */
int opencensus_trace_span_to_zval(opencensus_trace_span_t *trace_span, zval *span TSRMLS_DC)
{
    zval attributes, links, time_events;
    object_init_ex(span, opencensus_trace_span_ce);
    zend_update_property_str(opencensus_trace_span_ce, span, "spanId", sizeof("spanId") - 1, trace_span->span_id);
    if (trace_span->parent) {
        zend_update_property_str(opencensus_trace_span_ce, span, "parentSpanId", sizeof("parentSpanId") - 1, trace_span->parent->span_id);
    } else if (OPENCENSUS_G(trace_parent_span_id)) {
        zend_update_property_str(opencensus_trace_span_ce, span, "parentSpanId", sizeof("parentSpanId") - 1, OPENCENSUS_G(trace_parent_span_id));
    }
    zend_update_property_str(opencensus_trace_span_ce, span, "name", sizeof("name") - 1, trace_span->name);
    zend_update_property_double(opencensus_trace_span_ce, span, "startTime", sizeof("startTime") - 1, trace_span->start);
    zend_update_property_double(opencensus_trace_span_ce, span, "endTime", sizeof("endTime") - 1, trace_span->stop);

    array_init(&attributes);
    zend_hash_copy(Z_ARRVAL(attributes), trace_span->attributes, zval_add_ref);
    zend_update_property(opencensus_trace_span_ce, span, "attributes", sizeof("attributes") - 1, &attributes);

    zend_update_property(opencensus_trace_span_ce, span, "stackTrace", sizeof("stackTrace") - 1, &trace_span->stackTrace);

    array_init(&links);
    opencensus_trace_update_links(trace_span, &links);
    zend_update_property(opencensus_trace_span_ce, span, "links", sizeof("links") - 1, &links);

    array_init(&time_events);
    opencensus_trace_update_time_events(trace_span, &time_events);
    zend_update_property(opencensus_trace_span_ce, span, "timeEvents", sizeof("timeEvents") - 1, &time_events);

    if (trace_span->kind) {
        zend_update_property_str(opencensus_trace_span_ce, span, "kind", sizeof("kind") - 1, trace_span->kind);
    }
    zend_update_property_bool(opencensus_trace_span_ce, span, "sameProcessAsParentSpan", sizeof("sameProcessAsParentSpan") - 1, trace_span->same_process_as_parent_span);

    return SUCCESS;
}
