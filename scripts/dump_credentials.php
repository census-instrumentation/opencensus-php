<?php
/**
 * Copyright 2015 Google Inc.
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

/**
 * Dumps the contents of the environment variable GOOGLE_CREDENTIALS_BASE64 to
 * a file.
 *
 * To setup Travis to run on your fork, read TRAVIS.md.
 */
if (getenv('GOOGLE_CREDENTIALS_BASE64') === false) {
    exit(0);
}
file_put_contents(
    getenv('PHP_DOCKER_GOOGLE_CREDENTIALS'),
    base64_decode(getenv('GOOGLE_CREDENTIALS_BASE64'))
);
