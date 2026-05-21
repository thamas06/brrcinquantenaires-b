FROM php:8.2-cli

# Installer dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    zip

# Installer extension ZIP
RUN docker-php-ext-install zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Dossier du projet
WORKDIR /var/www/html

# Copier les fichiers
COPY . .

# Installer Laravel
RUN composer install --optimize-autoloader --no-dev

# Générer cache Laravel
RUN php artisan config:cache

# Exposer le port Render
EXPOSE 10000

# Démarrage
CMD php artisan serve --host=0.0.0.0 --port=$PORT