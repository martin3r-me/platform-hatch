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
        return 'PUT /hatch/template_blocks/{id} - Aktualisiert einen Block innerhalb eines Templates (Typ, Konfiguration, Reihenfolge, Pflichtfeld, Label, Gruppen-Zugehörigkeit, Sichtbarkeitsregeln). Parameter: template_block_id (required).';
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
                'block_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Block-Typ. Erlaubt: text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, info, custom, matrix, ranking, nps, dropdown, datetime, time, slider, image_choice, consent, section, hidden, address, color, lookup, signature, date_range, calculated, repeater.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Feld-Label. Bei Abfrage-Headern (erster Block einer Gruppe) = Name der Abfrage.',
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
                'display_compact' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wirkt nur wenn das Template flow_mode = "overview" hat. true = die Gruppe dieses Blocks wird im Übersichts-Modus als kompakte Tabellenzeile gerendert. Aufeinanderfolgende compact-Gruppen mit identischer Feld-Struktur werden zu einer gemeinsamen Tabelle zusammengefasst (z. B. Mo–Fr Wochenfeedback). Reicht aus, das Flag auf einem Block der Gruppe zu setzen — typischerweise dem Header-Block (erstes Feld der Gruppe).',
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

            if (isset($arguments['block_type'])) {
                $validTypes = HatchBlockType::values();
                if (!in_array($arguments['block_type'], $validTypes)) {
                    return ToolResult::error('VALIDATION_ERROR', 'Ungültiger block_type. Erlaubt: ' . implode(', ', $validTypes));
                }
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

            foreach ($fields as $field) {
                if (array_key_exists($field, $arguments)) {
                    $templateBlock->{$field} = $arguments[$field];
                }
            }

            $templateBlock->save();

            return ToolResult::success([
                'template_block_id' => $templateBlock->id,
                'template_id' => $templateBlock->project_template_id,
                'block_type' => $templateBlock->block_type,
                'name' => $templateBlock->name,
                'description' => $templateBlock->description,
                'group_uuid' => $templateBlock->group_uuid,
                'visibility_rules' => $templateBlock->visibility_rules,
                'sort_order' => (int)$templateBlock->sort_order,
                'is_required' => (bool)$templateBlock->is_required,
                'display_compact' => (bool)$templateBlock->display_compact,
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
