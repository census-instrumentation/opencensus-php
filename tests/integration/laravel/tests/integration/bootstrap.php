<?php
/**
 * Copyright 2018 OpenCensusAuthors
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

/**
 * This PHPUnit bootstrap file starts a local web server using the built-in
 * PHP web server. The PHPUnit tests that run then hit this running server.
 * We also register a shutdown function to stop this server after tests have
 * run.
 */

require __DIR__ . '/../../vendor/autoload.php';

$host = getenv('TESTHOST') ?: 'localhost';
$port = (int)(getenv('TESTPORT') ?: 9000);
putenv(
    sprintf(
        'TESTURL=http://%s:%d',
        $host,
        $port
    )
);

$command = sprintf(
    'php artisan serve --host=%s --port=%d >/dev/null 2>&1 & echo $!',
    $host,
    $port
);

$output = [];
printf('Starting web server with command: %s' . PHP_EOL, $command);
exec($command, $output);
$pid = (int) $output[0];

printf(
    '%s - Web server started on %s:%d with PID %d',
    date('r'),
    $host,
    $port,
    $pid
);

// Give the server time to boot.
sleep(1);

// Kill the web server when the process ends
register_shutdown_function(function () use ($pid) {
    echo sprintf('%s - Killing process with ID %d', date('r'), $pid) . PHP_EOL;
    exec('kill ' . $pid);
});
