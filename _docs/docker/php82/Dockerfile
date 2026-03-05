FROM php:8.2-apache-bookworm

# --- Linux tools  ---
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl wget file unzip zip \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libonig-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libkrb5-dev libc-client2007e-dev \
    libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# --- Apache modules ---
RUN a2enmod rewrite headers

# --- PHP extensions ---
# mysqli, mbstring, soap, exif, gd, calendar, zip, intl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mysqli mbstring soap exif gd calendar zip intl

# imap (+ calendar is already installed above)
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j$(nproc) imap

# --- Composer ---
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# --- Configs ---
COPY php82/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY php82/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/ef_root

COPY php82/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]

