<?php

namespace Platform\Hatch\Support;

use Platform\Hatch\Enums\HatchBlockType;

/**
 * Verzeichnis der Feldtypen für Template-Blöcke: Gruppe, Icon, Standard-
 * Konfiguration, editierbare Einstellungen und Kurz-Zusammenfassung.
 *
 * Maßgeblich ist, was der Public-Renderer (public/partials/overview-block)
 * aus logic_config liest – die Einstellungen hier entsprechen genau diesen
 * Schlüsseln. Der Template-Editor baut sein Formular aus settings(), die
 * MCP-Beschreibung (HatchOverviewTool) aus describeConfig().
 *
 * Einstellungs-Arten ('kind'):
 *   text, textarea, number, toggle, select (mit 'choices'),
 *   options (Liste {label, value}), items (Matrix-Zeilen {label, group}),
 *   lookup (Auswahlliste des Teams)
 * Typen ohne passende Einstellungen (Repeater, Bildauswahl …) bearbeitet man
 * über das JSON-Feld im Editor oder per MCP.
 */
class BlockTypes
{
    public const GROUPS = [
        'text' => 'Text & Kontakt',
        'choice' => 'Auswahl',
        'rating' => 'Bewertung',
        'date' => 'Datum & Zeit',
        'content' => 'Inhalt',
        'special' => 'Spezial',
    ];

    /** Typen, deren Optionen beim Typwechsel erhalten bleiben. */
    public const OPTION_TYPES = ['select', 'multi_select', 'dropdown', 'ranking'];

