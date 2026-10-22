# Prompt original de Laravel Vellum

Este es el encargo que dio origen a [`laravel-vellum`](../laravel-vellum) y al panel de esta aplicación. Se conserva tal cual, en su idioma original (inglés), como referencia del alcance funcional del proyecto.

- **Archivo de origen:** [`ne.txt`](../ne.txt)
- **Conservado en:** `atd/PROMPT.md`

---

```text
Build me a Laravel package that gives me a unified PDF generation layer so I can produce PDFs from any source and with any engine, without being locked into Blade or DOMPDF.

I want to load content from a Blade view, a plain HTML string, a local file, a Twig template, or a remote URL — and I want the source to be a first-class concept, not a hardcoded method. Give me a Source contract so I can plug in my own if I need to.

The rendering engine should be a driver, not a hard dependency. Ship first-party drivers for DOMPDF, Browsershot (for modern CSS and Tailwind), and mPDF, and let me register my own driver through a clean contract. I should be able to pick the driver globally in config, or per-document at runtime.

For every PDF, I want to stream it in the browser, download it, save it to disk, or get the raw bytes — and the output should be consistent no matter which driver produced it.

Give me a publishable config file with layered configuration: sensible global defaults, per-driver defaults, and per-document overrides. I should be able to change paper size, orientation, font, DPI, margins, temp folder, font cache path, and whether remote assets are allowed — but organized as typed config objects, not one giant array.

The public API should be a fluent, strictly-typed facade and a service resolvable from the container. Something like Pdf::from(BladeSource::make('invoice', $data))->driver('browsershot')->paper('a4', 'landscape')->download('invoice.pdf'). Everything chainable, everything immutable until the final render call.

Error handling must be explicit and fast. Define my own exceptions (PdfGenerationException, SourceNotFoundException, DriverNotSupportedException, RenderingTimeoutException) and never let a missing template or asset hang the process. Log the driver, the source type, the render time, and the output size on every run.

Add a Fake driver so I can test PDF generation in my app without rendering real bytes — and make sure the package's own test suite covers the main flows (source → driver → output) for every shipped driver.

Include a post-processing pipeline so I can transform the PDF after rendering: embed XMP metadata, add signatures, merge PDFs, or mark it PDF/A — and let me register my own processors. This must work regardless of which driver produced the file.

If a driver supports it, expose PDF/A-3b compliance and embedded files (like XML invoices for Zugferd/Factur-X) through the post-processing layer, so the feature isn't tied to a single engine.

It should work cleanly in Laravel 10/11/12 and PHP 8.2+, and be easy to register in Lumen. Handle the basics properly: UTF-8 output, page breaks, warnings surfaced as exceptions or logs (never silently swallowed), and deterministic behavior across runs.

Add PHPStan at max level, Laravel Pint, and a CI workflow. Keep the API simple and natural to use — the whole point is that generating a PDF should feel like one line of code, even though everything under the hood is swappable.
```
