<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class BulkUpdateBlockDefinitionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.block_definitions.BULK_PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /hatch/block_definitions/bulk - Aktualisiert mehrere Block-Definitionen in einem Aufruf. ERFORDERLICH: items (Array mit je block_definition_id). Maximal 50 Items pro Aufruf.';
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
                    'description' => 'ERFORDERLICH: Array von Updates. Jedes Item benötigt: block_definition_id. Optional: name, block_type, description, ai_prompt, conditional_logic, response_format, fallback_questions, validation_rules, logic_config, ai_behavior, is_active.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'block_definition_id' => [
                                'type' => 'integer',
                                'description' => 'ID der Block-Definition (ERFORDERLICH).',
                            ],
                            'name' => [
                                'type' => 'string',
                                'description' => 'Optional: Neuer Name.',
                            ],
                            'block_type' => [
                                'type' => 'string',
                                'description' => 'Optional: Neuer Block-Typ.',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Optional: Neue Beschreibung.',
                            ],
                            'ai_prompt' => [
                                'type' => 'string',
                                'description' => 'Optional: Neuer AI-Prompt.',
                            ],
                            'conditional_logic' => [
                                'type' => 'object',
                                'description' => 'Optional: Neue bedingte Logik.',
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
                                'description' => 'Optional: Neue Logik-Konfiguration.',
                            ],
                            'ai_behavior' => [
                                'type' => 'object',
                                'description' => 'Optional: Neue AI-Verhaltenskonfiguration.',
                            ],
                            'is_active' => [
                                'type' => 'boolean',
                                'description' => 'Optional: Status.',
                            ],
                        ],
                        'required' => ['block_definition_id'],
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

            $validTypes = array_keys(HatchBlockDefinition::getBlockTypes());
            $fields = [
                'name', 'block_type', 'description', 'ai_prompt',
                'conditional_logic', 'response_format', 'fallback_questions',
                'validation_rules', 'logic_config', 'ai_behavior', 'is_active',
            ];

            $updated = [];
            $errors = [];

            foreach ($items as $index => $item) {
                $bdId = (int)($item['block_definition_id'] ?? 0);
                if ($bdId <= 0) {
                    $errors[] = ['index' => $index, 'error' => 'block_definition_id ist erforderlich.'];
                    continue;
                }

                try {
                    $bd = HatchBlockDefinition::query()
                        ->where('team_id', $teamId)
                        ->find($bdId);

                    if (!$bd) {
                        $errors[] = ['index' => $index, 'block_definition_id' => $bdId, 'error' => 'Block-Definition nicht gefunden oder kein Zugriff.'];
                        continue;
                    }

                    if (isset($item['block_type']) && !in_array($item['block_type'], $validTypes)) {
                        $errors[] = ['index' => $index, 'block_definition_id' => $bdId, 'error' => 'Ungültiger block_type: ' . $item['block_type']];
                        continue;
                    }

                    foreach ($fields as $field) {
                        if (array_key_exists($field, $item)) {
                            $bd->{$field} = $item[$field] === '' ? null : $item[$field];
                        }
                    }

                    $bd->save();

                    $updated[] = [
                        'index' => $index,
                        'id' => $bd->id,
                        'uuid' => $bd->uuid,
                        'name' => $bd->name,
                        'block_type' => $bd->block_type,
                        'is_active' => (bool)$bd->is_active,
                    ];
                } catch (\Throwable $e) {
                    $errors[] = ['index' => $index, 'block_definition_id' => $bdId, 'error' => $e->getMessage()];
                }
            }

            return ToolResult::success([
                'updated_count' => count($updated),
                'error_count' => count($errors),
                'updated' => $updated,
                'errors' => $errors,
                'message' => count($updated) . ' Block-Definition(en) aktualisiert, ' . count($errors) . ' Fehler.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Aktualisieren der Block-Definitionen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'block_definitions', 'bulk', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
