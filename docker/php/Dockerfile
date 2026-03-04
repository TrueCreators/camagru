FROM php:8.2-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    msmtp \
    msmtp-mta \
    && rm -rf /var/lib/apt/lists/*

# Настройка и установка PHP-расширений
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    pdo_mysql \
    zip

# Настройка PHP
RUN echo "upload_max_filesize = 20M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 20M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Настройка msmtp для почты
RUN printf "account default\nhost mailpit\nport 1025\nfrom noreply@camagru.local\n" > /etc/msmtprc \
    && echo "sendmail_path = /usr/bin/msmtp -t" >> /usr/local/etc/php/conf.d/mail.ini

# Установка рабочей директории
WORKDIR /var/www/html

# Установка прав доступа
RUN chown -R www-data:www-data /var/www/html

USER www-data