    public static function definitions(): array
    {
        $options = ['key' => 'options', 'kind' => 'options', 'label' => 'Antwortmöglichkeiten'];
        $placeholder = ['key' => 'placeholder', 'kind' => 'text', 'label' => 'Platzhalter im Feld', 'help' => 'Grauer Hinweistext, solange nichts eingetragen ist'];
        // value bleibt leer: beim ersten Speichern wird er aus dem (dann umbenannten) Text gebildet
        $defaultOptions = [
            ['label' => 'Option 1', 'value' => ''],
            ['label' => 'Option 2', 'value' => ''],
        ];

        return [
            // Text & Kontakt
            'text' => ['group' => 'text', 'icon' => 'heroicon-o-minus', 'hint' => 'Kurze Antwort in einer Zeile',
                'defaults' => [], 'settings' => [$placeholder, ['key' => 'maxlength', 'kind' => 'number', 'label' => 'Max. Zeichen']]],
            'long_text' => ['group' => 'text', 'icon' => 'heroicon-o-bars-3-bottom-left', 'hint' => 'Freitext über mehrere Zeilen',
                'defaults' => [], 'settings' => [$placeholder, ['key' => 'rows', 'kind' => 'number', 'label' => 'Höhe (Zeilen)', 'placeholder' => '5'], ['key' => 'maxlength', 'kind' => 'number', 'label' => 'Max. Zeichen']]],
            'email' => ['group' => 'text', 'icon' => 'heroicon-o-at-symbol', 'hint' => 'E-Mail-Adresse mit Prüfung',
                'defaults' => [], 'settings' => [$placeholder]],
            'phone' => ['group' => 'text', 'icon' => 'heroicon-o-phone', 'hint' => 'Telefonnummer',
                'defaults' => [], 'settings' => [$placeholder]],
            'url' => ['group' => 'text', 'icon' => 'heroicon-o-link', 'hint' => 'Webadresse',
                'defaults' => [], 'settings' => [$placeholder]],
            'number' => ['group' => 'text', 'icon' => 'heroicon-o-hashtag', 'hint' => 'Zahl mit optionaler Einheit',
                'defaults' => [], 'settings' => [$placeholder, ['key' => 'min', 'kind' => 'number', 'label' => 'Minimum'], ['key' => 'max', 'kind' => 'number', 'label' => 'Maximum'], ['key' => 'step', 'kind' => 'number', 'label' => 'Schrittweite'], ['key' => 'unit', 'kind' => 'text', 'label' => 'Einheit', 'placeholder' => 'z.B. Personen']]],

            // Auswahl
            'select' => ['group' => 'choice', 'icon' => 'heroicon-o-check-circle', 'hint' => 'Eine Antwort aus einer Liste',
                'defaults' => ['options' => $defaultOptions], 'settings' => [$options]],
            'multi_select' => ['group' => 'choice', 'icon' => 'heroicon-o-queue-list', 'hint' => 'Mehrere Antworten ankreuzen',
                'defaults' => ['options' => $defaultOptions], 'settings' => [$options, ['key' => 'min_selections', 'kind' => 'number', 'label' => 'Mindestens'], ['key' => 'max_selections', 'kind' => 'number', 'label' => 'Höchstens']]],
            'dropdown' => ['group' => 'choice', 'icon' => 'heroicon-o-chevron-up-down', 'hint' => 'Auswahl als aufklappbare Liste',
                'defaults' => ['options' => $defaultOptions], 'settings' => [$options, $placeholder]],
            'boolean' => ['group' => 'choice', 'icon' => 'heroicon-o-hand-thumb-up', 'hint' => 'Ja oder Nein',
                'defaults' => [], 'settings' => [['key' => 'true_label', 'kind' => 'text', 'label' => 'Text für Ja', 'placeholder' => 'Ja'], ['key' => 'false_label', 'kind' => 'text', 'label' => 'Text für Nein', 'placeholder' => 'Nein']]],
            'ranking' => ['group' => 'choice', 'icon' => 'heroicon-o-bars-arrow-down', 'hint' => 'Optionen in Reihenfolge bringen',
                'defaults' => ['options' => $defaultOptions], 'settings' => [$options]],
            'lookup' => ['group' => 'choice', 'icon' => 'heroicon-o-list-bullet', 'hint' => 'Auswahl aus einer gepflegten Auswahlliste',
                'defaults' => [], 'settings' => [['key' => 'lookup_id', 'kind' => 'lookup', 'label' => 'Auswahlliste'], ['key' => 'multiple', 'kind' => 'toggle', 'label' => 'Mehrfachauswahl erlauben'], $placeholder]],
            'image_choice' => ['group' => 'choice', 'icon' => 'heroicon-o-photo', 'hint' => 'Auswahl über Bilder',
                'defaults' => ['columns' => 3], 'settings' => [['key' => 'columns', 'kind' => 'number', 'label' => 'Spalten']], 'advanced_only' => 'Bilder werden per JSON bzw. MCP hinterlegt (options mit file_id).'],

            // Bewertung
            'rating' => ['group' => 'rating', 'icon' => 'heroicon-o-star', 'hint' => 'Sterne vergeben',
                'defaults' => ['max' => 5], 'settings' => [['key' => 'max', 'kind' => 'number', 'label' => 'Anzahl Sterne', 'placeholder' => '5']]],
            'scale' => ['group' => 'rating', 'icon' => 'heroicon-o-adjustments-horizontal', 'hint' => 'Zahl auf einer Skala, z. B. 1–10',
                'defaults' => ['min' => 1, 'max' => 5], 'settings' => [['key' => 'min', 'kind' => 'number', 'label' => 'Von', 'placeholder' => '1'], ['key' => 'max', 'kind' => 'number', 'label' => 'Bis', 'placeholder' => '5'], ['key' => 'min_label', 'kind' => 'text', 'label' => 'Beschriftung links', 'placeholder' => 'z.B. gar nicht'], ['key' => 'max_label', 'kind' => 'text', 'label' => 'Beschriftung rechts', 'placeholder' => 'z.B. sehr']]],
            'nps' => ['group' => 'rating', 'icon' => 'heroicon-o-face-smile', 'hint' => 'Weiterempfehlung 0–10',
                'defaults' => [], 'settings' => []],
            'matrix' => ['group' => 'rating', 'icon' => 'heroicon-o-table-cells', 'hint' => 'Mehrere Aspekte auf derselben Skala bewerten',
                'defaults' => ['items' => [['label' => 'Aspekt 1', 'value' => ''], ['label' => 'Aspekt 2', 'value' => '']], 'scale_min' => 1, 'scale_max' => 5],
                'settings' => [
                    ['key' => 'items', 'kind' => 'items', 'label' => 'Aspekte (Zeilen)'],
                    ['key' => 'scale_min', 'kind' => 'number', 'label' => 'Skala von', 'placeholder' => '1'],
                    ['key' => 'scale_max', 'kind' => 'number', 'label' => 'Skala bis', 'placeholder' => '5'],
                    ['key' => 'scale_labels.min_label', 'kind' => 'text', 'label' => 'Beschriftung links', 'placeholder' => 'z.B. schlecht'],
                    ['key' => 'scale_labels.max_label', 'kind' => 'text', 'label' => 'Beschriftung rechts', 'placeholder' => 'z.B. sehr gut'],
                    ['key' => 'required_mode', 'kind' => 'select', 'label' => 'Pflicht gilt für', 'choices' => ['matrix' => 'alle Zeilen', 'per_row' => 'nur markierte Zeilen (je Zeile festlegen)']],
                ]],
            'slider' => ['group' => 'rating', 'icon' => 'heroicon-o-arrows-right-left', 'hint' => 'Wert per Schieberegler',
                'defaults' => ['min' => 0, 'max' => 100, 'step' => 1], 'settings' => [['key' => 'min', 'kind' => 'number', 'label' => 'Von', 'placeholder' => '0'], ['key' => 'max', 'kind' => 'number', 'label' => 'Bis', 'placeholder' => '100'], ['key' => 'step', 'kind' => 'number', 'label' => 'Schrittweite', 'placeholder' => '1'], ['key' => 'unit', 'kind' => 'text', 'label' => 'Einheit'], ['key' => 'show_value', 'kind' => 'toggle', 'label' => 'Wert anzeigen']]],

            // Datum & Zeit
            'date' => ['group' => 'date', 'icon' => 'heroicon-o-calendar', 'hint' => 'Ein Datum',
                'defaults' => [], 'settings' => [['key' => 'min', 'kind' => 'text', 'label' => 'Frühestens', 'placeholder' => 'JJJJ-MM-TT'], ['key' => 'max', 'kind' => 'text', 'label' => 'Spätestens', 'placeholder' => 'JJJJ-MM-TT']]],
            'time' => ['group' => 'date', 'icon' => 'heroicon-o-clock', 'hint' => 'Eine Uhrzeit',
                'defaults' => [], 'settings' => [['key' => 'min_time', 'kind' => 'text', 'label' => 'Frühestens', 'placeholder' => 'HH:MM'], ['key' => 'max_time', 'kind' => 'text', 'label' => 'Spätestens', 'placeholder' => 'HH:MM'], ['key' => 'step_minutes', 'kind' => 'number', 'label' => 'Raster (Minuten)', 'placeholder' => '15']]],
            'datetime' => ['group' => 'date', 'icon' => 'heroicon-o-calendar-days', 'hint' => 'Datum und Uhrzeit',
                'defaults' => [], 'settings' => [['key' => 'min_datetime', 'kind' => 'text', 'label' => 'Frühestens', 'placeholder' => 'JJJJ-MM-TTTHH:MM'], ['key' => 'max_datetime', 'kind' => 'text', 'label' => 'Spätestens', 'placeholder' => 'JJJJ-MM-TTTHH:MM']]],
            'date_range' => ['group' => 'date', 'icon' => 'heroicon-o-calendar-date-range', 'hint' => 'Zeitraum von – bis',
                'defaults' => [], 'settings' => []],

            // Inhalt
            'info' => ['group' => 'content', 'icon' => 'heroicon-o-information-circle', 'hint' => 'Hinweistext ohne Eingabe',
                'defaults' => [], 'settings' => [['key' => 'content', 'kind' => 'textarea', 'label' => 'Text']]],
            'section' => ['group' => 'content', 'icon' => 'heroicon-o-bookmark', 'hint' => 'Zwischenüberschrift',
                'defaults' => [], 'settings' => [['key' => 'title', 'kind' => 'text', 'label' => 'Überschrift'], ['key' => 'subtitle', 'kind' => 'text', 'label' => 'Unterzeile'], ['key' => 'content', 'kind' => 'textarea', 'label' => 'Text']]],
            'consent' => ['group' => 'content', 'icon' => 'heroicon-o-shield-check', 'hint' => 'Zustimmung, z. B. Datenschutz',
                'defaults' => [], 'settings' => [['key' => 'text', 'kind' => 'textarea', 'label' => 'Zustimmungstext'], ['key' => 'link_url', 'kind' => 'text', 'label' => 'Link (z. B. Datenschutz)', 'placeholder' => 'https://…'], ['key' => 'link_label', 'kind' => 'text', 'label' => 'Link-Text', 'placeholder' => 'Datenschutzerklärung']]],

            // Spezial
            'address' => ['group' => 'special', 'icon' => 'heroicon-o-home', 'hint' => 'Straße, PLZ, Ort',
                'defaults' => [], 'settings' => []],
            'location' => ['group' => 'special', 'icon' => 'heroicon-o-map-pin', 'hint' => 'Ort als Freitext',
                'defaults' => [], 'settings' => [$placeholder]],
            'file' => ['group' => 'special', 'icon' => 'heroicon-o-paper-clip', 'hint' => 'Datei hochladen',
                'defaults' => [], 'settings' => []],
            'signature' => ['group' => 'special', 'icon' => 'heroicon-o-pencil', 'hint' => 'Unterschrift zeichnen',
                'defaults' => [], 'settings' => []],
            'color' => ['group' => 'special', 'icon' => 'heroicon-o-swatch', 'hint' => 'Farbe wählen',
                'defaults' => [], 'settings' => []],
            'hidden' => ['group' => 'special', 'icon' => 'heroicon-o-eye-slash', 'hint' => 'Unsichtbarer Wert, z. B. aus dem Link',
                'defaults' => ['source' => 'static'], 'settings' => [['key' => 'source', 'kind' => 'select', 'label' => 'Wert kommt aus', 'choices' => ['static' => 'festem Wert', 'url_param' => 'einem Link-Parameter', 'referrer' => 'der Herkunftsseite']], ['key' => 'default_value', 'kind' => 'text', 'label' => 'Wert bzw. Name des Link-Parameters', 'help' => 'Bei „Link-Parameter“: z. B. tisch für …?tisch=12']]],
            'calculated' => ['group' => 'special', 'icon' => 'heroicon-o-calculator', 'hint' => 'Wird aus anderen Antworten berechnet',
                'defaults' => [], 'settings' => [], 'advanced_only' => 'Formel und Quellfelder per JSON bzw. MCP (formula, source_blocks, display_format).'],
            'repeater' => ['group' => 'special', 'icon' => 'heroicon-o-square-2-stack', 'hint' => 'Mehrere gleichartige Einträge',
                'defaults' => ['min_entries' => 0, 'max_entries' => 10], 'settings' => [['key' => 'min_entries', 'kind' => 'number', 'label' => 'Mindestens'], ['key' => 'max_entries', 'kind' => 'number', 'label' => 'Höchstens']], 'advanced_only' => 'Die Unterfelder (fields) werden per JSON bzw. MCP hinterlegt.'],
            'custom' => ['group' => 'special', 'icon' => 'heroicon-o-code-bracket', 'hint' => 'Freie Konfiguration',
                'defaults' => [], 'settings' => [], 'advanced_only' => 'Konfiguration nur per JSON bzw. MCP.'],
        ];
    }

