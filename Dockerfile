FROM php:8.2-fpm

# Installer dépendances système, nginx et supervisor
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
    supervisor \
    nginx \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configurer et installer extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd xml zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Dossier du projet
WORKDIR /var/www/html

# Copier le projet
COPY . .

# Créer .env de base si absent
RUN cp .env.example .env

# Copier les configs Docker
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Copier la config nginx
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Installer les dépendances PHP
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-plugins

# ✅ Permissions correctes pour Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Exposer le port Render
EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]