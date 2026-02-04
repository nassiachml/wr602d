FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    libonig-dev \
    && docker-php-ext-install pdo_mysql zip opcache

RUN a2enmod rewrite

COPY docker/apache/symfony.conf /etc/apache2/sites-available/000-default.conf

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/symfony
