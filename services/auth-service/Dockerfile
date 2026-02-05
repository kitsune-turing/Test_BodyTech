# Stage 1: Build
FROM composer:2 AS builder

WORKDIR /app

# Copiar archivos de dependencias
COPY composer.json composer.lock* ./

# Instalar dependencias (sin dev para producción)
# Forzar generación del autoloader incluso si no hay dependencias
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction 2>&1; \
    mkdir -p vendor/composer; \
    if [ ! -f vendor/autoload.php ]; then \
        echo '<?php return array();' > vendor/autoload.php; \
    fi

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

# Asegurar que autoload.php existe
RUN if [ ! -f vendor/autoload.php ]; then \
        mkdir -p vendor/composer && \
        echo '<?php return array();' > vendor/autoload.php; \
    fi

# Exponer puerto
EXPOSE 80

# Comando por defecto
CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]
