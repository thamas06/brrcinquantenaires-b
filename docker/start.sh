#!/bin/sh
set -e

cd /var/www/html

# Générer la clé si elle n'existe pas
php artisan key:generate --force

# Ajuster la configuration Nginx pour utiliser le port fourni par l'environnement (Render fournit $PORT)
PORT=${PORT:-10000}
if [ -f /etc/nginx/conf.d/default.conf ]; then
    sed -i "s/listen 8080;/listen ${PORT};/g" /etc/nginx/conf.d/default.conf || true
fi

# Vider le cache de config
php artisan config:clear
php artisan cache:clear

# Lancer les migrations
php artisan migrate --force

# Lancer le seeder seulement si la table users est vide
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -1)
if [ "$USER_COUNT" = "0" ]; then
    php artisan db:seed --force
fi

# Optimiser pour la production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Démarrer les services
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
