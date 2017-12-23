# Copyright 2017 OpenCensus Authors
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

ARG BASE_IMAGE
FROM $BASE_IMAGE

RUN mkdir -p /build && \
    apt-get update -y && \
    apt-get install -y -q --no-install-recommends \
        build-essential \
        g++ \
        gcc \
        libc-dev \
        make \
        autoconf \
        git \
        unzip

COPY . /build/

WORKDIR /build/ext

ENV TEST_PHP_ARGS="-q" \
    REPORT_EXIT_STATUS=1

RUN phpize && \
    ./configure --enable-opencensus && \
    make clean && \
    make && \
    make test && \
    make install

WORKDIR /build

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');"

RUN php composer.phar install && \
    vendor/bin/phpcs --standard=./phpcs-ruleset.xml && \
    vendor/bin/phpunit && \
    php -d extension=opencensus.so vendor/bin/phpunit
