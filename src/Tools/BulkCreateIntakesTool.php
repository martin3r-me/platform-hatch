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

class BulkCreateIntakesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.intakes.BULK_POST';
    }

    public function getDescription(): string
    {
        return 'POST /hatch/intakes/bulk - Erstellt mehrere Project Intakes im Status "draft". ERFORDERLICH: items (Array mit je project_template_id, name). Maximal 50 Items pro Aufruf. Nutze hatch.intakes.BULK_PUT mit status="published" zum Veröffentlichen.';
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
                    'description' => 'ERFORDERLICH: Array von Intakes. Jedes Item benötigt: project_template_id, name. Optional: description.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'project_template_id' => [
                                'type' => 'integer',
                                'description' => 'ID des Projekt-Templates (ERFORDERLICH). Nutze "hatch.templates.GET".',
                            ],
                            'name' => [
                                'type' => 'string',
                                'description' => 'Name des Intakes (ERFORDERLICH).',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Optional: Beschreibung.',
                            ],
                        ],
                        'required' => ['project_template_id', 'name'],
                    ],
                ],
            ],
            'required' => ['items'],
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

            $items = $arguments['items'] ?? [];
            if (empty($items) || !is_array($items)) {
                return ToolResult::error('VALIDATION_ERROR', 'items ist erforderlich und muss ein nicht-leeres Array sein.');
            }

            if (count($items) > 50) {
                return ToolResult::error('VALIDATION_ERROR', 'Maximal 50 Items pro Bulk-Aufruf erlaubt.');
            }

            // Alle referenzierten Templates vorab laden
            $templateIds = array_unique(array_filter(array_map(
                fn($item) => (int)($item['project_template_id'] ?? 0),
                $items
            )));
            $templates = HatchProjectTemplate::query()
                ->where('team_id', $teamId)
                ->whereIn('id', $templateIds)
                ->get()
                ->keyBy('id');

            $created = [];
            $errors = [];

            foreach ($items as $index => $item) {
                $templateId = (int)($item['project_template_id'] ?? 0);
                if ($templateId <= 0) {
                    $errors[] = ['index' => $index, 'error' => 'project_template_id ist erforderlich.'];
                    continue;
                }

                $template = $templates->get($templateId);
                if (!$template) {
                    $errors[] = ['index' => $index, 'project_template_id' => $templateId, 'error' => 'Projekt-Template nicht gefunden (oder kein Zugriff).'];
                    continue;
                }

                $name = trim((string)($item['name'] ?? ''));
                if ($name === '') {
                    $errors[] = ['index' => $index, 'error' => 'name ist erforderlich.'];
                    continue;
                }

                try {
                    $intake = HatchProjectIntake::create([
                        'project_template_id' => $template->id,
                        'name' => $name,
                        'description' => $item['description'] ?? null,
                        'status' => 'draft',
                        'is_active' => false,
                        'created_by_user_id' => $context->user->id,
                        'owned_by_user_id' => $context->user->id,
                        'team_id' => $teamId,
                    ]);

                    $created[] = [
                        'index' => $index,
                        'id' => $intake->id,
                        'uuid' => $intake->uuid,
                        'name' => $intake->name,
                        'status' => $intake->status,
                        'project_template_id' => $intake->project_template_id,
                    ];
                } catch (\Throwable $e) {
                    $errors[] = ['index' => $index, 'error' => $e->getMessage()];
                }
            }

            return ToolResult::success([
                'created_count' => count($created),
                'error_count' => count($errors),
                'created' => $created,
                'errors' => $errors,
                'message' => count($created) . ' Intake(s) erstellt, ' . count($errors) . ' Fehler.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Erstellen der Intakes: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'intakes', 'bulk', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
