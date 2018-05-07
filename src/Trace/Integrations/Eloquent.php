<?php
/**
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

namespace OpenCensus\Trace\Integrations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use OpenCensus\Trace\Span;

/**
 * This class handles instrumenting the Eloquent ORM queries using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Eloquent;
 *
 * Eloquent::load();
 * ```
 */
class Eloquent implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to Eloquent ORM calls
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Eloquent integrations.', E_USER_WARNING);
            return;
        }

        // public function getModels($columns = ['*'])
        opencensus_trace_method(Builder::class, 'getModels', function ($builder) {
            return [
                'name' => 'eloquent/get',
                'attributes' => [
                    'query' => $builder->toBase()->toSql()
                ],
                'kind' => Span::KIND_CLIENT
            ];
        });

        // protected function performInsert(Builder $query)
        opencensus_trace_method(Model::class, 'performInsert', function ($model, $query) {
            return [
                'name' => 'eloquent/insert',
                'attributes' => [
                    'query' => $query->toBase()->toSql()
                ],
                'kind' => Span::KIND_CLIENT
            ];
        });

        // protected function performUpdate(Builder $query)
        opencensus_trace_method(Model::class, 'performUpdate', function ($model, $query) {
            return [
                'name' => 'eloquent/update',
                'attributes' => [
                    'query' => $query->toBase()->toSql()
                ],
                'kind' => Span::KIND_CLIENT
            ];
        });

        // public function delete()
        opencensus_trace_method(Model::class, 'delete', function ($model) {
            return [
                'name' => 'eloquent/delete',
                'kind' => Span::KIND_CLIENT
            ];
        });
    }
}
