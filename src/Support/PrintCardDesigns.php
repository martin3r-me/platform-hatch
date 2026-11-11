<?php

namespace Platform\Hatch\Support;

use Platform\Hatch\Models\HatchProjectIntake;

/**
 * Designs des A5-Aufstellers. Pro Erhebung wählbar (intake_settings.print_design);
 * ohne Auswahl gilt DEFAULT.
 */
class PrintCardDesigns
{
    public const DEFAULT = 'beige-flaeche';

    public const DESIGNS = [
        'beige-flaeche' => 'Beige Fläche',
        'beige-rahmen' => 'Beiger Rahmen',
        'weiss' => 'Weiß',
    ];

    public static function isValid(?string $design): bool
    {
        return is_string($design) && array_key_exists($design, self::DESIGNS);
    }

    public static function for(HatchProjectIntake $intake): string
    {
        $design = $intake->intake_settings['print_design'] ?? null;

        return self::isValid($design) ? $design : self::DEFAULT;
    }

    /**
     * Größenstufe für Titel/Beschreibung, damit lange Texte auf A5 nicht über den Rand laufen
     * bzw. den QR-Code verdrängen.
     */
    public static function titleSize(string $title): string
    {
        $length = mb_strlen($title);

        return match (true) {
            $length <= 17 => 'xl',   // eine Zeile, maximal groß („Catering-Feedback“)
            $length <= 34 => 'l',    // bis zu zwei Zeilen
            default => 'm',
        };
    }

    public static function descriptionSize(?string $description): string
    {
        return mb_strlen((string) $description) <= 90 ? 'l' : 'm';
    }
}
