<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class HatchOverviewTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'hatch.overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /hatch/overview - Zeigt Übersicht über Hatch-Konzepte (Project Templates, Block Definitions, Project Intakes, Intake Sessions, Intake Steps) und verfügbare Tools.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            return ToolResult::success([
                'module' => 'hatch',
                'scope' => [
                    'team_scoped' => true,
                    'team_id_source' => 'ToolContext.team bzw. team_id Parameter',
                ],
                'concepts' => [
                    'project_templates' => [
                        'model' => 'Platform\\Hatch\\Models\\HatchProjectTemplate',
                        'table' => 'hatch_project_templates',
                        'key_fields' => ['id', 'uuid', 'name', 'description', 'ai_personality', 'industry_context', 'complexity_level', 'ai_instructions', 'is_active', 'team_id'],
                        'note' => 'Vorlagen für Project Intakes. Definieren Struktur und AI-Verhalten über verknüpfte Block Definitions.',
                    ],
                    'block_definitions' => [
                        'model' => 'Platform\\Hatch\\Models\\HatchBlockDefinition',
                        'table' => 'hatch_block_definitions',
                        'key_fields' => ['id', 'uuid', 'name', 'block_type', 'ai_prompt', 'is_active', 'team_id'],
                        'note' => 'Wiederverwendbare Bausteine (Frage-Typen) für Templates. Konfiguration via logic_config JSON.',
                        'block_types' => [
                            'text' => ['label' => 'Text-Eingabe', 'config' => 'placeholder, min_length, max_length (255)'],
                            'long_text' => ['label' => 'Langer Text', 'config' => 'placeholder, min_length, max_length (5000), rows (6)'],
                            'email' => ['label' => 'E-Mail', 'config' => 'placeholder'],
                            'phone' => ['label' => 'Telefon', 'config' => 'placeholder, format'],
                            'url' => ['label' => 'URL', 'config' => 'placeholder'],
                            'select' => ['label' => 'Auswahl (Single)', 'config' => 'options: [{label, value}]'],
                            'multi_select' => ['label' => 'Auswahl (Multiple)', 'config' => 'options: [{label, value}]', 'storage' => 'JSON array'],
                            'number' => ['label' => 'Zahl', 'config' => 'placeholder, min, max, step, unit'],
                            'scale' => ['label' => 'Skala', 'config' => 'min (1), max (10), step, labels: {min_label, max_label}'],
                            'date' => ['label' => 'Datum', 'config' => 'format, min_date, max_date'],
                            'boolean' => ['label' => 'Ja/Nein', 'config' => 'true_label, false_label, style'],
                            'file' => ['label' => 'Datei-Upload', 'config' => 'allowed_types, max_size_mb'],
                            'rating' => ['label' => 'Bewertung', 'config' => 'min (1), max (5), step'],
                            'location' => ['label' => 'Standort', 'config' => 'placeholder, format'],
                            'info' => ['label' => 'Info (kein Input)', 'config' => 'content'],
                            'custom' => ['label' => 'Benutzerdefiniert', 'config' => '{}'],
                            'matrix' => ['label' => 'Matrix / Likert', 'config' => 'items: [{label, value}], scale_min (1), scale_max (5), scale_labels: {min_label, max_label}', 'storage' => 'JSON {item_key: value}'],
                            'ranking' => ['label' => 'Sortierung / Ranking', 'config' => 'options: [{label, value}]', 'storage' => 'JSON array of values'],
                            'nps' => ['label' => 'Net Promoter Score', 'config' => '{} (fix 0-10)'],
                            'dropdown' => ['label' => 'Dropdown', 'config' => 'options: [{label, value}], placeholder, searchable'],
                            'datetime' => ['label' => 'Datum & Uhrzeit', 'config' => 'min_datetime, max_datetime'],
                            'time' => ['label' => 'Uhrzeit', 'config' => 'min_time, max_time, step_minutes (15)'],
                            'slider' => ['label' => 'Schieberegler', 'config' => 'min (0), max (100), step (1), unit, show_value'],
                            'image_choice' => ['label' => 'Bildauswahl', 'config' => 'options: [{label, value, file_id}], columns (3)'],
                            'consent' => ['label' => 'Einwilligung / DSGVO', 'config' => 'text, link_url, link_label, must_accept'],
                            'section' => ['label' => 'Abschnittstrenner', 'config' => 'title, subtitle, content (optional Rich-Text)'],
                            'hidden' => ['label' => 'Verstecktes Feld', 'config' => 'default_value, source: static|url_param|referrer'],
                            'address' => ['label' => 'Strukturierte Adresse', 'config' => 'fields: [street, house_number, zip, city, country], country_lookup_id', 'storage' => 'JSON {street, zip, city, ...}'],
                            'color' => ['label' => 'Farbauswahl', 'config' => 'format: hex, presets: ["#..."]'],
                            'lookup' => ['label' => 'Lookup-Auswahl', 'config' => 'lookup_id (required, aus hatch_lookups), multiple, searchable, placeholder'],
                            'signature' => ['label' => 'Digitale Unterschrift', 'config' => 'width (400), height (200), pen_color', 'storage' => 'Base64 PNG Data-URI'],
                            'date_range' => ['label' => 'Datumsbereich', 'config' => 'min_date, max_date, format', 'storage' => 'JSON {start, end}'],
                            'calculated' => ['label' => 'Berechnetes Feld (read-only)', 'config' => 'formula: "{block_ID} + {block_ID}", source_blocks, display_format: "{result} kg", operation: custom|sum|avg|min|max'],
                            'repeater' => ['label' => 'Wiederholung / Repeater', 'config' => 'fields: [{key, label, type, options?}], min_entries (0), max_entries (10), add_label', 'field_types' => 'text, long_text, email, url, phone, number, date, time, select, color', 'storage' => 'JSON array of objects'],
                        ],
                    ],
                    'project_intakes' => [
                        'model' => 'Platform\\Hatch\\Models\\HatchProjectIntake',
                        'table' => 'hatch_project_intakes',
                        'key_fields' => ['id', 'uuid', 'name', 'status', 'project_template_id', 'public_token', 'is_active', 'team_id'],
                        'note' => 'Konkrete Intake-Instanzen basierend auf einem Template. Haben einen öffentlichen Link (public_token) für Respondenten.',
                    ],
                    'intake_sessions' => [
                        'model' => 'Platform\\Hatch\\Models\\HatchIntakeSession',
                        'table' => 'hatch_intake_sessions',
                        'key_fields' => ['id', 'uuid', 'session_token', 'project_intake_id', 'status', 'respondent_name', 'respondent_email', 'answers'],
                        'note' => 'Einzelne Antwort-Sessions von Respondenten. Read-only via LLM (werden von Respondenten befüllt).',
                    ],
                    'intake_steps' => [
                        'model' => 'Platform\\Hatch\\Models\\HatchProjectIntakeStep',
                        'table' => 'hatch_project_intake_steps',
                        'key_fields' => ['id', 'uuid', 'project_intake_id', 'block_definition_id', 'answers', 'ai_confidence', 'is_completed'],
                        'note' => 'Einzelne Steps innerhalb eines Intakes (pro Block Definition).',
                    ],
                    'lookups' => [
                        'model' => 'Platform\\Hatch\\Models\\HatchLookup',
                        'table' => 'hatch_lookups',
                        'key_fields' => ['id', 'team_id', 'name', 'label', 'description', 'is_system'],
                        'note' => 'Vordefinierte Auswahllisten (z.B. Länder, Sprachen). Verwendet im Block-Typ "lookup". Verwaltung über Admin-UI unter /lookups.',
                    ],
                    'lookup_values' => [
                        'model' => 'Platform\\Hatch\\Models\\HatchLookupValue',
                        'table' => 'hatch_lookup_values',
                        'key_fields' => ['id', 'lookup_id', 'value', 'label', 'order', 'is_active', 'meta'],
                        'note' => 'Einzelne Werte einer Lookup-Liste. Sortierbar, aktivierbar, mit optionalen Metadaten.',
                    ],
                ],
                'relationships' => [
                    'template_has_blocks' => 'ProjectTemplate → TemplateBlocks → BlockDefinitions',
                    'intake_from_template' => 'ProjectIntake → ProjectTemplate',
                    'intake_has_steps' => 'ProjectIntake → IntakeSteps',
                    'intake_has_sessions' => 'ProjectIntake → IntakeSessions',
                ],
                'related_tools' => [
                    'templates' => [
                        'list' => 'hatch.templates.GET',
                        'get' => 'hatch.template.GET',
                        'create' => 'hatch.templates.POST',
                        'update' => 'hatch.templates.PUT',
                        'delete' => 'hatch.templates.DELETE',
                    ],
                    'template_blocks' => [
                        'add' => 'hatch.template_blocks.POST',
                        'update' => 'hatch.template_blocks.PUT',
                        'remove' => 'hatch.template_blocks.DELETE',
                        'note' => 'Verknüpft wiederverwendbare Block-Definitionen mit Templates. Blöcke sind in hatch.template.GET sichtbar.',
                    ],
                    'block_definitions' => [
                        'list' => 'hatch.block_definitions.GET',
                        'get' => 'hatch.block_definition.GET',
                        'create' => 'hatch.block_definitions.POST',
                        'update' => 'hatch.block_definitions.PUT',
                        'delete' => 'hatch.block_definitions.DELETE',
                    ],
                    'intakes' => [
                        'list' => 'hatch.intakes.GET',
                        'get' => 'hatch.intake.GET',
                        'create' => 'hatch.intakes.POST',
                        'update' => 'hatch.intakes.PUT',
                        'delete' => 'hatch.intakes.DELETE',
                    ],
                    'sessions' => [
                        'list' => 'hatch.intake_sessions.GET',
                        'get' => 'hatch.intake_session.GET',
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Hatch-Übersicht: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'overview',
            'tags' => ['overview', 'help', 'hatch', 'templates', 'intakes'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
