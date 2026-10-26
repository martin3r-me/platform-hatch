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

    /**
     * Link zu den Datenschutzhinweisen der Umfragen: config('hatch.privacy.url'),
     * sonst die Route "datenschutz" der jeweiligen App (z. B. office.broichcatering.com/datenschutz).
     */
    public static function privacyUrl(): ?string
    {
        $url = config('hatch.privacy.url');
        if (is_string($url) && $url !== '') {
            return $url;
        }

        return \Illuminate\Support\Facades\Route::has('datenschutz') ? route('datenschutz') : null;
    }

    public static function appName(): string
    {
        return (string) config('app.name');
    }
}
