<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchTemplateBlock;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class BulkUpdateTemplateBlocksTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.template_blocks.BULK_PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /hatch/template_blocks/bulk - Aktualisiert mehrere Template-Blocks in einem Aufruf (Reihenfolge, Pflichtfeld, Aktiv-Status). ERFORDERLICH: items (Array mit je template_block_id). Maximal 50 Items pro Aufruf.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'items' => [
                    'type' => 'array',
                    'description' => 'ERFORDERLICH: Array von Updates. Jedes Item benötigt: template_block_id. Optional: sort_order, is_required, is_active.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
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
                    ],
                ],
            ],
            'required' => ['items'],
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

            $items = $arguments['items'] ?? [];
            if (empty($items) || !is_array($items)) {
                return ToolResult::error('VALIDATION_ERROR', 'items ist erforderlich und muss ein nicht-leeres Array sein.');
            }

            if (count($items) > 50) {
                return ToolResult::error('VALIDATION_ERROR', 'Maximal 50 Items pro Bulk-Aufruf erlaubt.');
            }

            $fields = ['sort_order', 'is_required', 'is_active'];
            $updated = [];
            $errors = [];

            foreach ($items as $index => $item) {
                $tbId = (int)($item['template_block_id'] ?? 0);
                if ($tbId <= 0) {
                    $errors[] = ['index' => $index, 'error' => 'template_block_id ist erforderlich.'];
                    continue;
                }

                try {
                    $templateBlock = HatchTemplateBlock::query()
                        ->where('team_id', $teamId)
                        ->find($tbId);

                    if (!$templateBlock) {
                        $errors[] = ['index' => $index, 'template_block_id' => $tbId, 'error' => 'Template-Block nicht gefunden oder kein Zugriff.'];
                        continue;
                    }

                    foreach ($fields as $field) {
                        if (array_key_exists($field, $item)) {
                            $templateBlock->{$field} = $item[$field];
                        }
                    }

                    $templateBlock->save();
                    $templateBlock->load('blockDefinition:id,name,block_type');

                    $updated[] = [
                        'index' => $index,
                        'template_block_id' => $templateBlock->id,
                        'template_id' => $templateBlock->project_template_id,
                        'block_definition_id' => $templateBlock->block_definition_id,
                        'block_definition_name' => $templateBlock->blockDefinition?->name,
                        'sort_order' => (int)$templateBlock->sort_order,
                        'is_required' => (bool)$templateBlock->is_required,
                        'is_active' => (bool)$templateBlock->is_active,
                    ];
                } catch (\Throwable $e) {
                    $errors[] = ['index' => $index, 'template_block_id' => $tbId, 'error' => $e->getMessage()];
                }
            }

            return ToolResult::success([
                'updated_count' => count($updated),
                'error_count' => count($errors),
                'updated' => $updated,
                'errors' => $errors,
                'message' => count($updated) . ' Template-Block(s) aktualisiert, ' . count($errors) . ' Fehler.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Aktualisieren der Template-Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'template_blocks', 'bulk', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
