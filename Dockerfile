# Dockerfile
FROM php:8.3-apache

# variables non-interactives
ENV DEBIAN_FRONTEND=noninteractive

# Installer paquets système nécessaires puis extensions PHP
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
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        intl \
        pdo \
        pdo_mysql \
        mysqli \
        opcache \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# Copier tout le projet dans le container
COPY . /var/www/html/

# Installer composer si besoin (optionnel si vendor déjà présent)
# Si tu veux exécuter composer inside built image, décommente ci-dessous
# RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
#     && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
#     && php -r "unlink('composer-setup.php');"

# Donner les droits corrects à Symfony (var + vendor)
RUN chown -R www-data:www-data /var/www/html/var /var/www/html/vendor \
    && chmod -R 775 /var/www/html/var /var/www/html/vendor || true

# DocumentRoot sur /var/www/html/public
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# Droits généraux
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

WORKDIR /var/www/html
