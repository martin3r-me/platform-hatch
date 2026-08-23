<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Enums\HatchBlockType;
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
        return 'PUT /hatch/template_blocks/bulk - Aktualisiert mehrere Template-Blocks in einem Aufruf (Reihenfolge, Label, Gruppen-Zugehörigkeit, Sichtbarkeitsregeln, Pflichtfeld, Aktiv-Status). ERFORDERLICH: items (Array mit je template_block_id). Maximal 50 Items pro Aufruf.';
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
                    'description' => 'ERFORDERLICH: Array von Updates. Jedes Item benötigt: template_block_id. Optional: name, description, sort_order, is_required, group_uuid, visibility_rules, is_active.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'template_block_id' => [
                                'type' => 'integer',
                                'description' => 'ID des Template-Blocks (ERFORDERLICH). Sichtbar in "hatch.template.GET" als template_block_id.',
                            ],
                            'block_type' => [
                                'type' => 'string',
                                'description' => 'Optional: Neuer Block-Typ. Erlaubt: text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, info, custom, matrix, ranking, nps, dropdown, datetime, time, slider, image_choice, consent, section, hidden, address, color, lookup, signature, date_range, calculated, repeater.',
                            ],
                            'name' => [
                                'type' => 'string',
                                'description' => 'Optional: Feld-Label / Abfrage-Name (am Abfrage-Header).',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Optional: Feld-Beschreibung / Abfrage-Beschreibung.',
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
                                'description' => 'Optional: UUID der Abfrage-Gruppe. Null = Einzelblock.',
                            ],
                            'visibility_rules' => [
                                'type' => 'object',
                                'description' => 'Optional: Conditional Logic — {combinator: AND|OR, rules: [{source_block_id, operator, value}]}. Null = immer sichtbar.',
                            ],
                            'display_compact' => [
                                'type' => 'boolean',
                                'description' => 'Optional: Wirkt nur bei flow_mode = "overview". true = Gruppe als kompakte Tabellenzeile rendern.',
                            ],
                            'is_active' => [
                                'type' => 'boolean',
                                'description' => 'Optional: Aktiv-Status.',
                            ],
                            'ai_prompt' => [
                                'type' => 'string',
                                'description' => 'Optional: Neuer AI-Prompt.',
                            ],
                            'conditional_logic' => [
                                'type' => 'object',
                                'description' => 'Optional: Neue bedingte Logik (AI-Gesprächsführung).',
                            ],
                            'response_format' => [
                                'type' => 'object',
                                'description' => 'Optional: Neues Antwortformat.',
                            ],
                            'fallback_questions' => [
                                'type' => 'object',
                                'description' => 'Optional: Neue Fallback-Fragen.',
                            ],
                            'validation_rules' => [
                                'type' => 'object',
                                'description' => 'Optional: Neue Validierungsregeln.',
                            ],
                            'logic_config' => [
                                'type' => 'object',
                                'description' => 'Optional: Neue Typ-spezifische Konfiguration als JSON. Siehe "hatch.overview.GET" für die vollständige Referenz je block_type.',
                            ],
                            'ai_behavior' => [
                                'type' => 'object',
                                'description' => 'Optional: Neue AI-Verhaltenskonfiguration.',
                            ],
                            'exit_conditions' => [
                                'type' => 'object',
                                'description' => 'Optional: Neue Abbruch-/Exit-Bedingungen für die AI-Konversation.',
                            ],
                            'min_confidence_threshold' => [
                                'type' => 'number',
                                'description' => 'Optional: Neue Mindest-Konfidenz (0.00-1.00).',
                            ],
                            'max_clarification_attempts' => [
                                'type' => 'integer',
                                'description' => 'Optional: Neue maximale Anzahl an Nachfragen der AI bei Unklarheit.',
                            ],
                            'max_messages_per_block' => [
                                'type' => 'integer',
                                'description' => 'Optional: Neue maximale Anzahl an Nachrichten für diesen Block.',
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

            $fields = [
                'name',
                'description',
                'sort_order',
                'is_required',
                'group_uuid',
                'visibility_rules',
                'display_compact',
                'is_active',
                'block_type',
                'ai_prompt',
                'conditional_logic',
                'response_format',
                'fallback_questions',
                'validation_rules',
                'logic_config',
                'ai_behavior',
                'exit_conditions',
                'min_confidence_threshold',
                'max_clarification_attempts',
                'max_messages_per_block',
            ];
            $validTypes = HatchBlockType::values();
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

                    if (isset($item['block_type']) && !in_array($item['block_type'], $validTypes)) {
                        $errors[] = ['index' => $index, 'template_block_id' => $tbId, 'error' => 'Ungültiger block_type. Erlaubt: ' . implode(', ', $validTypes)];
                        continue;
                    }

                    foreach ($fields as $field) {
                        if (array_key_exists($field, $item)) {
                            $templateBlock->{$field} = $item[$field];
                        }
                    }

                    $templateBlock->save();

                    $updated[] = [
                        'index' => $index,
                        'template_block_id' => $templateBlock->id,
                        'template_id' => $templateBlock->project_template_id,
                        'block_type' => $templateBlock->block_type,
                        'name' => $templateBlock->name,
                        'group_uuid' => $templateBlock->group_uuid,
                        'sort_order' => (int)$templateBlock->sort_order,
                        'is_required' => (bool)$templateBlock->is_required,
                        'display_compact' => (bool)$templateBlock->display_compact,
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
