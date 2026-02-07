<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class ListTemplatesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.templates.GET';
    }

    public function getDescription(): string
    {
        return 'GET /hatch/templates - Listet Projekt-Templates. Parameter: team_id (optional), is_active (optional), complexity_level (optional: simple|medium|complex), industry_context (optional), filters/search/sort/limit/offset (optional).';
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
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'Optional: nur aktive/inaktive Templates.',
                    ],
                    'complexity_level' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter nach Komplexität (simple, medium, complex).',
                    ],
                    'industry_context' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter nach Branchenkontext.',
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

            $query = HatchProjectTemplate::query()
                ->withCount('templateBlocks', 'projectIntakes')
                ->forTeam($teamId);

            if (isset($arguments['is_active'])) {
                $query->where('is_active', (bool)$arguments['is_active']);
            }
            if (isset($arguments['complexity_level'])) {
                $query->byComplexity($arguments['complexity_level']);
            }
            if (isset($arguments['industry_context'])) {
                $query->byIndustry($arguments['industry_context']);
            }

            $this->applyStandardFilters($query, $arguments, [
                'name',
                'is_active',
                'complexity_level',
                'industry_context',
                'created_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'name',
                'complexity_level',
                'created_at',
                'updated_at',
            ], 'name', 'asc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (HatchProjectTemplate $t) {
                return [
                    'id' => $t->id,
                    'uuid' => $t->uuid,
                    'name' => $t->name,
                    'description' => $t->description,
                    'ai_personality' => $t->ai_personality,
                    'industry_context' => $t->industry_context,
                    'complexity_level' => $t->complexity_level,
                    'is_active' => (bool)$t->is_active,
                    'template_blocks_count' => $t->template_blocks_count,
                    'project_intakes_count' => $t->project_intakes_count,
                    'team_id' => $t->team_id,
                    'created_at' => $t->created_at?->toISOString(),
                    'updated_at' => $t->updated_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Templates: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['hatch', 'templates', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
