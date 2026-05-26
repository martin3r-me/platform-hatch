<?php

namespace Platform\Hatch\Support;

use Platform\Hatch\Models\HatchProjectIntake;

/**
 * Minimaler Platzhalter-Renderer für Intake-Strings (Name, Description) im
 * Public-View. Bewusst klein gehalten — keine echte Template-Engine, nur
 * "{{key}}" → Wert. Unbekannte Platzhalter werden 1:1 stehengelassen, damit
 * Tippfehler sichtbar bleiben.
 *
 * Verfügbare Tokens:
 *   {{iso_week}}     → 18
 *   {{iso_week2}}    → 18 (zweistellig: 02, 18, 53)
 *   {{iso_year}}     → 2026
 *   {{iso_year2}}    → 26 (zweistellig)
 */
class IntakeStringRenderer
{
    public function __construct(private readonly IsoWeekResolver $resolver)
    {
    }

    public function render(?string $template, ?HatchProjectIntake $intake): ?string
    {
        if ($template === null || $template === '' || !str_contains($template, '{{')) {
            return $template;
        }

        $iso = $this->resolver->resolve($intake);
        $tokens = [
            '{{iso_week}}' => (string) $iso['iso_week'],
            '{{iso_week2}}' => str_pad((string) $iso['iso_week'], 2, '0', STR_PAD_LEFT),
            '{{iso_year}}' => (string) $iso['iso_year'],
            '{{iso_year2}}' => substr((string) $iso['iso_year'], -2),
        ];

        return strtr($template, $tokens);
    }
}
