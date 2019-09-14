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

composer create-project --prefer-dist symfony/skeleton symfony ^4.0

cp -r src tests phpunit.xml.dist symfony/

pushd symfony

composer require --no-interaction symfony/orm-pack
composer remove symfony/flex # Necessary so that we can work with branches that have slash in them
composer config repositories.opencensus git ${REPO}
composer require --no-interaction opencensus/opencensus:dev-${BRANCH} --no-scripts
composer require --dev --no-interaction phpunit/phpunit:^7 guzzlehttp/guzzle:^6 --no-scripts

bin/console doctrine:migrations:migrate -n
vendor/bin/phpunit

popd
popd
