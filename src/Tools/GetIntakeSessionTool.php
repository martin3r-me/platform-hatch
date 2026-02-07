<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Hatch\Models\HatchIntakeSession;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class GetIntakeSessionTool implements ToolContract, ToolMetadataContract
{
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.intake_session.GET';
    }

    public function getDescription(): string
    {
        return 'GET /hatch/intake_sessions/{id} - Ruft eine einzelne Intake-Session ab (inkl. Antworten und Metadata). Parameter: session_id (required), team_id (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'session_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Session (ERFORDERLICH). Nutze "hatch.intake_sessions.GET" um IDs zu finden.',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
            ],
            'required' => ['session_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $sessionId = (int)($arguments['session_id'] ?? 0);
            if ($sessionId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'session_id ist erforderlich.');
            }

            $session = HatchIntakeSession::query()
                ->with('projectIntake:id,name,team_id')
                ->find($sessionId);

            if (!$session) {
                return ToolResult::error('NOT_FOUND', 'Session nicht gefunden.');
            }

            if (!$session->projectIntake || (int)$session->projectIntake->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Session.');
            }

            return ToolResult::success([
                'id' => $session->id,
                'uuid' => $session->uuid,
                'session_token' => $session->session_token,
                'project_intake_id' => $session->project_intake_id,
                'project_intake_name' => $session->projectIntake?->name,
                'status' => $session->status,
                'respondent_name' => $session->respondent_name,
                'respondent_email' => $session->respondent_email,
                'current_step' => (int)$session->current_step,
                'answers' => $session->answers,
                'metadata' => $session->metadata,
                'started_at' => $session->started_at?->toISOString(),
                'completed_at' => $session->completed_at?->toISOString(),
                'created_at' => $session->created_at?->toISOString(),
                'updated_at' => $session->updated_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Session: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['hatch', 'intake_session', 'get'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
