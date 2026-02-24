<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class BulkCreateBlockDefinitionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.block_definitions.BULK_POST';
    }

    public function getDescription(): string
    {
        return 'POST /hatch/block_definitions/bulk - Erstellt mehrere Block-Definitionen in einem Aufruf. ERFORDERLICH: items (Array mit je name, block_type). Maximal 50 Items pro Aufruf.';
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
                    'description' => 'ERFORDERLICH: Array von Block-Definitionen. Jedes Item benötigt: name, block_type. Optional: description, ai_prompt, conditional_logic, response_format, fallback_questions, validation_rules, logic_config, ai_behavior, is_active.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'description' => 'Name der Block-Definition (ERFORDERLICH).',
                            ],
                            'block_type' => [
                                'type' => 'string',
                                'description' => 'Block-Typ (ERFORDERLICH). Erlaubt: text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, info, custom, matrix, ranking, nps, dropdown, datetime, time, slider, image_choice, consent, section, hidden, address, color, lookup, signature, date_range, calculated, repeater.',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Optional: Beschreibung.',
                            ],
                            'ai_prompt' => [
                                'type' => 'string',
                                'description' => 'Optional: AI-Prompt für diesen Block.',
                            ],
                            'conditional_logic' => [
                                'type' => 'object',
                                'description' => 'Optional: Bedingte Logik als JSON.',
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
                                'description' => 'Optional: Logik-Konfiguration als JSON.',
                            ],
                            'ai_behavior' => [
                                'type' => 'object',
                                'description' => 'Optional: AI-Verhaltenskonfiguration als JSON.',
                            ],
                            'is_active' => [
                                'type' => 'boolean',
                                'description' => 'Optional: Status. Default: true.',
                            ],
                        ],
                        'required' => ['name', 'block_type'],
                    ],
                ],
            ],
            'required' => ['items'],
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

            $items = $arguments['items'] ?? [];
            if (empty($items) || !is_array($items)) {
                return ToolResult::error('VALIDATION_ERROR', 'items ist erforderlich und muss ein nicht-leeres Array sein.');
            }

            if (count($items) > 50) {
                return ToolResult::error('VALIDATION_ERROR', 'Maximal 50 Items pro Bulk-Aufruf erlaubt.');
            }

            $validTypes = array_keys(HatchBlockDefinition::getBlockTypes());
            $created = [];
            $errors = [];

            foreach ($items as $index => $item) {
                $name = trim((string)($item['name'] ?? ''));
                if ($name === '') {
                    $errors[] = ['index' => $index, 'error' => 'name ist erforderlich.'];
                    continue;
                }

                $blockType = trim((string)($item['block_type'] ?? ''));
                if (!in_array($blockType, $validTypes)) {
                    $errors[] = ['index' => $index, 'error' => 'Ungültiger block_type: ' . $blockType];
                    continue;
                }

                try {
                    $bd = HatchBlockDefinition::create([
                        'name' => $name,
                        'block_type' => $blockType,
                        'description' => $item['description'] ?? null,
                        'ai_prompt' => $item['ai_prompt'] ?? null,
                        'conditional_logic' => $item['conditional_logic'] ?? null,
                        'response_format' => $item['response_format'] ?? null,
                        'fallback_questions' => $item['fallback_questions'] ?? null,
                        'validation_rules' => $item['validation_rules'] ?? null,
                        'logic_config' => $item['logic_config'] ?? null,
                        'ai_behavior' => $item['ai_behavior'] ?? null,
                        'is_active' => (bool)($item['is_active'] ?? true),
                        'created_by_user_id' => $context->user->id,
                        'team_id' => $teamId,
                    ]);

                    $created[] = [
                        'index' => $index,
                        'id' => $bd->id,
                        'uuid' => $bd->uuid,
                        'name' => $bd->name,
                        'block_type' => $bd->block_type,
                    ];
                } catch (\Throwable $e) {
                    $errors[] = ['index' => $index, 'error' => $e->getMessage()];
                }
            }

            return ToolResult::success([
                'created_count' => count($created),
                'error_count' => count($errors),
                'created' => $created,
                'errors' => $errors,
                'message' => count($created) . ' Block-Definition(en) erstellt, ' . count($errors) . ' Fehler.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Erstellen der Block-Definitionen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'block_definitions', 'bulk', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
