<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class DeleteIntakeTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.intakes.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /hatch/intakes/{id} - Deaktiviert oder löscht einen Project Intake. Parameter: intake_id (required), confirm (required=true), hard_delete (optional, default false = nur deaktivieren).';
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
                    'description' => 'ID des Intakes (ERFORDERLICH).',
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
            'required' => ['intake_id', 'confirm'],
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

            $intakeId = (int)$intake->id;
            $intakeName = (string)$intake->name;
            $hardDelete = (bool)($arguments['hard_delete'] ?? false);

            if ($hardDelete) {
                $intake->delete();
                return ToolResult::success([
                    'intake_id' => $intakeId,
                    'name' => $intakeName,
                    'message' => 'Intake endgültig gelöscht.',
                ]);
            }

            $intake->is_active = false;
            $intake->save();

            return ToolResult::success([
                'intake_id' => $intakeId,
                'name' => $intakeName,
                'is_active' => false,
                'message' => 'Intake deaktiviert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Intakes: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'intakes', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
