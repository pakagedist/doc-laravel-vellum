@php
    $drivers = ['dompdf', 'mpdf', 'browsershot'];
    $labels = \App\Showcase\Fonts\FontFamily::DRIVER_LABELS;
@endphp

<div class="section-head" style="margin-top: 0">
    <div>
        <h2>Catálogo de fuentes</h2>
        <p>
            {{ $totals['total'] }} familias disponibles en esta instalación: {{ $totals['latino'] }} para texto
            latino y {{ $totals['otros'] }} para otros alfabetos. La columna «Motores» indica cuáles pueden usarlas
            de verdad; el resto ignora la familia o cae en una fuente por defecto.
        </p>
    </div>
    <span class="hint">
        {{ $totals['sistema'] ?? 0 }} del sistema · {{ $totals['mpdf'] ?? 0 }} de mPDF ·
        {{ $totals['dompdf'] ?? 0 }} de dompdf · {{ $totals['core'] ?? 0 }} del núcleo
    </span>
</div>

<div class="font-filter">
    <input type="search" id="font-filter" placeholder="Filtrar por familia, cobertura u origen…"
           aria-label="Filtrar el catálogo de fuentes" autocomplete="off">
    <span class="count" id="font-count">{{ $totals['total'] }} familias</span>
</div>

<div class="table-wrap font-table-wrap" style="margin-bottom: 8px">
    <table class="font-table" id="font-catalog">
        <thead>
        <tr>
            <th style="width: 130px">Muestra</th>
            <th style="width: 30%">Familia</th>
            <th style="width: 120px">Cobertura</th>
            <th style="width: 100px">Origen</th>
            <th style="width: 160px">Archivo</th>
            <th>Motores</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($fonts as $font)
            <tr data-search="{{ mb_strtolower($font->family.' '.$font->script.' '.$font->originLabel().' '.$font->kindLabel().' '.implode(' ', $font->driverLabels())) }}">
                <td>
                    @if ($font->supports('browsershot'))
                        <span class="font-sample" style="font-family: '{{ $font->family }}', serif">Aa Gg 123</span>
                    @else
                        <span class="muted" title="La familia no está instalada en el sistema: Chromium no puede mostrarla en esta tabla">—</span>
                    @endif
                </td>
                <td>
                    @if (in_array($font->script, [\App\Showcase\Fonts\FontFamily::LATIN, \App\Showcase\Fonts\FontFamily::MULTI], true))
                        <strong><a href="{{ route('pdf.demo.variant', ['tipografias', 'familias', $font->key]) }}"
                                   style="text-decoration: none">{{ $font->family }}</a></strong>
                    @else
                        <strong>{{ $font->family }}</strong>
                    @endif
                    <span class="chip" style="margin-left: 4px">{{ $font->kindLabel() }}</span>
                    @if ($font->note !== '')
                        <div class="small muted" style="margin-top: 2px">{{ $font->note }}</div>
                    @endif
                </td>
                <td><span class="chip">{{ $font->script }}</span></td>
                <td class="small muted">{{ $font->originLabel() }}</td>
                <td class="small mono">
                    @if ($font->fileName())
                        {{ $font->fileName() }}
                        @if ($font->kilobytes())
                            <div class="small muted">{{ $font->kilobytes() }}</div>
                        @endif
                    @else
                        —
                    @endif
                </td>
                <td>
                    <div class="engine-list">
                        @foreach ($drivers as $driver)
                            <span class="badge {{ $font->supports($driver) ? 'ok' : 'neutral' }}"
                                  @unless ($font->supports($driver)) title="Este motor no puede usar la familia" @endunless>
                                {{ $labels[$driver] }}
                            </span>
                        @endforeach
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<p class="note">
    Las {{ $totals['otros'] }} familias de otros alfabetos salen del catálogo de mPDF
    ({{ $bundled['mpdf'] }} archivos TTF, de los que se registran las familias que mPDF conoce) y de fontconfig.
    dompdf trae {{ $bundled['dompdf'] }} archivos para tres familias. Puedes verlas en vivo en la demostración
    <a href="{{ route('pdf.demo', ['tipografias', 'alfabetos']) }}">Otros alfabetos</a>.
</p>

<script>
    (() => {
        const input = document.getElementById('font-filter');
        const table = document.getElementById('font-catalog');
        const count = document.getElementById('font-count');

        if (!input || !table || !count) {
            return;
        }

        const rows = Array.from(table.tBodies[0].rows);
        const total = rows.length;
        const normalize = (value) => value.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();

        input.addEventListener('input', () => {
            const query = normalize(input.value.trim());
            let shown = 0;

            rows.forEach((row) => {
                const matches = query === '' || normalize(row.dataset.search).includes(query);
                row.hidden = !matches;
                shown += matches ? 1 : 0;
            });

            count.textContent = query === '' ? `${total} familias` : `${shown} de ${total} familias`;
        });
    })();
