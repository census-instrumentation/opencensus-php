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

#ifndef PHP_OPENCENSUS_TRACE_TIME_EVENT_H
#define PHP_OPENCENSUS_TRACE_TIME_EVENT_H 1

#include "php.h"

#define OPENCENSUS_TRACE_TIME_EVENT_ANNOTATION 1
#define OPENCENSUS_TRACE_TIME_EVENT_MESSAGE_EVENT 2

typedef struct opencensus_trace_time_event_t {
    double time;
    int type;
} opencensus_trace_time_event_t;

#endif /* PHP_OPENCENSUS_TRACE_TIME_EVENT_H */
