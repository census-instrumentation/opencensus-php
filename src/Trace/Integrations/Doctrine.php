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

use Doctrine\ORM\Version;

/**
 * This class handles instrumenting the Doctrine ORM queries using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Doctrine
 *
 * Doctrine::load();
 */
class Doctrine implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to Doctrine ORM calls
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Doctrine integrations.', E_USER_WARNING);
            return;
        }

        PDO::load();

        $persisterClass = (Version::compare('2.5.0') < 0)
            ? 'Doctrine\ORM\Persisters\Entity\BasicEntityPersister'    // Doctrine 2.5 or greater
            : 'Doctrine\ORM\Persisters\BasicEntityPersister';          // Doctrine 2.4 or earlier

        // public function load(array $criteria, $entity = null, $assoc = null, array $hints = array(),
        //      $lockMode = null, $limit = null, array $orderBy = null)
        opencensus_trace_method($persisterClass, 'load', function ($bep) {
            return [
                'name' => 'doctrine/load',
                'attributes' => ['entity' => $bep->getClassMetadata()->name]
            ];
        });

        // public function loadAll(array $criteria = array(), array $orderBy = null, $limit = null, $offset = null)
        opencensus_trace_method($persisterClass, 'loadAll', function ($bep) {
            return [
                'name' => 'doctrine/loadAll',
                'attributes' => ['entity' => $bep->getClassMetadata()->name]
            ];
        });

        // public int PDOConnection::exec(string $query)
        opencensus_trace_method(PDOConnection::class, 'exec', function ($scope, $query) {
            return [
                'name' => 'doctrine/exec',
                'attributes' => ['query' => $query]
            ];
        });
    }
}
