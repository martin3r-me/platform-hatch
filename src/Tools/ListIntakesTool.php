<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class ListIntakesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.intakes.GET';
    }

    public function getDescription(): string
    {
        return 'GET /hatch/intakes - Listet Project Intakes. Parameter: team_id (optional), status (optional), template_id (optional), is_active (optional), filters/search/sort/limit/offset (optional).';
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
                    'status' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter nach Status (z.B. draft, active, completed).',
                    ],
                    'template_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Projekt-Template.',
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'Optional: nur aktive/inaktive Intakes.',
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

            $query = HatchProjectIntake::query()
                ->with('projectTemplate:id,name')
                ->withCount('intakeSteps', 'sessions')
                ->forTeam($teamId);

            if (isset($arguments['status'])) {
                $query->byStatus($arguments['status']);
            }
            if (isset($arguments['template_id'])) {
                $query->where('project_template_id', (int)$arguments['template_id']);
            }
            if (isset($arguments['is_active'])) {
                $query->where('is_active', (bool)$arguments['is_active']);
            }

            $this->applyStandardFilters($query, $arguments, [
                'name',
                'status',
                'is_active',
                'project_template_id',
                'created_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'name',
                'status',
                'created_at',
                'updated_at',
            ], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (HatchProjectIntake $i) {
                return [
                    'id' => $i->id,
                    'uuid' => $i->uuid,
                    'name' => $i->name,
                    'description' => $i->description,
                    'status' => $i->status,
                    'project_template_id' => $i->project_template_id,
                    'project_template_name' => $i->projectTemplate?->name,
                    'public_token' => $i->public_token,
                    'public_url' => $i->getPublicUrl(),
                    'is_active' => (bool)$i->is_active,
                    'intake_steps_count' => $i->intake_steps_count,
                    'sessions_count' => $i->sessions_count,
                    'ai_confidence_score' => (float)$i->ai_confidence_score,
                    'team_id' => $i->team_id,
                    'started_at' => $i->started_at?->toISOString(),
                    'completed_at' => $i->completed_at?->toISOString(),
                    'created_at' => $i->created_at?->toISOString(),
                    'updated_at' => $i->updated_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Intakes: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['hatch', 'intakes', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
