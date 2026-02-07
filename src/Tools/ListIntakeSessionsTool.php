<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Hatch\Models\HatchIntakeSession;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class ListIntakeSessionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.intake_sessions.GET';
    }

    public function getDescription(): string
    {
        return 'GET /hatch/intake_sessions - Listet Sessions eines Intakes. Parameter: intake_id (required), team_id (optional), status (optional), filters/search/sort/limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                    ],
                    'intake_id' => [
                        'type' => 'integer',
                        'description' => 'ID des Intakes (ERFORDERLICH). Nutze "hatch.intakes.GET" um IDs zu finden.',
                    ],
                    'status' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter nach Session-Status (z.B. started, completed).',
                    ],
                ],
            ]
        );
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
                ->where('team_id', $teamId)
                ->find($intakeId);
            if (!$intake) {
                return ToolResult::error('NOT_FOUND', 'Intake nicht gefunden (oder kein Zugriff).');
            }

            $query = HatchIntakeSession::query()
                ->where('project_intake_id', $intakeId);

            if (isset($arguments['status'])) {
                $query->where('status', $arguments['status']);
            }

            $this->applyStandardFilters($query, $arguments, [
                'status',
                'respondent_name',
                'respondent_email',
                'created_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['respondent_name', 'respondent_email']);
            $this->applyStandardSort($query, $arguments, [
                'status',
                'created_at',
                'started_at',
                'completed_at',
            ], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (HatchIntakeSession $s) {
                return [
                    'id' => $s->id,
                    'uuid' => $s->uuid,
                    'session_token' => $s->session_token,
                    'status' => $s->status,
                    'respondent_name' => $s->respondent_name,
                    'respondent_email' => $s->respondent_email,
                    'current_step' => (int)$s->current_step,
                    'started_at' => $s->started_at?->toISOString(),
                    'completed_at' => $s->completed_at?->toISOString(),
                    'created_at' => $s->created_at?->toISOString(),
                    'updated_at' => $s->updated_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'intake_id' => $intakeId,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Sessions: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['hatch', 'intake_sessions', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
