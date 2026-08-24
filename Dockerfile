FROM php:8.4-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork rewrite

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080

CMD ["bash", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-8080}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT:-8080}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
