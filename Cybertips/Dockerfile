FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
# Railway provides the port to listen on in $PORT
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
 && sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf \
 && apache2-foreground
