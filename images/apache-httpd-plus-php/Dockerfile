# Specify the base image
# php:<version>-apache containts Debian's Apache httpd web server plus PHP
FROM php:8.2-apache

# Install additional PHP extensions required by MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set permissions for the web server
RUN chown -R www-data:www-data /var/www/html

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Expose the container's port 80
EXPOSE 80