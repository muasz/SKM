FROM php:8.2-cli

# Install MySQL extension
RUN docker-php-ext-install mysqli

# Copy project
COPY . /var/www/html/

# Permission
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-80} -t /var/www/html"]