<?php
/**
 * Copyright 2018 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Trace\Exporter;

class OneLineEchoExporter implements ExporterInterface
{
    public function export(array $spans): bool
    {
        foreach ($spans as $span) {
            $time = (float) ($span->endTime()->format('U.u')) - (float) ($span->startTime()->format('U.u'));
            printf("[ %8.2f ms] %s%s", $time * 1000, $span->name(), PHP_EOL);
            foreach ($span->attributes() as $key => $value) {
                printf("  [%s] %s%s", $key, $value, PHP_EOL);
            }
        }
        return true;
    }
}
