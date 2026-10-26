<?php

namespace Platform\Hatch\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Platform\Hatch\Models\HatchIntakeSession;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectTemplate;

/**
 * Vergleich über alle Erhebungen einer Vorlage (z. B. ein Wochenfeedback je Standort).
 *
 * Verglichen werden die Bewertungsfragen – nur dort ist ein Durchschnitt sinnvoll:
 * rating, scale, slider und nps je Frage, Matrix je Aspekt. Je Kennzahl entsteht
 *   - ein Wert pro Erhebung (Spalten der Vergleichstabelle) und gesamt
 *   - ein Wert pro Kalenderwoche, gesamt und pro Erhebung (Verlauf)
 * NPS wird als Score (-100 … 100) verdichtet, alles andere als Mittelwert.
 *
 * Gezählt werden nur Teilnahmen mit mindestens einer Antwort (scopeWithAnswers).
 */
class TemplateAnalytics
{
    public const NUMERIC_TYPES = ['rating', 'scale', 'slider', 'nps'];

    /**
     * @param array{period?: string, completed_only?: bool} $filters period: 'all' | Anzahl Kalenderwochen ('4', '12')
     */
    public function build(HatchProjectTemplate $template, array $filters = []): array
    {
        $placeholders = app(IntakePlaceholders::class);
        $intakes = $template->projectIntakes()
            ->where('team_id', $template->team_id)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $sessions = HatchIntakeSession::query()
            ->whereIn('project_intake_id', $intakes->keys())
            ->withAnswers()
            ->when($filters['completed_only'] ?? false, fn ($q) => $q->where('status', 'completed'))
            ->when(is_numeric($filters['period'] ?? null), fn ($q) => $q->where(
                'started_at', '>=', Carbon::now()->startOfWeek()->subWeeks((int) $filters['period'] - 1)
            ))
            ->get(['id', 'project_intake_id', 'status', 'iso_year', 'iso_week', 'answers', 'started_at']);

        $metrics = $this->metrics($template->templateBlocks()->orderBy('sort_order')->get());

        // [metric][bucket] => Rohwerte;  bucket = intake id | 'all' | "w:2026-39" | "w:2026-39:<intake>"
        $values = [];
        $counts = [];   // [bucket] => ['total' => n, 'completed' => n]
        foreach ($sessions as $session) {
            $answers = is_array($session->answers) ? $session->answers : [];
            $week = $session->iso_year && $session->iso_week ? sprintf('%d-%02d', $session->iso_year, $session->iso_week) : null;
            $buckets = array_filter([$session->project_intake_id, 'all', $week ? "w:$week" : null, $week ? "w:$week:{$session->project_intake_id}" : null]);

            foreach ($buckets as $bucket) {
                $counts[$bucket]['total'] = ($counts[$bucket]['total'] ?? 0) + 1;
                $counts[$bucket]['completed'] = ($counts[$bucket]['completed'] ?? 0) + ($session->status === 'completed' ? 1 : 0);
            }

            foreach ($metrics as $key => $metric) {
                $value = ($metric['extract'])($answers);
                if ($value === null) {
                    continue;
                }
                foreach ($buckets as $bucket) {
                    $values[$key][$bucket][] = $value;
                }
            }
        }

        $weeks = $sessions->filter(fn ($s) => $s->iso_year && $s->iso_week)
            ->map(fn ($s) => sprintf('%d-%02d', $s->iso_year, $s->iso_week))
            ->unique()->sort()->values()
            ->slice(-12)->values()   // Verlauf: höchstens die letzten 12 Wochen mit Antworten
            ->map(fn ($w) => ['key' => $w, 'label' => 'KW ' . (int) substr($w, 5), 'year' => (int) substr($w, 0, 4)])
            ->all();

        $columns = $intakes
            ->filter(fn (HatchProjectIntake $i) => ($counts[$i->id]['total'] ?? 0) > 0)
            ->map(fn (HatchProjectIntake $i) => [
                'id' => $i->id,
                'name' => $placeholders->render($i->name, $i),
                'event_reference' => $i->event_reference,
                'status' => $i->status,
                'total' => $counts[$i->id]['total'],
                'completion_rate' => (int) round($counts[$i->id]['completed'] / $counts[$i->id]['total'] * 100),
            ])
            ->values()
            ->all();
        $columns = $this->withShortNames($columns);

        $rows = [];
        foreach ($metrics as $key => $metric) {
            $cells = [];
            foreach (array_merge(['all'], array_column($columns, 'id')) as $bucket) {
                $cells[$bucket] = $this->aggregate($metric, $values[$key][$bucket] ?? []);
            }
            $trend = [];
            foreach ($weeks as $w) {
                $trend['all'][$w['key']] = $this->aggregate($metric, $values[$key]["w:{$w['key']}"] ?? []);
                foreach ($columns as $c) {
                    $trend[$c['id']][$w['key']] = $this->aggregate($metric, $values[$key]["w:{$w['key']}:{$c['id']}"] ?? []);
                }
            }
            $rows[$key] = array_diff_key($metric, ['extract' => true]) + ['cells' => $cells, 'trend' => $trend];
        }

        return [
            'total' => $counts['all']['total'] ?? 0,
            'completion_rate' => isset($counts['all']) ? (int) round($counts['all']['completed'] / $counts['all']['total'] * 100) : 0,
            'intake_count' => $intakes->count(),
            'intakes_without_answers' => $intakes->count() - count($columns),
            'columns' => $columns,
            'weeks' => $weeks,
            'week_counts' => collect($weeks)->mapWithKeys(fn ($w) => [$w['key'] => $counts["w:{$w['key']}"]['total'] ?? 0])->all(),
            'rows' => $rows,
        ];
    }