    public static function get(?string $type): array
    {
        $defs = self::definitions();
        $def = $defs[$type] ?? $defs['text'];
        $def['type'] = isset($defs[$type]) ? $type : 'text';
        $def['label'] = HatchBlockType::tryFrom($def['type'])?->label() ?? $def['type'];

        return $def;
    }

    /** Für die Typ-Auswahl: [groupKey => ['label' => …, 'types' => [[type, label, icon, hint], …]]] */
    public static function grouped(): array
    {
        $out = [];
        foreach (self::GROUPS as $key => $label) {
            $out[$key] = ['label' => $label, 'types' => []];
        }
        foreach (array_keys(self::definitions()) as $type) {
            $def = self::get($type);
            $out[$def['group']]['types'][] = ['type' => $type, 'label' => $def['label'], 'icon' => $def['icon'], 'hint' => $def['hint']];
        }

        return $out;
    }

    public static function defaults(string $type): array
    {
        return self::get($type)['defaults'];
    }

    /** Einzeilige Beschreibung der Einstellungen für eine Karte, z. B. „3 Optionen · Pflicht“. */
    public static function summary(?string $type, ?array $config): string
    {
        $c = $config ?? [];

        return match ($type) {
            'select', 'multi_select', 'dropdown', 'ranking' => self::countLabel(count($c['options'] ?? []), 'Option', 'Optionen')
                . (!empty($c['options']) ? ': ' . self::joinLabels($c['options']) : ''),
            'matrix' => self::countLabel(count($c['items'] ?? []), 'Aspekt', 'Aspekte')
                . ' · Skala ' . ($c['scale_min'] ?? 1) . '–' . ($c['scale_max'] ?? 5),
            'rating' => ($c['max'] ?? 5) . ' Sterne',
            'scale' => 'Skala ' . ($c['min'] ?? 1) . '–' . ($c['max'] ?? 5)
                . (!empty($c['min_label']) || !empty($c['max_label']) ? ' (' . ($c['min_label'] ?? '') . ' … ' . ($c['max_label'] ?? '') . ')' : ''),
            'slider' => ($c['min'] ?? 0) . '–' . ($c['max'] ?? 100) . (!empty($c['unit']) ? ' ' . $c['unit'] : ''),
            'nps' => 'Skala 0–10',
            'boolean' => ($c['true_label'] ?? 'Ja') . ' / ' . ($c['false_label'] ?? 'Nein'),
            'lookup' => !empty($c['lookup_id']) ? 'Auswahlliste verknüpft' : 'Keine Auswahlliste gewählt',
            'number' => !empty($c['unit']) ? 'Einheit: ' . $c['unit'] : '',
            'info', 'section' => \Illuminate\Support\Str::limit(strip_tags((string) ($c['title'] ?? $c['content'] ?? '')), 60),
            'hidden' => match ($c['source'] ?? 'static') { 'url_param' => 'aus Link-Parameter „' . ($c['default_value'] ?? '') . '“', 'referrer' => 'aus Herkunftsseite', default => 'fester Wert' },
            default => '',
        };
    }

