<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aufsteller – {{ $title }}</title>
    <style>
        @page { size: A5 portrait; margin: 0; }

        :root {
            --ink: #16161d;
            --muted: #5b5b6b;
            --faint: #9a9aab;
            --accent: #4f46e5;
            --accent-soft: #eef0ff;
            --line: #e7e7ee;
        }

        * { box-sizing: border-box; }

        html, body { margin: 0; padding: 0; }

        body {
            background: #ececf1;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--ink);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Bildschirm: Werkzeugleiste + Karte mittig */
        .toolbar {
            position: sticky; top: 0; z-index: 10;
            display: flex; align-items: center; justify-content: center; gap: 16px; flex-wrap: wrap;
            padding: 12px 16px; background: #fff; border-bottom: 1px solid var(--line);
            font-size: 13px; color: var(--muted);
        }
        .toolbar button {
            display: inline-flex; align-items: center; gap: 8px;
            height: 36px; padding: 0 16px; border: 0; border-radius: 8px;
            background: var(--ink); color: #fff; font: inherit; font-weight: 600; cursor: pointer;
        }
        .toolbar button:hover { opacity: .9; }
        .stage { display: flex; justify-content: center; padding: 32px 16px 64px; }

        /* Die Karte: exakt A5 */
        .card {
            position: relative;
            width: 148mm; height: 210mm; overflow: hidden;
            background: #fff;
            box-shadow: 0 20px 50px rgba(20, 20, 40, .18);
            display: flex; flex-direction: column; align-items: center;
            padding: 16mm 14mm 12mm;
            text-align: center;
        }

        /* dezente Akzentfläche oben */
        .card::before {
            content: ""; position: absolute; inset: 0 0 auto 0; height: 62mm;
            background: linear-gradient(180deg, var(--accent-soft) 0%, rgba(238, 240, 255, 0) 100%);
            z-index: 0;
        }
        .card > * { position: relative; z-index: 1; }

        .logo { height: 13mm; max-width: 55mm; object-fit: contain; }
        .logo-fallback {
            font-size: 11pt; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted);
        }

        .eyebrow {
            margin-top: 9mm;
            font-size: 9pt; font-weight: 600; letter-spacing: .16em; text-transform: uppercase;
            color: var(--accent);
        }

        h1 {
            margin: 3mm 0 0;
            font-size: 22pt; line-height: 1.15; font-weight: 800; letter-spacing: -.01em;
            max-width: 118mm;
        }

        .description {
            margin: 4mm 0 0;
            font-size: 11pt; line-height: 1.45; color: var(--muted);
            max-width: 112mm;
            display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden;
        }

        .qr-wrap {
            margin-top: auto;
            padding: 5mm; border-radius: 6mm;
            border: 1px solid var(--line); background: #fff;
            box-shadow: 0 6px 24px rgba(20, 20, 40, .08);
        }
        .qr-wrap svg { display: block; width: 62mm; height: 62mm; }

        .scan {
            margin-top: 6mm;
            display: inline-flex; align-items: center; gap: 2.5mm;
            font-size: 12pt; font-weight: 700;
        }
        .scan svg { width: 5.5mm; height: 5.5mm; color: var(--accent); }

        .url { margin-top: 1.5mm; font-size: 8pt; color: var(--faint); word-break: break-all; max-width: 110mm; }

        .footer {
            margin-top: 8mm; padding-top: 4mm; width: 100%;
            border-top: 1px solid var(--line);
            font-size: 8pt; color: var(--faint);
        }

        .notice {
            max-width: 148mm; margin: 0 auto 16px; padding: 10px 14px; border-radius: 8px;
            background: #fff4e6; color: #8a4b0f; font-size: 13px; text-align: center;
        }

        @media print {
            body { background: #fff; }
            .toolbar, .notice { display: none; }
            .stage { padding: 0; }
            .card { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
            Drucken
        </button>
        <span>Im Druckdialog: Papierformat <strong>A5</strong>, Ränder <strong>keine</strong>, Hintergrundgrafiken <strong>an</strong>.</span>
    </div>

    <div class="stage" style="flex-direction: column; align-items: center;">
        @if($status !== 'published')
            <div class="notice">Hinweis: Die Erhebung ist noch nicht veröffentlicht. Der QR-Code funktioniert erst, wenn sie live ist.</div>
        @endif

        <div class="card">
            @if($logoUrl)
                <img class="logo" src="{{ $logoUrl }}" alt="{{ $appName }}">
            @else
                <div class="logo-fallback">{{ $appName }}</div>
            @endif

            <div class="eyebrow">Ihre Meinung ist uns wichtig</div>
            <h1>{{ $title }}</h1>
            @if($description)
                <p class="description">{{ $description }}</p>
            @endif

            <div class="qr-wrap">{!! preg_replace('/^<\?xml[^>]*>\s*/', '', $qrSvg) !!}</div>

            <div class="scan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/></svg>
                QR-Code mit der Handykamera scannen
            </div>
            <div class="url">{{ $displayUrl }}</div>

            <div class="footer">Dauert nur wenige Minuten · Ihre Antworten werden vertraulich behandelt</div>
        </div>
    </div>
</body>
</html>
