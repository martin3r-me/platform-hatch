<?php

namespace Platform\Hatch\Http\Controllers;

use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Support\IntakePlaceholders;
use Platform\Hatch\Support\QrCodeRenderer;

/**
 * Druckbare A5-Karte (Tischaufsteller) mit Titel, Beschreibung, QR-Code und Logo.
 * Eigenständige Seite ohne App-Layout, damit der Browser-Druck exakt A5 ergibt.
 */
class IntakePrintCardController
{
    public function __invoke(HatchProjectIntake $projectIntake)
    {
        $url = $projectIntake->getPublicUrl();
        abort_unless($url, 404, 'Für diese Erhebung gibt es noch keinen öffentlichen Link.');

        $placeholders = app(IntakePlaceholders::class);

        // Logo der Anwendung: erst das große Logo, sonst das Favicon.
        $logo = collect(['logo.png', 'logo.svg', 'logo_square.png', 'favicon/favicon.svg', 'favicon.ico'])
            ->first(fn (string $file) => is_file(public_path($file)));

        return view('hatch::print.intake-card', [
            'title' => $placeholders->render($projectIntake->name, $projectIntake),
            'description' => $placeholders->render($projectIntake->description, $projectIntake),
            'url' => $url,
            'qrSvg' => app(QrCodeRenderer::class)->svg($url),
            'logoUrl' => $logo ? asset($logo) : null,
            'appName' => config('app.name'),
            'status' => $projectIntake->status,
        ]);
    }
}
