FROM phpswoole/swoole:php7.4-alpine

# Instalar extensiones adicionales
RUN apk add --no-cache \
        libsodium-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install sodium \
    && pecl install redis-5.3.7 \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Instalar composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias y generar autoload de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Exponer puerto WebSocket
EXPOSE 9501

# Comando para iniciar servidor Swoole
CMD ["php", "server.php"]
