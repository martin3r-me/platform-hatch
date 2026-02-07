<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class CreateBlockDefinitionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.block_definitions.POST';
    }

    public function getDescription(): string
    {
        return 'POST /hatch/block_definitions - Erstellt eine neue Block-Definition. ERFORDERLICH: name, block_type. Optional: description, ai_prompt, conditional_logic, response_format, fallback_questions, validation_rules, logic_config, ai_behavior.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Block-Definition (ERFORDERLICH).',
                ],
                'block_type' => [
                    'type' => 'string',
                    'description' => 'Block-Typ (ERFORDERLICH). Erlaubt: text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, info, custom.',
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
                    'description' => 'Optional: Logik-Konfiguration als JSON. Fuer block_type "select"/"multi_select": options-Array setzen. Fuer block_type "scale": min (default 1), max (default 5), min_label, max_label setzen. Fuer block_type "rating": max (default 5). Fuer block_type "boolean": true_label, false_label.',
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

            $name = trim((string)($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $blockType = trim((string)($arguments['block_type'] ?? ''));
            $validTypes = array_keys(HatchBlockDefinition::getBlockTypes());
            if (!in_array($blockType, $validTypes)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger block_type. Erlaubt: ' . implode(', ', $validTypes));
            }

            $bd = HatchBlockDefinition::create([
                'name' => $name,
                'block_type' => $blockType,
                'description' => $arguments['description'] ?? null,
                'ai_prompt' => $arguments['ai_prompt'] ?? null,
                'conditional_logic' => $arguments['conditional_logic'] ?? null,
                'response_format' => $arguments['response_format'] ?? null,
                'fallback_questions' => $arguments['fallback_questions'] ?? null,
                'validation_rules' => $arguments['validation_rules'] ?? null,
                'logic_config' => $arguments['logic_config'] ?? null,
                'ai_behavior' => $arguments['ai_behavior'] ?? null,
                'is_active' => (bool)($arguments['is_active'] ?? true),
                'created_by_user_id' => $context->user->id,
                'team_id' => $teamId,
            ]);

            return ToolResult::success([
                'id' => $bd->id,
                'uuid' => $bd->uuid,
                'name' => $bd->name,
                'block_type' => $bd->block_type,
                'is_active' => (bool)$bd->is_active,
                'team_id' => $bd->team_id,
                'message' => 'Block-Definition erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Block-Definition: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'block_definitions', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
