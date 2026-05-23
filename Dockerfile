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
    nginx

# Configurer et installer extensions PHP courantes requises par Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd xml zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Dossier du projet
WORKDIR /var/www/html

# Copier le projet (y compris docker/) dans l'image
COPY . .

# Copier la configuration supervisor et le script de démarrage
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Copier la configuration nginx dans le dossier de conf
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Installer les dépendances PHP sans exécuter de commandes artisan au build
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-plugins

# Exposer le port (Render fournira la variable d'environnement $PORT au runtime)
EXPOSE 10000

# Utiliser le script de démarrage qui gère key:generate, migrations et démarrage des services
CMD ["/usr/local/bin/start.sh"]