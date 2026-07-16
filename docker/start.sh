#!/bin/sh
set -e

cd /var/www/html

echo "==> Démarrage de l'application Laravel..."

# ✅ 1. Vérifier la connexion à la base de données avant tout
echo "==> Vérification de la connexion à PostgreSQL..."
MAX_TRIES=10
COUNT=0
until php -r "
    \$pdo = new PDO(
        'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo 'OK';
" 2>/dev/null | grep -q "OK"; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "❌ Impossible de se connecter à PostgreSQL après $MAX_TRIES tentatives"
        exit 1
    fi
    echo "  Tentative $COUNT/$MAX_TRIES - attente 3s..."
    sleep 3
done
echo "✅ Connexion PostgreSQL OK"

# ✅ 2. Vider uniquement les caches Laravel (sans toucher SQLite)
echo "==> Nettoyage du cache..."
php artisan config:clear  || true
php artisan route:clear   || true
php artisan view:clear    || true

# ✅ 3. Mettre en cache la config AVANT les migrations
echo "==> Mise en cache de la configuration..."
php artisan config:cache

# ✅ 4. Lancer les migrations (--force sans --fresh pour ne pas supprimer les données)
echo "==> Lancement des migrations..."
php artisan migrate --force

# ✅ 5. Seeder — uniquement si aucun utilisateur
echo "==> Vérification des seeders..."
USER_COUNT=$(php -r "
    try {
        \$pdo = new PDO(
            'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        echo \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    } catch(Exception \$e) {
        echo '0';
    }
" 2>/dev/null || echo "0")
echo "==> Nombre d'utilisateurs: $USER_COUNT"
if [ "$USER_COUNT" = "0" ]; then
    echo "==> Aucun utilisateur — lancement du seeder..."
    php artisan db:seed --force
else
    echo "==> Seeder ignoré ($USER_COUNT utilisateurs déjà présents)"
fi

# ✅ 6. Optimiser pour la production
echo "==> Optimisation production..."
php artisan route:cache || true
php artisan view:cache  || true

# ✅ 7. Ajuster le port Nginx dynamiquement
PORT=${PORT:-10000}
echo "==> Configuration Nginx sur le port $PORT..."
sed -i "s/listen [0-9]*;/listen ${PORT};/g" /etc/nginx/conf.d/default.conf

# ✅ 8. Permissions finales
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# ✅ 9. Démarrer Supervisor (Nginx + PHP-FPM)
echo "==> Démarrage de Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf