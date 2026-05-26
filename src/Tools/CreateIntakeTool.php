<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class CreateIntakeTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.intakes.POST';
    }

    public function getDescription(): string
    {
        return 'POST /hatch/intakes - Erstellt einen neuen Project Intake im Status "draft". ERFORDERLICH: project_template_id, name. Optional: description, intake_settings. name/description unterstützen Platzhalter {{iso_week}} / {{iso_year}} (z. B. für wiederkehrende Erhebungen). Nutze hatch.intakes.PUT mit status="published" um zu veröffentlichen.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'project_template_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Projekt-Templates (ERFORDERLICH). Nutze "hatch.templates.GET".',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Intakes (ERFORDERLICH). Unterstützt Platzhalter {{iso_week}}, {{iso_week2}}, {{iso_year}}, {{iso_year2}}.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung. Gleiche Platzhalter wie name.',
                ],
                'intake_settings' => [
                    'type' => 'object',
                    'description' => 'Optional: Owner-Konfiguration. Beispiel: {"week_cutoff": {"rollover_weekday": "saturday", "rollover_time": "12:00"}} – Antworten ab Samstag 12 Uhr werden der kommenden ISO-KW zugeordnet. Ohne Setting gilt ISO-Standard (Montag 00:00).',
                    'additionalProperties' => true,
                ],
            ],
            'required' => ['project_template_id', 'name'],
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

            $templateId = (int)($arguments['project_template_id'] ?? 0);
            if ($templateId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'project_template_id ist erforderlich.');
            }

            $template = HatchProjectTemplate::query()
                ->where('team_id', $teamId)
                ->find($templateId);
            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Projekt-Template nicht gefunden (oder kein Zugriff).');
            }

            $name = trim((string)($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $intakeSettings = $arguments['intake_settings'] ?? null;
            if ($intakeSettings !== null && !is_array($intakeSettings)) {
                return ToolResult::error('VALIDATION_ERROR', 'intake_settings muss ein Objekt sein.');
            }

            $intake = HatchProjectIntake::create([
                'project_template_id' => $template->id,
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'status' => 'draft',
                'is_active' => false,
                'intake_settings' => $intakeSettings,
                'created_by_user_id' => $context->user->id,
                'owned_by_user_id' => $context->user->id,
                'team_id' => $teamId,
            ]);

            return ToolResult::success([
                'id' => $intake->id,
                'uuid' => $intake->uuid,
                'name' => $intake->name,
                'status' => $intake->status,
                'intake_settings' => $intake->intake_settings,
                'project_template_id' => $intake->project_template_id,
                'team_id' => $intake->team_id,
                'message' => 'Intake im Status "draft" erstellt. Nutze hatch.intakes.PUT mit status="published" zum Veröffentlichen.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Intakes: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'intakes', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
