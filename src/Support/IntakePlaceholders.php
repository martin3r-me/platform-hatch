<?php

namespace Platform\Hatch\Support;

use Platform\Hatch\Models\HatchProjectIntake;

/**
 * Zentrales Verzeichnis der Platzhalter für Intake-Texte (Name, Description).
 *
 * Alles, was Platzhalter kennt, liest von hier: der Public-Renderer, das
 * Baustein-Feld in der UI (Einfügen-Menü + Vorschau), die KW-Erkennung und
 * die MCP-Tool-Beschreibungen. Ein neuer Platzhalter ist deshalb genau ein
 * Eintrag in definitions() — er taucht danach überall automatisch auf.
 *
 * Gespeichert wird immer die technische Form "{{key}}", damit bestehende
 * Intakes und MCP-Aufrufe unverändert funktionieren. Unbekannte Platzhalter
 * bleiben beim Rendern 1:1 stehen, damit Tippfehler sichtbar werden.
 */
class IntakePlaceholders
{
    public const RECURRENCE_WEEKLY = 'weekly';

    public function __construct(private readonly IsoWeekResolver $resolver)
    {
    }

    /**
     * key => [
     *   'label'       => Anzeigename im Baustein,
     *   'description' => Erklärung im Einfügen-Menü,
     *   'recurrence'  => macht die Erhebung wiederkehrend (z. B. KW-Spalte), sonst null,
     *   'resolve'     => fn(array $context): string,
     * ]
     *
     * $context enthält 'iso' (Ergebnis von IsoWeekResolver::resolve) und 'intake'.
     */
    public function definitions(): array
    {
        return [
            'iso_week' => [
                'label' => 'Kalenderwoche',
                'description' => 'Aktuelle Kalenderwoche, z. B. 9 oder 39',
                'recurrence' => self::RECURRENCE_WEEKLY,
                'resolve' => fn (array $c) => (string) $c['iso']['iso_week'],
            ],
            'iso_week2' => [
                'label' => 'Kalenderwoche (2-stellig)',
                'description' => 'Kalenderwoche immer zweistellig, z. B. 09',
                'recurrence' => self::RECURRENCE_WEEKLY,
                'resolve' => fn (array $c) => str_pad((string) $c['iso']['iso_week'], 2, '0', STR_PAD_LEFT),
            ],
            'iso_year' => [
                'label' => 'Jahr',
                'description' => 'Jahr der Kalenderwoche, z. B. 2026',
                'recurrence' => null,
                'resolve' => fn (array $c) => (string) $c['iso']['iso_year'],
            ],
            'iso_year2' => [
                'label' => 'Jahr (2-stellig)',
                'description' => 'Jahr der Kalenderwoche zweistellig, z. B. 26',
                'recurrence' => null,
                'resolve' => fn (array $c) => substr((string) $c['iso']['iso_year'], -2),
            ],
        ];
    }

    /** key => aktueller Wert */
    public function values(?HatchProjectIntake $intake): array
    {
        $context = [
            'iso' => $this->resolver->resolve($intake),
            'intake' => $intake,
        ];

        return array_map(fn (array $def) => ($def['resolve'])($context), $this->definitions());
    }

    /**
     * Für die UI: Liste mit key, label, description und aktuellem Beispielwert.
     */
    public function catalog(?HatchProjectIntake $intake): array
    {
        $values = $this->values($intake);

        return collect($this->definitions())
            ->map(fn (array $def, string $key) => [
                'key' => $key,
                'label' => $def['label'],
                'description' => $def['description'],
                'example' => $values[$key],
            ])
            ->values()
            ->all();
    }

    public function render(?string $text, ?HatchProjectIntake $intake): ?string
    {
        if ($text === null || $text === '' || !str_contains($text, '{{')) {
            return $text;
        }

        $tokens = [];
        foreach ($this->values($intake) as $key => $value) {
            $tokens['{{' . $key . '}}'] = $value;
        }

        return strtr($text, $tokens);
    }

    /** Alle bekannten Platzhalter-Keys, die in den Texten vorkommen. */
    public function keysIn(?string ...$texts): array
    {
        $known = $this->definitions();
        $found = [];

        foreach ($texts as $text) {
            if ($text === null || !preg_match_all('/\{\{(\w+)\}\}/', $text, $matches)) {
                continue;
            }
            foreach ($matches[1] as $key) {
                if (isset($known[$key])) {
                    $found[$key] = true;
                }
            }
        }

        return array_keys($found);
    }

    /** Wiederholungsart, die sich aus den verwendeten Platzhaltern ergibt (z. B. 'weekly'), sonst null. */
    public function recurrence(?string ...$texts): ?string
    {
        $definitions = $this->definitions();

        foreach ($this->keysIn(...$texts) as $key) {
            if ($definitions[$key]['recurrence'] !== null) {
                return $definitions[$key]['recurrence'];
            }
        }

        return null;
    }

    /** Kurzliste für MCP-Tool-Beschreibungen: "{{iso_week}} (Kalenderwoche), …" */
    public function describeForTools(): string
    {
        return collect($this->definitions())
            ->map(fn (array $def, string $key) => '{{' . $key . '}} (' . $def['label'] . ')')
            ->implode(', ');
    }
}
