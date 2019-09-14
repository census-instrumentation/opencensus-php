#!/bin/bash
# Copyright 2018 OpenCensus Authors
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

set -e

pushd $(dirname ${BASH_SOURCE[0]})
source ../setup_test_repo.sh

if [[ ! -d symfony_test ]]; then
    composer create-project --prefer-dist symfony/skeleton symfony_test ^4.0
    cp -r src tests phpunit.xml.dist symfony_test/
fi

pushd symfony_test

composer require --no-interaction symfony/orm-pack
composer require --no-interaction --dev phpunit guzzlehttp/guzzle:~6.0

if [[ ! -z ${CIRCLE_PR_NUMBER} ]]; then
    composer config repositories.opencensus git ${REPO}
    composer remove symfony/flex # Necessary so that we can work with branches that have slash in them
    composer config repositories.opencensus git ${REPO}
    composer require --no-interaction opencensus/opencensus:dev-${BRANCH}
else
    mkdir -p vendor/opencensus/opencensus
    cp -r ../../../../src/ opencensus
    jq '.["autoload"]["psr-4"] += {"OpenCensus\\": "opencensus/"}' composer.json > composer.test
    mv composer.test composer.json
    composer dumpautoload
fi

bin/console doctrine:migrations:migrate -n

echo "Running PHP server at ${TEST_HOST}:${TEST_PORT}"
php -S ${TEST_HOST}:${TEST_PORT} -t public &

vendor/bin/simple-phpunit

# Clean up running PHP processes
function cleanup {
    echo "Killing PHP processes..."
    killall php
}
trap cleanup EXIT INT QUIT TERM

popd
popd
