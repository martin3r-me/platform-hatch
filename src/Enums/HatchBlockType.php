<?php

namespace Platform\Hatch\Enums;

enum HatchBlockType: string
{
    case TEXT = 'text';
    case LONG_TEXT = 'long_text';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case URL = 'url';
    case SELECT = 'select';
    case MULTI_SELECT = 'multi_select';
    case NUMBER = 'number';
    case SCALE = 'scale';
    case DATE = 'date';
    case BOOLEAN = 'boolean';
    case FILE = 'file';
    case RATING = 'rating';
    case LOCATION = 'location';
    case INFO = 'info';
    case CUSTOM = 'custom';
    case MATRIX = 'matrix';
    case RANKING = 'ranking';
    case NPS = 'nps';
    case DROPDOWN = 'dropdown';
    case DATETIME = 'datetime';
    case TIME = 'time';
    case SLIDER = 'slider';
    case IMAGE_CHOICE = 'image_choice';
    case CONSENT = 'consent';
    case SECTION = 'section';
    case HIDDEN = 'hidden';
    case ADDRESS = 'address';
    case COLOR = 'color';
    case LOOKUP = 'lookup';
    case SIGNATURE = 'signature';
    case DATE_RANGE = 'date_range';
    case CALCULATED = 'calculated';
    case REPEATER = 'repeater';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text-Eingabe',
            self::LONG_TEXT => 'Langer Text / Freitext',
            self::EMAIL => 'E-Mail Adresse',
            self::PHONE => 'Telefonnummer',
            self::URL => 'URL / Webadresse',
            self::SELECT => 'Auswahl (Single)',
            self::MULTI_SELECT => 'Auswahl (Multiple)',
            self::NUMBER => 'Zahl',
            self::SCALE => 'Skala (1-10, 1-5 etc.)',
            self::DATE => 'Datum',
            self::BOOLEAN => 'Ja/Nein',
            self::FILE => 'Datei-Upload',
            self::RATING => 'Bewertung',
            self::LOCATION => 'Standort',
            self::INFO => 'Info / Hinweis (ohne Eingabe)',
            self::CUSTOM => 'Benutzerdefiniert',
            self::MATRIX => 'Matrix / Likert-Raster',
            self::RANKING => 'Sortierung / Ranking',
            self::NPS => 'Net Promoter Score',
            self::DROPDOWN => 'Dropdown-Auswahl',
            self::DATETIME => 'Datum & Uhrzeit',
            self::TIME => 'Uhrzeit',
            self::SLIDER => 'Schieberegler',
            self::IMAGE_CHOICE => 'Bildauswahl',
            self::CONSENT => 'Einwilligung / DSGVO',
            self::SECTION => 'Abschnittstrenner',
            self::HIDDEN => 'Verstecktes Feld',
            self::ADDRESS => 'Strukturierte Adresse',
            self::COLOR => 'Farbauswahl',
            self::LOOKUP => 'Lookup-Auswahl',
            self::SIGNATURE => 'Digitale Unterschrift',
            self::DATE_RANGE => 'Datumsbereich',
            self::CALCULATED => 'Berechnetes Feld',
            self::REPEATER => 'Wiederholung / Repeater',
        };
    }

    /**
     * Alle Block-Typen als [value => label] für Select-Optionen.
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            []
        );
    }

    /**
     * Alle Block-Typ-Werte als Array (für Validierung).
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
