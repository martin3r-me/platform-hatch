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
        return 'PUT /hatch/intakes/{id} - Aktualisiert einen Project Intake. Parameter: intake_id (required). Alle anderen Felder optional.';
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

            $fields = [
                'name',
                'description',
                'status',
                'is_active',
            ];

            foreach ($fields as $field) {
                if (array_key_exists($field, $arguments)) {
                    $intake->{$field} = $arguments[$field] === '' ? null : $arguments[$field];
                }
            }

            $intake->save();

            return ToolResult::success([
                'id' => $intake->id,
                'uuid' => $intake->uuid,
                'name' => $intake->name,
                'status' => $intake->status,
                'is_active' => (bool)$intake->is_active,
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
