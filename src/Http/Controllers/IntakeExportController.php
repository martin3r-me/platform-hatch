<?php

namespace Platform\Hatch\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Platform\Hatch\Models\HatchLookupValue;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Support\IntakeAnalytics;
use Platform\Hatch\Support\IntakePlaceholders;

/**
 * CSV-Export aller Antworten einer Erhebung – eine Zeile je Session, eine
 * Spalte je Feld (Matrix: eine Spalte je Aspekt). Semikolon + UTF-8-BOM,
 * damit Excel (DE) die Datei direkt korrekt öffnet. Filter wie in der
 * Auswertung (completed_only, iso_year/iso_week, since).
 */
class IntakeExportController
{
    public function __invoke(Request $request, HatchProjectIntake $projectIntake)
    {
        abort_unless((int) $projectIntake->team_id === (int) auth()->user()->current_team_id, 403);

        $analytics = app(IntakeAnalytics::class);
        $filters = [
            'completed_only' => $request->boolean('completed_only'),
            'iso_year' => $request->integer('iso_year') ?: null,
            'iso_week' => $request->integer('iso_week') ?: null,
            'since' => $request->query('since'),
        ];
        $sessions = $analytics->sessions($projectIntake, $filters)->sortBy('started_at')->values();
        $blocks = $analytics->blocks($projectIntake)->reject(fn ($b) => in_array($b->block_type, IntakeAnalytics::DISPLAY_ONLY, true))->values();

        $lookupLabels = [];
        foreach ($blocks->where('block_type', 'lookup') as $b) {
            if ($id = $b->logic_config['lookup_id'] ?? null) {
                $lookupLabels[$b->id] = HatchLookupValue::where('lookup_id', $id)->pluck('label', 'value')->all();
            }
        }

        // Spaltenköpfe: Frage-Titel (bei weiteren Feldern „Titel – Feld“), Matrix je Aspekt
        $titles = [];
        foreach ($analytics->groupBlocks($blocks) as $group) {
            foreach ($group['fields'] as $i => $b) {
                $titles[$b->id] = $i === 0 ? ($b->name ?: 'Frage') : (($group['header']->name ?: 'Frage') . ' – ' . ($b->name ?: 'Feld ' . ($i + 1)));
            }
        }

        $columns = [];
        foreach ($blocks as $b) {
            if ($b->block_type === 'matrix') {
                foreach ($b->logic_config['items'] ?? [] as $item) {
                    $key = is_array($item) ? ($item['value'] ?? $item['label'] ?? '') : (string) $item;
                    $columns[] = ['block' => $b, 'matrix_key' => $key, 'title' => $titles[$b->id] . ' – ' . (is_array($item) ? ($item['label'] ?? $key) : $key)];
                }
            } else {
                $columns[] = ['block' => $b, 'matrix_key' => null, 'title' => $titles[$b->id]];
            }
        }

        $name = app(IntakePlaceholders::class)->render($projectIntake->name, $projectIntake);
        $filename = 'antworten-' . Str::slug($name ?: 'erhebung') . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($sessions, $columns, $analytics, $lookupLabels, $projectIntake) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $head = ['Code', 'Status', 'KW', 'Gestartet', 'Abgeschlossen', 'Respondent', 'E-Mail'];
            if ($projectIntake->event_reference) {
                array_unshift($head, 'Veranstaltungsnummer');
            }
            fputcsv($out, array_merge($head, array_column($columns, 'title')), ';');

            foreach ($sessions as $s) {
                $answers = is_array($s->answers) ? $s->answers : [];
                $row = [
                    $s->session_token,
                    $s->status === 'completed' ? 'abgeschlossen' : 'begonnen',
                    $s->iso_week && $s->iso_year ? sprintf('%d/%02d', $s->iso_year, $s->iso_week) : '',
                    $s->started_at?->format('d.m.Y H:i') ?? '',
                    $s->completed_at?->format('d.m.Y H:i') ?? '',
                    (string) $s->respondent_name,
                    (string) $s->respondent_email,
                ];
                if ($projectIntake->event_reference) {
                    array_unshift($row, $projectIntake->event_reference);
                }
                foreach ($columns as $col) {
                    $raw = $answers['block_' . $col['block']->id] ?? null;
                    if ($col['matrix_key'] !== null) {
                        $map = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);
                        $row[] = (string) ($map[$col['matrix_key']] ?? '');
                    } else {
                        $row[] = $analytics->display($col['block'], $raw, $lookupLabels);
                    }
                }
                fputcsv($out, $row, ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
