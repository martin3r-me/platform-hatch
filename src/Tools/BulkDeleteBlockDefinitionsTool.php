<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class BulkDeleteBlockDefinitionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.block_definitions.BULK_DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /hatch/block_definitions/bulk - Deaktiviert oder löscht mehrere Block-Definitionen in einem Aufruf. ERFORDERLICH: items (Array mit je block_definition_id), confirm=true. Maximal 50 Items pro Aufruf.';
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
                    'description' => 'ERFORDERLICH: Array von Block-Definitionen zum Löschen/Deaktivieren. Jedes Item benötigt: block_definition_id.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'block_definition_id' => [
                                'type' => 'integer',
                                'description' => 'ID der Block-Definition (ERFORDERLICH).',
                            ],
                        ],
                        'required' => ['block_definition_id'],
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
                $bdId = (int)($item['block_definition_id'] ?? 0);
                if ($bdId <= 0) {
                    $errors[] = ['index' => $index, 'error' => 'block_definition_id ist erforderlich.'];
                    continue;
                }

                try {
                    $bd = HatchBlockDefinition::query()
                        ->where('team_id', $teamId)
                        ->find($bdId);

                    if (!$bd) {
                        $errors[] = ['index' => $index, 'block_definition_id' => $bdId, 'error' => 'Block-Definition nicht gefunden oder kein Zugriff.'];
                        continue;
                    }

                    $bdName = (string)$bd->name;

                    if ($hardDelete) {
                        $bd->delete();
                        $deleted[] = [
                            'index' => $index,
                            'block_definition_id' => $bdId,
                            'name' => $bdName,
                            'action' => 'hard_deleted',
                        ];
                    } else {
                        $bd->is_active = false;
                        $bd->save();
                        $deleted[] = [
                            'index' => $index,
                            'block_definition_id' => $bdId,
                            'name' => $bdName,
                            'action' => 'deactivated',
                        ];
                    }
                } catch (\Throwable $e) {
                    $errors[] = ['index' => $index, 'block_definition_id' => $bdId, 'error' => $e->getMessage()];
                }
            }

            $actionLabel = $hardDelete ? 'gelöscht' : 'deaktiviert';

            return ToolResult::success([
                'deleted_count' => count($deleted),
                'error_count' => count($errors),
                'deleted' => $deleted,
                'errors' => $errors,
                'message' => count($deleted) . ' Block-Definition(en) ' . $actionLabel . ', ' . count($errors) . ' Fehler.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Löschen der Block-Definitionen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'block_definitions', 'bulk', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
