<?php
/**
 * Copyright 2019 OpenCensus Authors
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

namespace OpenCensus\Tests\Unit\Trace\Integrations;


use OpenCensus\Trace\Integrations\Elastica;
use OpenCensus\Trace\Span;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class ElasticaTest extends TestCase
{
    public function testHandleConstruct()
    {
        $scope = null;
        $config = [
            'transport' => 'http',
            'host' => 'elastic',
            'port' => '9200',
        ];

        $spanOptions = Elastica::handleConstruct($scope, $config);
        $expected = [
            'attributes' => [
                'transport' => 'http',
                'host' => 'elastic',
                'port' => '9200',
            ],
            'kind' => Span::KIND_CLIENT
        ];

        $this->assertEquals($expected, $spanOptions);
    }

    public function testHandleRequest()
    {
        $scope = null;
        $path = '/test/path';
        $method = 'GET';
        $data = [];
        $query = [];

        $spanOptions = Elastica::handleRequest($scope, $path, $method, $data, $query);
        $expected = [
            'attributes' => [
                'path' => $path,
                'method' => $method,
                'data' => json_encode($data),
                'query' => json_encode($query),
            ],
            'kind' => Span::KIND_CLIENT
        ];

        $this->assertEquals($expected, $spanOptions);
    }
}