<?php

namespace Platform\Hatch\Tests\Tools;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Tools\BulkCreateIntakesTool;
use Platform\Hatch\Tools\BulkDeleteIntakesTool;
use Platform\Hatch\Tools\BulkUpdateIntakesTool;
use Tests\TestCase;

class BulkIntakeToolsTest extends TestCase
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

    // ===== BULK CREATE =====

    public function test_bulk_create_intakes_success(): void
    {
        $tool = new BulkCreateIntakesTool();

        $result = $tool->execute([
            'items' => [
                ['project_template_id' => $this->template->id, 'name' => 'Intake A'],
                ['project_template_id' => $this->template->id, 'name' => 'Intake B', 'description' => 'Test'],
                ['project_template_id' => $this->template->id, 'name' => 'Intake C', 'status' => 'draft'],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(3, $data['created_count']);
        $this->assertEquals(0, $data['error_count']);
        $this->assertCount(3, $data['created']);

        $this->assertDatabaseCount('hatch_project_intakes', 3);
        $this->assertDatabaseHas('hatch_project_intakes', [
            'name' => 'Intake A',
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
        ]);
    }

    public function test_bulk_create_intakes_partial_errors(): void
    {
        $tool = new BulkCreateIntakesTool();

        $result = $tool->execute([
            'items' => [
                ['project_template_id' => $this->template->id, 'name' => 'Valid Intake'],
                ['project_template_id' => 99999, 'name' => 'Bad Template'], // Invalid template
                ['project_template_id' => $this->template->id, 'name' => ''], // Empty name
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(1, $data['created_count']);
        $this->assertEquals(2, $data['error_count']);
    }

    public function test_bulk_create_intakes_empty_items(): void
    {
        $tool = new BulkCreateIntakesTool();

        $result = $tool->execute(['items' => []], $this->context);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_create_intakes_max_items_exceeded(): void
    {
        $tool = new BulkCreateIntakesTool();

        $items = array_fill(0, 51, [
            'project_template_id' => $this->template->id,
            'name' => 'Intake',
        ]);

        $result = $tool->execute(['items' => $items], $this->context);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_create_intakes_requires_auth(): void
    {
        $tool = new BulkCreateIntakesTool();

        $contextNoUser = new ToolContext(user: null, team: $this->team);

        $result = $tool->execute([
            'items' => [
                ['project_template_id' => $this->template->id, 'name' => 'Intake'],
            ],
        ], $contextNoUser);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_create_intakes_default_status(): void
    {
        $tool = new BulkCreateIntakesTool();

        $result = $tool->execute([
            'items' => [
                ['project_template_id' => $this->template->id, 'name' => 'Draft Intake'],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('draft', $data['created'][0]['status']);
    }

    public function test_bulk_create_tool_name(): void
    {
        $tool = new BulkCreateIntakesTool();
        $this->assertEquals('hatch.intakes.BULK_POST', $tool->getName());
    }

    public function test_bulk_create_tool_metadata(): void
    {
        $tool = new BulkCreateIntakesTool();
        $metadata = $tool->getMetadata();

        $this->assertFalse($metadata['read_only']);
        $this->assertEquals('action', $metadata['category']);
        $this->assertContains('bulk', $metadata['tags']);
        $this->assertEquals('write', $metadata['risk_level']);
    }

    public function test_bulk_create_tool_schema(): void
    {
        $tool = new BulkCreateIntakesTool();
        $schema = $tool->getSchema();

        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('items', $schema['properties']);
        $this->assertEquals('array', $schema['properties']['items']['type']);
    }

    // ===== BULK UPDATE =====

    public function test_bulk_update_intakes_success(): void
    {
        $intake1 = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'name' => 'Old Name 1',
            'status' => 'draft',
        ]);
        $intake2 = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'name' => 'Old Name 2',
            'status' => 'draft',
        ]);

        $tool = new BulkUpdateIntakesTool();

        $result = $tool->execute([
            'items' => [
                ['intake_id' => $intake1->id, 'name' => 'New Name 1'],
                ['intake_id' => $intake2->id, 'name' => 'New Name 2', 'description' => 'Updated'],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(2, $data['updated_count']);
        $this->assertEquals(0, $data['error_count']);

        $intake1->refresh();
        $intake2->refresh();
        $this->assertEquals('New Name 1', $intake1->name);
        $this->assertEquals('New Name 2', $intake2->name);
        $this->assertEquals('Updated', $intake2->description);
    }

    public function test_bulk_update_intakes_auto_started_at(): void
    {
        $intake = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'status' => 'draft',
            'started_at' => null,
        ]);

        $tool = new BulkUpdateIntakesTool();

        $result = $tool->execute([
            'items' => [
                ['intake_id' => $intake->id, 'status' => 'in_progress'],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $intake->refresh();
        $this->assertNotNull($intake->started_at);
    }

    public function test_bulk_update_intakes_not_found(): void
    {
        $tool = new BulkUpdateIntakesTool();

        $result = $tool->execute([
            'items' => [
                ['intake_id' => 99999, 'name' => 'New Name'],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertEquals(0, $data['updated_count']);
        $this->assertEquals(1, $data['error_count']);
    }

    public function test_bulk_update_intakes_other_team(): void
    {
        $otherTeam = Team::factory()->create();
        $template = HatchProjectTemplate::factory()->create(['team_id' => $otherTeam->id]);
        $intake = HatchProjectIntake::factory()->create([
            'team_id' => $otherTeam->id,
            'project_template_id' => $template->id,
        ]);

        $tool = new BulkUpdateIntakesTool();

        $result = $tool->execute([
            'items' => [
                ['intake_id' => $intake->id, 'name' => 'Hacked'],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertEquals(0, $data['updated_count']);
        $this->assertEquals(1, $data['error_count']);
    }

    public function test_bulk_update_tool_name(): void
    {
        $tool = new BulkUpdateIntakesTool();
        $this->assertEquals('hatch.intakes.BULK_PUT', $tool->getName());
    }

    public function test_bulk_update_tool_metadata(): void
    {
        $tool = new BulkUpdateIntakesTool();
        $metadata = $tool->getMetadata();

        $this->assertTrue($metadata['idempotent']);
        $this->assertContains('bulk', $metadata['tags']);
    }

    // ===== BULK DELETE =====

    public function test_bulk_delete_intakes_deactivate(): void
    {
        $intake1 = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'is_active' => true,
        ]);
        $intake2 = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'is_active' => true,
        ]);

        $tool = new BulkDeleteIntakesTool();

        $result = $tool->execute([
            'confirm' => true,
            'items' => [
                ['intake_id' => $intake1->id],
                ['intake_id' => $intake2->id],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(2, $data['deleted_count']);
        $this->assertEquals(0, $data['error_count']);

        $intake1->refresh();
        $intake2->refresh();
        $this->assertFalse((bool)$intake1->is_active);
        $this->assertFalse((bool)$intake2->is_active);
    }

    public function test_bulk_delete_intakes_hard_delete(): void
    {
        $intake1 = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
        ]);
        $intake2 = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
        ]);

        $tool = new BulkDeleteIntakesTool();

        $result = $tool->execute([
            'confirm' => true,
            'hard_delete' => true,
            'items' => [
                ['intake_id' => $intake1->id],
                ['intake_id' => $intake2->id],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(2, $data['deleted_count']);

        $this->assertDatabaseMissing('hatch_project_intakes', ['id' => $intake1->id]);
        $this->assertDatabaseMissing('hatch_project_intakes', ['id' => $intake2->id]);
    }

    public function test_bulk_delete_intakes_requires_confirm(): void
    {
        $tool = new BulkDeleteIntakesTool();

        $result = $tool->execute([
            'items' => [['intake_id' => 1]],
        ], $this->context);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_delete_intakes_not_found(): void
    {
        $tool = new BulkDeleteIntakesTool();

        $result = $tool->execute([
            'confirm' => true,
            'items' => [
                ['intake_id' => 99999],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertEquals(0, $data['deleted_count']);
        $this->assertEquals(1, $data['error_count']);
    }

    public function test_bulk_delete_intakes_other_team(): void
    {
        $otherTeam = Team::factory()->create();
        $template = HatchProjectTemplate::factory()->create(['team_id' => $otherTeam->id]);
        $intake = HatchProjectIntake::factory()->create([
            'team_id' => $otherTeam->id,
            'project_template_id' => $template->id,
        ]);

        $tool = new BulkDeleteIntakesTool();

        $result = $tool->execute([
            'confirm' => true,
            'items' => [
                ['intake_id' => $intake->id],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertEquals(0, $data['deleted_count']);
        $this->assertEquals(1, $data['error_count']);
    }

    public function test_bulk_delete_tool_name(): void
    {
        $tool = new BulkDeleteIntakesTool();
        $this->assertEquals('hatch.intakes.BULK_DELETE', $tool->getName());
    }

    public function test_bulk_delete_tool_metadata(): void
    {
        $tool = new BulkDeleteIntakesTool();
        $metadata = $tool->getMetadata();

        $this->assertFalse($metadata['idempotent']);
        $this->assertContains('bulk', $metadata['tags']);
    }
}
