FROM php:7.3

RUN apt update
RUN apt install -y \
            autoconf \
            build-essential \
            g++ \
            gcc \
            git \
            jq \
            libc-dev \
            libmemcached-dev \
            libmemcached11 \
            libpq-dev \
            libpqxx-dev \
            make \
            unzip \
            zip \
            zlib1g \
            zlib1g-dev && \
    pecl install memcached && \
    docker-php-ext-enable memcached && \
    docker-php-ext-install pcntl pdo_mysql pdo_pgsql

COPY ext /ext
RUN cd /ext && phpize && ./configure --enable-opencensus && make install && docker-php-ext-enable opencensus

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
php -r "if (hash_file('sha384', 'composer-setup.php') === 'a5c698ffe4b8e849a443b120cd5ba38043260d5c4023dbf93e1558871f1f07f58274fc6f4c93bcfd858c6bd0775cd8d1') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
php composer-setup.php && \
php -r "unlink('composer-setup.php');" && \
mv composer.phar /usr/bin/composer && chmod +x /usr/bin/composer

ENTRYPOINT ["bash"]
