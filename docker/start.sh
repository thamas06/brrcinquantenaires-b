#!/bin/sh

cd /var/www/html

echo "==> Démarrage de l'application Laravel..."

# ✅ 1. Attendre PostgreSQL
echo "==> Vérification de la connexion à PostgreSQL..."
MAX_TRIES=15
COUNT=0
until php -r "
    try {
        \$pdo = new PDO(
            'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        echo 'OK';
    } catch(Exception \$e) {
        exit(1);
    }
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

# ✅ 2. Créer les dossiers storage nécessaires
echo "==> Création des dossiers storage..."
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/testing
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
touch /var/www/html/storage/logs/laravel.log
chmod -R 777 /var/www/html/storage || true
chmod -R 777 /var/www/html/bootstrap/cache || true

# ✅ 3. Vider les caches
echo "==> Nettoyage du cache..."
php artisan config:clear  || true
php artisan route:clear   || true
php artisan view:clear    || true

# ✅ 4. Générer la clé si absente
echo "==> Vérification de la clé application..."
php artisan key:generate --force --no-interaction || true

# ✅ 5. Mettre en cache la config
echo "==> Mise en cache de la configuration..."
php artisan config:cache || true

# ✅ 6. Migrations (ne supprime JAMAIS les données existantes)
echo "==> Lancement des migrations..."
php artisan migrate --force || true

# ✅ 7. Seeder uniquement si aucun utilisateur
echo "==> Vérification des seeders..."
USER_COUNT=$(php -r "
    try {
        \$pdo = new PDO(
            'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        \$count = \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        echo \$count;
    } catch(Exception \$e) {
        echo '0';
    }
" 2>/dev/null || echo "0")

echo "==> Nombre d'utilisateurs: $USER_COUNT"
if [ "$USER_COUNT" = "0" ]; then
    echo "==> Lancement du seeder (première installation)..."
    php artisan db:seed --force || true
else
    echo "==> Seeder ignoré ($USER_COUNT utilisateurs existants - données préservées)"
fi

# ✅ 8. Optimiser
echo "==> Optimisation production..."
php artisan route:cache || true
php artisan view:cache  || true

# ✅ 9. Ajuster le port Nginx
PORT=${PORT:-10000}
echo "==> Configuration Nginx sur le port $PORT..."
sed -i "s/listen [0-9]*;/listen ${PORT};/g" /etc/nginx/conf.d/default.conf

# ✅ 10. Permissions finales
echo "==> Permissions finales..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

# ✅ 11. Afficher le log Laravel au démarrage pour debug
echo "==> Vérification Laravel..."
php artisan --version || true
php -r "
    define('LARAVEL_START', microtime(true));
    require '/var/www/html/vendor/autoload.php';
    \$app = require_once '/var/www/html/bootstrap/app.php';
    echo 'Bootstrap OK' . PHP_EOL;
" 2>&1 || true
echo "==> Log Laravel (dernières erreurs):"
cat /var/www/html/storage/logs/laravel.log 2>/dev/null | tail -30 || echo "Pas de log disponible"

# ✅ 12. Démarrer Supervisor
echo "==> Démarrage de Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
