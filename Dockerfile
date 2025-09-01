# Dockerfile
FROM php:8.3-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql mysqli opcache

# Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# Copier tout le projet dans le container
COPY . /var/www/html/

# Donner les droits corrects à Symfony
RUN chown -R www-data:www-data /var/www/html/var /var/www/html/vendor \
    && chmod -R 775 /var/www/html/var /var/www/html/vendor

# DocumentRoot sur /var/www/html/public
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# Droits
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

WORKDIR /var/www/html
