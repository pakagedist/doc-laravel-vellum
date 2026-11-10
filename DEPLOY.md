# Despliegue en un VPS

Esta app es de **documentación** y funciona **sin base de datos**: no hay
migraciones, modelos ni seeders, y ninguna conexión está configurada. Las
demos de usuarios (`/documents/users`, `/pdf/users` y `/pdf/inspect`) muestran
siempre datos de ejemplo.

## Requisitos

| | Necesitas |
| :-: | --- |
| 🐘 | PHP **8.4+** con `dom`, `mbstring`, `gd`, `zip`, `intl` y `opcache` |
| 📦 | Composer 2 y `git` (la dependencia Vellum se instala desde GitHub) |
| 🟢 | Node.js 22+ y npm (solo para compilar assets). `.nvmrc` fija la versión |
| 🐳 | Docker + Docker Compose v2 (si usas el camino Docker) |

Extensiones en Debian/Ubuntu:

```bash
sudo apt install php8.4-fpm php8.4-cli php8.4-xml php8.4-mbstring \
    php8.4-gd php8.4-zip php8.4-intl php8.4-opcache \
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
- La app **no tiene base de datos**: no hay que configurar Postgres ni ejecutar
  `migrate`. Si tu hook de despliegue la ejecuta, termina con éxito sin hacer nada.

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

Notas:

- Sin `APP_KEY` Laravel lanza `MissingAppKeyException` y todas las rutas devuelven 500.
- Borra cualquier variable `DB_*` heredada de otro proyecto: la app ignora las
  conexiones, pero `config:cache` las congelaría en la imagen.
- Nixpacks sirve en `PORT` (80 por defecto).
- La imagen no incluye Chromium: `PDF_DRIVER=browsershot` no funciona; usa `PDF_DRIVER=dompdf` (default).
- Nixpacks da permiso de escritura a `storage` al arrancar; `storage/framework/*` y
  `storage/logs` viajan en la imagen.
- Alternativa a `.nvmrc`: variable `NIXPACKS_NODE_VERSION=22`.

## Sin base de datos

```env
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

La configuración de base de datos está vacía (`database.default` es `null` y no
hay conexiones), así que **ninguna petición abre una conexión** ni crea un
archivo SQLite. `php artisan migrate` (y el resto de la familia `migrate:*`) es
un no-op que termina con código 0: si tu plataforma lo ejecuta en el
despliegue, no rompe nada.

Además, `AppServiceProvider` degrada en tiempo de ejecución los drivers
respaldados por BD (`session.driver`, `cache.default` y `queue.default`) a
`file` / `file` / `sync`. Si el entorno llega a `SESSION_DRIVER=database`
(por ejemplo, copiado de otra app), la sesión **no** consulta la tabla
`sessions` y la aplicación funciona sin base de datos. Aun así, deja esas
variables en `file` / `file` / `sync` para que la configuración refleje lo real.

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
| `The [mpdf] driver failed to render the PDF: Temporary files directory ".../vendor/mpdf/mpdf/tmp/mpdf" is not writable` | `vendor/` no es escribible en producción. La app usa `storage/framework/mpdf` para los temporales de mPDF (`PDF_TEMP_FOLDER` lo cambia). Borra la caché de configuración y vuelve a desplegar |
| `Database connection [null] not configured` | Algún código intenta usar la BD: esta app no tiene conexiones. Revisa el fragmento que lo llama |
| `Could not find package laravel-vellum/laravel-vellum` | `composer.json` volvió al repo `path`. Usa el repo VCS y `composer update laravel-vellum/laravel-vellum` |
| `Unable to locate file in Vite manifest` | Falta `npm run build` (o `./start.sh build`) |
| HTTP 500 tras cambiar `.env` | `php artisan config:clear && php artisan config:cache` |
| `The stream or file "storage/logs/laravel.log" could not be opened` | Permisos: `chown -R www-data:www-data storage bootstrap/cache` |
| `browsershot` da timeout | Falta Chromium: `npx puppeteer browsers install chrome` o usa `PDF_DRIVER=dompdf` |
| `SyntaxError: ... 'node:util' does not provide an export named 'styleText'` | Nixpacks usó Node 18: añade `.nvmrc` con `22` (o `NIXPACKS_NODE_VERSION=22`) |

## Diferencia con `timelock`

`timelock` es una app de uso: **Postgres obligatorio**, 21 migraciones y
`compose.yaml` con servicio `pgsql`. Esta app es de documentación: **sin BD**,
ni migraciones ni seeders, y su `compose.yaml` levanta únicamente app + nginx.
