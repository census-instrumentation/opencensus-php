FROM php:7.3-apache

VOLUME /var/www

COPY config.m4 opencensus*.c opencensus*.h php_opencensus.h varint.* /tmp/opencensus/

RUN cd /tmp/opencensus \
    && phpize \
    && ./configure --enable-opencensus \
    && make -j "$(nproc)" \
    && make install \
    && cd ~ \
    && rm -r /tmp/opencensus \
    && docker-php-ext-enable opencensus \
    && apt-get update && apt-get install -y git unzip && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /var/www/html \
    && sh -c "curl -sS https://getcomposer.org/installer | php  -- --install-dir=/usr/local/bin --filename=composer"

WORKDIR /var/www
