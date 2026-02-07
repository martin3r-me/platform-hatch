<?php

namespace Platform\Hatch\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Tools\Concerns\ResolvesHatchTeam;

class GetTemplateTool implements ToolContract, ToolMetadataContract
{
    use ResolvesHatchTeam;

    public function getName(): string
    {
        return 'hatch.template.GET';
    }

    public function getDescription(): string
    {
        return 'GET /hatch/templates/{id} - Ruft ein einzelnes Projekt-Template ab (inkl. Block-Definitionen und Intake-Anzahl). Parameter: template_id (required), team_id (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'template_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Templates (ERFORDERLICH). Nutze "hatch.templates.GET" um IDs zu finden.',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
            ],
            'required' => ['template_id'],
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

            $templateId = (int)($arguments['template_id'] ?? 0);
            if ($templateId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'template_id ist erforderlich.');
            }

            $template = HatchProjectTemplate::query()
                ->with(['templateBlocks.blockDefinition'])
                ->withCount('projectIntakes')
                ->where('team_id', $teamId)
                ->find($templateId);

            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Template nicht gefunden (oder kein Zugriff).');
            }

            return ToolResult::success([
                'id' => $template->id,
                'uuid' => $template->uuid,
                'name' => $template->name,
                'description' => $template->description,
                'ai_personality' => $template->ai_personality,
                'industry_context' => $template->industry_context,
                'complexity_level' => $template->complexity_level,
                'ai_instructions' => $template->ai_instructions,
                'is_active' => (bool)$template->is_active,
                'team_id' => $template->team_id,
                'project_intakes_count' => $template->project_intakes_count,
                'blocks' => $template->templateBlocks->map(fn ($tb) => [
                    'template_block_id' => $tb->id,
                    'sort_order' => $tb->sort_order,
                    'is_required' => (bool)$tb->is_required,
                    'block_definition' => $tb->blockDefinition ? [
                        'id' => $tb->blockDefinition->id,
                        'uuid' => $tb->blockDefinition->uuid,
                        'name' => $tb->blockDefinition->name,
                        'block_type' => $tb->blockDefinition->block_type,
                        'description' => $tb->blockDefinition->description,
                        'is_active' => (bool)$tb->blockDefinition->is_active,
                    ] : null,
                ])->values()->toArray(),
                'created_at' => $template->created_at?->toISOString(),
                'updated_at' => $template->updated_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Templates: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['hatch', 'template', 'get'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
