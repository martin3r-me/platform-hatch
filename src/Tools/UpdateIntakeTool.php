<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class UpdateIntakeTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.intakes.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /hatch/intakes/{id} - Aktualisiert einen Project Intake. Parameter: intake_id (required). Status-Modell: draft → published → closed. Beim Wechsel auf "published" wird started_at automatisch gesetzt, beim Wechsel auf "closed" wird completed_at gesetzt. name/description unterstützen Platzhalter {{iso_week}}, {{iso_week2}}, {{iso_year}}, {{iso_year2}} — werden im Public-View durch die aktuelle Kalenderwoche ersetzt.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'intake_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Intakes (ERFORDERLICH). Nutze "hatch.intakes.GET".',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name. Unterstützt Platzhalter {{iso_week}} / {{iso_year}} (z. B. "Wochenfeedback – KW {{iso_week}}/{{iso_year}}").',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung. Unterstützt die gleichen Platzhalter wie name.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'published', 'closed'],
                    'description' => 'Optional: Neuer Status (draft, published, closed). "published" = Erhebung live, "closed" = Erhebung beendet.',
                ],
                'intake_settings' => [
                    'type' => 'object',
                    'description' => 'Optional: Owner-Konfiguration des Intakes. Aktuell unterstützt: week_cutoff (steuert die KW-Zuordnung der Sessions).',
                    'properties' => [
                        'week_cutoff' => [
                            'type' => 'object',
                            'description' => 'Konfiguration für die Zuordnung einer Session zu einer ISO-KW. Default (ohne Setting): ISO-Standard (Montag 00:00). Beispiel "rollover_weekday=saturday, rollover_time=12:00" → Antworten ab Samstag 12 Uhr zählen zur kommenden KW.',
                            'properties' => [
                                'enabled' => ['type' => 'boolean', 'description' => 'true = Cutoff aktiv. Default: true wenn rollover_* gesetzt.'],
                                'rollover_weekday' => [
                                    'type' => 'string',
                                    'enum' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                                    'description' => 'Wochentag, an dem die "neue" KW beginnt.',
                                ],
                                'rollover_time' => [
                                    'type' => 'string',
                                    'description' => 'Uhrzeit im Format HH:MM (24h). Default 00:00.',
                                ],
                            ],
                        ],
                    ],
                    'additionalProperties' => true,
                ],
            ],
            'required' => ['intake_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $found = $this->validateAndFindModel(
                $arguments,
                $context,
                'intake_id',
                HatchProjectIntake::class,
                'NOT_FOUND',
                'Intake nicht gefunden.'
            );
            if ($found['error']) {
                return $found['error'];
            }
            /** @var HatchProjectIntake $intake */
            $intake = $found['model'];

            if ((int)$intake->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Intake.');
            }

            // Einfache Felder aktualisieren
            foreach (['name', 'description'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $intake->{$field} = $arguments[$field] === '' ? null : $arguments[$field];
                }
            }

            // intake_settings (JSON) – Partial-Merge, damit ein einzelnes
            // Subfeld gesetzt werden kann ohne andere Settings zu killen.
            if (array_key_exists('intake_settings', $arguments)) {
                $patch = $arguments['intake_settings'];
                if ($patch === null || $patch === []) {
                    $intake->intake_settings = null;
                } elseif (is_array($patch)) {
                    $existing = is_array($intake->intake_settings) ? $intake->intake_settings : [];
                    $intake->intake_settings = array_replace_recursive($existing, $patch);
                } else {
                    return ToolResult::error('VALIDATION_ERROR', 'intake_settings muss ein Objekt sein.');
                }
            }

            // Status-Wechsel mit automatischer Logik
            if (array_key_exists('status', $arguments) && $arguments['status'] !== $intake->status) {
                $newStatus = $arguments['status'];

                if (!in_array($newStatus, ['draft', 'published', 'closed'])) {
                    return ToolResult::error('VALIDATION_ERROR', 'Ungültiger Status. Erlaubt: draft, published, closed.');
                }

                if ($newStatus === 'published') {
                    $intake->status = 'published';
                    $intake->is_active = true;
                    if (empty($intake->started_at)) {
                        $intake->started_at = now();
                    }
                } elseif ($newStatus === 'closed') {
                    $intake->status = 'closed';
                    $intake->is_active = false;
                    if (empty($intake->completed_at)) {
                        $intake->completed_at = now();
                    }
                } elseif ($newStatus === 'draft') {
                    $intake->status = 'draft';
                    $intake->is_active = false;
                }
            }

            $intake->save();

            return ToolResult::success([
                'id' => $intake->id,
                'uuid' => $intake->uuid,
                'name' => $intake->name,
                'status' => $intake->status,
                'intake_settings' => $intake->intake_settings,
                'started_at' => $intake->started_at?->toISOString(),
                'completed_at' => $intake->completed_at?->toISOString(),
                'team_id' => $intake->team_id,
                'message' => 'Intake erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Intakes: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'intakes', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
