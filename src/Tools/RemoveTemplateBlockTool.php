<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchTemplateBlock;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class RemoveTemplateBlockTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.template_blocks.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /hatch/template_blocks/{id} - Entfernt einen Block aus einem Template. Die Block-Definition selbst bleibt erhalten und kann in anderen Templates weiter genutzt werden. Parameter: template_block_id (required), confirm (required=true).';
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
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'ERFORDERLICH: Setze confirm=true um den Block zu entfernen.',
                ],
            ],
            'required' => ['template_block_id', 'confirm'],
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

            if (!($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Bitte bestätige mit confirm: true.');
            }

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

            $templateBlock->load('blockDefinition:id,name');

            $blockId = (int)$templateBlock->id;
            $templateId = (int)$templateBlock->project_template_id;
            $bdName = $templateBlock->blockDefinition?->name ?? 'Unbekannt';

            $templateBlock->delete();

            return ToolResult::success([
                'template_block_id' => $blockId,
                'template_id' => $templateId,
                'block_definition_name' => $bdName,
                'message' => "Block \"{$bdName}\" aus Template entfernt. Die Block-Definition existiert weiterhin.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Entfernen des Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'template_blocks', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
