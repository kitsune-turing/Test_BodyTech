# Stage 1: Build
FROM composer:2 AS builder

WORKDIR /app

# Copiar archivos de dependencias
COPY composer.json composer.lock* ./

# Instalar dependencias (sin dev para producción)
# --ignore-platform-reqs porque ext-redis/phalcon estarán en el runtime stage
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --ignore-platform-reqs

# Stage 2: Runtime
FROM php:7.4-cli-alpine

# Instalar extensiones PHP necesarias
RUN apk add --no-cache \
        postgresql-dev \
        redis \
        libsodium-dev \
        pcre-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_pgsql sodium \
    && pecl install redis-5.3.7 \
    && pecl install psr-1.2.0 \
    && pecl install phalcon-4.1.2 \
    && docker-php-ext-enable redis psr phalcon \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html

# Copiar código fuente primero (sin vendor)
COPY . .

# Eliminar vendor local si existe
RUN rm -rf ./vendor

# Copiar dependencias desde builder (después, para no sobrescribir)
COPY --from=builder /app/vendor ./vendor

# Verificar que las dependencias se instalaron correctamente
RUN test -f vendor/autoload.php || \
    (echo "ERROR: vendor/autoload.php not found" && exit 1)

# Exponer puerto
EXPOSE 80

# Comando por defecto (puede ser sobreescrito en docker-compose)
CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]
