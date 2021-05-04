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
 * This is the implementation of the OpenCensus\Trace\Ext\Annotation class. The PHP
 * equivalent is:
 *
 * namespace OpenCensus\Trace\Ext;
 *
 * class Annotation {
 *   protected $description;
 *   protected $time;
 *   protected $options;
 *
 *   public function description()
 *   {
 *     return $this->description;
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
#include "opencensus_trace_annotation.h"
#include "Zend/zend_alloc.h"

zend_class_entry* opencensus_trace_annotation_ce = NULL;

ZEND_BEGIN_ARG_INFO_EX(arginfo_void, 0, 0, 0)
ZEND_END_ARG_INFO();

/**
 * Fetch the annotation description
 *
 * @return string
 */
static PHP_METHOD(OpenCensusTraceAnnotation, description) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_annotation_ce, OPENCENSUS_OBJ_P(getThis()), "description", sizeof("description") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the annotation time
 *
 * @return float
 */
static PHP_METHOD(OpenCensusTraceAnnotation, time) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_annotation_ce, OPENCENSUS_OBJ_P(getThis()), "time", sizeof("time") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/**
 * Fetch the annotation options
 *
 * @return float
 */
static PHP_METHOD(OpenCensusTraceAnnotation, options) {
    zval *val, rv;

    if (zend_parse_parameters_none() == FAILURE) {
        return;
    }

    val = zend_read_property(opencensus_trace_annotation_ce, OPENCENSUS_OBJ_P(getThis()), "options", sizeof("options") - 1, 1, &rv);

    RETURN_ZVAL(val, 1, 0);
}

/* Declare method entries for the OpenCensus\Trace\Ext\Annotation class */
static zend_function_entry opencensus_trace_annotation_methods[] = {
    PHP_ME(OpenCensusTraceAnnotation, description, arginfo_void, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceAnnotation, time, arginfo_void, ZEND_ACC_PUBLIC)
    PHP_ME(OpenCensusTraceAnnotation, options, arginfo_void, ZEND_ACC_PUBLIC)
    PHP_FE_END
};

/* Module init handler for registering the OpenCensus\Trace\Ext\Annotation class */
int opencensus_trace_annotation_minit(INIT_FUNC_ARGS)
{
    zend_class_entry ce;

    INIT_CLASS_ENTRY(ce, "OpenCensus\\Trace\\Ext\\Annotation", opencensus_trace_annotation_methods);
    opencensus_trace_annotation_ce = zend_register_internal_class(&ce);

    zend_declare_property_null(opencensus_trace_annotation_ce, "description", sizeof("description") - 1, ZEND_ACC_PROTECTED);
    zend_declare_property_null(opencensus_trace_annotation_ce, "time", sizeof("time") - 1, ZEND_ACC_PROTECTED);
    zend_declare_property_null(opencensus_trace_annotation_ce, "options", sizeof("options") - 1, ZEND_ACC_PROTECTED);

    return SUCCESS;
}

/**
 * Returns an allocated initialized pointer to a opencensus_trace_annotation_t
 * struct.
 *
 * Note that you will have to call opencensus_trace_annotation_span_free
 * yourself when it's time to clean up the memory.
 */
opencensus_trace_annotation_t *opencensus_trace_annotation_alloc()
{
    opencensus_trace_annotation_t *annotation = emalloc(sizeof(opencensus_trace_annotation_t));
    annotation->time_event.type = OPENCENSUS_TRACE_TIME_EVENT_ANNOTATION;
    annotation->description = NULL;
    array_init(&annotation->options);
    return annotation;
}

void opencensus_trace_annotation_free(opencensus_trace_annotation_t *annotation)
{
    if (annotation->description) {
        zend_string_release(annotation->description);
    }
    if (Z_TYPE(annotation->options) != IS_NULL) {
        zval_dtor(&annotation->options);
    }
    efree(annotation);
}

int opencensus_trace_annotation_to_zval(opencensus_trace_annotation_t *annotation, zval *zv)
{
    object_init_ex(zv, opencensus_trace_annotation_ce);
    zend_update_property_str(opencensus_trace_annotation_ce, OPENCENSUS_OBJ_P(zv), "description", sizeof("description") - 1, annotation->description);
    zend_update_property_double(opencensus_trace_annotation_ce, OPENCENSUS_OBJ_P(zv), "time", sizeof("time") - 1, annotation->time_event.time);
    zend_update_property(opencensus_trace_annotation_ce, OPENCENSUS_OBJ_P(zv), "options", sizeof("options") - 1, &annotation->options);
    return SUCCESS;
}
