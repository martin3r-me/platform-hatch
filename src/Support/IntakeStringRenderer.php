<?php

namespace Platform\Hatch\Support;

use Platform\Hatch\Models\HatchProjectIntake;

/**
 * Rendert Platzhalter in Intake-Strings (Name, Description) für den
 * Public-View. Welche Platzhalter es gibt, steht in IntakePlaceholders —
 * diese Klasse bleibt als schmale Fassade für bestehende Aufrufer.
 */
class IntakeStringRenderer
{
    public function __construct(private readonly IntakePlaceholders $placeholders)
    {
    }

    public function render(?string $template, ?HatchProjectIntake $intake): ?string
    {
        return $this->placeholders->render($template, $intake);
    }
}