</script>

<div class="section-head">
    <div>
        <h2>Cómo aplicarlo en la app</h2>
        <p>
            La aplicación expone <code>font</code> y <code>fontFile</code> en <code>DocumentOptions</code>;
            Vellum se encarga de inyectarlos en el HTML y de elegir el motor.
        </p>
    </div>
</div>

<div class="grid cols-3" style="margin-bottom: 14px">
    <div class="card">
        <span class="badge accent">Paso 1</span>
        <h3 style="margin-top: 8px">Elige la familia</h3>
        <p class="small muted" style="margin: 0">
            Usa una del catálogo de arriba que soporte el motor del documento. Si dudas, DejaVu Sans
            funciona en los tres.
        </p>
    </div>
    <div class="card">
        <span class="badge accent">Paso 2</span>
        <h3 style="margin-top: 8px">Fija el motor</h3>
        <p class="small muted" style="margin: 0">
            <code>dompdf</code> y <code>mPDF</code> solo ven su catálogo; Chromium ve las fuentes
            del sistema. El motor se elige en <code>config/pdf.php</code> o por documento.
        </p>
    </div>
    <div class="card">
        <span class="badge accent">Paso 3</span>
        <h3 style="margin-top: 8px">Incrusta si hace falta</h3>
        <p class="small muted" style="margin: 0">
            Con un TTF propio, pasa el archivo a <code>fontFile</code>. Solo dompdf lo aprovecha;
            en Chromium sirve el archivo por HTTP.
        </p>
    </div>
</div>

<div class="code-block">
    <div class="code-head">
        <span>PHP · App\Documents</span>
        <button type="button" class="copy" data-copy>Copiar</button>
    </div>
    <pre><code>use App\Documents\DocumentOptions;
use App\Documents\DocumentRenderer;

return $this->renderer->download('documents.users', $data, new DocumentOptions(
    driver: 'browsershot',          // Chromium: fuentes del sistema
    font: 'Nimbus Sans',
));

// Una fuente del catálogo de dompdf/mPDF:
new DocumentOptions(font: 'DejaVu Serif');

// Un TTF propio (dompdf lo incrusta con @font-face):
new DocumentOptions(
    font: 'Vellum Sans',
    fontFile: resource_path('fonts/VellumSans-Regular.ttf'),
);</code></pre>
</div>

<div class="table-wrap" style="margin-bottom: 18px">
    <table>
        <thead>
        <tr>
            <th style="width: 130px">Motor</th>
            <th>De dónde salen las fuentes</th>
            <th style="width: 190px">Incrustar un TTF</th>
            <th style="width: 150px">Ajuste</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="mono">dompdf</td>
            <td class="small">Su carpeta <code>lib/fonts</code>, las tres DejaVu y las fuentes del núcleo PDF.</td>
            <td class="small">Sí, con <code>font('Alias', $ttf)</code>.</td>
            <td class="small mono">default_font</td>
        </tr>
        <tr>
            <td class="mono">mpdf</td>
            <td class="small">Su carpeta <code>ttfonts</code> y la configuración de fuentes de mPDF.</td>
            <td class="small">No: <code>fontFile</code> se ignora y cae en otra familia.</td>
            <td class="small mono">default_font</td>
        </tr>
        <tr>
            <td class="mono">browsershot</td>
            <td class="small">Las fuentes instaladas en el sistema (fontconfig).</td>
            <td class="small">No por archivo: Browsershot bloquea <code>file://</code>.</td>
            <td class="small mono">font-family</td>
        </tr>
        </tbody>
    </table>
</div>

<div class="section-head">
    <div>
        <h2>Añadir más tipografías</h2>
        <p>
            Cualquier fuente nueva aparece en el catálogo al vaciar la caché
            (<code>php artisan cache:clear --store=file</code>). Hay tres vías según el motor.
        </p>
    </div>
</div>

