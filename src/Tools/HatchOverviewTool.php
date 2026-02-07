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
                        'note' => 'Wiederverwendbare Bausteine (Frage-Typen) für Templates. Block-Typen: text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, custom.',
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
