FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

FROM php:8.2-apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
 && a2enmod rewrite
COPY --from=vendor /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY --from=vendor /app/vendor vendor
COPY . .
RUN composer dump-autoload --no-dev --optimize \
 && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache
