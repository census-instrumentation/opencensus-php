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

composer create-project --prefer-dist laravel/laravel laravel
cp -R app config routes tests phpunit.xml.dist laravel

pushd laravel

composer config repositories.opencensus git ${REPO}
composer require opencensus/opencensus:dev-${BRANCH}
composer require --dev guzzlehttp/guzzle:~6.0

php artisan migrate
vendor/bin/phpunit --config=phpunit.xml.dist

popd
popd
