FROM php:8.4-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

RUN sed -i \
    's/LoadModule mpm_event_module/#LoadModule mpm_event_module/' \
    /etc/apache2/mods-enabled/mpm_event.load \
    || true

RUN sed -i \
    's/LoadModule mpm_worker_module/#LoadModule mpm_worker_module/' \
    /etc/apache2/mods-enabled/mpm_worker.load \
    || true

RUN sed -i \
    's/Listen 80/Listen 8080/' \
    /etc/apache2/ports.conf

RUN sed -i \
    's/:80>/:8080>/' \
    /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

CMD ["apache2-foreground"]
