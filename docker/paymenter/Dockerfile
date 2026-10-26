# ==============================================================================
# STAGE 1: PHP 8.3-FPM-ALPINE ACTUALIZADO + CORE DE PAYMENTER
# Construye desde la imagen oficial de PHP para garantizar binarios PHP >= 8.3.36
# ==============================================================================
FROM php:8.3-fpm-alpine AS final
WORKDIR /app

# 1. Instalar dependencias del sistema y extensiones de PHP requeridas por Paymenter
RUN apk update && apk upgrade --no-cache && \
    apk add --no-cache ca-certificates dcron curl git supervisor tar unzip nginx \
    libpng-dev libxml2-dev libzip-dev icu-dev autoconf make g++ gcc libc-dev linux-headers gmp-dev && \
    docker-php-ext-configure zip && \
    docker-php-ext-install bcmath gd pdo_mysql zip intl sockets gmp && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apk del autoconf make g++ gcc libc-dev

# 2. Verificar en tiempo de compilación que la versión de PHP sea >= 8.3.36
RUN php -r 'if (version_compare(PHP_VERSION, "8.3.36", "<")) { fwrite(STDERR, "ERROR: PHP " . PHP_VERSION . " es menor a 8.3.36\n"); exit(1); }'

# 3. Instalar Composer y clonar el repositorio oficial de Paymenter
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    git clone --depth 1 https://github.com/Paymenter/Paymenter.git /app && \
    composer install --no-dev --optimize-autoloader

# 4. Inyectar nuestro Tema "oasis" y nuestra Extensión "OasisP2P" antes de compilar Vite
COPY themes/oasis /app/themes/oasis
COPY extensions/Gateways/OasisP2P /app/extensions/Gateways/OasisP2P

RUN cp .env.example .env && \
    composer dump-autoload -o && \
    chmod 777 -R bootstrap storage/* && \
    rm -rf .env bootstrap/cache/*.php && \
    chown -R nginx:nginx /app && \
    rm -f /usr/local/etc/php-fpm.conf && \
    echo "* * * * * /usr/local/bin/php /app/artisan schedule:run >> /dev/null 2>&1" >> /var/spool/cron/crontabs/root && \
    mkdir -p /var/run/php /var/run/nginx /app/var /app/storage/app/tickets/uploads && \
    chown -R nginx:nginx /app/var /app/storage

# ==============================================================================
# STAGE 2: COMPILACIÓN DE ASSETS FRONTEND (VITE + TAILWIND V4)
# Compila tanto el tema `default` como nuestro tema `oasis`
# ==============================================================================
FROM node:22-alpine AS build
WORKDIR /app

COPY --from=final /app /app
RUN npm install && \
    npm run build && \
    npx vite build --config themes/oasis/vite.config.js

# ==============================================================================
# STAGE 3: IMAGEN FINAL DE PRODUCCIÓN BLINDADA
# ==============================================================================
FROM final AS production

COPY --from=build /app/public /app/public

# Respaldar temas y extensiones (incluyendo oasis y OasisP2P) en las carpetas _default
# y ajustar entrypoint.sh para conservar permisos de escritura en volúmenes montados
RUN cp -r /app/themes /app/themes_default && \
    cp -r /app/extensions /app/extensions_default && \
    sed -i 's/chmod -R 755 \/app\/themes \/app\/extensions/chmod -R 777 \/app\/themes \/app\/extensions/g' /app/.github/docker/entrypoint.sh && \
    chown -R nginx:nginx /app/public /app/themes /app/themes_default /app/extensions /app/extensions_default && \
    chmod -R 777 /app/themes /app/extensions

ENV PAYMENTER_SKIP_DEFAULT=true

# Configuración interna de PHP-FPM y Supervisor de Paymenter
COPY --from=final /app/.github/docker/www.conf /usr/local/etc/php-fpm.conf
COPY --from=final /app/.github/docker/supervisord.conf /etc/supervisord.conf

# Sobrescribir el default.conf de Nginx con nuestra configuración endurecida real
COPY docker/nginx/oasis-security.conf /etc/nginx/http.d/default.conf

# Inyectar nuestro php.ini de blindaje en la ruta exacta de php:8.3-fpm-alpine
COPY docker/php/99-oasis-hardening.ini /usr/local/etc/php/conf.d/99-oasis-hardening.ini

EXPOSE 80
ENTRYPOINT [ "/bin/ash", ".github/docker/entrypoint.sh" ]
CMD [ "supervisord", "-n", "-c", "/etc/supervisord.conf" ]
