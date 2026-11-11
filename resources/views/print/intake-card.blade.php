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
            --accent: #c81f3e;   /* Rot aus dem Logo */
            --beige: #efe9dd;    /* Broich-Beige */
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
        .designs { display: inline-flex; padding: 3px; gap: 3px; border-radius: 9px; background: #f1f1f5; }
        .designs a {
            padding: 6px 12px; border-radius: 7px; color: var(--muted); text-decoration: none; font-weight: 500;
        }
        .designs a[aria-current="true"] { background: #fff; color: var(--ink); font-weight: 600; box-shadow: 0 1px 3px rgba(20, 20, 40, .12); }
        .stage { display: flex; flex-direction: column; align-items: center; padding: 32px 16px 64px; }

        /* Die Karte: exakt A5 */
        .card {
            position: relative;
            width: 148mm; height: 210mm; overflow: hidden;
            background: #fff;
            box-shadow: 0 20px 50px rgba(20, 20, 40, .18);
            display: flex; flex-direction: column; align-items: center;
            padding: 14mm 14mm 12mm;
            text-align: center;
        }
        .card > * { position: relative; z-index: 1; }

        .logo { height: 14mm; max-width: 55mm; object-fit: contain; }
        .logo-fallback {
            font-size: 11pt; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted);
        }

        .eyebrow {
            margin-top: 10mm;
            font-size: 13pt; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
            color: var(--accent);
        }

        h1 {
            margin: 2.5mm 0 0;
            font-weight: 800; letter-spacing: -.025em;
            max-width: 124mm; text-wrap: balance;
        }
        h1.size-xl { font-size: 37pt; line-height: 1.05; }
        h1.size-l  { font-size: 29pt; line-height: 1.1; }
        h1.size-m  { font-size: 23pt; line-height: 1.15; }

        .description {
            margin: 4mm 0 0;
            font-weight: 500; color: var(--ink);
            max-width: 118mm; text-wrap: balance;
            display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
        }
        .description.size-l { font-size: 17pt; line-height: 1.3; }
        .description.size-m { font-size: 13pt; line-height: 1.35; }

        .head { display: flex; flex-direction: column; align-items: center; padding-bottom: 6mm; }
        .bottom { margin-top: auto; width: 100%; display: flex; flex-direction: column; align-items: center; }

        .qr-wrap { padding: 4.5mm; border-radius: 6mm; background: #fff; }
        .qr-wrap svg { display: block; width: 62mm; height: 62mm; }
        .long-text .qr-wrap svg { width: 52mm; height: 52mm; }

        .scan {
            margin-top: 7mm;
            display: inline-flex; align-items: center; gap: 2.5mm;
            font-size: 13pt; font-weight: 700;
        }
        .scan svg { width: 5.5mm; height: 5.5mm; color: var(--accent); }

        .footer {
            margin-top: 7mm; padding-top: 4mm; width: 100%;
            border-top: 1px solid var(--line);
            font-size: 8pt; color: var(--faint);
        }

        /* Design „Beige Fläche“: oben weiß, QR-Code unten auf beiger Fläche */
        .design-beige-flaeche .bottom {
            /* wächst bis 122 mm Höhe, weicht aber langem Text darüber aus */
            flex: 1 1 auto; max-height: 122mm;
            width: auto; align-self: stretch; margin: auto -14mm -12mm;
            padding: 10mm 14mm 12mm; justify-content: flex-end;
            background: var(--beige); border-radius: 12mm 12mm 0 0;
        }
        .design-beige-flaeche .footer { margin-top: auto; }
        .design-beige-flaeche .scan { margin-bottom: 7mm; }
        .design-beige-flaeche .qr-wrap { box-shadow: 0 4px 18px rgba(80, 60, 20, .08); }
        .design-beige-flaeche .footer { padding-top: 4mm; border-top-color: #ddd4c2; color: #8f8677; }

        /* Design „Beiger Rahmen“: beiger Rand, innen weiße Fläche */
        .design-beige-rahmen { padding: 18mm 18mm 13mm; background: var(--beige); }
        .design-beige-rahmen::after {
            content: ""; position: absolute; inset: 9mm; background: #fff; border-radius: 7mm; z-index: 0;
        }
        .design-beige-rahmen .eyebrow { margin-top: 12mm; }
        .design-beige-rahmen h1 { max-width: 108mm; }
        .design-beige-rahmen h1.size-xl { font-size: 33pt; }
        .design-beige-rahmen h1.size-l { font-size: 26pt; }
        .design-beige-rahmen h1.size-m { font-size: 21pt; }
        .design-beige-rahmen .description { max-width: 106mm; }
        .design-beige-rahmen .qr-wrap { padding: 4mm; border: 1px solid #e7e0d2; }
        .design-beige-rahmen .qr-wrap svg { width: 58mm; height: 58mm; }
        .design-beige-rahmen .scan { margin-top: 5mm; }
        .design-beige-rahmen .footer { margin-top: 6mm; border-top-color: #ece6da; }

        /* Design „Weiß“: ohne Hintergrundfläche, QR-Code mit feinem Rahmen */
        .design-weiss .qr-wrap { border: 1px solid var(--line); box-shadow: 0 6px 24px rgba(20, 20, 40, .08); }

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
        <nav class="designs" aria-label="Design">
            @foreach($designs as $key => $label)
                <a href="?design={{ $key }}" aria-current="{{ $key === $design ? 'true' : 'false' }}">{{ $label }}{{ $key === $savedDesign ? ' ✓' : '' }}</a>
            @endforeach
        </nav>
        <span>Im Druckdialog: Papierformat <strong>A5</strong>, Ränder <strong>keine</strong>, Hintergrundgrafiken <strong>an</strong>.</span>
    </div>

    <div class="stage">
        @if($status !== 'published')
            <div class="notice">Hinweis: Die Erhebung ist noch nicht veröffentlicht. Der QR-Code funktioniert erst, wenn sie live ist.</div>
        @endif

        <div class="card design-{{ $design }} {{ $titleSize !== 'xl' || ($description && $descriptionSize !== 'l') ? 'long-text' : '' }}">
            <div class="head">
                @if($logoUrl)
                    <img class="logo" src="{{ $logoUrl }}" alt="{{ $appName }}">
                @else
                    <div class="logo-fallback">{{ $appName }}</div>
                @endif

                <div class="eyebrow">Ihre Meinung ist uns wichtig</div>
                <h1 class="size-{{ $titleSize }}">{{ $title }}</h1>
                @if($description)
                    <p class="description size-{{ $descriptionSize }}">{{ $description }}</p>
                @endif
            </div>

            <div class="bottom">
                <div class="qr-wrap">{!! preg_replace('/^<\?xml[^>]*>\s*/', '', $qrSvg) !!}</div>

                <div class="scan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/></svg>
                    QR-Code mit der Handykamera scannen
                </div>

                <div class="footer">Dauert nur wenige Minuten · Ihre Antworten werden vertraulich behandelt</div>
            </div>
        </div>
    </div>
</body>
</html>
