FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
        mc \
        curl \
        wget \
        git \
        libpq-dev \
        && docker-php-ext-install pdo_pgsql

RUN docker-php-ext-install bcmath

RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" > /usr/local/etc/php/conf.d/error_reporting.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

CMD ["sh", "-c", "composer require symfony/orm-pack --working-dir=/var/www/rabbit_mq-test-task && \
composer install --optimize-autoloader --working-dir=/var/www/rabbit_mq-test-task && \
php /var/www/rabbit_mq-test-task/bin/console doctrine:migrations:migrate --no-interaction && \
php-fpm"]