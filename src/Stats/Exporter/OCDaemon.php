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

namespace OpenCensus\Stats;

class OCDaemon
{
    private static $instance;

    private $sock;
    private $pid;
    private $tid;

    private function __construct($sock)
    {
        $this->sock = $sock;
        \stream_set_blocking($this->sock, false);
        $this->pid = \getmypid();
        if (function_exists('zend_thread_id')) {
            $this->tid = \zend_thread_id();
        }
        $msg = \pack("CJJG", 0x03, $this->pid, $this->tid, \microtime(true));
        \fwrite($socket, $msg, strlen($msg));
    }

    public static function create($socketPath)
    {
        if (self::$instance instanceof OCDaemon) {
            return self::$instance;
        }

        $sock = \pfsockopen("unix://$socketPath", -1, $errno, $errstr, 2);
        if ($sock === false) {
            throw new \Exception("$errstr [$errno]");
        }

        return self::$instance = new OCDaemon($sock);
    }
}


/**
 * protocol
 * msg_type,msg_size,msg_data
 *
 * msg_type:
 * 1 initialize
 * 2 shutdown
 * 3 greeting
 * 11 int measure
 * 12 float measure
 * 21
 */
