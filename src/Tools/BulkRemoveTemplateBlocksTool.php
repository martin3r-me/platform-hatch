<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchTemplateBlock;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class BulkRemoveTemplateBlocksTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.template_blocks.BULK_DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /hatch/template_blocks/bulk - Entfernt mehrere Blocks aus einem Template. Die Block-Definitionen bleiben erhalten. ERFORDERLICH: items (Array mit je template_block_id), confirm=true. Maximal 50 Items pro Aufruf.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'ERFORDERLICH: Setze confirm=true um die Blocks zu entfernen.',
                ],
                'items' => [
                    'type' => 'array',
                    'description' => 'ERFORDERLICH: Array von Template-Blocks zum Entfernen. Jedes Item benötigt: template_block_id.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'template_block_id' => [
                                'type' => 'integer',
                                'description' => 'ID des Template-Blocks (ERFORDERLICH). Sichtbar in "hatch.template.GET" als template_block_id.',
                            ],
                        ],
                        'required' => ['template_block_id'],
                    ],
                ],
            ],
            'required' => ['items', 'confirm'],
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

            $items = $arguments['items'] ?? [];
            if (empty($items) || !is_array($items)) {
                return ToolResult::error('VALIDATION_ERROR', 'items ist erforderlich und muss ein nicht-leeres Array sein.');
            }

            if (count($items) > 50) {
                return ToolResult::error('VALIDATION_ERROR', 'Maximal 50 Items pro Bulk-Aufruf erlaubt.');
            }

            $deleted = [];
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
                        ->with('blockDefinition:id,name')
                        ->find($tbId);

                    if (!$templateBlock) {
                        $errors[] = ['index' => $index, 'template_block_id' => $tbId, 'error' => 'Template-Block nicht gefunden oder kein Zugriff.'];
                        continue;
                    }

                    $bdName = $templateBlock->blockDefinition?->name ?? 'Unbekannt';
                    $templateId = (int)$templateBlock->project_template_id;

                    $templateBlock->delete();

                    $deleted[] = [
                        'index' => $index,
                        'template_block_id' => $tbId,
                        'template_id' => $templateId,
                        'block_definition_name' => $bdName,
                    ];
                } catch (\Throwable $e) {
                    $errors[] = ['index' => $index, 'template_block_id' => $tbId, 'error' => $e->getMessage()];
                }
            }

            return ToolResult::success([
                'deleted_count' => count($deleted),
                'error_count' => count($errors),
                'deleted' => $deleted,
                'errors' => $errors,
                'message' => count($deleted) . ' Template-Block(s) entfernt, ' . count($errors) . ' Fehler. Die Block-Definitionen existieren weiterhin.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Entfernen der Template-Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'template_blocks', 'bulk', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
