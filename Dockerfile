FROM php:7.4-fpm

ARG user
ARG uid

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

RUN echo "upload_max_filesize=10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=10M" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www

RUN mkdir -p /var/www/storage && \
    mkdir -p /var/www/storage/app/public && \
    chown -R $user:www-data /var/www/storage && \
    chmod -R 777 /var/www/storage

RUN mkdir -p "/etc/supervisor/logs"

COPY docker-compose/supervisor/supervisor.conf /etc/supervisor/conf.d/supervisord.conf

USER $user
