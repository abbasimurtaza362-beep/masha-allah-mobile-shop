FROM php:8.4-cli

RUN docker-php-ext-install pdo pdo_mysql mysqli

WORKDIR /app

COPY . /app

RUN chmod -R 755 /app

EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]
