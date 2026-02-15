<?php

namespace Platform\Hatch\Tests\Tools;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchTemplateBlock;
use Platform\Hatch\Tools\BulkAddTemplateBlocksTool;
use Platform\Hatch\Tools\BulkRemoveTemplateBlocksTool;
use Platform\Hatch\Tools\BulkUpdateTemplateBlocksTool;
use Tests\TestCase;

class BulkTemplateBlockToolsTest extends TestCase
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

    // ===== BULK ADD =====

    public function test_bulk_add_template_blocks_success(): void
    {
        $bd1 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id]);
        $bd2 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id]);
        $bd3 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id]);

        $tool = new BulkAddTemplateBlocksTool();

        $result = $tool->execute([
            'template_id' => $this->template->id,
            'items' => [
                ['block_definition_id' => $bd1->id],
                ['block_definition_id' => $bd2->id, 'is_required' => false],
                ['block_definition_id' => $bd3->id, 'sort_order' => 10],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(3, $data['created_count']);
        $this->assertEquals(0, $data['error_count']);
        $this->assertEquals($this->template->id, $data['template_id']);
        $this->assertDatabaseCount('hatch_template_blocks', 3);
    }

    public function test_bulk_add_template_blocks_auto_sort_order(): void
    {
        $bd1 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id]);
        $bd2 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id]);

        $tool = new BulkAddTemplateBlocksTool();

        $result = $tool->execute([
            'template_id' => $this->template->id,
            'items' => [
                ['block_definition_id' => $bd1->id],
                ['block_definition_id' => $bd2->id],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        // Sort orders should be auto-incremented
        $this->assertEquals(1, $data['created'][0]['sort_order']);
        $this->assertEquals(2, $data['created'][1]['sort_order']);
    }

    public function test_bulk_add_template_blocks_invalid_template(): void
    {
        $tool = new BulkAddTemplateBlocksTool();

        $result = $tool->execute([
            'template_id' => 99999,
            'items' => [['block_definition_id' => 1]],
        ], $this->context);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_add_template_blocks_invalid_block_definition(): void
    {
        $tool = new BulkAddTemplateBlocksTool();

        $result = $tool->execute([
            'template_id' => $this->template->id,
            'items' => [['block_definition_id' => 99999]],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(0, $data['created_count']);
        $this->assertEquals(1, $data['error_count']);
    }

    public function test_bulk_add_template_blocks_requires_template_id(): void
    {
        $tool = new BulkAddTemplateBlocksTool();

        $result = $tool->execute([
            'items' => [['block_definition_id' => 1]],
        ], $this->context);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_add_template_blocks_empty_items(): void
    {
        $tool = new BulkAddTemplateBlocksTool();

        $result = $tool->execute([
            'template_id' => $this->template->id,
            'items' => [],
        ], $this->context);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_add_template_blocks_max_items_exceeded(): void
    {
        $tool = new BulkAddTemplateBlocksTool();

        $items = array_fill(0, 51, ['block_definition_id' => 1]);

        $result = $tool->execute([
            'template_id' => $this->template->id,
            'items' => $items,
        ], $this->context);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_add_tool_name(): void
    {
        $tool = new BulkAddTemplateBlocksTool();
        $this->assertEquals('hatch.template_blocks.BULK_POST', $tool->getName());
    }

    public function test_bulk_add_tool_metadata(): void
    {
        $tool = new BulkAddTemplateBlocksTool();
        $metadata = $tool->getMetadata();

        $this->assertFalse($metadata['read_only']);
        $this->assertEquals('action', $metadata['category']);
        $this->assertContains('bulk', $metadata['tags']);
    }

    public function test_bulk_add_tool_schema(): void
    {
        $tool = new BulkAddTemplateBlocksTool();
        $schema = $tool->getSchema();

        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('template_id', $schema['properties']);
        $this->assertArrayHasKey('items', $schema['properties']);
    }

    // ===== BULK UPDATE =====

    public function test_bulk_update_template_blocks_success(): void
    {
        $bd1 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id]);
        $bd2 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id]);

        $tb1 = HatchTemplateBlock::factory()->create([
            'project_template_id' => $this->template->id,
            'block_definition_id' => $bd1->id,
            'team_id' => $this->team->id,
            'sort_order' => 1,
        ]);
        $tb2 = HatchTemplateBlock::factory()->create([
            'project_template_id' => $this->template->id,
            'block_definition_id' => $bd2->id,
            'team_id' => $this->team->id,
            'sort_order' => 2,
        ]);

        $tool = new BulkUpdateTemplateBlocksTool();

        $result = $tool->execute([
            'items' => [
                ['template_block_id' => $tb1->id, 'sort_order' => 5],
                ['template_block_id' => $tb2->id, 'is_required' => false, 'sort_order' => 3],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(2, $data['updated_count']);
        $this->assertEquals(0, $data['error_count']);

        $tb1->refresh();
        $tb2->refresh();
        $this->assertEquals(5, $tb1->sort_order);
        $this->assertEquals(3, $tb2->sort_order);
        $this->assertFalse((bool)$tb2->is_required);
    }

    public function test_bulk_update_template_blocks_not_found(): void
    {
        $tool = new BulkUpdateTemplateBlocksTool();

        $result = $tool->execute([
            'items' => [
                ['template_block_id' => 99999, 'sort_order' => 1],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertEquals(0, $data['updated_count']);
        $this->assertEquals(1, $data['error_count']);
    }

    public function test_bulk_update_template_blocks_other_team(): void
    {
        $otherTeam = Team::factory()->create();
        $bd = HatchBlockDefinition::factory()->create(['team_id' => $otherTeam->id]);
        $template = HatchProjectTemplate::factory()->create(['team_id' => $otherTeam->id]);
        $tb = HatchTemplateBlock::factory()->create([
            'project_template_id' => $template->id,
            'block_definition_id' => $bd->id,
            'team_id' => $otherTeam->id,
        ]);

        $tool = new BulkUpdateTemplateBlocksTool();

        $result = $tool->execute([
            'items' => [
                ['template_block_id' => $tb->id, 'sort_order' => 99],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertEquals(0, $data['updated_count']);
        $this->assertEquals(1, $data['error_count']);
    }

    public function test_bulk_update_template_blocks_tool_name(): void
    {
        $tool = new BulkUpdateTemplateBlocksTool();
        $this->assertEquals('hatch.template_blocks.BULK_PUT', $tool->getName());
    }

    // ===== BULK REMOVE =====

    public function test_bulk_remove_template_blocks_success(): void
    {
        $bd1 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id]);
        $bd2 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id]);

        $tb1 = HatchTemplateBlock::factory()->create([
            'project_template_id' => $this->template->id,
            'block_definition_id' => $bd1->id,
            'team_id' => $this->team->id,
        ]);
        $tb2 = HatchTemplateBlock::factory()->create([
            'project_template_id' => $this->template->id,
            'block_definition_id' => $bd2->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new BulkRemoveTemplateBlocksTool();

        $result = $tool->execute([
            'confirm' => true,
            'items' => [
                ['template_block_id' => $tb1->id],
                ['template_block_id' => $tb2->id],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(2, $data['deleted_count']);
        $this->assertEquals(0, $data['error_count']);

        $this->assertDatabaseMissing('hatch_template_blocks', ['id' => $tb1->id]);
        $this->assertDatabaseMissing('hatch_template_blocks', ['id' => $tb2->id]);

        // Block definitions should still exist
        $this->assertDatabaseHas('hatch_block_definitions', ['id' => $bd1->id]);
        $this->assertDatabaseHas('hatch_block_definitions', ['id' => $bd2->id]);
    }

    public function test_bulk_remove_template_blocks_requires_confirm(): void
    {
        $tool = new BulkRemoveTemplateBlocksTool();

        $result = $tool->execute([
            'items' => [['template_block_id' => 1]],
        ], $this->context);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_remove_template_blocks_not_found(): void
    {
        $tool = new BulkRemoveTemplateBlocksTool();

        $result = $tool->execute([
            'confirm' => true,
            'items' => [
                ['template_block_id' => 99999],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertEquals(0, $data['deleted_count']);
        $this->assertEquals(1, $data['error_count']);
    }

    public function test_bulk_remove_tool_name(): void
    {
        $tool = new BulkRemoveTemplateBlocksTool();
        $this->assertEquals('hatch.template_blocks.BULK_DELETE', $tool->getName());
    }

    public function test_bulk_remove_tool_metadata(): void
    {
        $tool = new BulkRemoveTemplateBlocksTool();
        $metadata = $tool->getMetadata();

        $this->assertFalse($metadata['idempotent']);
        $this->assertContains('bulk', $metadata['tags']);
    }
}
