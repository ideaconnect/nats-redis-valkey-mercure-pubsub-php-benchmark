FROM php:8.3-cli
RUN apt-get update && apt-get install -y zip unzip git
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
VOLUME /app
WORKDIR /app
RUN install-php-extensions redis igbinary