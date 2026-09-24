<?php

namespace Platform\Hatch\Support;

/**
 * Logo/Name der Anwendung für öffentliche Seiten (Umfrage, Aufsteller).
 * Nimmt das erste vorhandene Bild aus public/ – jede Plattform-Instanz
 * bringt ihr eigenes Logo mit.
 */
class PublicBranding
{
    private const LOGO_CANDIDATES = ['logo.png', 'logo.svg', 'logo_square.png', 'favicon/favicon.svg', 'favicon.ico'];

    public static function logoUrl(): ?string
    {
        foreach (self::LOGO_CANDIDATES as $file) {
            if (is_file(public_path($file))) {
                return asset($file);
            }
        }

        return null;
    }

    public static function appName(): string
    {
        return (string) config('app.name');
    }
}
