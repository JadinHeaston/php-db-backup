FROM php:8.4.2-fpm-alpine3.21
RUN docker-php-ext-install pdo_mysql

RUN apk add --no-cache zip libzip-dev
RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip