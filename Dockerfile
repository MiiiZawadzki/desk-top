FROM dunglas/frankenphp:1-php8.3

RUN install-php-extensions pdo_sqlite

COPY Caddyfile /etc/frankenphp/Caddyfile

WORKDIR /app
COPY . /app

RUN mkdir -p /app/data && chown -R www-data:www-data /app/data

EXPOSE 80
