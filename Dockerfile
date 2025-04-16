FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    nginx \
    nano \
    procps \
    psmisc \
    zip \
    git \
    htop \
    nano \
    cron \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    supervisor \
    libgmp-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql sockets gd zip gmp bcmath curl \
    && pecl install \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY app/ /app/

COPY /php.ini ${PHP_INI_DIR}/conf.d/99-php.ini

COPY cacert.pem /app/cacert.pem

COPY default.conf /etc/nginx/sites-available/default

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN mkdir -p /etc/supervisor/conf.d
COPY supervisord.conf /etc/supervisor/conf.d

RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app

RUN composer config platform.php-64bit 8.3
RUN composer install --no-interaction --optimize-autoloader

ENV WORKERS=1
ENV TZ=UTC

EXPOSE 8088

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]