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

curl -L https://wordpress.org/latest.tar.gz | tar zxf -
sed -i "s|dev-master|dev-${BRANCH}|" composer.json
sed -i "s|https://github.com/beatlabs/opencensus-php|${REPO}|" composer.json
composer install -n --prefer-dist
cp wp-config.php wordpress
vendor/bin/wp core install  --admin_user=admin \
                            --admin_password=password \
                            --allow-root \
                            --path=wordpress/ \
                            --url=http://wordpress/ \
                            --admin_email=admin@opencensus.io \
                            --title="OpenCensus Test"

vendor/bin/phpunit

popd
