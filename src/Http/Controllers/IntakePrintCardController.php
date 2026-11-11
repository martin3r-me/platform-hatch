<?php

namespace Platform\Hatch\Http\Controllers;

use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Support\IntakePlaceholders;
use Platform\Hatch\Support\PrintCardDesigns;
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
        $title = (string) $placeholders->render($projectIntake->name, $projectIntake);
        $description = $placeholders->render($projectIntake->description, $projectIntake);

        // ?design=… zeigt ein anderes Design zur Ansicht, ohne die Auswahl der Erhebung zu ändern
        $savedDesign = PrintCardDesigns::for($projectIntake);
        $design = PrintCardDesigns::isValid(request()->query('design')) ? request()->query('design') : $savedDesign;

        return view('hatch::print.intake-card', [
            'title' => $title,
            'description' => $description,
            'design' => $design,
            'savedDesign' => $savedDesign,
            'designs' => PrintCardDesigns::DESIGNS,
            'titleSize' => PrintCardDesigns::titleSize($title),
            'descriptionSize' => PrintCardDesigns::descriptionSize($description),
            'url' => $url,
            'qrSvg' => app(QrCodeRenderer::class)->svg($url),
            'logoUrl' => \Platform\Hatch\Support\PublicBranding::logoUrl(),
            'appName' => \Platform\Hatch\Support\PublicBranding::appName(),
            'status' => $projectIntake->status,
        ]);
    }
}
