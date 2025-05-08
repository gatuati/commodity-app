FROM php:8.2-apache

# Install MySQLi extension and dependencies
RUN docker-php-ext-install mysqli && \
    docker-php-ext-enable mysqli

COPY . /var/www/html/
EXPOSE 80