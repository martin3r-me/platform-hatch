<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class UpdateTemplateTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.templates.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /hatch/templates/{id} - Aktualisiert ein Projekt-Template. Parameter: template_id (required). Alle anderen Felder optional.';
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
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung.',
                ],
                'ai_personality' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue AI-Persönlichkeit.',
                ],
                'industry_context' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Branchenkontext.',
                ],
                'complexity_level' => [
                    'type' => 'string',
                    'description' => 'Optional: Neues Komplexitätslevel (simple, medium, complex).',
                ],
                'ai_instructions' => [
                    'type' => 'object',
                    'description' => 'Optional: Neue AI-Anweisungen als JSON-Objekt.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Status.',
                ],
            ],
            'required' => ['template_id'],
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

            if (isset($arguments['complexity_level']) && !in_array($arguments['complexity_level'], ['simple', 'medium', 'complex'])) {
                return ToolResult::error('VALIDATION_ERROR', 'complexity_level muss simple, medium oder complex sein.');
            }

            $fields = [
                'name',
                'description',
                'ai_personality',
                'industry_context',
                'complexity_level',
                'ai_instructions',
                'is_active',
            ];

            foreach ($fields as $field) {
                if (array_key_exists($field, $arguments)) {
                    $template->{$field} = $arguments[$field] === '' ? null : $arguments[$field];
                }
            }

            $template->save();

            return ToolResult::success([
                'id' => $template->id,
                'uuid' => $template->uuid,
                'name' => $template->name,
                'complexity_level' => $template->complexity_level,
                'is_active' => (bool)$template->is_active,
                'team_id' => $template->team_id,
                'message' => 'Template erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Templates: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'templates', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
