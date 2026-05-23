FROM php:8.2-fpm

# Installer dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    zip \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    zlib1g-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    supervisor \
    nginx \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd xml zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Dossier projet
WORKDIR /var/www/html

# Copier projet
COPY . .

RUN mkdir -p storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ✅ IMPORTANT : créer dossiers Laravel nécessaires
RUN mkdir -p storage bootstrap/cache

# ❌ NE PAS copier .env (Render gère les variables d’environnement)
# RUN cp .env.example .env

# Config supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Start script
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Nginx config
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Installer dépendances PHP
RUN composer install --optimize-autoloader --no-dev --no-interaction

# ✅ Permissions correctes Laravel (IMPORTANT)
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Exposer port Render
EXPOSE 10000

# Lancer le serveur
CMD ["/usr/local/bin/start.sh"]