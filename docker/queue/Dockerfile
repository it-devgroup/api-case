FROM php:8.5-cli-alpine

RUN apk add --no-cache \
        git unzip libpq-dev libzip-dev icu-dev oniguruma-dev supervisor \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql bcmath pcntl intl zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/queue/supervisor/ /etc/supervisor.d/
COPY docker/queue/crontab /etc/crontabs/root
COPY docker/queue/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-n", "-c", "/etc/supervisord.conf"]
