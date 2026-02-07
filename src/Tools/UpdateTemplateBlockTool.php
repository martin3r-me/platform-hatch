<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchTemplateBlock;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class UpdateTemplateBlockTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.template_blocks.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /hatch/template_blocks/{id} - Aktualisiert einen Block innerhalb eines Templates (Reihenfolge, Pflichtfeld, Aktiv-Status). Parameter: template_block_id (required).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'template_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Template-Blocks (ERFORDERLICH). Sichtbar in "hatch.template.GET" als template_block_id.',
                ],
                'sort_order' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Position im Template.',
                ],
                'is_required' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Pflichtfeld ja/nein.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Aktiv-Status.',
                ],
            ],
            'required' => ['template_block_id'],
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
                'template_block_id',
                HatchTemplateBlock::class,
                'NOT_FOUND',
                'Template-Block nicht gefunden.'
            );
            if ($found['error']) {
                return $found['error'];
            }
            /** @var HatchTemplateBlock $templateBlock */
            $templateBlock = $found['model'];

            if ((int)$templateBlock->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Template-Block.');
            }

            $fields = ['sort_order', 'is_required', 'is_active'];

            foreach ($fields as $field) {
                if (array_key_exists($field, $arguments)) {
                    $templateBlock->{$field} = $arguments[$field];
                }
            }

            $templateBlock->save();

            $templateBlock->load('blockDefinition:id,name,block_type');

            return ToolResult::success([
                'template_block_id' => $templateBlock->id,
                'template_id' => $templateBlock->project_template_id,
                'block_definition_id' => $templateBlock->block_definition_id,
                'block_definition_name' => $templateBlock->blockDefinition?->name,
                'sort_order' => (int)$templateBlock->sort_order,
                'is_required' => (bool)$templateBlock->is_required,
                'is_active' => (bool)$templateBlock->is_active,
                'message' => 'Template-Block erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Template-Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'template_blocks', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
