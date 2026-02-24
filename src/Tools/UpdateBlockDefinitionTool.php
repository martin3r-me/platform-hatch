<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class UpdateBlockDefinitionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.block_definitions.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /hatch/block_definitions/{id} - Aktualisiert eine Block-Definition. Parameter: block_definition_id (required). Alle anderen Felder optional.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'block_definition_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Block-Definition (ERFORDERLICH). Nutze "hatch.block_definitions.GET".',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name.',
                ],
                'block_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Block-Typ. Erlaubt: text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, info, custom, matrix, ranking, nps, dropdown, datetime, time, slider, image_choice, consent, section, hidden, address, color, lookup, signature, date_range, calculated, repeater.',
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
                    'description' => 'Optional: Neue Logik-Konfiguration als JSON. Struktur abhaengig von block_type — siehe hatch.overview.GET fuer vollstaendige Referenz. Beispiele: select: {options: [{label, value}]}, repeater: {fields: [{key, label, type, options?}], min_entries, max_entries, add_label}, lookup: {lookup_id, multiple, searchable}, section: {title, subtitle, content}.',
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
                'block_definition_id',
                HatchBlockDefinition::class,
                'NOT_FOUND',
                'Block-Definition nicht gefunden.'
            );
            if ($found['error']) {
                return $found['error'];
            }
            /** @var HatchBlockDefinition $bd */
            $bd = $found['model'];

            if ((int)$bd->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Block-Definition.');
            }

            if (isset($arguments['block_type'])) {
                $validTypes = array_keys(HatchBlockDefinition::getBlockTypes());
                if (!in_array($arguments['block_type'], $validTypes)) {
                    return ToolResult::error('VALIDATION_ERROR', 'Ungültiger block_type. Erlaubt: ' . implode(', ', $validTypes));
                }
            }

            $fields = [
                'name',
                'block_type',
                'description',
                'ai_prompt',
                'conditional_logic',
                'response_format',
                'fallback_questions',
                'validation_rules',
                'logic_config',
                'ai_behavior',
                'is_active',
            ];

            foreach ($fields as $field) {
                if (array_key_exists($field, $arguments)) {
                    $bd->{$field} = $arguments[$field] === '' ? null : $arguments[$field];
                }
            }

            $bd->save();

            return ToolResult::success([
                'id' => $bd->id,
                'uuid' => $bd->uuid,
                'name' => $bd->name,
                'block_type' => $bd->block_type,
                'is_active' => (bool)$bd->is_active,
                'team_id' => $bd->team_id,
                'message' => 'Block-Definition erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Block-Definition: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'block_definitions', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
