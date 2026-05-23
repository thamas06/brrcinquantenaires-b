#!/bin/sh
set -e

cd /var/www/html

echo "==> Démarrage de l'application Laravel..."

# ✅ 1. Vérifier la connexion à la base de données avant tout
echo "==> Vérification de la connexion à MySQL (Railway)..."
MAX_TRIES=10
COUNT=0
until php artisan db:monitor 2>/dev/null || php -r "
    \$pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo 'OK';
" 2>/dev/null | grep -q "OK"; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "❌ Impossible de se connecter à MySQL après $MAX_TRIES tentatives"
        exit 1
    fi
    echo "  Tentative $COUNT/$MAX_TRIES - attente 3s..."
    sleep 3
done
echo "✅ Connexion MySQL OK"

# ✅ 2. Générer la clé app si absente
echo "==> Génération de la clé application..."
php artisan key:generate --force

# ✅ 3. Vider les anciens caches
echo "==> Nettoyage du cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# ✅ 4. Mettre en cache la config AVANT les migrations
echo "==> Mise en cache de la configuration..."
php artisan config:cache

# ✅ 5. Lancer les migrations
echo "==> Lancement des migrations..."
php artisan migrate --force

# ✅ 6. Seeder - vérification propre sans tinker
echo "==> Vérification des seeders..."
USER_COUNT=$(php artisan db:seed --class=CheckUserSeeder 2>/dev/null || php -r "
    \$pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
" 2>/dev/null || echo "0")

if [ "$USER_COUNT" = "0" ]; then
    echo "==> Lancement du seeder..."
    php artisan db:seed --force
else
    echo "==> Seeder ignoré (utilisateurs déjà présents)"
fi

# ✅ 7. Optimiser pour la production
echo "==> Optimisation production..."
php artisan route:cache
php artisan view:cache

# ✅ 8. Ajuster le port Nginx dynamiquement
PORT=${PORT:-10000}
echo "==> Configuration Nginx sur le port $PORT..."
sed -i "s/listen [0-9]*;/listen ${PORT};/g" /etc/nginx/conf.d/default.conf

# ✅ 9. Permissions finales
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# ✅ 10. Démarrer Supervisor (Nginx + PHP-FPM)
echo "==> Démarrage de Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf