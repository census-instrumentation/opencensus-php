<?php
/**
 * Copyright 2017 Google Inc. All Rights Reserved.
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

/**
 * This class handles instrumenting the Eloquent ORM queries using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Eloquent
 *
 * Eloquent::load();
 */
class Eloquent implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to the Symfony framework
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            return;
        }

        // public function getModels($columns = ['*'])
        opencensus_method(Builder::class, 'getModels', function ($scope, $columns) {
            // Builder class has $model property but it's protected - use reflection to read the property
            $reflection = new \ReflectionClass(Builder::class);
            $modelProperty = $reflection->getProperty('model');
            $modelProperty->setAccessible(true);
            $model = $modelProperty->getValue($scope);

            return [
                'name' => 'eloquent/get',
                'labels' => [
                    'model' => get_class($model)
                ]
            ];
        });

        // protected function performInsert(Builder $query)
        opencensus_method(Model::class, 'performInsert', function ($scope, $query) {
            return [
                'name' => 'eloquent/insert',
                'labels' => [
                    'model' => get_class($scope)
                ]
            ];
        });

        // protected function performUpdate(Builder $query)
        opencensus_method(Model::class, 'performUpdate', function ($scope, $query) {
            return [
                'name' => 'eloquent/update',
                'labels' => [
                    'model' => get_class($scope)
                ]
            ];
        });

        // public function delete()
        opencensus_method(Model::class, 'delete', function ($scope, $query) {
            return [
                'name' => 'eloquent/delete',
                'labels' => [
                    'model' => get_class($scope)
                ]
            ];
        });
    }
}
