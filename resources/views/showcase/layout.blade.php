<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Panel de demostración de laravel-vellum: todas las categorías de la API de PDF, con su código y su resultado.">
    <title>@yield('title', 'Panel de demostración') · laravel-vellum</title>
    <style>
        :root {
            --bg: #f6f7f9;
            --surface: #ffffff;
            --surface-alt: #f9fafb;
            --ink: #101828;
            --muted: #667085;
            --line: #e4e7ec;
            --line-strong: #d0d5dd;
            --accent: #4f46e5;
            --accent-ink: #3730a3;
            --accent-soft: #eef2ff;
            --ok: #067647;
            --ok-soft: #ecfdf3;
            --ok-line: #abefc6;
            --warn: #b54708;
            --warn-soft: #fffaeb;
            --warn-line: #fedf89;
            --bad: #b42318;
            --bad-soft: #fef3f2;
            --bad-line: #fecdca;
            --sidebar: #0d0f14;
            --sidebar-2: #151822;
            --sidebar-line: #252a36;
            --sidebar-ink: #a7adbb;
            --radius: 14px;
            --radius-sm: 9px;
            --mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
            --shadow-sm: 0 1px 2px rgba(16, 24, 40, .06);
            --shadow-md: 0 16px 32px -18px rgba(16, 24, 40, .35);
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            font-size: 15px;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; }

        .skip {
            position: absolute;
            left: -9999px;
            top: 0;
            z-index: 20;
            padding: 10px 16px;
            background: var(--accent);
            color: #fff;
            border-radius: 0 0 8px 0;
            text-decoration: none;
        }
        .skip:focus { left: 0; }

        .shell { display: flex; min-height: 100vh; align-items: flex-start; }

        /* ── Barra lateral ───────────────────────────────────────────── */
        .sidebar {
            width: 300px;
            flex: 0 0 300px;
            display: flex;
            flex-direction: column;
            background: var(--sidebar);
            color: var(--sidebar-ink);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #2b3040 transparent;
        }
        .sidebar::-webkit-scrollbar { width: 9px; }
        .sidebar::-webkit-scrollbar-thumb { background: #2b3040; border-radius: 99px; border: 2px solid var(--sidebar); }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 20px 20px 18px;
            border-bottom: 1px solid var(--sidebar-line);
            text-decoration: none;
        }
        .brand-mark {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 10px;
            background: linear-gradient(140deg, #6366f1, #4338ca);
            color: #fff;
            box-shadow: 0 6px 16px -8px rgba(79, 70, 229, .9);
        }
        .brand-text { min-width: 0; }
        .brand-text strong {
            display: block;
            color: #fff;
            font-size: 15.5px;
            letter-spacing: -0.01em;
            white-space: nowrap;
        }
        .brand-text span {
            display: block;
            font-size: 11.5px;
            color: #7d8494;
            letter-spacing: .02em;
            white-space: nowrap;
        }
        .brand:hover .brand-mark { filter: brightness(1.08); }

        .nav-search { padding: 14px 14px 8px; }
        .nav-search input {
            width: 100%;
            padding: 8px 11px;
            border-radius: 8px;
            border: 1px solid var(--sidebar-line);
            background: #11141b;
            color: #e7e9ee;
            font: inherit;
            font-size: 13px;
            outline: none;
        }
        .nav-search input::placeholder { color: #6b7280; }
        .nav-search input:focus { border-color: #4c51a8; box-shadow: 0 0 0 3px rgba(79, 70, 229, .18); }

        .nav { padding: 4px 10px 18px; flex: 1; }

        .nav-section { margin-bottom: 4px; }
        .nav-section[hidden] { display: none; }

        .nav-label {
            display: block;
            padding: 12px 10px 5px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .11em;
            text-transform: uppercase;
            color: #5c6376;
        }

        .nav-item,
        .nav-title {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 8px;
            font-size: 13.5px;
            text-decoration: none;
            color: #b6bccb;
            transition: background .12s ease, color .12s ease;
        }
        .nav-item:hover,
        .nav-title:hover { background: var(--sidebar-2); color: #fff; }
        .nav-item.active,
        .nav-title.active {
            background: rgba(79, 70, 229, .16);
            color: #fff;
            box-shadow: inset 2px 0 0 var(--accent);
        }

        .nav-title { font-weight: 600; color: #d3d7e0; margin-top: 2px; }
        .nav-title .icon { color: #7c8499; }
        .nav-title.active .icon { color: #a5b4fc; }
        .nav-title.is-parent {
            color: #fff;
            background: rgba(79, 70, 229, .09);
            box-shadow: inset 2px 0 0 rgba(79, 70, 229, .5);
        }
        .nav-title.is-parent .icon { color: #a5b4fc; }

        .nav-count {
            margin-left: auto;
            font-family: var(--mono);
            font-size: 11px;
            color: #7b8294;
            background: #1a1e28;
            border: 1px solid var(--sidebar-line);
            border-radius: 999px;
            padding: 0 7px;
            line-height: 17px;
        }
        .nav-title.active .nav-count { color: #c7d2fe; border-color: #3b3f77; background: #1a1c33; }

        .nav-section ul { list-style: none; margin: 3px 0 8px; padding: 0 0 0 14px; }
        .nav-section li[hidden] { display: none; }
        .nav-link {
            display: block;
            padding: 5px 10px;
            border-left: 1px solid var(--sidebar-line);
            border-radius: 0 7px 7px 0;
            font-size: 13px;
            line-height: 1.45;
            text-decoration: none;
            color: #969dae;
            transition: color .12s ease, background .12s ease, border-color .12s ease;
        }
        .nav-link:hover { color: #fff; background: #151922; border-left-color: #4b5265; }
        .nav-link.active {
            color: #fff;
            background: rgba(79, 70, 229, .14);
            border-left-color: var(--accent);
            font-weight: 600;
        }

        .nav-empty { padding: 8px 12px; font-size: 12.5px; color: #6b7280; }

        .sidebar-foot {
            padding: 14px 20px 20px;
            border-top: 1px solid var(--sidebar-line);
            font-size: 11.5px;
            color: #6a7182;
            line-height: 1.7;
        }
        .sidebar-foot a { color: #9aa2b4; text-decoration: none; }
        .sidebar-foot a:hover { color: #fff; text-decoration: underline; }

        /* ── Contenido ───────────────────────────────────────────────── */
        .main { flex: 1; min-width: 0; padding: 0 38px 70px; }
        .main-inner { max-width: 1120px; margin: 0 auto; }

        .topbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 16px 0;
            margin-bottom: 26px;
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            z-index: 5;
            background: rgba(246, 247, 249, .88);
            backdrop-filter: blur(8px);
        }
        .topbar .crumbs { margin: 0; }
        .topbar-meta { display: flex; align-items: center; gap: 8px; }

        .crumbs {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 8px;
        }
        .crumbs a { color: var(--muted); text-decoration: none; }
        .crumbs a:hover { color: var(--ink); text-decoration: underline; }
        .crumbs .sep { color: #b8bfcc; }
        .crumbs .here { color: var(--ink); font-weight: 600; }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 3px 10px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--surface);
            font-size: 12px;
            color: var(--muted);
            white-space: nowrap;
        }
        .pill code { color: var(--ink); font-size: 12px; }

        h1 { font-size: 30px; letter-spacing: -0.025em; margin: 0 0 8px; line-height: 1.15; }
        h2 { font-size: 19px; letter-spacing: -0.015em; margin: 0 0 4px; }
        h3 { font-size: 15.5px; margin: 0 0 6px; letter-spacing: -0.01em; }
        p { margin: 0 0 12px; }
        .lead { color: var(--muted); font-size: 16px; max-width: 74ch; }

        .page-head { margin-bottom: 24px; }
        .page-head .lead { margin-top: 6px; }
        .page-head .meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }

        .section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 14px;
            margin: 34px 0 12px;
        }
        .section-head:first-of-type { margin-top: 0; }
        .section-head > div { min-width: 0; }
        .section-head p { margin: 0; color: var(--muted); font-size: 13.5px; max-width: 70ch; }
        .section-head .hint { font-size: 12.5px; color: var(--muted); white-space: nowrap; }

        /* ── Portada ─────────────────────────────────────────────────── */
        .hero {
            position: relative;
            overflow: hidden;
            border-radius: var(--radius);
            padding: 34px 36px;
            margin-bottom: 22px;
            color: #fff;
            background:
                radial-gradient(900px 380px at 88% -10%, rgba(129, 140, 248, .55), transparent 60%),
                radial-gradient(700px 320px at 6% 118%, rgba(45, 212, 191, .28), transparent 60%),
                linear-gradient(135deg, #1b1d2a 0%, #12141c 100%);
            box-shadow: var(--shadow-md);
        }
        .hero .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: var(--mono);
            font-size: 12px;
            letter-spacing: .02em;
            color: #c7d2fe;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .14);
            padding: 3px 11px;
            border-radius: 999px;
            margin-bottom: 16px;
        }
        .hero h1 { font-size: 38px; color: #fff; max-width: 22ch; margin-bottom: 12px; }
        .hero h1 .thin { color: #a5b4fc; }
        .hero p { color: #b9c0d0; font-size: 16px; max-width: 66ch; margin-bottom: 20px; }
        .hero p code { color: #e0e7ff; background: rgba(255, 255, 255, .1); border-color: rgba(255, 255, 255, .16); }
        .hero .actions { margin-bottom: 0; }
        .hero .btn { border-color: #fff; background: #fff; color: #1b1d2a; font-weight: 600; }
        .hero .btn:hover { opacity: 1; background: #eef2ff; }
        .hero .btn.ghost { background: rgba(255, 255, 255, .06); color: #e5e7f0; border-color: rgba(255, 255, 255, .22); }
        .hero .btn.ghost:hover { background: rgba(255, 255, 255, .14); }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 8px; }
        .stat {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 16px 18px;
            box-shadow: var(--shadow-sm);
        }
        .stat .k {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .stat .v { display: block; font-size: 30px; font-weight: 700; letter-spacing: -0.03em; line-height: 1.15; margin-top: 6px; }
        .stat .v small { font-size: 15px; font-weight: 600; color: var(--muted); letter-spacing: 0; }
        .stat .hint { display: block; font-size: 12.5px; color: var(--muted); margin-top: 2px; }

        /* ── Tarjetas ────────────────────────────────────────────────── */
        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 20px 22px;
            box-shadow: var(--shadow-sm);
        }
        .card > :last-child { margin-bottom: 0; }
        .card h3 a { text-decoration: none; }
        .card h3 a:hover { color: var(--accent-ink); }

        .grid { display: grid; gap: 14px; }
        .grid.cols-2 { grid-template-columns: repeat(auto-fit, minmax(330px, 1fr)); }
        .grid.cols-3 { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        .grid.cols-4 { grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }

        .engine { display: flex; flex-direction: column; gap: 8px; }
        .engine .top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .engine .name { font-family: var(--mono); font-size: 13.5px; font-weight: 600; }
        .engine .metrics { display: flex; flex-wrap: wrap; gap: 10px; font-size: 12.5px; color: var(--muted); }
        .engine .metrics strong { color: var(--ink); font-weight: 600; }

        .cat-card {
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 20px 22px;
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            transition: border-color .14s ease, box-shadow .14s ease, transform .14s ease;
        }
        .cat-card:hover {
            border-color: #c7cdf7;
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .cat-card .cat-top { display: flex; align-items: flex-start; gap: 12px; }
        .cat-icon {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            border-radius: 10px;
            background: var(--accent-soft);
            color: var(--accent-ink);
        }
        .cat-card h3 { margin: 0; font-size: 16px; }
        .cat-card p { margin: 0; color: var(--muted); font-size: 13.5px; }
        .cat-card .cat-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid var(--line);
            font-size: 12.5px;
            color: var(--muted);
        }
        .cat-card .arrow { color: var(--accent); font-weight: 600; transition: transform .14s ease; }
        .cat-card:hover .arrow { transform: translateX(3px); }

        .demo-card { display: flex; flex-direction: column; gap: 10px; }
        .demo-card .demo-top { display: flex; align-items: baseline; gap: 10px; }
        .demo-num {
            font-family: var(--mono);
            font-size: 12px;
            color: var(--accent-ink);
            background: var(--accent-soft);
            border-radius: 6px;
            padding: 1px 7px;
            flex: 0 0 auto;
        }
        .demo-card .demo-top h3 { flex: 1; margin: 0; }
        .demo-card .demo-top h3 a { text-decoration: none; }
        .demo-card .demo-top h3 a:hover { color: var(--accent-ink); }
        .demo-card .demo-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--line);
            margin-top: auto;
        }

        /* ── Etiquetas de estado ─────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 9px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 600;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .badge.ok { background: var(--ok-soft); color: var(--ok); border-color: var(--ok-line); }
        .badge.missing { background: var(--warn-soft); color: var(--warn); border-color: var(--warn-line); }
        .badge.error { background: var(--bad-soft); color: var(--bad); border-color: var(--bad-line); }
        .badge.neutral { background: var(--surface-alt); color: var(--muted); border-color: var(--line); }
        .badge.accent { background: var(--accent-soft); color: var(--accent-ink); border-color: #c7d2fe; }

        .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; flex: 0 0 auto; }
        .dot.ok { color: var(--ok); }
        .dot.missing { color: var(--warn); }
        .dot.error { color: var(--bad); }

        /* ── Tablas ──────────────────────────────────────────────────── */
        .table-wrap {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            overflow-x: auto;
            box-shadow: var(--shadow-sm);
        }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 10px 16px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--muted);
            font-weight: 700;
            background: var(--surface-alt);
        }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #fcfcfd; }
        td.mono, .mono { font-family: var(--mono); font-size: 12.5px; }

        .chip {
            display: inline-block;
            font-size: 11.5px;
            padding: 1px 8px;
            border-radius: 999px;
            background: var(--surface-alt);
            border: 1px solid var(--line);
            color: var(--muted);
            white-space: nowrap;
        }

        /* ── Catálogo de fuentes ─────────────────────────────────────── */
        .font-table td { vertical-align: middle; }
        .font-table .badge.neutral { opacity: .45; }
        .font-sample {
            display: inline-block;
            font-size: 19px;
            line-height: 1.15;
            white-space: nowrap;
            letter-spacing: -0.01em;
        }
        .engine-list { display: flex; flex-wrap: wrap; gap: 4px; }
        .font-filter { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .font-filter input {
            flex: 1;
            max-width: 420px;
            padding: 9px 12px;
            border: 1px solid var(--line-strong);
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--ink);
            font: inherit;
            font-size: 13.5px;
        }
        .font-filter input:focus { outline: 2px solid var(--accent-soft); border-color: var(--accent); }
        .font-filter .count { font-size: 12.5px; color: var(--muted); }
        .font-table-wrap { max-height: 460px; overflow: auto; }
        .font-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--surface);
            box-shadow: inset 0 -1px 0 var(--line);
        }

        /* ── Código ──────────────────────────────────────────────────── */
        .code-block { border-radius: var(--radius); overflow: hidden; border: 1px solid #1e222c; margin-bottom: 16px; }
        .code-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 14px;
            background: #171a22;
            border-bottom: 1px solid #232733;
            color: #8d94a3;
            font-size: 12px;
            letter-spacing: .02em;
        }
        .code-head .lang { font-family: var(--mono); color: #6b7280; }
        .copy {
            font: inherit;
            font-size: 12px;
            color: #c7cbd6;
            background: #222734;
            border: 1px solid #2e3444;
            border-radius: 6px;
            padding: 3px 10px;
            cursor: pointer;
        }
        .copy:hover { background: #2a3040; color: #fff; }

        pre {
            background: #0f1115;
            color: #e6e8ec;
            padding: 16px 18px;
            margin: 0;
            overflow-x: auto;
            font-family: var(--mono);
            font-size: 12.5px;
            line-height: 1.65;
        }
        code { font-family: var(--mono); font-size: .88em; }
        p code, li code, td code { background: var(--surface-alt); border: 1px solid var(--line); border-radius: 4px; padding: 1px 5px; overflow-wrap: anywhere; }

        /* ── Métricas ────────────────────────────────────────────────── */
        .metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(185px, 1fr)); gap: 10px; }
        .metric { background: var(--surface-alt); border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 10px 13px; }
        .metric .k { display: block; font-size: 10.5px; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); font-weight: 600; }
        .metric .v { display: block; font-size: 14px; font-weight: 600; margin-top: 3px; word-break: break-word; }

        /* ── Variantes ───────────────────────────────────────────────── */
        .variants { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 0; }
        .variants a {
            border: 1px solid var(--line);
            background: var(--surface);
            border-radius: 999px;
            padding: 4px 13px;
            font-size: 12.5px;
            text-decoration: none;
            color: var(--ink);
            transition: border-color .12s ease, background .12s ease;
        }
        .variants a:hover { border-color: var(--line-strong); background: var(--surface-alt); }
        .variants a.active { background: var(--accent); border-color: var(--accent); color: #fff; font-weight: 600; }

        .variant-tabs { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 18px; }

        /* ── Vista previa del PDF ────────────────────────────────────── */
        .preview {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            overflow: hidden;
            background: var(--surface);
            margin-bottom: 16px;
            box-shadow: var(--shadow-sm);
        }
        .preview header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 9px 15px;
            background: var(--surface-alt);
            border-bottom: 1px solid var(--line);
            font-size: 12.5px;
            color: var(--muted);
        }
        .preview header .name { font-weight: 600; color: var(--ink); }
        .preview iframe { display: block; width: 100%; height: 660px; border: 0; background: #525659; }

        /* ── Botones ─────────────────────────────────────────────────── */
        .actions { display: flex; flex-wrap: wrap; gap: 9px; margin-bottom: 18px; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid var(--ink);
            background: var(--ink);
            color: #fff;
            border-radius: 8px;
            padding: 7px 15px;
            font-size: 13.5px;
            text-decoration: none;
            transition: opacity .12s ease, background .12s ease;
        }
        .btn:hover { opacity: .88; }
        .btn.ghost { background: var(--surface); color: var(--ink); border-color: var(--line-strong); }
        .btn.ghost:hover { border-color: var(--muted); opacity: 1; }
        .btn.accent { background: var(--accent); border-color: var(--accent); }

        .note {
            border-left: 3px solid var(--line-strong);
            padding: 2px 0 2px 13px;
            color: var(--muted);
            font-size: 13.5px;
            margin: 0 0 8px;
        }

        .alert { border-radius: var(--radius); padding: 14px 16px; margin-bottom: 16px; border: 1px solid; }
        .alert strong { display: block; margin-bottom: 4px; }
        .alert.missing { background: var(--warn-soft); border-color: var(--warn-line); color: #7a2e0e; }
        .alert.error { background: var(--bad-soft); border-color: var(--bad-line); color: #7a271a; }

        /* ── Desplegables ────────────────────────────────────────────── */
        details.panel {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        details.panel > summary {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 13px 18px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            list-style: none;
            user-select: none;
        }
        details.panel > summary::-webkit-details-marker { display: none; }
        details.panel > summary::before {
            content: "";
            width: 7px;
            height: 7px;
            border-right: 2px solid var(--muted);
            border-bottom: 2px solid var(--muted);
            transform: rotate(-45deg);
            transition: transform .15s ease;
            flex: 0 0 auto;
        }
        details.panel[open] > summary::before { transform: rotate(45deg); }
        details.panel > summary:hover { background: var(--surface-alt); }
        details.panel > summary .chip { margin-left: auto; }
        details.panel > .panel-body { border-top: 1px solid var(--line); }
        details.panel table { border-radius: 0; }

        .muted { color: var(--muted); }
        .small { font-size: 13px; }
        .mono-block { font-family: var(--mono); font-size: 12.5px; }
        .right { text-align: right; }

        .site-foot {
            margin-top: 44px;
            padding-top: 18px;
            border-top: 1px solid var(--line);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 12.5px;
            color: var(--muted);
        }
        .site-foot .mono { color: var(--ink); font-weight: 600; }

        @media (max-width: 980px) {
            .shell { display: block; }
            .sidebar { width: 100%; height: auto; position: static; }
            .nav { max-height: 320px; overflow-y: auto; }
            .main { padding: 0 18px 56px; }
            .hero { padding: 26px 22px; }
            .hero h1 { font-size: 30px; }
            .topbar { position: static; }
            .section-head .hint { white-space: normal; }
            .font-table { min-width: 700px; }
        }
    </style>
</head>
<body>
<a class="skip" href="#contenido">Saltar al contenido</a>

<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('showcase.home') }}" aria-label="laravel-vellum, ir al inicio">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
                    <path d="M14 3v5h5"/>
                    <path d="M9 13h6M9 17h4"/>
                </svg>
            </span>
            <span class="brand-text">
                <strong>laravel-vellum</strong>
                <span>Panel de demostración</span>
            </span>
        </a>

        <div class="nav-search">
            <input type="search" id="nav-filter" placeholder="Filtrar demostraciones…" aria-label="Filtrar demostraciones" autocomplete="off">
        </div>

        <nav class="nav" aria-label="Navegación del panel">
            <div class="nav-section" data-nav-category data-nav-static>
                <span class="nav-label">General</span>
                <a @class(['nav-item', 'active' => request()->routeIs('showcase.home')])
                   href="{{ route('showcase.home') }}"
                   @if (request()->routeIs('showcase.home')) aria-current="page" @endif>
                    Inicio
                </a>
                <a @class(['nav-item', 'active' => request()->routeIs('pdf.index')])
                   href="{{ route('pdf.index') }}"
                   @if (request()->routeIs('pdf.index')) aria-current="page" @endif>
                    Índice del panel
                </a>
            </div>

            <div class="nav-section" data-nav-static>
                <span class="nav-label">Documentos</span>
                <a class="nav-item" href="{{ route('documents.users') }}">Ejemplo de la aplicación</a>
                <a class="nav-item" href="{{ route('pdf.inspect') }}">Métricas en JSON</a>
            </div>

            <span class="nav-label">Categorías</span>

            @foreach ($categories ?? [] as $navCategory)
                @php
                    $isActiveCategory = request()->route('category') === $navCategory->slug;
                    $isActiveDemo = $isActiveCategory ? request()->route('demo') : null;
                    $isOnCategoryPage = $isActiveCategory && $isActiveDemo === null;
                @endphp

                <div class="nav-section" data-nav-category>
                    <a @class(['nav-title', 'active' => $isOnCategoryPage, 'is-parent' => $isActiveDemo !== null])
                       href="{{ route('pdf.category', $navCategory->slug) }}"
                       @if ($isOnCategoryPage) aria-current="page" @endif>
                        <span class="icon" aria-hidden="true">
                            @include('showcase.partials.category-icon', ['slug' => $navCategory->slug])
                        </span>
                        {{ $navCategory->title }}
                        <span class="nav-count" title="{{ count($navCategory->demos) }} demostraciones">{{ count($navCategory->demos) }}</span>
                    </a>
                    <ul>
                        @foreach ($navCategory->demos as $navDemo)
                            <li>
                                <a @class(['nav-link', 'active' => $isActiveDemo === $navDemo->slug])
                                   data-nav-item
                                   href="{{ route('pdf.demo', [$navCategory->slug, $navDemo->slug]) }}"
                                   @if ($isActiveDemo === $navDemo->slug) aria-current="page" @endif>
                                    {{ $navDemo->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <p class="nav-empty" id="nav-empty" hidden>Ninguna demostración coincide.</p>
        </nav>

        <div class="sidebar-foot">
            <a href="{{ route('showcase.home') }}">laravel-vellum</a> · Laravel {{ app()->version() }}<br>
            PHP {{ PHP_VERSION }}
        </div>
    </aside>

    <main class="main" id="contenido">
        <div class="main-inner">
            <div class="topbar">
                <nav class="crumbs" aria-label="Migas de pan">
                    @yield('crumbs', '<span class="here">Inicio</span>')
                </nav>
                <div class="topbar-meta">
                    <span class="pill">
                        <span class="dot ok"></span>
                        motor <code>{{ config('pdf.default_driver') }}</code>
                    </span>
                </div>
            </div>

            @yield('content')

            <footer class="site-foot">
                <span><span class="mono">laravel-vellum</span> · panel de demostración de la API de PDF</span>
                <span>{{ count($categories ?? []) }} categorías · Laravel {{ app()->version() }}</span>
            </footer>
        </div>
    </main>
</div>

<script>
    (() => {
        const input = document.getElementById('nav-filter');
        const empty = document.getElementById('nav-empty');
        const staticGroups = Array.from(document.querySelectorAll('[data-nav-static]'));
        const groups = Array.from(document.querySelectorAll('[data-nav-category]')).filter((group) => group.querySelector('[data-nav-item]'));

        if (input) {
            input.addEventListener('input', () => {
                const query = input.value.trim().toLowerCase();
                let total = 0;

                staticGroups.forEach((group) => {
                    group.hidden = query !== '';
                });

                groups.forEach((group) => {
                    const title = (group.querySelector('.nav-title')?.textContent ?? '').toLowerCase();
                    const matchesTitle = query !== '' && title.includes(query);
                    let visible = 0;

                    group.querySelectorAll('[data-nav-item]').forEach((item) => {
                        const match = query === '' || matchesTitle || item.textContent.trim().toLowerCase().includes(query);
                        item.parentElement.hidden = !match;
                        if (match) {
                            visible++;
                        }
                    });

                    group.hidden = visible === 0;
                    total += visible;
                });

                if (empty) {
                    empty.hidden = total !== 0;
                }
            });
        }

        const sidebar = document.querySelector('.sidebar');
        const active = sidebar?.querySelector('.nav-link.active, .nav-title.active, .nav-title.is-parent');

        if (sidebar && active && sidebar.scrollHeight > sidebar.clientHeight) {
            sidebar.scrollTop = Math.max(0, active.offsetTop - sidebar.clientHeight / 2);
        }

        document.querySelectorAll('.code-block').forEach((block) => {
            const button = block.querySelector('[data-copy]');
            const code = block.querySelector('pre');

            if (!button || !code || !navigator.clipboard) {
                return;
            }

            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(code.innerText);
                    button.textContent = 'Copiado';
                    setTimeout(() => { button.textContent = 'Copiar'; }, 1500);
                } catch (error) {
                    button.textContent = 'No se pudo copiar';
                }
            });
        });
    })();
</script>
</body>
</html>
