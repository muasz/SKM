FROM php:8.2-apache

# Apache PHP images use prefork; disable every other MPM first.
RUN a2dismod mpm_event mpm_worker mpm_prefork 2>/dev/null || true \
    && a2enmod mpm_prefork \
    && find /etc/apache2/mods-enabled -type l -name 'mpm_*.load' ! -name 'mpm_prefork.load' -delete \
    && find /etc/apache2/mods-enabled -type l -name 'mpm_*.conf' ! -name 'mpm_prefork.conf' -delete \
    && apache2ctl configtest

# Install MySQL extension
RUN docker-php-ext-install mysqli

# Copy project
COPY . /var/www/html/

# Permission
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80