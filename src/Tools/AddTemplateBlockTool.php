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

class AddTemplateBlockTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.template_blocks.POST';
    }

    public function getDescription(): string
    {
        return 'POST /hatch/template_blocks - Fügt einen Block zu einem Template hinzu. ERFORDERLICH: template_id, block_type. Optional: name, description, sort_order, is_required, group_uuid (Abfragen mit mehreren Feldern), visibility_rules (Conditional Logic), ai_prompt, conditional_logic, response_format, fallback_questions, validation_rules, logic_config, ai_behavior, exit_conditions, min_confidence_threshold, max_clarification_attempts, max_messages_per_block. Tipp: Für eine Multi-Feld-Abfrage: mehrere Blocks mit derselben group_uuid anlegen.';
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
                'block_type' => [
                    'type' => 'string',
                    'description' => 'Block-Typ (ERFORDERLICH). Erlaubt: text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, info, custom, matrix, ranking, nps, dropdown, datetime, time, slider, image_choice, consent, section, hidden, address, color, lookup, signature, date_range, calculated, repeater.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Feld-Label. Bei einer Multi-Feld-Abfrage: der erste Block (niedrigster sort_order in der Gruppe) dient als Abfrage-Header, sein name = Name der Abfrage.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Feld-Beschreibung/Helptext (am Abfrage-Header = Abfrage-Beschreibung).',
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
                    'description' => 'Optional: UUID der Abfrage-Gruppe. Blocks mit gleicher group_uuid gehören zur selben Abfrage (Multi-Feld-Abfrage, z. B. Checkbox + Freitext). Beim ersten Feld eine neue UUID erzeugen und bei den weiteren Feldern wiederverwenden. Leer/NULL = Einzelblock (Standardverhalten).',
                ],
                'visibility_rules' => [
                    'type' => 'object',
                    'description' => 'Optional: Conditional Logic. Format: {combinator: AND|OR, rules: [{source_block_id: int, operator: equals|not_equals|contains|empty|not_empty|selected|not_selected, value: string}]}. source_block_id muss auf einen Block mit kleinerem sort_order zeigen. NULL = immer sichtbar.',
                ],
                'display_compact' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wirkt nur wenn das Template flow_mode = "overview" hat. true = Gruppe wird als kompakte Tabellenzeile gerendert. Default: false.',
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
                    'description' => 'Optional: Typ-spezifische Konfiguration als JSON (z. B. options bei select/dropdown, min/max bei scale/rating). Siehe "hatch.overview.GET" für die vollständige Referenz je block_type.',
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
                    'description' => 'Optional: Mindest-Konfidenz (0.00-1.00), ab der eine AI-Antwort akzeptiert wird.',
                ],
                'max_clarification_attempts' => [
                    'type' => 'integer',
                    'description' => 'Optional: Maximale Anzahl an Nachfragen der AI bei Unklarheit.',
                ],
                'max_messages_per_block' => [
                    'type' => 'integer',
                    'description' => 'Optional: Maximale Anzahl an Nachrichten für diesen Block in der AI-Konversation.',
                ],
            ],
            'required' => ['template_id', 'block_type'],
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

            $blockType = trim((string)($arguments['block_type'] ?? ''));
            $validTypes = HatchBlockType::values();
            if (!in_array($blockType, $validTypes)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger block_type. Erlaubt: ' . implode(', ', $validTypes));
            }

            $template = HatchProjectTemplate::query()
                ->where('team_id', $teamId)
                ->find($templateId);
            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Template nicht gefunden (oder kein Zugriff).');
            }

            // Sort-Order: wenn nicht angegeben, ans Ende setzen
            $sortOrder = $arguments['sort_order'] ?? null;
            if ($sortOrder === null) {
                $maxSort = HatchTemplateBlock::query()
                    ->where('project_template_id', $templateId)
                    ->max('sort_order');
                $sortOrder = ($maxSort ?? 0) + 1;
            }

            $payload = [
                'project_template_id' => $templateId,
                'block_type' => $blockType,
                'sort_order' => (int)$sortOrder,
                'is_required' => (bool)($arguments['is_required'] ?? true),
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
                if (array_key_exists($optional, $arguments)) {
                    $payload[$optional] = $arguments[$optional];
                }
            }

            $templateBlock = HatchTemplateBlock::create($payload);

            return ToolResult::success([
                'template_block_id' => $templateBlock->id,
                'uuid' => $templateBlock->uuid,
                'template_id' => $templateId,
                'template_name' => $template->name,
                'block_type' => $templateBlock->block_type,
                'name' => $templateBlock->name,
                'group_uuid' => $templateBlock->group_uuid,
                'sort_order' => (int)$templateBlock->sort_order,
                'is_required' => (bool)$templateBlock->is_required,
                'display_compact' => (bool)$templateBlock->display_compact,
                'message' => 'Block "' . ($templateBlock->name ?? HatchBlockType::from($blockType)->label()) . "\" zum Template \"{$template->name}\" hinzugefügt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Hinzufügen des Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'template_blocks', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
