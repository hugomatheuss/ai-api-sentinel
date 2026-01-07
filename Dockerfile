# Estágio do Composer para obter o binário do Composer
FROM composer:2 AS composer

# Imagem base PHP-FPM
FROM php:8.4-fpm

# Instalar dependências do sistema e extensões PHP necessárias
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git \
    unzip \
    zip \
    curl \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    bash \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && docker-php-ext-install pdo pdo_pgsql mbstring zip opcache \
  && rm -rf /var/lib/apt/lists/*

# Copiar o binário do composer do estágio anterior
COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar apenas o código da aplicação a partir do diretório `application/`
# Isso permite que o repositório mantenha a aplicação em ./application
COPY application/ /var/www/html/

# Se composer.json existir (dentro de /var/www/html), instalar dependências (sem dev) para acelerar cache
RUN if [ -f /var/www/html/composer.json ]; then composer install --no-interaction --prefer-dist --no-dev --no-scripts --no-autoloader -d /var/www/html || true; fi

# Instalar dependências finais se necessário e otimizar autoload
RUN if [ -f /var/www/html/composer.json ]; then composer install --no-interaction --prefer-dist -d /var/www/html && composer dump-autoload --optimize -d /var/www/html; fi

# Ajuste de permissões para storage e cache (padrões para Laravel)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true \
  && chmod -R 755 /var/www/html/storage || true

USER www-data

CMD ["php-fpm"]
