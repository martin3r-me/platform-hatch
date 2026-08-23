<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Enums\HatchBlockType;
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
        return 'POST /hatch/template_blocks/bulk - Fügt mehrere Blocks zu einem Template hinzu. ERFORDERLICH: template_id, items (Array mit je block_type). Optional pro Item: name, description, sort_order, is_required, group_uuid (für Multi-Feld-Abfragen — Items mit gleicher UUID bilden eine Abfrage), visibility_rules (Conditional Logic), ai_prompt, conditional_logic, response_format, fallback_questions, validation_rules, logic_config, ai_behavior, exit_conditions, min_confidence_threshold, max_clarification_attempts, max_messages_per_block. Maximal 50 Items pro Aufruf.';
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
                    'description' => 'ERFORDERLICH: Array von Template-Blocks. Jedes Item benötigt: block_type. Optional: name, description, sort_order, is_required, group_uuid, visibility_rules, ai_prompt, conditional_logic, response_format, fallback_questions, validation_rules, logic_config, ai_behavior, exit_conditions, min_confidence_threshold, max_clarification_attempts, max_messages_per_block. Für eine Multi-Feld-Abfrage: alle Felder dieser Abfrage mit derselben group_uuid übergeben.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'block_type' => [
                                'type' => 'string',
                                'description' => 'Block-Typ (ERFORDERLICH). Erlaubt: text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, info, custom, matrix, ranking, nps, dropdown, datetime, time, slider, image_choice, consent, section, hidden, address, color, lookup, signature, date_range, calculated, repeater.',
                            ],
                            'name' => [
                                'type' => 'string',
                                'description' => 'Optional: Feld-Label. Am Abfrage-Header = Name der Abfrage.',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Optional: Feld-Beschreibung/Helptext.',
                            ],
                            'sort_order' => [
                                'type' => 'integer',
                                'description' => 'Optional: Position im Template. Default: wird automatisch ans Ende gesetzt.',
                            ],
                            'is_required' => [
                                'type' => 'boolean',
                                'description' => 'Optional: Ist dieser Block Pflicht? Default: true.',
                            ],
                            'group_uuid' => [
                                'type' => 'string',
                                'description' => 'Optional: UUID der Abfrage-Gruppe. Mehrere Items mit derselben group_uuid bilden eine Abfrage mit mehreren Feldern. Leer = Einzelblock.',
                            ],
                            'visibility_rules' => [
                                'type' => 'object',
                                'description' => 'Optional: Conditional Logic — {combinator: AND|OR, rules: [{source_block_id, operator, value}]}.',
                            ],
                            'display_compact' => [
                                'type' => 'boolean',
                                'description' => 'Optional: Wirkt nur bei flow_mode = "overview". true = Gruppe als kompakte Tabellenzeile rendern. Default: false.',
                            ],
                            'ai_prompt' => [
                                'type' => 'string',
                                'description' => 'Optional: AI-Prompt für diesen Block.',
                            ],
                            'conditional_logic' => [
                                'type' => 'object',
                                'description' => 'Optional: Bedingte Logik (AI-Gesprächsführung) als JSON.',
                            ],
                            'response_format' => [
                                'type' => 'object',
                                'description' => 'Optional: Erwartetes Antwortformat als JSON.',
                            ],
                            'fallback_questions' => [
                                'type' => 'object',
                                'description' => 'Optional: Fallback-Fragen als JSON.',
                            ],
                            'validation_rules' => [
                                'type' => 'object',
                                'description' => 'Optional: Validierungsregeln als JSON.',
                            ],
                            'logic_config' => [
                                'type' => 'object',
                                'description' => 'Optional: Typ-spezifische Konfiguration als JSON. Siehe "hatch.block_definitions.POST" für die vollständige Referenz je block_type.',
                            ],
                            'ai_behavior' => [
                                'type' => 'object',
                                'description' => 'Optional: AI-Verhaltenskonfiguration als JSON.',
                            ],
                            'exit_conditions' => [
                                'type' => 'object',
                                'description' => 'Optional: Abbruch-/Exit-Bedingungen für die AI-Konversation als JSON.',
                            ],
                            'min_confidence_threshold' => [
                                'type' => 'number',
                                'description' => 'Optional: Mindest-Konfidenz (0.00-1.00).',
                            ],
                            'max_clarification_attempts' => [
                                'type' => 'integer',
                                'description' => 'Optional: Maximale Anzahl an Nachfragen der AI bei Unklarheit.',
                            ],
                            'max_messages_per_block' => [
                                'type' => 'integer',
                                'description' => 'Optional: Maximale Anzahl an Nachrichten für diesen Block.',
                            ],
                        ],
                        'required' => ['block_type'],
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

            $validTypes = HatchBlockType::values();

            foreach ($items as $index => $item) {
                $blockType = trim((string)($item['block_type'] ?? ''));
                if (!in_array($blockType, $validTypes)) {
                    $errors[] = ['index' => $index, 'error' => 'Ungültiger oder fehlender block_type. Erlaubt: ' . implode(', ', $validTypes)];
                    continue;
                }

                try {
                    $sortOrder = $item['sort_order'] ?? null;
                    if ($sortOrder === null) {
                        $maxSort++;
                        $sortOrder = $maxSort;
                    }

                    $payload = [
                        'project_template_id' => $templateId,
                        'block_type' => $blockType,
                        'sort_order' => (int)$sortOrder,
                        'is_required' => (bool)($item['is_required'] ?? true),
                        'is_active' => true,
                        'created_by_user_id' => $context->user->id,
                        'team_id' => $teamId,
                    ];

                    foreach ([
                        'name',
                        'description',
                        'group_uuid',
                        'visibility_rules',
                        'display_compact',
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
                    ] as $optional) {
                        if (array_key_exists($optional, $item)) {
                            $payload[$optional] = $item[$optional];
                        }
                    }

                    $templateBlock = HatchTemplateBlock::create($payload);

                    $created[] = [
                        'index' => $index,
                        'template_block_id' => $templateBlock->id,
                        'uuid' => $templateBlock->uuid,
                        'block_type' => $templateBlock->block_type,
                        'group_uuid' => $templateBlock->group_uuid,
                        'sort_order' => (int)$templateBlock->sort_order,
                        'is_required' => (bool)$templateBlock->is_required,
                        'display_compact' => (bool)$templateBlock->display_compact,
                    ];
                } catch (\Throwable $e) {
                    $errors[] = ['index' => $index, 'block_type' => $blockType, 'error' => $e->getMessage()];
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
