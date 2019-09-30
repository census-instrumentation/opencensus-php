FROM php:7.2-buster
RUN apt-get update && apt-get -y install git  # Needed to pull composer dependencies
COPY . /usr/src/opencensus-tests
WORKDIR /usr/src/opencensus-tests
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
RUN composer install
ENTRYPOINT ["./vendor/phpunit/phpunit/phpunit"]
