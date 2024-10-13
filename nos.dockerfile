FROM php:8.3-cli

RUN apt-get update -yqq >> /dev/null
RUN apt-get install -y libevent-dev \
    libssl-dev \
    pkg-config \
    build-essential \
    unzip >> /dev/null

RUN docker-php-ext-install pdo_mysql \
    opcache \
    posix \
    pcntl \
    sockets >> /dev/null

RUN pecl install event >> /dev/null

RUN docker-php-ext-enable opcache posix pcntl sockets
RUN docker-php-ext-enable --ini-name zz-event.ini event
RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
RUN echo "opcache.jit=1205" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
RUN echo "opcache.jit_buffer_size=128M" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

COPY --from=composer --link /usr/bin/composer /usr/local/bin/composer

# Initialize
WORKDIR /nos
COPY --link . .

# Configure
ENV NOS_HTTP_LISTEN=http://0.0.0.0:8080
ENV NOS_HTTP_COUNT=64
ENV NOS_APP_PATH=/nos/app

RUN composer install --quiet

# Start
EXPOSE 8080
ENTRYPOINT ["vendor/bin/nos"]
