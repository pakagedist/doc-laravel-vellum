#!/usr/bin/env bash
#
# start.sh — arranque de doc-laravel-vellum con Docker.
#
# Uso:
#   ./start.sh            # instala dependencias, compila assets y levanta la app
#   ./start.sh build      # solo compila los assets (npm run build)
#   ./start.sh migrate    # crea/migra SQLite y carga los datos de las demos
#   ./start.sh shell      # shell dentro del contenedor
#   ./start.sh logs       # logs en vivo
#   ./start.sh down       # detiene los contenedores

set -euo pipefail

cd "$(dirname "$0")"
MODE="${1:-up}"

if [[ ! -f .env ]]; then
    cp .env.example .env
    echo "==> .env creado desde .env.example"
fi

run() {
    docker compose run --rm --no-deps app "$@"
}

case "${MODE}" in
    down)
        docker compose down
        exit 0
        ;;
    logs)
        docker compose logs -f
        exit 0
        ;;
    shell)
        docker compose run --rm --no-deps app bash
        exit 0
        ;;
    migrate)
        run php artisan migrate --force --seed
        exit 0
        ;;
    build)
        run sh -lc 'test -d node_modules || npm install --ignore-scripts; npm run build'
        exit 0
        ;;
    up | "") ;;
    *)
        echo "Uso: ./start.sh [up|build|migrate|shell|logs|down]" >&2
        exit 1
        ;;
esac

echo "==> Construyendo imagen"
docker compose build

echo "==> Instalando dependencias PHP"
run composer install --no-interaction --prefer-dist --optimize-autoloader

if ! grep -q '^APP_KEY=base64:' .env; then
    echo "==> Generando APP_KEY"
    run php artisan key:generate --force
fi

echo "==> Ajustando permisos de storage"
docker compose run --rm --no-deps --user root app \
    sh -lc 'chown -R vellum:vellum storage bootstrap/cache'

if [[ ! -d node_modules ]]; then
    echo "==> Instalando dependencias JS"
    run npm install --ignore-scripts
fi

echo "==> Compilando assets"
run npm run build

if grep -q '^SHOWCASE_USERS_DEMO=true' .env; then
    echo "==> Migrando la base de datos de las demos"
    run php artisan migrate --force --seed
fi

echo "==> Levantando contenedores"
docker compose up -d

PORT="${APP_PORT:-$(grep -E '^APP_PORT=' .env | tail -1 | cut -d= -f2 | tr -d '[:space:]')}"
echo "==> Listo: http://localhost:${PORT:-8080}"
