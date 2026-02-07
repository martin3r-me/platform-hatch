<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchTemplateBlock;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class AddTemplateBlockTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.template_blocks.POST';
    }

    public function getDescription(): string
    {
        return 'POST /hatch/template_blocks - Fügt eine Block-Definition zu einem Template hinzu. ERFORDERLICH: template_id, block_definition_id. Optional: sort_order, is_required. Tipp: Block-Definitionen sind wiederverwendbar und können in mehreren Templates genutzt werden.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'template_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Templates (ERFORDERLICH). Nutze "hatch.templates.GET".',
                ],
                'block_definition_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Block-Definition (ERFORDERLICH). Nutze "hatch.block_definitions.GET".',
                ],
                'sort_order' => [
                    'type' => 'integer',
                    'description' => 'Optional: Position im Template. Default: wird automatisch ans Ende gesetzt.',
                ],
                'is_required' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ist dieser Block Pflicht? Default: true.',
                ],
            ],
            'required' => ['template_id', 'block_definition_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $templateId = (int)($arguments['template_id'] ?? 0);
            if ($templateId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'template_id ist erforderlich.');
            }

            $blockDefinitionId = (int)($arguments['block_definition_id'] ?? 0);
            if ($blockDefinitionId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'block_definition_id ist erforderlich.');
            }

            $template = HatchProjectTemplate::query()
                ->where('team_id', $teamId)
                ->find($templateId);
            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Template nicht gefunden (oder kein Zugriff).');
            }

            $blockDefinition = HatchBlockDefinition::query()
                ->where('team_id', $teamId)
                ->find($blockDefinitionId);
            if (!$blockDefinition) {
                return ToolResult::error('NOT_FOUND', 'Block-Definition nicht gefunden (oder kein Zugriff).');
            }

            // Sort-Order: wenn nicht angegeben, ans Ende setzen
            $sortOrder = $arguments['sort_order'] ?? null;
            if ($sortOrder === null) {
                $maxSort = HatchTemplateBlock::query()
                    ->where('project_template_id', $templateId)
                    ->max('sort_order');
                $sortOrder = ($maxSort ?? 0) + 1;
            }

            $templateBlock = HatchTemplateBlock::create([
                'project_template_id' => $templateId,
                'block_definition_id' => $blockDefinitionId,
                'sort_order' => (int)$sortOrder,
                'is_required' => (bool)($arguments['is_required'] ?? true),
                'is_active' => true,
                'created_by_user_id' => $context->user->id,
                'team_id' => $teamId,
            ]);

            return ToolResult::success([
                'template_block_id' => $templateBlock->id,
                'uuid' => $templateBlock->uuid,
                'template_id' => $templateId,
                'template_name' => $template->name,
                'block_definition_id' => $blockDefinitionId,
                'block_definition_name' => $blockDefinition->name,
                'block_type' => $blockDefinition->block_type,
                'sort_order' => (int)$templateBlock->sort_order,
                'is_required' => (bool)$templateBlock->is_required,
                'message' => "Block \"{$blockDefinition->name}\" zum Template \"{$template->name}\" hinzugefügt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Hinzufügen des Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'template_blocks', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
