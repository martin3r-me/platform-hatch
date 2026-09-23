<?php

namespace Platform\Hatch\Support;

use Platform\Hatch\Models\HatchPlaceholder;
use Platform\Hatch\Models\HatchProjectIntake;

/**
 * Zentrales Verzeichnis der Platzhalter für Intake-Texte (Name, Description).
 *
 * Alles, was Platzhalter kennt, liest von hier: der Public-Renderer, das
 * Baustein-Feld in der UI (Einfügen-Menü + Vorschau), die KW-Erkennung, die
 * Pflegeseite und die MCP-Tool-Beschreibungen.
 *
 * Zwei Arten:
 *   - eingebaute, berechnete Platzhalter (builtins(), z. B. Kalenderwoche) —
 *     ein neuer ist genau ein Eintrag dort
 *   - eigene Platzhalter eines Teams (Tabelle hatch_placeholders) mit festem
 *     Standardwert, pro Erhebung überschreibbar über
 *     intake_settings.placeholder_values[key]
 *
 * Gespeichert wird immer die technische Form "{{key}}", damit bestehende
 * Intakes und MCP-Aufrufe unverändert funktionieren. Unbekannte Platzhalter
 * bleiben beim Rendern 1:1 stehen, damit Tippfehler sichtbar werden.
 *
 * Als scoped Binding registriert: die Team-Platzhalter werden pro Request
 * einmal je Team geladen (die Sidebar rendert viele Intakes).
 */
class IntakePlaceholders
{
    public const RECURRENCE_WEEKLY = 'weekly';

    /** @var array<int, array> teamId => Custom-Definitionen */
    private array $customCache = [];

    public function __construct(private readonly IsoWeekResolver $resolver)
    {
    }

    /**
     * Eingebaute Platzhalter:
     * key => [label, description, recurrence (z. B. KW-Spalte) | null, resolve: fn(array $context): string]
     *
     * $context enthält 'iso' (Ergebnis von IsoWeekResolver::resolve) und 'intake'.
     */
    public function builtins(): array
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

    public function isReservedKey(string $key): bool
    {
        return array_key_exists($key, $this->builtins());
    }

    /**
     * Eigene Platzhalter eines Teams im selben Format wie builtins(),
     * zusätzlich 'custom' => true, 'id', 'default'.
     */
    public function customs(?int $teamId): array
    {
        if (!$teamId) {
            return [];
        }

        return $this->customCache[$teamId] ??= HatchPlaceholder::forTeam($teamId)
            ->orderBy('sort')
            ->orderBy('label')
            ->get()
            ->reject(fn (HatchPlaceholder $p) => $this->isReservedKey($p->key))
            ->mapWithKeys(fn (HatchPlaceholder $p) => [$p->key => [
                'label' => $p->label,
                'description' => $p->description ?: 'Eigener Platzhalter',
                'recurrence' => null,
                'custom' => true,
                'id' => $p->id,
                'default' => (string) $p->default_value,
                'resolve' => function (array $c) use ($p) {
                    $override = $c['intake']?->intake_settings['placeholder_values'][$p->key] ?? null;

                    return ($override !== null && $override !== '') ? (string) $override : (string) $p->default_value;
                },
            ]])
            ->all();
    }

    /** Eingebaute + eigene Platzhalter für das Team der Erhebung (bzw. des angemeldeten Users). */
    public function definitions(?HatchProjectIntake $intake = null): array
    {
        return $this->builtins() + $this->customs($this->teamOf($intake));
    }

    /** Nach Änderungen an Team-Platzhaltern im selben Request. */
    public function forget(?int $teamId = null): void
    {
        if ($teamId === null) {
            $this->customCache = [];
        } else {
            unset($this->customCache[$teamId]);
        }
    }

    /** key => aktueller Wert */
    public function values(?HatchProjectIntake $intake): array
    {
        $context = [
            'iso' => $this->resolver->resolve($intake),
            'intake' => $intake,
        ];

        return array_map(fn (array $def) => ($def['resolve'])($context), $this->definitions($intake));
    }

    /**
     * Für die UI: Liste mit key, label, description, aktuellem Beispielwert und Art.
     */
    public function catalog(?HatchProjectIntake $intake): array
    {
        $values = $this->values($intake);

        return collect($this->definitions($intake))
            ->map(fn (array $def, string $key) => [
                'key' => $key,
                'label' => $def['label'],
                'description' => $def['description'],
                'example' => $values[$key] !== '' ? $values[$key] : '(leer)',
                'custom' => (bool) ($def['custom'] ?? false),
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

    /** Alle bekannten Platzhalter-Keys, die in den Texten der Erhebung vorkommen. */
    public function keysIn(?HatchProjectIntake $intake, ?string ...$texts): array
    {
        $known = $this->definitions($intake);
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
    public function recurrence(?HatchProjectIntake $intake, ?string ...$texts): ?string
    {
        $definitions = $this->definitions($intake);

        foreach ($this->keysIn($intake, ...$texts) as $key) {
            if ($definitions[$key]['recurrence'] !== null) {
                return $definitions[$key]['recurrence'];
            }
        }

        return null;
    }

    /** Kurzliste für MCP-Tool-Beschreibungen: "{{iso_week}} (Kalenderwoche), …" */
    public function describeForTools(): string
    {
        return collect($this->builtins())
            ->map(fn (array $def, string $key) => '{{' . $key . '}} (' . $def['label'] . ')')
            ->implode(', ')
            . ' sowie eigene Platzhalter des Teams ({{key}}, gepflegt unter Formulare → Platzhalter)';
    }

    private function teamOf(?HatchProjectIntake $intake): ?int
    {
        return $intake?->team_id ?? auth()->user()?->current_team_id;
    }
}
