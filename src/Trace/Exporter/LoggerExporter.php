<?php
/**
 * Copyright 2017 OpenCensus Authors
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

use OpenCensus\Trace\SpanData;
use Psr\Log\LoggerInterface;

/**
 * This implementation of the ExporterInterface sends log messages to a
 * configurable PSR-3 logger.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Exporter\LoggerExporter;
 * use OpenCensus\Trace\Tracer;
 * use Monolog\Logger;
 *
 * // Example PSR-3 logger
 * $logger = new Logger('traces');
 * $exporter = new LoggerExporter($logger);
 * Tracer::begin($exporter);
 * ```
 */
class LoggerExporter implements ExporterInterface
{
    const DEFAULT_LOG_LEVEL = 'notice';

    /**
     * @var LoggerInterface The logger to write to.
     */
    private $logger;

    /**
     * @var string Logger level to report at
     */
    private $level;

    /**
     * Create a new LoggerExporter
     *
     * @param LoggerInterface $logger The logger to write to.
     * @param string $level The logger level to write as. **Defaults to** `notice`.
     */
    public function __construct(LoggerInterface $logger, $level = self::DEFAULT_LOG_LEVEL)
    {
        $this->logger = $logger;
        $this->level = $level;
    }

    public function export(array $spans): bool
    {
        try {
            $this->logger->log($this->level, json_encode($spans));
        } catch (\Exception $e) {
            error_log('Reporting the Trace data failed: ' . $e->getMessage());
            return false;
        }
        return true;
    }
}
