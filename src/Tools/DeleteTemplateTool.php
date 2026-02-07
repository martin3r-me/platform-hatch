<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class DeleteTemplateTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.templates.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /hatch/templates/{id} - Deaktiviert oder löscht ein Projekt-Template. Parameter: template_id (required), confirm (required=true), hard_delete (optional, default false = nur deaktivieren).';
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
                    'description' => 'ID des Templates (ERFORDERLICH).',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'ERFORDERLICH: Setze confirm=true um wirklich zu löschen/deaktivieren.',
                ],
                'hard_delete' => [
                    'type' => 'boolean',
                    'description' => 'Optional: true = endgültig löschen, false = nur deaktivieren (is_active=false). Default: false.',
                ],
            ],
            'required' => ['template_id', 'confirm'],
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

            if (!($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Bitte bestätige mit confirm: true.');
            }

            $found = $this->validateAndFindModel(
                $arguments,
                $context,
                'template_id',
                HatchProjectTemplate::class,
                'NOT_FOUND',
                'Template nicht gefunden.'
            );
            if ($found['error']) {
                return $found['error'];
            }
            /** @var HatchProjectTemplate $template */
            $template = $found['model'];

            if ((int)$template->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Template.');
            }

            $templateId = (int)$template->id;
            $templateName = (string)$template->name;
            $hardDelete = (bool)($arguments['hard_delete'] ?? false);

            if ($hardDelete) {
                $template->delete();
                return ToolResult::success([
                    'template_id' => $templateId,
                    'name' => $templateName,
                    'message' => 'Template endgültig gelöscht.',
                ]);
            }

            $template->is_active = false;
            $template->save();

            return ToolResult::success([
                'template_id' => $templateId,
                'name' => $templateName,
                'is_active' => false,
                'message' => 'Template deaktiviert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Templates: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'templates', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