    /** Für MCP-Tool-Beschreibungen: "type: key, key, …" je Typ. */
    public static function describeConfig(): array
    {
        $out = [];
        foreach (array_keys(self::definitions()) as $type) {
            $def = self::get($type);
            $keys = collect($def['settings'])->map(function ($s) {
                return match ($s['kind']) {
                    'options' => $s['key'] . ': [{label, value}]',
                    'items' => $s['key'] . ': [{label, value, group?}]',
                    default => $s['key'],
                };
            })->implode(', ');
            $out[$type] = ['label' => $def['label'], 'config' => $keys !== '' ? $keys : '{}'];
        }

        return $out;
    }

    /**
     * Ergänzt eine bestehende Typ-Beschreibung (z. B. mit 'storage'-Hinweisen)
     * um die Einstellungen aus diesem Verzeichnis. Wo das Verzeichnis
     * Einstellungen kennt, gewinnt es – so bleiben MCP-Doku und UI deckungsgleich.
     */
    public static function mergeDescriptions(array $existing): array
    {
        foreach (self::describeConfig() as $type => $desc) {
            $def = self::get($type);
            $entry = $existing[$type] ?? ['label' => $desc['label']];
            if (!empty($def['settings'])) {
                $entry['config'] = $desc['config'] . (!empty($def['advanced_only']) ? ' · ' . ($existing[$type]['config'] ?? '') : '');
            }
            $existing[$type] = $entry;
        }

        return $existing;
    }

    private static function countLabel(int $n, string $one, string $many): string
    {
        return $n . ' ' . ($n === 1 ? $one : $many);
    }

    private static function joinLabels(array $items, int $max = 3): string
    {
        $labels = collect($items)->pluck('label')->filter()->take($max)->implode(', ');

        return count($items) > $max ? $labels . ', …' : $labels;
    }
}
