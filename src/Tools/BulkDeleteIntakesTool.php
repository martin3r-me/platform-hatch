<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class BulkDeleteIntakesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.intakes.BULK_DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /hatch/intakes/bulk - Deaktiviert oder löscht mehrere Project Intakes in einem Aufruf. ERFORDERLICH: items (Array mit je intake_id), confirm=true. Maximal 50 Items pro Aufruf.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'ERFORDERLICH: Setze confirm=true um wirklich zu löschen/deaktivieren.',
                ],
                'hard_delete' => [
                    'type' => 'boolean',
                    'description' => 'Optional: true = endgültig löschen, false = nur deaktivieren (is_active=false). Default: false. Gilt für alle Items.',
                ],
                'items' => [
                    'type' => 'array',
                    'description' => 'ERFORDERLICH: Array von Intakes zum Löschen/Deaktivieren. Jedes Item benötigt: intake_id.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'intake_id' => [
                                'type' => 'integer',
                                'description' => 'ID des Intakes (ERFORDERLICH).',
                            ],
                        ],
                        'required' => ['intake_id'],
                    ],
                ],
            ],
            'required' => ['items', 'confirm'],
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

            if (!($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Bitte bestätige mit confirm: true.');
            }

            $items = $arguments['items'] ?? [];
            if (empty($items) || !is_array($items)) {
                return ToolResult::error('VALIDATION_ERROR', 'items ist erforderlich und muss ein nicht-leeres Array sein.');
            }

            if (count($items) > 50) {
                return ToolResult::error('VALIDATION_ERROR', 'Maximal 50 Items pro Bulk-Aufruf erlaubt.');
            }

            $hardDelete = (bool)($arguments['hard_delete'] ?? false);
            $deleted = [];
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

                    $intakeName = (string)$intake->name;

                    if ($hardDelete) {
                        $intake->delete();
                        $deleted[] = [
                            'index' => $index,
                            'intake_id' => $intakeId,
                            'name' => $intakeName,
                            'action' => 'hard_deleted',
                        ];
                    } else {
                        $intake->is_active = false;
                        $intake->save();
                        $deleted[] = [
                            'index' => $index,
                            'intake_id' => $intakeId,
                            'name' => $intakeName,
                            'action' => 'deactivated',
                        ];
                    }
                } catch (\Throwable $e) {
                    $errors[] = ['index' => $index, 'intake_id' => $intakeId, 'error' => $e->getMessage()];
                }
            }

            $actionLabel = $hardDelete ? 'gelöscht' : 'deaktiviert';

            return ToolResult::success([
                'deleted_count' => count($deleted),
                'error_count' => count($errors),
                'deleted' => $deleted,
                'errors' => $errors,
                'message' => count($deleted) . ' Intake(s) ' . $actionLabel . ', ' . count($errors) . ' Fehler.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Löschen der Intakes: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'intakes', 'bulk', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
