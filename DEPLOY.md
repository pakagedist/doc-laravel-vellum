# Despliegue en un VPS

Esta app es de **documentación**: por defecto funciona **sin base de datos**.
Solo las demos de usuarios (`/documents/users`, `/pdf/users` y `/pdf/inspect`)
pueden leer la tabla `users`; si no hay BD usan datos de ejemplo.

## Requisitos

| | Necesitas |
| :-: | --- |
| 🐘 | PHP **8.4+** con `dom`, `mbstring`, `gd`, `zip`, `intl`, `pdo_sqlite` y `opcache` |
| 📦 | Composer 2 y `git` (la dependencia Vellum se instala desde GitHub) |
| 🟢 | Node.js 22+ y npm (solo para compilar assets). `.nvmrc` fija la versión |
| 🐳 | Docker + Docker Compose v2 (si usas el camino Docker) |

Extensiones en Debian/Ubuntu:

```bash
sudo apt install php8.4-fpm php8.4-cli php8.4-xml php8.4-mbstring \
    php8.4-gd php8.4-zip php8.4-intl php8.4-sqlite3 php8.4-opcache \
    composer nginx
```

## Camino A — Docker (recomendado)

```bash
git clone https://github.com/pakagedist/doc-laravel-vellum.git
cd doc-laravel-vellum
./start.sh
```

`start.sh` crea el `.env`, instala Composer, genera `APP_KEY`, instala npm,
compila assets y levanta **app (PHP-FPM) + web (Nginx)** sin Postgres.

Abre `http://IP_DEL_VPS:8080` (o el `APP_PORT` que configures).

Comandos:

```bash
./start.sh build     # npm run build
./start.sh migrate   # solo si activas SHOWCASE_USERS_DEMO
./start.sh shell
./start.sh logs
./start.sh down
```

## Camino B — Nginx + PHP-FPM nativo

```bash
cd /var/www
git clone https://github.com/pakagedist/doc-laravel-vellum.git
cd doc-laravel-vellum

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
npm ci && npm run build

sudo chown -R www-data:www-data storage bootstrap/cache
```

`/etc/nginx/sites-available/doc-laravel-vellum`:

```nginx
server {
    listen 80;
    server_name docs.tudominio.com;
    root /var/www/doc-laravel-vellum/public;

    index index.php;
    charset utf-8;
    client_max_body_size 20m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/doc-laravel-vellum /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

En `.env`: `APP_URL=https://docs.tudominio.com`, `APP_ENV=production`,
`APP_DEBUG=false`. Luego:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Camino C — Nixpacks / Dokploy

Dokploy (y Railway) construyen la imagen con **Nixpacks**, sin Dockerfile.

Claves del build:

- `.nvmrc` = `22` → Nixpacks instala **Node 22**. Sin ese archivo cae a Node 18 y
  `npm run build` falla con
  `The requested module 'node:util' does not provide an export named 'styleText'`
  (Vite 8 / rolldown exigen Node `^20.19` o `>=22.12`).
- PHP 8.4 se detecta solo desde `composer.json` (`"php": "^8.4"`).
- La app **no necesita base de datos**: no corras `migrate` ni configures Postgres.

Variables de entorno (pestaña *Environment* de Dokploy):

| Variable | Valor | ¿Obligatoria? |
| --- | --- | --- |
| `APP_KEY` | `base64:...` (genera con `php artisan key:generate --show`) | Sí |
| `APP_ENV` | `production` | No |
| `APP_DEBUG` | `false` | No |
| `APP_URL` | `https://docs.tudominio.com` | No |
| `SESSION_DRIVER` | `file` | No (ya es el default) |
| `CACHE_STORE` | `file` | No (ya es el default) |
| `QUEUE_CONNECTION` | `sync` | No (ya es el default) |
| `SHOWCASE_USERS_DEMO` | `false` | No |

Notas:

