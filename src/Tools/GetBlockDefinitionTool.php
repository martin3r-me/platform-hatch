<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class GetBlockDefinitionTool implements ToolContract, ToolMetadataContract
{
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.block_definition.GET';
    }

    public function getDescription(): string
    {
        return 'GET /hatch/block_definitions/{id} - Ruft eine einzelne Block-Definition ab (inkl. AI-Konfiguration). Parameter: block_definition_id (required), team_id (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'block_definition_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Block-Definition (ERFORDERLICH). Nutze "hatch.block_definitions.GET" um IDs zu finden.',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
            ],
            'required' => ['block_definition_id'],
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

            $blockDefinitionId = (int)($arguments['block_definition_id'] ?? 0);
            if ($blockDefinitionId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'block_definition_id ist erforderlich.');
            }

            $bd = HatchBlockDefinition::query()
                ->withCount('templateBlocks')
                ->where('team_id', $teamId)
                ->find($blockDefinitionId);

            if (!$bd) {
                return ToolResult::error('NOT_FOUND', 'Block-Definition nicht gefunden (oder kein Zugriff).');
            }

            return ToolResult::success([
                'id' => $bd->id,
                'uuid' => $bd->uuid,
                'name' => $bd->name,
                'description' => $bd->description,
                'block_type' => $bd->block_type,
                'block_type_label' => $bd->getBlockTypeLabel(),
                'ai_prompt' => $bd->ai_prompt,
                'conditional_logic' => $bd->conditional_logic,
                'response_format' => $bd->response_format,
                'fallback_questions' => $bd->fallback_questions,
                'validation_rules' => $bd->validation_rules,
                'logic_config' => $bd->logic_config,
                'ai_behavior' => $bd->ai_behavior,
                'exit_conditions' => $bd->exit_conditions,
                'min_confidence_threshold' => (float)$bd->min_confidence_threshold,
                'max_clarification_attempts' => (int)$bd->max_clarification_attempts,
                'max_messages_per_block' => $bd->max_messages_per_block,
                'is_active' => (bool)$bd->is_active,
                'template_blocks_count' => $bd->template_blocks_count,
                'team_id' => $bd->team_id,
                'created_at' => $bd->created_at?->toISOString(),
                'updated_at' => $bd->updated_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Block-Definition: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['hatch', 'block_definition', 'get'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
