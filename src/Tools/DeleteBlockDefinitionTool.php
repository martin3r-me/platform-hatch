<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class DeleteBlockDefinitionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.block_definitions.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /hatch/block_definitions/{id} - Deaktiviert oder löscht eine Block-Definition. Parameter: block_definition_id (required), confirm (required=true), hard_delete (optional, default false = nur deaktivieren).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'block_definition_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Block-Definition (ERFORDERLICH).',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'ERFORDERLICH: Setze confirm=true um wirklich zu löschen/deaktivieren.',
                ],
                'hard_delete' => [
                    'type' => 'boolean',
                    'description' => 'Optional: true = endgültig löschen, false = nur deaktivieren (is_active=false). Default: false.',
                ],
            ],
            'required' => ['block_definition_id', 'confirm'],
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

            $found = $this->validateAndFindModel(
                $arguments,
                $context,
                'block_definition_id',
                HatchBlockDefinition::class,
                'NOT_FOUND',
                'Block-Definition nicht gefunden.'
            );
            if ($found['error']) {
                return $found['error'];
            }
            /** @var HatchBlockDefinition $bd */
            $bd = $found['model'];

            if ((int)$bd->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Block-Definition.');
            }

            $bdId = (int)$bd->id;
            $bdName = (string)$bd->name;
            $hardDelete = (bool)($arguments['hard_delete'] ?? false);

            if ($hardDelete) {
                $bd->delete();
                return ToolResult::success([
                    'block_definition_id' => $bdId,
                    'name' => $bdName,
                    'message' => 'Block-Definition endgültig gelöscht.',
                ]);
            }

            $bd->is_active = false;
            $bd->save();

            return ToolResult::success([
                'block_definition_id' => $bdId,
                'name' => $bdName,
                'is_active' => false,
                'message' => 'Block-Definition deaktiviert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Block-Definition: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'block_definitions', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
