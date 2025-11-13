# Dockerfile
FROM php:8.3-apache

ENV DEBIAN_FRONTEND=noninteractive

# --- DÉBUT DE LA MODIFICATION ---
# 1. On s'assure que le dossier de configuration existe
RUN echo "memory_limit = 1G" > /usr/local/etc/php/conf.d/zz-memory.ini
# --- FIN DE LA MODIFICATION ---

# Installer SEULEMENT les paquets PHP nécessaires
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        g++ \
        zip \
        unzip \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        curl \
        # --- AJOUT DE NODE.JS ---
    && curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \

    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        intl \
        pdo \
        pdo_mysql \
        mysqli \
        opcache \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Le reste de votre fichier (est correct)
RUN a2enmod rewrite
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/var /var/www/html/vendor \
    && chmod -R 775 /var/www/html/var /var/www/html/vendor || true
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

WORKDIR /var/www/html