<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class ListBlockDefinitionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.block_definitions.GET';
    }

    public function getDescription(): string
    {
        return 'GET /hatch/block_definitions - Listet Block-Definitionen. Parameter: team_id (optional), block_type (optional), is_active (optional), filters/search/sort/limit/offset (optional).';
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
                    'block_type' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter nach Block-Typ (text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, info, custom, matrix, ranking, nps, dropdown, datetime, time, slider, image_choice, consent, section, hidden, address, color, lookup, signature, date_range, calculated, repeater).',
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'Optional: nur aktive/inaktive Block-Definitionen.',
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

            $query = HatchBlockDefinition::query()
                ->withCount('templateBlocks')
                ->forTeam($teamId);

            if (isset($arguments['block_type'])) {
                $query->byType($arguments['block_type']);
            }
            if (isset($arguments['is_active'])) {
                $query->where('is_active', (bool)$arguments['is_active']);
            }

            $this->applyStandardFilters($query, $arguments, [
                'name',
                'block_type',
                'is_active',
                'created_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'name',
                'block_type',
                'created_at',
                'updated_at',
            ], 'name', 'asc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (HatchBlockDefinition $bd) {
                return [
                    'id' => $bd->id,
                    'uuid' => $bd->uuid,
                    'name' => $bd->name,
                    'description' => $bd->description,
                    'block_type' => $bd->block_type,
                    'block_type_label' => $bd->getBlockTypeLabel(),
                    'is_active' => (bool)$bd->is_active,
                    'template_blocks_count' => $bd->template_blocks_count,
                    'team_id' => $bd->team_id,
                    'created_at' => $bd->created_at?->toISOString(),
                    'updated_at' => $bd->updated_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
                'available_block_types' => array_keys(HatchBlockDefinition::getBlockTypes()),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Block-Definitionen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['hatch', 'block_definitions', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
