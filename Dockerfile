FROM php:8.2-apache

# Pastikan hanya MPM prefork yang digunakan
RUN a2dismod mpm_event mpm_worker mpm_event 2>/dev/null || true \
    && a2enmod mpm_prefork

# Install extension MySQL untuk PHP
RUN docker-php-ext-install mysqli

# Copy project
COPY . /var/www/html/

# Permission
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80