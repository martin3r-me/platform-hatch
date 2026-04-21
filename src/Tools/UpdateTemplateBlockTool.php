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
        return 'PUT /hatch/template_blocks/{id} - Aktualisiert einen Block innerhalb eines Templates (Reihenfolge, Pflichtfeld, Label, Gruppen-Zugehörigkeit, Sichtbarkeitsregeln). Parameter: template_block_id (required).';
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
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Feld-Label (überschreibt den Default-Namen aus der BlockDefinition). Bei Abfrage-Headern (erster Block einer Gruppe) = Name der Abfrage.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Feld-Beschreibung/Helptext.',
                ],
                'sort_order' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Position im Template.',
                ],
                'is_required' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Pflichtfeld ja/nein.',
                ],
                'group_uuid' => [
                    'type' => 'string',
                    'description' => 'Optional: UUID der Abfrage-Gruppe. Blocks mit gleicher group_uuid gehören zur selben Abfrage (mehrere Felder). NULL/leer = Einzelblock. Beim Neuanlegen mehrerer Felder für dieselbe Abfrage: dieselbe UUID setzen.',
                ],
                'visibility_rules' => [
                    'type' => 'object',
                    'description' => 'Optional: Conditional Logic. Format: {combinator: AND|OR, rules: [{source_block_id: int, operator: equals|not_equals|contains|empty|not_empty|selected|not_selected, value: string}]}. source_block_id muss auf einen Block mit kleinerem sort_order zeigen (keine Vorwärts-Referenzen, keine Zyklen). Block wird nur angezeigt, wenn die Regeln erfüllt sind. Null = immer sichtbar.',
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

            $fields = ['name', 'description', 'sort_order', 'is_required', 'group_uuid', 'visibility_rules', 'is_active'];

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
                'name' => $templateBlock->name,
                'description' => $templateBlock->description,
                'group_uuid' => $templateBlock->group_uuid,
                'visibility_rules' => $templateBlock->visibility_rules,
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
