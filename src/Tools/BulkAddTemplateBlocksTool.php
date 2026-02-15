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

class BulkAddTemplateBlocksTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.template_blocks.BULK_POST';
    }

    public function getDescription(): string
    {
        return 'POST /hatch/template_blocks/bulk - Fügt mehrere Block-Definitionen zu einem Template hinzu. ERFORDERLICH: template_id, items (Array mit je block_definition_id). Maximal 50 Items pro Aufruf.';
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
                'items' => [
                    'type' => 'array',
                    'description' => 'ERFORDERLICH: Array von Template-Blocks. Jedes Item benötigt: block_definition_id. Optional: sort_order, is_required.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
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
                        'required' => ['block_definition_id'],
                    ],
                ],
            ],
            'required' => ['template_id', 'items'],
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

            $template = HatchProjectTemplate::query()
                ->where('team_id', $teamId)
                ->find($templateId);
            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Template nicht gefunden (oder kein Zugriff).');
            }

            $items = $arguments['items'] ?? [];
            if (empty($items) || !is_array($items)) {
                return ToolResult::error('VALIDATION_ERROR', 'items ist erforderlich und muss ein nicht-leeres Array sein.');
            }

            if (count($items) > 50) {
                return ToolResult::error('VALIDATION_ERROR', 'Maximal 50 Items pro Bulk-Aufruf erlaubt.');
            }

            // Aktuelle maximale sort_order ermitteln
            $maxSort = HatchTemplateBlock::query()
                ->where('project_template_id', $templateId)
                ->max('sort_order') ?? 0;

            $created = [];
            $errors = [];

            foreach ($items as $index => $item) {
                $blockDefinitionId = (int)($item['block_definition_id'] ?? 0);
                if ($blockDefinitionId <= 0) {
                    $errors[] = ['index' => $index, 'error' => 'block_definition_id ist erforderlich.'];
                    continue;
                }

                try {
                    $blockDefinition = HatchBlockDefinition::query()
                        ->where('team_id', $teamId)
                        ->find($blockDefinitionId);
                    if (!$blockDefinition) {
                        $errors[] = ['index' => $index, 'block_definition_id' => $blockDefinitionId, 'error' => 'Block-Definition nicht gefunden (oder kein Zugriff).'];
                        continue;
                    }

                    $sortOrder = $item['sort_order'] ?? null;
                    if ($sortOrder === null) {
                        $maxSort++;
                        $sortOrder = $maxSort;
                    }

                    $templateBlock = HatchTemplateBlock::create([
                        'project_template_id' => $templateId,
                        'block_definition_id' => $blockDefinitionId,
                        'sort_order' => (int)$sortOrder,
                        'is_required' => (bool)($item['is_required'] ?? true),
                        'is_active' => true,
                        'created_by_user_id' => $context->user->id,
                        'team_id' => $teamId,
                    ]);

                    $created[] = [
                        'index' => $index,
                        'template_block_id' => $templateBlock->id,
                        'uuid' => $templateBlock->uuid,
                        'block_definition_id' => $blockDefinitionId,
                        'block_definition_name' => $blockDefinition->name,
                        'sort_order' => (int)$templateBlock->sort_order,
                        'is_required' => (bool)$templateBlock->is_required,
                    ];
                } catch (\Throwable $e) {
                    $errors[] = ['index' => $index, 'block_definition_id' => $blockDefinitionId, 'error' => $e->getMessage()];
                }
            }

            return ToolResult::success([
                'template_id' => $templateId,
                'template_name' => $template->name,
                'created_count' => count($created),
                'error_count' => count($errors),
                'created' => $created,
                'errors' => $errors,
                'message' => count($created) . ' Block(s) zum Template "' . $template->name . '" hinzugefügt, ' . count($errors) . ' Fehler.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Hinzufügen von Template-Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'template_blocks', 'bulk', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
