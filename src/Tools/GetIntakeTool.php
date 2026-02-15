<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class GetIntakeTool implements ToolContract, ToolMetadataContract
{
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.intake.GET';
    }

    public function getDescription(): string
    {
        return 'GET /hatch/intakes/{id} - Ruft einen einzelnen Project Intake ab (inkl. Steps, Sessions-Anzahl, Template-Info). Parameter: intake_id (required), team_id (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'intake_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Intakes (ERFORDERLICH). Nutze "hatch.intakes.GET" um IDs zu finden.',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
            ],
            'required' => ['intake_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $intakeId = (int)($arguments['intake_id'] ?? 0);
            if ($intakeId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'intake_id ist erforderlich.');
            }

            $intake = HatchProjectIntake::query()
                ->with([
                    'projectTemplate:id,name,complexity_level',
                    'intakeSteps.blockDefinition:id,name,block_type',
                ])
                ->withCount('sessions')
                ->where('team_id', $teamId)
                ->find($intakeId);

            if (!$intake) {
                return ToolResult::error('NOT_FOUND', 'Intake nicht gefunden (oder kein Zugriff).');
            }

            return ToolResult::success([
                'id' => $intake->id,
                'uuid' => $intake->uuid,
                'name' => $intake->name,
                'description' => $intake->description,
                'status' => $intake->status,
                'project_template' => $intake->projectTemplate ? [
                    'id' => $intake->projectTemplate->id,
                    'name' => $intake->projectTemplate->name,
                    'complexity_level' => $intake->projectTemplate->complexity_level,
                ] : null,
                'public_token' => $intake->public_token,
                'public_url' => $intake->getPublicUrl(),
                'workflow_status' => $intake->workflow_status,
                'current_step' => $intake->current_step,
                'ai_confidence_score' => (float)$intake->ai_confidence_score,
                'sessions_count' => $intake->sessions_count,
                'steps' => $intake->intakeSteps->map(fn ($s) => [
                    'id' => $s->id,
                    'uuid' => $s->uuid,
                    'block_definition' => $s->blockDefinition ? [
                        'id' => $s->blockDefinition->id,
                        'name' => $s->blockDefinition->name,
                        'block_type' => $s->blockDefinition->block_type,
                    ] : null,
                    'is_completed' => (bool)$s->is_completed,
                    'ai_confidence' => (float)$s->ai_confidence,
                    'message_count' => (int)$s->message_count,
                    'exit_reason' => $s->exit_reason,
                    'completed_at' => $s->completed_at?->toISOString(),
                ])->values()->toArray(),
                'team_id' => $intake->team_id,
                'started_at' => $intake->started_at?->toISOString(),
                'completed_at' => $intake->completed_at?->toISOString(),
                'created_at' => $intake->created_at?->toISOString(),
                'updated_at' => $intake->updated_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Intakes: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['hatch', 'intake', 'get'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
