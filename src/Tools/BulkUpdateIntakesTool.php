<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class BulkUpdateIntakesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.intakes.BULK_PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /hatch/intakes/bulk - Aktualisiert mehrere Project Intakes in einem Aufruf. ERFORDERLICH: items (Array mit je intake_id). Maximal 50 Items pro Aufruf.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'items' => [
                    'type' => 'array',
                    'description' => 'ERFORDERLICH: Array von Updates. Jedes Item benötigt: intake_id. Optional: name, description, status, is_active, started_at.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'intake_id' => [
                                'type' => 'integer',
                                'description' => 'ID des Intakes (ERFORDERLICH). Nutze "hatch.intakes.GET".',
                            ],
                            'name' => [
                                'type' => 'string',
                                'description' => 'Optional: Neuer Name.',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Optional: Neue Beschreibung.',
                            ],
                            'status' => [
                                'type' => 'string',
                                'description' => 'Optional: Neuer Status.',
                            ],
                            'is_active' => [
                                'type' => 'boolean',
                                'description' => 'Optional: Aktiv-Status.',
                            ],
                            'started_at' => [
                                'type' => 'string',
                                'description' => 'Optional: Startzeitpunkt (ISO 8601). Wird automatisch gesetzt beim Status-Wechsel auf "in_progress", falls leer.',
                            ],
                        ],
                        'required' => ['intake_id'],
                    ],
                ],
            ],
            'required' => ['items'],
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

            $items = $arguments['items'] ?? [];
            if (empty($items) || !is_array($items)) {
                return ToolResult::error('VALIDATION_ERROR', 'items ist erforderlich und muss ein nicht-leeres Array sein.');
            }

            if (count($items) > 50) {
                return ToolResult::error('VALIDATION_ERROR', 'Maximal 50 Items pro Bulk-Aufruf erlaubt.');
            }

            $fields = ['name', 'description', 'status', 'is_active', 'started_at'];
            $updated = [];
            $errors = [];

            foreach ($items as $index => $item) {
                $intakeId = (int)($item['intake_id'] ?? 0);
                if ($intakeId <= 0) {
                    $errors[] = ['index' => $index, 'error' => 'intake_id ist erforderlich.'];
                    continue;
                }

                try {
                    $intake = HatchProjectIntake::query()
                        ->where('team_id', $teamId)
                        ->find($intakeId);

                    if (!$intake) {
                        $errors[] = ['index' => $index, 'intake_id' => $intakeId, 'error' => 'Intake nicht gefunden oder kein Zugriff.'];
                        continue;
                    }

                    foreach ($fields as $field) {
                        if (array_key_exists($field, $item)) {
                            $intake->{$field} = $item[$field] === '' ? null : $item[$field];
                        }
                    }

                    // Auto-set started_at when status changes to 'in_progress' and started_at is still null
                    if (
                        $intake->isDirty('status')
                        && $intake->status === 'in_progress'
                        && empty($intake->started_at)
                    ) {
                        $intake->started_at = now();
                    }

                    $intake->save();

                    $updated[] = [
                        'index' => $index,
                        'id' => $intake->id,
                        'uuid' => $intake->uuid,
                        'name' => $intake->name,
                        'status' => $intake->status,
                        'is_active' => (bool)$intake->is_active,
                        'started_at' => $intake->started_at?->toISOString(),
                    ];
                } catch (\Throwable $e) {
                    $errors[] = ['index' => $index, 'intake_id' => $intakeId, 'error' => $e->getMessage()];
                }
            }

            return ToolResult::success([
                'updated_count' => count($updated),
                'error_count' => count($errors),
                'updated' => $updated,
                'errors' => $errors,
                'message' => count($updated) . ' Intake(s) aktualisiert, ' . count($errors) . ' Fehler.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Aktualisieren der Intakes: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'intakes', 'bulk', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
