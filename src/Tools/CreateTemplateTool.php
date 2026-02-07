<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class CreateTemplateTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.templates.POST';
    }

    public function getDescription(): string
    {
        return 'POST /hatch/templates - Erstellt ein neues Projekt-Template. ERFORDERLICH: name. Optional: description, ai_personality, industry_context, complexity_level, ai_instructions.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Templates (ERFORDERLICH).',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Templates.',
                ],
                'ai_personality' => [
                    'type' => 'string',
                    'description' => 'Optional: AI-Persönlichkeit für dieses Template.',
                ],
                'industry_context' => [
                    'type' => 'string',
                    'description' => 'Optional: Branchenkontext.',
                ],
                'complexity_level' => [
                    'type' => 'string',
                    'description' => 'Optional: Komplexitätslevel (simple, medium, complex). Default: medium.',
                ],
                'ai_instructions' => [
                    'type' => 'object',
                    'description' => 'Optional: AI-Anweisungen als JSON-Objekt.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Status. Default: true.',
                ],
            ],
            'required' => ['name'],
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

            $name = trim((string)($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $complexityLevel = $arguments['complexity_level'] ?? 'medium';
            if (!in_array($complexityLevel, ['simple', 'medium', 'complex'])) {
                return ToolResult::error('VALIDATION_ERROR', 'complexity_level muss simple, medium oder complex sein.');
            }

            $template = HatchProjectTemplate::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'ai_personality' => $arguments['ai_personality'] ?? null,
                'industry_context' => $arguments['industry_context'] ?? null,
                'complexity_level' => $complexityLevel,
                'ai_instructions' => $arguments['ai_instructions'] ?? null,
                'is_active' => (bool)($arguments['is_active'] ?? true),
                'created_by_user_id' => $context->user->id,
                'team_id' => $teamId,
            ]);

            return ToolResult::success([
                'id' => $template->id,
                'uuid' => $template->uuid,
                'name' => $template->name,
                'complexity_level' => $template->complexity_level,
                'is_active' => (bool)$template->is_active,
                'team_id' => $template->team_id,
                'message' => 'Template erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Templates: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['hatch', 'templates', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