- Sin `APP_KEY` Laravel lanza `MissingAppKeyException` y todas las rutas devuelven 500.
- Nixpacks sirve en `PORT` (80 por defecto).
- La imagen no incluye Chromium: `PDF_DRIVER=browsershot` no funciona; usa `PDF_DRIVER=dompdf` (default).
- Nixpacks da permiso de escritura a `storage` al arrancar; `storage/framework/*` y
  `storage/logs` viajan en la imagen.
- Alternativa a `.nvmrc`: variable `NIXPACKS_NODE_VERSION=22`.

## Modo sin base de datos (por defecto)

```env
DB_CONNECTION=sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
SHOWCASE_USERS_DEMO=false
```

No ejecutes `migrate`. La app no abre SQLite: sesión, caché y cola no la usan,
y las demos de usuarios muestran datos de ejemplo.

Además, `AppServiceProvider` degrada en tiempo de ejecución los drivers
respaldados por BD (`session.driver`, `cache.default` y `queue.default`) a
`file` / `file` / `sync`. Si el entorno llega a `SESSION_DRIVER=database`
(por ejemplo, copiado de otra app), la sesión **no** consulta la tabla
`sessions` y la aplicación funciona sin base de datos. Aun así, deja esas
variables en `file` / `file` / `sync` para que la configuración refleje lo real.

## Modo con SQLite (demos reales de usuarios)

```env
SHOWCASE_USERS_DEMO=true
```

```bash
php artisan migrate --force --seed
```

Con Docker: `./start.sh migrate`. Requiere que `database/` sea escribible por
PHP (el archivo `database/database.sqlite` se crea solo al migrar).

## Dependencia `laravel-vellum`

Ya **no** necesitas la carpeta hermana `../laravel-vellum`. `composer.json`
usa el repositorio VCS público y el lock fija el commit:

```json
"repositories": {
    "vellum": {
        "type": "vcs",
        "url": "https://github.com/pakagedist/laravel-vellum.git"
    }
}
```

> El paquete figura en Packagist pero **no publica versiones**; por eso se
> instala desde GitHub. No cambies a `"type": "path"` salvo que clones el
> paquete junto al proyecto.

## Motor de PDF

Por defecto `PDF_DRIVER=dompdf`, que solo necesita PHP. `mpdf` también funciona.
`browsershot` requiere Chromium y **no** se instala con `npm install` porque
`.npmrc` tiene `ignore-scripts=true`:

```bash
npx puppeteer browsers install chrome
PDF_DRIVER=browsershot
```

## Problemas típicos

| Síntoma | Causa / solución |
| --- | --- |
| `No application encryption key has been specified` | Falta `.env` o `APP_KEY`: `cp .env.example .env && php artisan key:generate` |
| `Database file at path ... does not exist ... select * from "sessions"` | Variable `SESSION_DRIVER=database` en el entorno: ponla en `file` (la app la degrada sola, pero mejor no dejarla) |
| `Could not find package laravel-vellum/laravel-vellum` | `composer.json` volvió al repo `path`. Usa el repo VCS y `composer update laravel-vellum/laravel-vellum` |
| `Unable to locate file in Vite manifest` | Falta `npm run build` (o `./start.sh build`) |
| HTTP 500 tras cambiar `.env` | `php artisan config:clear && php artisan config:cache` |
| `The stream or file "storage/logs/laravel.log" could not be opened` | Permisos: `chown -R www-data:www-data storage bootstrap/cache` |
| `browsershot` da timeout | Falta Chromium: `npx puppeteer browsers install chrome` o usa `PDF_DRIVER=dompdf` |
| `SyntaxError: ... 'node:util' does not provide an export named 'styleText'` | Nixpacks usó Node 18: añade `.nvmrc` con `22` (o `NIXPACKS_NODE_VERSION=22`) |

## Diferencia con `timelock`

`timelock` es una app de uso: **Postgres obligatorio**, 21 migraciones y
`compose.yaml` con servicio `pgsql`. Esta app es de documentación: **sin BD por
defecto**, SQLite opcional solo para las demos de usuarios, y su `compose.yaml`
levanta únicamente app + nginx.
