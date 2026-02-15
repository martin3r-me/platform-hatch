<?php

namespace Platform\Hatch\Tests\Tools;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Tools\CreateIntakeTool;
use Platform\Hatch\Tools\UpdateIntakeTool;
use Tests\TestCase;

/**
 * Tests für das vereinfachte Veröffentlichungsmodell:
 * draft → published → closed
 */
class IntakePublishingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private ToolContext $context;
    private HatchProjectTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->user->teams()->attach($this->team);

        $this->context = new ToolContext(
            user: $this->user,
            team: $this->team,
        );

        $this->template = HatchProjectTemplate::factory()->create([
            'team_id' => $this->team->id,
            'created_by_user_id' => $this->user->id,
        ]);
    }

    // ===== MODEL METHODS =====

    public function test_model_publish_sets_status_and_timestamps(): void
    {
        $intake = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'status' => 'draft',
            'is_active' => false,
            'started_at' => null,
        ]);

        $intake->publish();

        $this->assertEquals('published', $intake->status);
        $this->assertTrue((bool)$intake->is_active);
        $this->assertNotNull($intake->started_at);
    }

    public function test_model_publish_preserves_existing_started_at(): void
    {
        $originalDate = now()->subDays(3);

        $intake = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'status' => 'closed',
            'started_at' => $originalDate,
        ]);

        $intake->publish();

        $this->assertEquals('published', $intake->status);
        $this->assertEquals(
            $originalDate->format('Y-m-d H:i:s'),
            $intake->started_at->format('Y-m-d H:i:s')
        );
    }

    public function test_model_close_sets_status_and_timestamps(): void
    {
        $intake = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'status' => 'published',
            'is_active' => true,
            'completed_at' => null,
        ]);

        $intake->close();

        $this->assertEquals('closed', $intake->status);
        $this->assertFalse((bool)$intake->is_active);
        $this->assertNotNull($intake->completed_at);
    }

    public function test_model_unpublish_resets_to_draft(): void
    {
        $intake = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'status' => 'closed',
            'is_active' => false,
        ]);

        $intake->unpublish();

        $this->assertEquals('draft', $intake->status);
        $this->assertFalse((bool)$intake->is_active);
    }

    public function test_model_status_helper_methods(): void
    {
        $intake = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'status' => 'draft',
        ]);

        $this->assertTrue($intake->isDraft());
        $this->assertFalse($intake->isPublished());
        $this->assertFalse($intake->isClosed());

        $intake->publish();

        $this->assertFalse($intake->isDraft());
        $this->assertTrue($intake->isPublished());
        $this->assertFalse($intake->isClosed());

        $intake->close();

        $this->assertFalse($intake->isDraft());
        $this->assertFalse($intake->isPublished());
        $this->assertTrue($intake->isClosed());
    }

    public function test_model_active_scope_uses_published_status(): void
    {
        HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'status' => 'draft',
        ]);

        HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'status' => 'published',
            'is_active' => true,
        ]);

        HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'status' => 'closed',
        ]);

        $activeIntakes = HatchProjectIntake::active()->get();

        $this->assertCount(1, $activeIntakes);
        $this->assertEquals('published', $activeIntakes->first()->status);
    }

    // ===== CREATE TOOL =====

    public function test_create_tool_always_creates_as_draft(): void
    {
        $tool = new CreateIntakeTool();

        $result = $tool->execute([
            'project_template_id' => $this->template->id,
            'name' => 'Test Intake',
        ], $this->context);

        $this->assertTrue($result->isSuccess());

        $data = $result->getData();
        $this->assertEquals('draft', $data['status']);

        $intake = HatchProjectIntake::find($data['id']);
        $this->assertEquals('draft', $intake->status);
        $this->assertFalse((bool)$intake->is_active);
        $this->assertNull($intake->started_at);
    }

    // ===== UPDATE TOOL =====

    public function test_update_tool_publish_intake(): void
    {
        $intake = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'status' => 'draft',
            'started_at' => null,
        ]);

        $tool = new UpdateIntakeTool();

        $result = $tool->execute([
            'intake_id' => $intake->id,
            'status' => 'published',
        ], $this->context);

        $this->assertTrue($result->isSuccess());

        $data = $result->getData();
        $this->assertEquals('published', $data['status']);
        $this->assertNotNull($data['started_at']);

        $intake->refresh();
        $this->assertTrue((bool)$intake->is_active);
    }

    public function test_update_tool_close_intake(): void
    {
        $intake = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'status' => 'published',
            'is_active' => true,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $tool = new UpdateIntakeTool();

        $result = $tool->execute([
            'intake_id' => $intake->id,
            'status' => 'closed',
        ], $this->context);

        $this->assertTrue($result->isSuccess());

        $data = $result->getData();
        $this->assertEquals('closed', $data['status']);
        $this->assertNotNull($data['completed_at']);

        $intake->refresh();
        $this->assertFalse((bool)$intake->is_active);
    }

    public function test_update_tool_rejects_invalid_status(): void
    {
        $intake = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'status' => 'draft',
        ]);

        $tool = new UpdateIntakeTool();

        $result = $tool->execute([
            'intake_id' => $intake->id,
            'status' => 'in_progress',
        ], $this->context);

        $this->assertFalse($result->isSuccess());

        $intake->refresh();
        $this->assertEquals('draft', $intake->status);
    }

    public function test_update_tool_full_lifecycle(): void
    {
        // 1) Erstellen (draft)
        $createTool = new CreateIntakeTool();
        $createResult = $createTool->execute([
            'project_template_id' => $this->template->id,
            'name' => 'Lifecycle Test',
        ], $this->context);

        $this->assertTrue($createResult->isSuccess());
        $intakeId = $createResult->getData()['id'];

        $intake = HatchProjectIntake::find($intakeId);
        $this->assertEquals('draft', $intake->status);

        // 2) Veröffentlichen (published)
        $updateTool = new UpdateIntakeTool();
        $publishResult = $updateTool->execute([
            'intake_id' => $intakeId,
            'status' => 'published',
        ], $this->context);

        $this->assertTrue($publishResult->isSuccess());
        $intake->refresh();
        $this->assertEquals('published', $intake->status);
        $this->assertTrue((bool)$intake->is_active);
        $this->assertNotNull($intake->started_at);

        // 3) Schliessen (closed)
        $closeResult = $updateTool->execute([
            'intake_id' => $intakeId,
            'status' => 'closed',
        ], $this->context);

        $this->assertTrue($closeResult->isSuccess());
        $intake->refresh();
        $this->assertEquals('closed', $intake->status);
        $this->assertFalse((bool)$intake->is_active);
        $this->assertNotNull($intake->completed_at);
    }

    // ===== STATUS CONSTANTS =====

    public function test_status_constants_defined(): void
    {
        $this->assertEquals('draft', HatchProjectIntake::STATUS_DRAFT);
        $this->assertEquals('published', HatchProjectIntake::STATUS_PUBLISHED);
        $this->assertEquals('closed', HatchProjectIntake::STATUS_CLOSED);
    }

    public function test_statuses_map_defined(): void
    {
        $statuses = HatchProjectIntake::STATUSES;

        $this->assertCount(3, $statuses);
        $this->assertArrayHasKey('draft', $statuses);
        $this->assertArrayHasKey('published', $statuses);
        $this->assertArrayHasKey('closed', $statuses);
    }
}
