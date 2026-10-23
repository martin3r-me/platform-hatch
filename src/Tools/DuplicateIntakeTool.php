<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class DuplicateIntakeTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.intakes.DUPLICATE';
    }

    public function getDescription(): string
    {
        return 'POST /hatch/intakes/{id}/duplicate - Legt eine Kopie einer Erhebung als Entwurf an. Übernimmt Vorlage (dieselben Fragen), Name mit „(Kopie)“ bzw. optional neuen Namen, Beschreibung und intake_settings (z. B. Platzhalter-Werte). Nicht übernommen: Antworten, öffentlicher Link/QR-Code, Veranstaltungsnummer. Parameter: intake_id (required), name (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'intake_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu kopierenden Erhebung (ERFORDERLICH).',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name der Kopie. Default: „<Name> (Kopie)“. Platzhalter wie {{iso_week}} möglich.',
                ],
                'event_reference' => [
                    'type' => 'string',
                    'description' => 'Optional: Interne Veranstaltungsnummer der Kopie.',
                ],
            ],
            'required' => ['intake_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int) $resolved['team_id'];

            $found = $this->validateAndFindModel($arguments, $context, 'intake_id', HatchProjectIntake::class, 'NOT_FOUND', 'Intake nicht gefunden.');
            if ($found['error']) {
                return $found['error'];
            }
            /** @var HatchProjectIntake $intake */
            $intake = $found['model'];

            if ((int) $intake->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Intake.');
            }

            $copy = $intake->duplicate((int) $context->user->id, $teamId);

            $updates = [];
            if (trim((string) ($arguments['name'] ?? '')) !== '') {
                $updates['name'] = trim($arguments['name']);
            }
            if (trim((string) ($arguments['event_reference'] ?? '')) !== '') {
                $updates['event_reference'] = trim($arguments['event_reference']);
            }
            if ($updates) {
                $copy->update($updates);
            }

            return ToolResult::success([
                'intake_id' => $copy->id,
                'source_intake_id' => $intake->id,
                'name' => $copy->name,
                'status' => $copy->status,
                'project_template_id' => $copy->project_template_id,
                'event_reference' => $copy->event_reference,
                'message' => 'Erhebung dupliziert (Entwurf). Zum Veröffentlichen hatch.intakes.PUT mit status="published" nutzen.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Duplizieren: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'intakes', 'duplicate', 'copy'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
