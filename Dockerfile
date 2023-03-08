FROM php:8.2-cli

WORKDIR /app

RUN apt-get update \
    && apt-get install -y git zip libsodium-dev libsodium23 \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

RUN docker-php-ext-install sodium
RUN docker-php-ext-install bcmath


ENTRYPOINT ["composer"]
