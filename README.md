<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## Generación de PDF (`laravel-vellum`)

La aplicación usa [`laravel-vellum`](https://github.com/laravel-vellum/laravel-vellum), una capa
de generación de PDF independiente del motor: el mismo documento se puede renderizar con
DOMPDF, mPDF o Chromium (Browsershot) sin tocar el código de la aplicación.

### Qué hay instalado

| Paquete | Para qué |
| --- | --- |
| `laravel-vellum/laravel-vellum` | La capa de PDF (vía repositorio local `path`, con *symlink*) |
| `dompdf/dompdf` | Motor por defecto: HTML → PDF en PHP puro |
| `mpdf/mpdf` | Motor alternativo, rápido y con mejor soporte de Unicode |
| `spatie/browsershot` + `puppeteer` | Motor Chromium: HTML/CSS moderno (Node.js + Chromium) |
| `twig/twig` | Motor de plantillas Twig como fuente de documento |
| `setasign/fpdi` + `setasign/fpdf` | Unir varios PDF en uno (post-procesado `merge()`) |

El motor se elige en `config/pdf.php` (`PDF_DRIVER`) o por documento con `->driver('mpdf')`.

### Cómo se usa

El punto de entrada de la aplicación es `App\Documents\DocumentRenderer`: recibe una vista Blade
y unas opciones, y devuelve el PDF ya renderizado.

```php
use App\Documents\DocumentOptions;
use App\Documents\DocumentRenderer;

final class FacturaController extends Controller
{
    public function __construct(private readonly DocumentRenderer $renderer) {}

    public function descargar(Factura $factura): Response
    {
        return $this->renderer->download('documents.factura', [
            'factura' => $factura,
        ], new DocumentOptions(
            filename: 'factura-'.$factura->numero.'.pdf',
            metadata: ['title' => 'Factura '.$factura->numero],
        ));
    }
}
```

El controlador de ejemplo (`App\Http\Controllers\DocumentController`) usa exactamente ese patrón
para servir el listado de usuarios en `/documents/users`.

> El endpoint de ejemplo es público porque la aplicación todavía no tiene autenticación:
> envuélvelo en `->middleware('auth')` antes de publicarlo.

Opciones disponibles en `DocumentOptions`: `driver`, `paper`, `orientation`, `margins`,
`filename` y `metadata`. Lo que no se define se hereda de `config/pdf.php`.

### Plantillas

Las plantillas viven en `resources/views/documents/` y son HTML plano con Blade.

> **El papel y la orientación los decide el documento**, no el CSS: el paquete descarta las
> declaraciones `@page { size: … }` de la plantilla porque cada motor las lee de forma distinta
> (mPDF las interpreta como una página de tamaño cero). Si una plantilla declara un tamaño que no
> coincide con el configurado, el motor emite un aviso en lugar de cambiar el papel en silencio.

### Comprobación de los motores

```bash
php artisan pdf:check            # todos los motores configurados
php artisan pdf:check --driver=mpdf
```

| Motor | Estado | KB | ms | Avisos |
| --- | --- | --- | --- | --- |
| dompdf | OK | 1.1 | 39.0 | 0 |
| browsershot | OK | 7.0 | 521.8 | 0 |
| mpdf | OK | 14.7 | 81.5 | 0 |
| fake | OK | 0.7 | 0.5 | 0 |

El comando devuelve código de salida `1` si algún motor falla, así que sirve como comprobación
en CI.

### Prueba de humo de los documentos

`pdf:check` comprueba que el motor arranca; `pdf:smoke` renderiza además los documentos reales
(factura, informe y HTML) y resume páginas, tamaño, duración y avisos:

```bash
php artisan pdf:smoke                       # los tres documentos con cada motor
php artisan pdf:smoke --driver=dompdf
php artisan pdf:smoke --driver=fake --save  # guarda en storage/app/pdf-smoke
```

### Activar Chromium (opcional)

El motor `browsershot` necesita Node.js y Chromium:

```bash
composer require spatie/browsershot
npm install puppeteer
```

La configuración publicada ya apunta `node_modules_path` a `base_path('node_modules')`.

## Panel de demostración

La aplicación incluye un panel web en español que recorre **todas** las categorías de la API
de `laravel-vellum`. Cada pantalla ejecuta la demostración de verdad y enseña el PDF generado,
las métricas del render y el código que lo produce.

### Rutas

| Ruta | Qué muestra |
| --- | --- |
| `/` | Portada: el catálogo completo, los motores detectados y las rutas de la aplicación |
| `/pdf` | Índice técnico del panel, agrupado por categoría |
| `/pdf/{categoria}` | Las demostraciones de una categoría |
| `/pdf/{categoria}/{demo}` | Una demostración (con la primera variante) |
| `/pdf/{categoria}/{demo}/{variante}` | Una variante concreta |
| `/pdf/archivo/{categoria}/{demo}/{variante}.pdf` | El PDF servido en línea |
| `/pdf/descarga/{categoria}/{demo}/{variante}` | El PDF como descarga |
| `/pdf/json/{categoria}/{demo}/{variante}` | El PDF en JSON, en base64, con su `sha256` |
| `/documents/users` | Listado de usuarios en PDF (documento de la propia aplicación) |
| `/pdf/users` | El mismo documento con parámetros (`?driver=mpdf`, `?paper=a5`, `?font=DejaVu%20Serif`, `?inline=1`…) |
| `/pdf/inspect` | Métricas del render en JSON (tamaño, páginas, MediaBox, sha256, avisos) |

### Categorías

Ocho categorías, 36 demostraciones y 109 variantes:

| Categoría | Qué enseña |
| --- | --- |
| **Fuentes de documento** | Las seis fuentes: HTML, Blade, archivo, URL, Twig y fuente propia |
| **Motores de render** | El mismo documento con los cuatro motores, sus capacidades y el motor de pruebas |
| **Papel, márgenes y tipografía** | Tamaños de papel, orientación, márgenes, resolución y tipografías |
| **Tipografías** | El catálogo de fuentes de la instalación, el mismo espécimen con cada familia y el peso del PDF |
| **Metadatos y conformidad** | Diccionario `/Info`, paquete XMP, PDF/A-3b y Factur-X/ZUGFeRD |
| **Post-procesado del documento** | Adjuntar archivos, unir documentos, firma digital y post-procesador propio |
| **Entrega del documento** | Servido en línea, descarga, guardado en disco, JSON en base64 y archivo temporal |
| **Funcionamiento interno** | Capas de configuración, registro de motores, orden de los post-procesadores y pruebas |

### Tipografías

La categoría `/pdf/tipografias` reúne las 74 familias que esta instalación puede usar de verdad:
29 con cobertura latina y 45 para otros alfabetos (CJK, árabe, hebreo, tailandés, birmano, kannada…).
El catálogo se construye en tiempo de ejecución a partir de cuatro orígenes y se fusiona por familia:

- **fontconfig** (`fc-list`, una sola llamada): las fuentes instaladas en el sistema, que son las que
  ve Chromium.
- **mPDF** (`fontdata`): sus 40 familias registradas, no solo los TTF del disco.
- **dompdf**: sus tres DejaVu y las fuentes del núcleo PDF.
- **Proyecto**: cualquier TTF que dejes en `resources/fonts/`.

El resultado se guarda un día en el almacén `file` (`Cache::store('file')`), así que el panel no
repite el descubrimiento en cada petición. Si instalas fuentes nuevas, vacía la caché:

```bash
php artisan cache:clear --store=file
```

Cada fila muestra una muestra renderizada en el navegador (solo si la familia está instalada en el
sistema), la cobertura, el tipo, el tamaño del archivo y los motores compatibles. La tabla se puede
filtrar por familia, cobertura u origen.

Las demostraciones cubren las preguntas habituales:

- **Familias latinas**: una variante por fuente con cobertura latina, con el mismo espécimen.
- **Otros alfabetos**: chino, japonés, coreano, árabe, hebreo, tailandés y birmano con texto real de
  cada script. mPDF aplica el shaping OpenType (OTL) de árabe, hebreo y las escrituras índicas.
- **La misma fuente en cada motor**: DejaVu Sans con `dompdf`, `mpdf` y `browsershot`.
- **Cuánto pesa la tipografía**: la misma página con Helvetica (núcleo PDF, no se incrusta),
  DejaVu Sans (catálogo del motor, se incrusta) y un TTF propio.
- **Incrustar un TTF propio**: dónde funciona `font('Alias', $ruta)` y dónde no.

Cada motor resuelve las fuentes a su manera:

| Motor | De dónde salen | `fontFile` |
| --- | --- | --- |
| `dompdf` | Su carpeta `lib/fonts` y las fuentes del núcleo PDF | Sí, inyecta `@font-face` |
| `mpdf` | Su carpeta `ttfonts` y su configuración de fuentes | No: usa el nombre de la familia |
| `browsershot` | Las fuentes instaladas en el sistema (fontconfig) | No: Browsershot bloquea `file://` |

Para ampliar el catálogo, la vía más rápida es instalar colecciones del sistema:

```bash
sudo apt install fonts-texgyre fonts-liberation fonts-noto-core fonts-noto-cjk \
    fonts-firacode fonts-jetbrains-mono fonts-hack fonts-ibm-plex
fc-cache -f
```

Para dompdf, deja el TTF en `resources/fonts/` (aparece como origen «Proyecto») y pásalo con
`fontFile`. dompdf no lee OTF/CFF ni fuentes variables: conviértelas a TTF estático con `fonttools`
(`fonttools varLib.instancer`). Para Google Fonts, TeX Gyre, IBM Plex, Noto o Fontsource, descarga
los TTF y colócalos en el sistema (`~/.local/share/fonts` + `fc-cache -f`) o en el proyecto.

En la aplicación, `DocumentOptions` acepta `font` y `fontFile`, así que el listado de usuarios se
puede generar con cualquier familia:

```php
return $this->renderer->download('documents.users', $data, new DocumentOptions(
    driver: 'browsershot',
    font: 'Nimbus Sans',
));
```

También por query string: `/pdf/users?font=Nimbus%20Sans&driver=browsershot`.

### Arranque

```bash
php artisan serve
```

> **Importante:** la demostración de fuente por URL descarga `/showcase/remoto.html`, un archivo
> estático publicado por la propia aplicación. Con `php artisan serve` el servidor tiene un único
> worker, así que la descarga se quedaría esperando a la petición que la ha lanzado. Arranca el
> servidor con varios workers:
>
> ```bash
> PHP_CLI_SERVER_WORKERS=4 php artisan serve --no-reload
> ```
>
> Si la aplicación se sirve en un dominio al que el servidor no puede llegar, se puede apuntar la
> demo a otra URL con `SHOWCASE_REMOTE_URL`.

Las vistas previas se cachean en `storage/app/pdf-showcase/`; borra esa carpeta para forzar el
renderizado de nuevo.

### Pruebas

Las pruebas del panel (`tests/Feature/Http/Controllers/ShowcaseControllerTest.php`) recorren todas
las categorías, demostraciones y variantes, y fallan si alguna pantalla muestra un error de
renderizado:

```bash
php artisan test --compact
```

## Prompt original

El encargo que dio origen a la librería y a este panel está en [PROMPT.md](PROMPT.md).