<div class="grid cols-3" style="margin-bottom: 14px">
    <div class="card">
        <span class="badge ok">Chromium</span>
        <h3 style="margin-top: 8px">Paquetes del sistema</h3>
        <p class="small muted" style="margin: 0">
            Instala colecciones completas con <code>apt</code> y refresca fontconfig. Es la vía más rápida
            para tener decenas de familias en Chromium: TeX Gyre, Liberation, Noto, IBM Plex, Fira, Hack,
            JetBrains Mono…
        </p>
    </div>
    <div class="card">
        <span class="badge ok">dompdf</span>
        <h3 style="margin-top: 8px">Carpeta del proyecto</h3>
        <p class="small muted" style="margin: 0">
            Deja el TTF en <code>resources/fonts/</code>: aparece como origen «Proyecto» y se usa con
            <code>font('Alias', resource_path(...))</code>. dompdf no lee OTF/CFF ni fuentes variables.
        </p>
    </div>
    <div class="card">
        <span class="badge ok">mPDF</span>
        <h3 style="margin-top: 8px">Registro en mPDF</h3>
        <p class="small muted" style="margin: 0">
            Copia el TTF a <code>ttfonts</code> y añade la familia a <code>fontdata</code>
            (<code>Mpdf\Config\FontVariables</code>). Después se pide por su nombre, como las 40 que
            ya trae.
        </p>
    </div>
</div>

<div class="code-block">
    <div class="code-head">
        <span>Shell · ampliar el catálogo del sistema</span>
        <button type="button" class="copy" data-copy>Copiar</button>
    </div>
    <pre><code># Colecciones libres con muchos estilos (Debian/Ubuntu)
sudo apt install fonts-texgyre fonts-liberation fonts-noto-core \
    fonts-noto-cjk fonts-firacode fonts-jetbrains-mono fonts-hack fonts-ibm-plex

# Refrescar fontconfig: Chromium ya las ve
fc-cache -f

# Comprobar qué ha detectado
fc-list : family | sort -u</code></pre>
</div>

<div class="code-block">
    <div class="code-head">
        <span>Shell · fuentes propias para dompdf y Chromium</span>
        <button type="button" class="copy" data-copy>Copiar</button>
    </div>
    <pre><code># dompdf: TTF estático en la carpeta del proyecto (se detecta solo)
cp MiFuente-Regular.ttf resources/fonts/

# Chromium: instalar en el perfil del usuario
mkdir -p ~/.local/share/fonts
cp MiFuente-Regular.ttf ~/.local/share/fonts/
fc-cache -f

# OTF/CFF y variables: convertir a TTF estático antes de usarlas con dompdf
pip install fonttools
fonttools varLib.instancer MiFuente-Variable.ttf wght=400 -o MiFuente-Regular.ttf</code></pre>
</div>

<p class="note">
    Fuentes de referencia: <a href="https://fonts.google.com" rel="noreferrer">Google Fonts</a> (más de 1500
    familias con licencia abierta), <a href="https://www.gust.org.pl/projects/e-foundry/tex-gyre" rel="noreferrer">TeX Gyre</a>
    (excelente tipografía editorial), <a href="https://github.com/IBM/plex" rel="noreferrer">IBM Plex</a>,
    <a href="https://fontsource.org" rel="noreferrer">Fontsource</a> y las colecciones
    <a href="https://notofonts.github.io" rel="noreferrer">Noto</a> (cobertura de casi todos los alfabetos).
</p>

<div class="card">
    <h3>Compruébalo en la aplicación</h3>
    <p class="small muted">
        El listado de usuarios acepta <code>?font=</code> desde que <code>DocumentOptions</code> expone la
        tipografía. Cada enlace genera el PDF de verdad:
    </p>
    <ul style="margin: 0; padding-left: 18px">
        <li class="small" style="margin: 5px 0">
            <a href="{{ route('pdf.users', ['font' => 'Nimbus Sans', 'driver' => 'browsershot']) }}">Nimbus Sans en Chromium</a>
            — fuente del sistema, sin incrustar en la app.
        </li>
        <li class="small" style="margin: 5px 0">
            <a href="{{ route('pdf.users', ['font' => 'DejaVu Serif']) }}">DejaVu Serif en dompdf</a>
            — fuente del catálogo, incrustada en el PDF.
        </li>
        <li class="small" style="margin: 5px 0">
            <a href="{{ route('pdf.users', ['font' => 'Helvetica']) }}">Helvetica en dompdf</a>
            — fuente del núcleo, el archivo no la incrusta.
        </li>
        <li class="small" style="margin: 5px 0">
            <a href="{{ route('pdf.users', ['font' => 'FreeSerif', 'driver' => 'mpdf']) }}">FreeSerif en mPDF</a>
            — familia del catálogo de mPDF.
        </li>
        <li class="small" style="margin: 5px 0">
            <a href="{{ route('pdf.users', ['font' => 'XB Riyaz', 'driver' => 'mpdf']) }}">XB Riyaz en mPDF</a>
            — árabe con shaping; el texto de ejemplo es latino, así que se ve el fallback.
        </li>
    </ul>
</div>
