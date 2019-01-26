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
 * This is the implementation of the OpenCensus\Trace\Ext\MessageEvent class. The PHP
 * equivalent is:
 *
 * namespace OpenCensus\Trace\Ext;
 *
 * class MessageEvent {
 *   protected $type;
 *   protected $id;
 *   protected $time;
 *   protected $options;
 *
 *   public function type()
 *   {
 *     return $this->type;
 *   }
 *
 *   public function id()
 *   {
 *     return $this->id;
 *   }
 *
 *   public function time()
 *   {
 *     return $this->time;
 *   }
 *
 *   public function options()
 *   {
 *     return $this->options;
 *   }
 * }
 */

#include "php_opencensus.h"
#include "opencensus_trace_message_event.h"
#include "Zend/zend_alloc.h"

zend_class_entry* opencensus_trace_message_event_ce = NULL;

/**
 * Fetch the message_event type
 *
 * @return string
 */
static PHP_METHOD(OpenCensusTraceMessageEvent, type) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_message_event_ce, getThis(), "type", sizeof("type") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the message_event id
 *
 * @return string
 */
static PHP_METHOD(OpenCensusTraceMessageEvent, id) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_message_event_ce, getThis(), "id", sizeof("id") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the message_event time
 *
 * @return float
 */
static PHP_METHOD(OpenCensusTraceMessageEvent, time) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_message_event_ce, getThis(), "time", sizeof("time") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the message_event options
 *
 * @return float
 */
static PHP_METHOD(OpenCensusTraceMessageEvent, options) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_message_event_ce, getThis(), "options", sizeof("options") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/* Declare method entries for the OpenCensus\Trace\Ext\MessageEvent class */
static zend_function_entry opencensus_trace_message_event_methods[] = {
    PHP_ME(OpenCensusTraceMessageEvent, type, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceMessageEvent, id, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceMessageEvent, time, NULL, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceMessageEvent, options, NULL, ZEND_ACC_PUBLIC)
    PHP_FE_END
};

/* Module init handler for registering the OpenCensus\Trace\Ext\MessageEvent class */
int opencensus_trace_message_event_minit(INIT_FUNC_ARGS)
{
    zend_class_entry ce;

    INIT_CLASS_ENTRY(ce, "OpenCensus\\Trace\\Ext\\MessageEvent", opencensus_trace_message_event_methods);
    opencensus_trace_message_event_ce = zend_register_internal_class(&ce);

    zend_declare_property_null(opencensus_trace_message_event_ce, "type", sizeof("type") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_message_event_ce, "id", sizeof("id") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_message_event_ce, "time", sizeof("time") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);
    zend_declare_property_null(opencensus_trace_message_event_ce, "options", sizeof("options") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);

    return SUCCESS;
}

/**
 * Returns an allocated initialized pointer to a opencensus_trace_message_event_t
 * struct.
 *
 * Note that you will have to call opencensus_trace_message_event_span_free
 * yourself when it's time to clean up the memory.
 */
opencensus_trace_message_event_t *opencensus_trace_message_event_alloc()
{
    opencensus_trace_message_event_t *message_event = emalloc(sizeof(opencensus_trace_message_event_t));
    message_event->time_event.type = OPENCENSUS_TRACE_TIME_EVENT_MESSAGE_EVENT;
    message_event->type = NULL;
    message_event->id = NULL;
    array_init(&message_event->options);
    return message_event;
}

void opencensus_trace_message_event_free(opencensus_trace_message_event_t *message_event)
{
    if (message_event->type) {
        zend_string_release(message_event->type);
    }
    if (message_event->id) {
        zend_string_release(message_event->id);
    }
    if (Z_TYPE(message_event->options) != IS_NULL) {
        zval_dtor(&message_event->options);
    }
    efree(message_event);
}

int opencensus_trace_message_event_to_zval(opencensus_trace_message_event_t *message_event, zval *zv)
{
    object_init_ex(zv, opencensus_trace_message_event_ce);
    zend_update_property_str(opencensus_trace_message_event_ce, zv, "type", sizeof("type") - 1, message_event->type);
    zend_update_property_str(opencensus_trace_message_event_ce, zv, "id", sizeof("id") - 1, message_event->id);
    zend_update_property_double(opencensus_trace_message_event_ce, zv, "time", sizeof("time") - 1, message_event->time_event.time);
    zend_update_property(opencensus_trace_message_event_ce, zv, "options", sizeof("options") - 1, &message_event->options);
    return SUCCESS;
}
