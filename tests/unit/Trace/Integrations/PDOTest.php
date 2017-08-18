<?php
/**
 * Copyright 2017 Google Inc.
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

use OpenCensus\Trace\Integrations\PDO;

/**
 * @group trace
 */
class PDOTest extends \PHPUnit_Framework_TestCase
{
    public function testHandleQuery()
    {
        $scope = null;
        $query = 'select * from users';

        $spanOptions = PDO::handleQuery($scope, $query);
        $expected = [
            'labels' => [
                'query' => 'select * from users'
            ]
        ];

        $this->assertEquals($expected, $spanOptions);
    }

    public function testHandleConnect()
    {
        $dsn = 'mysql:host=localhost;dbname=testdb';
        $spanOptions = PDO::handleConnect(null, $dsn);
        $expected = [
            'labels' => [
                'dsn' => 'mysql:host=localhost;dbname=testdb'
            ]
        ];

        $this->assertEquals($expected, $spanOptions);
    }

    public function testStatmentExecute()
    {
        $this->markTestSkipped('Cannot test without a database instance');
    }
}
