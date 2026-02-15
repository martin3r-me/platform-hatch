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
        return 'PUT /hatch/intakes/{id} - Aktualisiert einen Project Intake. Parameter: intake_id (required). Status-Modell: draft → published → closed. Beim Wechsel auf "published" wird started_at automatisch gesetzt, beim Wechsel auf "closed" wird completed_at gesetzt.';
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
                    'enum' => ['draft', 'published', 'closed'],
                    'description' => 'Optional: Neuer Status (draft, published, closed). "published" = Erhebung live, "closed" = Erhebung beendet.',
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
