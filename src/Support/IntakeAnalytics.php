<?php

namespace Platform\Hatch\Support;

use Illuminate\Support\Collection;
use Platform\Hatch\Models\HatchIntakeSession;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchTemplateBlock;

/**
 * Auswertung einer Erhebung: je Frage/Feld eine typgerechte Zusammenfassung.
 *
 * Antworten liegen verschlüsselt in hatch_intake_sessions.answers
 * (answers["block_{id}"]: Text, bei Mehrfach-Typen JSON). Die Datenbank kann
 * sie daher nicht aggregieren – gerechnet wird hier in PHP.
 *
 * Ergebnis-Arten ('kind') je Feld:
 *   numeric   – Ø, Verteilung (rating, scale, nps, slider, number)
 *   choice    – Anzahl je Option (select, dropdown, boolean, lookup, image_choice)
 *   multi     – wie choice, Prozent bezogen auf Antwortende (multi_select, lookup multiple)
 *   matrix    – Ø + Verteilung je Aspekt
 *   ranking   – Ø Rang je Option
 *   text      – Liste der Antworten
 *   count     – nur Anzahl (address, file, signature, repeater, …)
 */
class IntakeAnalytics
{
    public const DISPLAY_ONLY = ['info', 'section', 'calculated'];

    /**
     * @param array{completed_only?: bool, iso_year?: int|null, iso_week?: int|null, since?: string|null} $filters
     */
    public function build(HatchProjectIntake $intake, array $filters = []): array
    {
        $sessions = $this->sessions($intake, $filters);
        $blocks = $this->blocks($intake);
        $lookupLabels = $this->lookupLabels($blocks);

        $answersBySession = $sessions->map(fn (HatchIntakeSession $s) => is_array($s->answers) ? $s->answers : [])->values();

        $groups = [];
        foreach ($this->groupBlocks($blocks) as $group) {
            $fields = [];
            foreach ($group['fields'] as $i => $block) {
                if (in_array($block->block_type, self::DISPLAY_ONLY, true)) {
                    continue;
                }
                $raw = $answersBySession
                    ->map(fn (array $a) => $a['block_' . $block->id] ?? null)
                    ->all();
                $fields[] = [
                    'block' => $block,
                    'label' => $i === 0 ? null : $block->name,
                    'stats' => $this->fieldStats($block, $raw, $sessions, $lookupLabels),
                ];
            }
            if ($fields) {
                $groups[] = ['title' => $group['header']->name ?: 'Ohne Titel', 'description' => $group['header']->description, 'fields' => $fields];
            }
        }

        $total = $sessions->count();
        $completed = $sessions->where('status', 'completed')->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'completion_rate' => $total > 0 ? round($completed / $total * 100) : 0,
            'first_at' => $sessions->min('started_at'),
            'last_at' => $sessions->max('started_at'),
            'headline' => $this->headline($groups),
            'groups' => $groups,
        ];
    }

    /** Verfügbare Kalenderwochen der Erhebung, neueste zuerst: [['year' => 2026, 'week' => 39, 'count' => 12], …] */
    public function weeks(HatchProjectIntake $intake): array
    {
        return $intake->sessions()
            ->whereNotNull('iso_year')->whereNotNull('iso_week')
            ->selectRaw('iso_year, iso_week, count(*) as c')
            ->groupBy('iso_year', 'iso_week')
            ->orderByDesc('iso_year')->orderByDesc('iso_week')
            ->get()
            ->map(fn ($r) => ['year' => (int) $r->iso_year, 'week' => (int) $r->iso_week, 'count' => (int) $r->c])
            ->all();
    }

    public function sessions(HatchProjectIntake $intake, array $filters): Collection
    {
        return $intake->sessions()
            ->when($filters['completed_only'] ?? false, fn ($q) => $q->where('status', 'completed'))
            ->when(!empty($filters['iso_year']) && !empty($filters['iso_week']), fn ($q) => $q
                ->where('iso_year', (int) $filters['iso_year'])
                ->where('iso_week', (int) $filters['iso_week']))
            ->when(!empty($filters['since']), fn ($q) => $q->where('started_at', '>=', $filters['since']))
            ->orderByDesc('started_at')
            ->get();
    }

    /** Template-Blöcke in Formular-Reihenfolge. */
    public function blocks(HatchProjectIntake $intake): Collection
    {
        return $intake->projectTemplate?->templateBlocks()->orderBy('sort_order')->get() ?? collect();
    }

    /** Wie im Formular: Blöcke mit gleicher group_uuid bilden eine Frage. */
    public function groupBlocks(Collection $blocks): array
    {
        $groups = [];
        foreach ($blocks as $block) {
            $key = $block->group_uuid ?: 'single:' . $block->id;
            $groups[$key] ??= ['header' => $block, 'fields' => []];
            $groups[$key]['fields'][] = $block;
        }

        return array_values($groups);
    }

    /** Lesbarer Wert einer Antwort (für CSV / Freitext-Listen). */
    public function display(HatchTemplateBlock $block, $raw, array $lookupLabels = []): string
    {
        if ($raw === null || $raw === '') {
            return '';
        }
        $config = $block->logic_config ?? [];
        $type = $block->block_type;

        return match ($type) {
            'select', 'dropdown', 'image_choice' => $this->optionLabel($config['options'] ?? [], (string) $raw),
            'multi_select', 'ranking' => collect($this->decodeList($raw))->map(fn ($v) => $this->optionLabel($config['options'] ?? [], (string) $v))->implode(', '),
            'boolean' => $raw === 'true' ? ($config['true_label'] ?? 'Ja') : ($raw === 'false' ? ($config['false_label'] ?? 'Nein') : (string) $raw),
            'lookup' => collect(($config['multiple'] ?? false) ? $this->decodeList($raw) : [$raw])
                ->map(fn ($v) => $lookupLabels[$block->id][(string) $v] ?? (string) $v)->implode(', '),
            'matrix' => collect($this->decodeMap($raw))->map(fn ($v, $k) => $this->optionLabel($config['items'] ?? [], (string) $k) . ': ' . $v)->implode(' | '),
            'address' => collect($this->decodeMap($raw))->filter()->implode(', '),
            'date_range' => trim(implode(' – ', array_filter($this->decodeMap($raw)))),
            'repeater' => collect($this->decodeList($raw))->map(fn ($row) => is_array($row) ? implode(', ', array_filter(array_map('strval', $row))) : (string) $row)->implode(' | '),
            'signature', 'file' => '[vorhanden]',
            default => is_array($raw) ? json_encode($raw, JSON_UNESCAPED_UNICODE) : (string) $raw,
        };
    }

    // ---------------------------------------------------------------------

    private function fieldStats(HatchTemplateBlock $block, array $raw, Collection $sessions, array $lookupLabels): array
    {
        $config = $block->logic_config ?? [];
        $type = $block->block_type;
        $answered = array_filter($raw, fn ($v) => !$this->isEmpty($type, $v));
        $n = count($answered);
        $base = ['type' => $type, 'answered' => $n, 'sessions' => count($raw)];

        switch ($type) {
            case 'rating':
            case 'scale':
            case 'nps':
            case 'slider':
            case 'number':
                $values = array_values(array_filter(array_map(fn ($v) => is_numeric($v) ? $v + 0 : null, $answered), fn ($v) => $v !== null));
                [$min, $max] = match ($type) {
                    'rating' => [1, (int) ($config['max'] ?? 5)],
                    'scale' => [(int) ($config['min'] ?? 1), (int) ($config['max'] ?? 5)],
                    'nps' => [0, 10],
                    default => [null, null],
                };
                $stats = $base + [
                    'kind' => 'numeric',
                    'avg' => $values ? round(array_sum($values) / count($values), 1) : null,
                    'min_value' => $values ? min($values) : null,
                    'max_value' => $values ? max($values) : null,
                    'scale_min' => $min,
                    'scale_max' => $max,
                    'unit' => $config['unit'] ?? null,
                    'min_label' => $config['min_label'] ?? null,
                    'max_label' => $config['max_label'] ?? null,
                    'distribution' => [],
                ];
                if ($min !== null && $max !== null && $max - $min <= 20) {
                    for ($i = $min; $i <= $max; $i++) {
                        $stats['distribution'][$i] = count(array_filter($values, fn ($v) => (int) round($v) === $i));
                    }
                }
                if ($type === 'nps' && $values) {
                    $promoters = count(array_filter($values, fn ($v) => $v >= 9));
                    $detractors = count(array_filter($values, fn ($v) => $v <= 6));
                    $stats['nps'] = (int) round(($promoters - $detractors) / count($values) * 100);
                    $stats['promoters'] = $promoters;
                    $stats['detractors'] = $detractors;
                }

                return $stats;

            case 'select':
            case 'dropdown':
            case 'image_choice':
            case 'boolean':
                $options = $type === 'boolean'
                    ? [['value' => 'true', 'label' => $config['true_label'] ?? 'Ja'], ['value' => 'false', 'label' => $config['false_label'] ?? 'Nein']]
                    : ($config['options'] ?? []);

                return $base + ['kind' => 'choice', 'options' => $this->countOptions($options, array_map('strval', $answered), $n)];

            case 'multi_select':
                $picked = array_merge(...array_map(fn ($v) => $this->decodeList($v), array_values($answered)) ?: [[]]);

                return $base + ['kind' => 'multi', 'options' => $this->countOptions($config['options'] ?? [], array_map('strval', $picked), $n)];

            case 'lookup':
                $labels = $lookupLabels[$block->id] ?? [];
                $options = collect($labels)->map(fn ($l, $v) => ['value' => (string) $v, 'label' => $l])->values()->all();
                if ($config['multiple'] ?? false) {
                    $picked = array_merge(...array_map(fn ($v) => $this->decodeList($v), array_values($answered)) ?: [[]]);

                    return $base + ['kind' => 'multi', 'options' => $this->countOptions($options, array_map('strval', $picked), $n)];
                }

                return $base + ['kind' => 'choice', 'options' => $this->countOptions($options, array_map('strval', $answered), $n)];

            case 'matrix':
                $sMin = (int) ($config['scale_min'] ?? 1);
                $sMax = (int) ($config['scale_max'] ?? 5);
                $maps = array_map(fn ($v) => $this->decodeMap($v), array_values($answered));
                $items = [];
                foreach ($config['items'] ?? [] as $item) {
                    $key = is_array($item) ? ($item['value'] ?? $item['label'] ?? '') : (string) $item;
                    $vals = array_values(array_filter(array_map(fn ($m) => isset($m[$key]) && is_numeric($m[$key]) ? (int) $m[$key] : null, $maps), fn ($v) => $v !== null));
                    $dist = [];
                    for ($i = $sMin; $i <= $sMax; $i++) {
                        $dist[$i] = count(array_filter($vals, fn ($v) => $v === $i));
                    }
                    $items[] = [
                        'label' => is_array($item) ? ($item['label'] ?? $key) : $key,
                        'group' => is_array($item) ? ($item['group'] ?? null) : null,
                        'count' => count($vals),
                        'avg' => $vals ? round(array_sum($vals) / count($vals), 1) : null,
                        'distribution' => $dist,
                    ];
                }
                $avgs = array_filter(array_column($items, 'avg'), fn ($v) => $v !== null);

                return $base + [
                    'kind' => 'matrix',
                    'scale_min' => $sMin,
                    'scale_max' => $sMax,
                    'min_label' => $config['scale_labels']['min_label'] ?? null,
                    'max_label' => $config['scale_labels']['max_label'] ?? null,
                    'items' => $items,
                    'avg' => $avgs ? round(array_sum($avgs) / count($avgs), 1) : null,
                    'lowest' => $avgs ? array_search(min($avgs), array_column($items, 'avg'), true) : null,
                ];

            case 'ranking':
                $orders = array_map(fn ($v) => $this->decodeList($v), array_values($answered));
                $options = [];
                foreach ($config['options'] ?? [] as $opt) {
                    $value = (string) ($opt['value'] ?? $opt['label'] ?? '');
                    $ranks = array_values(array_filter(array_map(function ($order) use ($value) {
                        $pos = array_search($value, array_map('strval', $order), true);

                        return $pos === false ? null : $pos + 1;
                    }, $orders), fn ($v) => $v !== null));
                    $options[] = ['label' => $opt['label'] ?? $value, 'avg_rank' => $ranks ? round(array_sum($ranks) / count($ranks), 1) : null, 'count' => count($ranks)];
                }
                usort($options, fn ($a, $b) => ($a['avg_rank'] ?? 99) <=> ($b['avg_rank'] ?? 99));

                return $base + ['kind' => 'ranking', 'options' => $options];

            case 'text':
            case 'long_text':
            case 'email':
            case 'phone':
            case 'url':
            case 'location':
            case 'date':
            case 'time':
            case 'datetime':
            case 'color':
            case 'hidden':
            case 'date_range':
            case 'address':
                $entries = [];
                foreach ($raw as $idx => $value) {
                    if ($this->isEmpty($type, $value)) {
                        continue;
                    }
                    $session = $sessions->values()[$idx] ?? null;
                    $entries[] = [
                        'text' => $this->display($block, $value, $lookupLabels),
                        'at' => $session?->completed_at ?? $session?->started_at,
                        'token' => $session?->session_token,
                        'session_id' => $session?->id,
                    ];
                }

                return $base + ['kind' => 'text', 'entries' => $entries];

            default:
                return $base + ['kind' => 'count'];
        }
    }

    /** Zählt Antworten je Option; unbekannte Werte (z. B. gelöschte Optionen) landen unter ihrem Rohwert. */
    private function countOptions(array $options, array $values, int $respondents): array
    {
        $counts = array_count_values(array_filter($values, fn ($v) => $v !== ''));
        $rows = [];
        foreach ($options as $opt) {
            $value = (string) (is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? '') : $opt);
            $rows[] = ['label' => is_array($opt) ? ($opt['label'] ?? $value) : $value, 'count' => $counts[$value] ?? 0];
            unset($counts[$value]);
        }
        foreach ($counts as $value => $count) {
            $rows[] = ['label' => $value . ' (nicht mehr in der Vorlage)', 'count' => $count];
        }
        foreach ($rows as &$row) {
            $row['percent'] = $respondents > 0 ? (int) round($row['count'] / $respondents * 100) : 0;
        }

        return $rows;
    }

    private function headline(array $groups): ?array
    {
        // Erste Bewertungs-Frage als Kennzahl oben (z. B. „Gesamtbewertung Ø 4,3“)
        foreach ($groups as $group) {
            foreach ($group['fields'] as $field) {
                $s = $field['stats'];
                if (($s['kind'] ?? null) === 'numeric' && in_array($s['type'], ['rating', 'scale', 'nps'], true) && $s['avg'] !== null) {
                    return ['label' => $group['title'], 'avg' => $s['avg'], 'max' => $s['scale_max'], 'type' => $s['type'], 'nps' => $s['nps'] ?? null];
                }
            }
        }

        return null;
    }

    private function lookupLabels(Collection $blocks): array
    {
        $out = [];
        foreach ($blocks->where('block_type', 'lookup') as $block) {
            $id = $block->logic_config['lookup_id'] ?? null;
            if ($id) {
                $out[$block->id] = \Platform\Hatch\Models\HatchLookupValue::where('lookup_id', $id)->pluck('label', 'value')->map(fn ($l) => (string) $l)->all();
            }
        }

        return $out;
    }

    private function optionLabel(array $options, string $value): string
    {
        foreach ($options as $opt) {
            if (is_array($opt) && (string) ($opt['value'] ?? '') === $value) {
                return (string) ($opt['label'] ?? $value);
            }
        }

        return $value;
    }

    private function isEmpty(?string $type, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (in_array($type, ['multi_select', 'ranking', 'repeater'], true)) {
            return $this->decodeList($value) === [];
        }
        if (in_array($type, ['matrix', 'address', 'date_range'], true)) {
            return array_filter($this->decodeMap($value), fn ($v) => $v !== '' && $v !== null) === [];
        }
        if ($type === 'lookup' && is_string($value) && str_starts_with($value, '[')) {
            return $this->decodeList($value) === [];
        }

        return false;
    }

    private function decodeList($value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function decodeMap($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