    /**
     * Vergleichbare Kennzahlen der Vorlage in Formular-Reihenfolge.
     * key => [label, group, type, min, max, mid, extract: fn(array $answers): float|null]
     */
    public function metrics(Collection $blocks): array
    {
        $metrics = [];
        $groupHeaders = [];   // group key => erster Block der Frage (trägt den Fragetitel)
        foreach ($blocks as $block) {
            $groupKey = $block->group_uuid ?: 'single:' . $block->id;
            $header = $groupHeaders[$groupKey] ??= $block;
            $groupTitle = $header->name ?: 'Frage';
            $isFirst = $header->id === $block->id;
            $config = $block->logic_config ?? [];
            $answerKey = 'block_' . $block->id;

            if (in_array($block->block_type, self::NUMERIC_TYPES, true)) {
                [$min, $max] = match ($block->block_type) {
                    'rating' => [1, (int) ($config['max'] ?? 5)],
                    'nps' => [0, 10],
                    default => [(float) ($config['min'] ?? 1), (float) ($config['max'] ?? 5)],
                };
                if ($max <= $min) {
                    continue;
                }
                $isNps = $block->block_type === 'nps';
                $metrics['b' . $block->id] = [
                    'label' => $block->name ?: $groupTitle,
                    'group' => $isFirst ? null : $groupTitle,
                    'type' => $block->block_type,
                    'min' => $isNps ? -100 : $min,
                    'max' => $isNps ? 100 : $max,
                    'mid' => $isNps ? 0 : ($min + $max) / 2,
                    'extract' => function (array $answers) use ($answerKey) {
                        $v = $answers[$answerKey] ?? null;

                        return is_numeric($v) ? (float) $v : null;
                    },
                ];
            } elseif ($block->block_type === 'matrix') {
                $min = (int) ($config['scale_min'] ?? 1);
                $max = (int) ($config['scale_max'] ?? 5);
                if ($max <= $min) {
                    continue;
                }
                foreach ($config['items'] ?? [] as $i => $item) {
                    $itemKey = is_array($item) ? (string) ($item['value'] ?? $item['label'] ?? '') : (string) $item;
                    if ($itemKey === '') {
                        continue;
                    }
                    $metrics['b' . $block->id . ':' . $i] = [
                        'label' => is_array($item) ? ($item['label'] ?? $itemKey) : $itemKey,
                        'group' => $block->name ?: $groupTitle,
                        'type' => 'matrix',
                        'min' => $min,
                        'max' => $max,
                        'mid' => ($min + $max) / 2,
                        'extract' => function (array $answers) use ($answerKey, $itemKey) {
                            $v = AnswerValue::decodeMap($answers[$answerKey] ?? null)[$itemKey] ?? null;

                            return is_numeric($v) ? (float) $v : null;
                        },
                    ];
                }
            }
        }

        return $metrics;
    }

    /**
     * Kurzname je Spalte ohne den gemeinsamen Anfang aller Namen
     * („Wochenfeedback WCCB“, „Wochenfeedback Borussia Park“ → „WCCB“, „Borussia Park“).
     */
    private function withShortNames(array $columns): array
    {
        $words = array_map(fn ($c) => preg_split('/\s+/u', trim((string) $c['name'])), $columns);
        $common = 0;
        if (count($columns) >= 2) {
            while (true) {
                $word = $words[0][$common] ?? null;
                if ($word === null) break;
                foreach ($words as $w) {
                    // mindestens ein Wort muss übrig bleiben
                    if (($w[$common] ?? null) !== $word || count($w) <= $common + 1) break 2;
                }
                $common++;
            }
        }

        foreach ($columns as $i => $column) {
            $short = trim(implode(' ', array_slice($words[$i], $common)), " \t–-:|/");
            $columns[$i]['short'] = $short !== '' ? $short : $column['name'];
        }

        return $columns;
    }

    /** ['value' => float|null, 'n' => int, 'tone' => -1…1 (unter/über der Skalenmitte) | null] */
    private function aggregate(array $metric, array $values): array
    {
        $n = count($values);
        if ($n === 0) {
            return ['value' => null, 'n' => 0, 'tone' => null];
        }

        if ($metric['type'] === 'nps') {
            $promoters = count(array_filter($values, fn ($v) => $v >= 9));
            $detractors = count(array_filter($values, fn ($v) => $v <= 6));
            $value = round(($promoters - $detractors) / $n * 100);
        } else {
            $value = round(array_sum($values) / $n, 1);
        }

        $half = ($metric['max'] - $metric['min']) / 2;
        $tone = $half > 0 ? max(-1, min(1, ($value - $metric['mid']) / $half)) : 0;

        return ['value' => $value, 'n' => $n, 'tone' => $tone];
    }
}
