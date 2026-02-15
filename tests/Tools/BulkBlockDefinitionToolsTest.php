<?php

namespace Platform\Hatch\Tests\Tools;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Tools\BulkCreateBlockDefinitionsTool;
use Platform\Hatch\Tools\BulkDeleteBlockDefinitionsTool;
use Platform\Hatch\Tools\BulkUpdateBlockDefinitionsTool;
use Tests\TestCase;

class BulkBlockDefinitionToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private ToolContext $context;

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
    }

    // ===== BULK CREATE =====

    public function test_bulk_create_block_definitions_success(): void
    {
        $tool = new BulkCreateBlockDefinitionsTool();

        $result = $tool->execute([
            'items' => [
                ['name' => 'Block A', 'block_type' => 'text'],
                ['name' => 'Block B', 'block_type' => 'email'],
                ['name' => 'Block C', 'block_type' => 'number', 'description' => 'A number block'],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(3, $data['created_count']);
        $this->assertEquals(0, $data['error_count']);
        $this->assertCount(3, $data['created']);
        $this->assertEmpty($data['errors']);

        $this->assertDatabaseCount('hatch_block_definitions', 3);
        $this->assertDatabaseHas('hatch_block_definitions', [
            'name' => 'Block A',
            'block_type' => 'text',
            'team_id' => $this->team->id,
        ]);
    }

    public function test_bulk_create_block_definitions_partial_errors(): void
    {
        $tool = new BulkCreateBlockDefinitionsTool();

        $result = $tool->execute([
            'items' => [
                ['name' => 'Valid Block', 'block_type' => 'text'],
                ['name' => '', 'block_type' => 'text'], // Empty name
                ['name' => 'Invalid Type', 'block_type' => 'invalid'], // Invalid type
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(1, $data['created_count']);
        $this->assertEquals(2, $data['error_count']);
    }

    public function test_bulk_create_block_definitions_empty_items(): void
    {
        $tool = new BulkCreateBlockDefinitionsTool();

        $result = $tool->execute([
            'items' => [],
        ], $this->context);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_create_block_definitions_max_items_exceeded(): void
    {
        $tool = new BulkCreateBlockDefinitionsTool();

        $items = array_fill(0, 51, ['name' => 'Block', 'block_type' => 'text']);

        $result = $tool->execute(['items' => $items], $this->context);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_create_block_definitions_requires_auth(): void
    {
        $tool = new BulkCreateBlockDefinitionsTool();

        $contextNoUser = new ToolContext(user: null, team: $this->team);

        $result = $tool->execute([
            'items' => [['name' => 'Block', 'block_type' => 'text']],
        ], $contextNoUser);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_create_tool_name(): void
    {
        $tool = new BulkCreateBlockDefinitionsTool();
        $this->assertEquals('hatch.block_definitions.BULK_POST', $tool->getName());
    }

    public function test_bulk_create_tool_metadata(): void
    {
        $tool = new BulkCreateBlockDefinitionsTool();
        $metadata = $tool->getMetadata();

        $this->assertFalse($metadata['read_only']);
        $this->assertEquals('action', $metadata['category']);
        $this->assertContains('bulk', $metadata['tags']);
        $this->assertEquals('write', $metadata['risk_level']);
    }

    public function test_bulk_create_tool_schema(): void
    {
        $tool = new BulkCreateBlockDefinitionsTool();
        $schema = $tool->getSchema();

        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('items', $schema['properties']);
        $this->assertEquals('array', $schema['properties']['items']['type']);
    }

    // ===== BULK UPDATE =====

    public function test_bulk_update_block_definitions_success(): void
    {
        $bd1 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id, 'name' => 'Old Name 1']);
        $bd2 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id, 'name' => 'Old Name 2']);

        $tool = new BulkUpdateBlockDefinitionsTool();

        $result = $tool->execute([
            'items' => [
                ['block_definition_id' => $bd1->id, 'name' => 'New Name 1'],
                ['block_definition_id' => $bd2->id, 'name' => 'New Name 2', 'description' => 'Updated'],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(2, $data['updated_count']);
        $this->assertEquals(0, $data['error_count']);

        $bd1->refresh();
        $bd2->refresh();
        $this->assertEquals('New Name 1', $bd1->name);
        $this->assertEquals('New Name 2', $bd2->name);
        $this->assertEquals('Updated', $bd2->description);
    }

    public function test_bulk_update_block_definitions_not_found(): void
    {
        $tool = new BulkUpdateBlockDefinitionsTool();

        $result = $tool->execute([
            'items' => [
                ['block_definition_id' => 99999, 'name' => 'New Name'],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(0, $data['updated_count']);
        $this->assertEquals(1, $data['error_count']);
    }

    public function test_bulk_update_block_definitions_invalid_type(): void
    {
        $bd = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id]);

        $tool = new BulkUpdateBlockDefinitionsTool();

        $result = $tool->execute([
            'items' => [
                ['block_definition_id' => $bd->id, 'block_type' => 'invalid_type'],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertEquals(0, $data['updated_count']);
        $this->assertEquals(1, $data['error_count']);
    }

    public function test_bulk_update_block_definitions_other_team(): void
    {
        $otherTeam = Team::factory()->create();
        $bd = HatchBlockDefinition::factory()->create(['team_id' => $otherTeam->id]);

        $tool = new BulkUpdateBlockDefinitionsTool();

        $result = $tool->execute([
            'items' => [
                ['block_definition_id' => $bd->id, 'name' => 'Hacked'],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertEquals(0, $data['updated_count']);
        $this->assertEquals(1, $data['error_count']);
    }

    public function test_bulk_update_tool_name(): void
    {
        $tool = new BulkUpdateBlockDefinitionsTool();
        $this->assertEquals('hatch.block_definitions.BULK_PUT', $tool->getName());
    }

    public function test_bulk_update_tool_metadata(): void
    {
        $tool = new BulkUpdateBlockDefinitionsTool();
        $metadata = $tool->getMetadata();

        $this->assertTrue($metadata['idempotent']);
        $this->assertContains('bulk', $metadata['tags']);
    }

    // ===== BULK DELETE =====

    public function test_bulk_delete_block_definitions_deactivate(): void
    {
        $bd1 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id, 'is_active' => true]);
        $bd2 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id, 'is_active' => true]);

        $tool = new BulkDeleteBlockDefinitionsTool();

        $result = $tool->execute([
            'confirm' => true,
            'items' => [
                ['block_definition_id' => $bd1->id],
                ['block_definition_id' => $bd2->id],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(2, $data['deleted_count']);
        $this->assertEquals(0, $data['error_count']);

        $bd1->refresh();
        $bd2->refresh();
        $this->assertFalse((bool)$bd1->is_active);
        $this->assertFalse((bool)$bd2->is_active);
    }

    public function test_bulk_delete_block_definitions_hard_delete(): void
    {
        $bd1 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id]);
        $bd2 = HatchBlockDefinition::factory()->create(['team_id' => $this->team->id]);

        $tool = new BulkDeleteBlockDefinitionsTool();

        $result = $tool->execute([
            'confirm' => true,
            'hard_delete' => true,
            'items' => [
                ['block_definition_id' => $bd1->id],
                ['block_definition_id' => $bd2->id],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(2, $data['deleted_count']);

        $this->assertDatabaseMissing('hatch_block_definitions', ['id' => $bd1->id]);
        $this->assertDatabaseMissing('hatch_block_definitions', ['id' => $bd2->id]);
    }

    public function test_bulk_delete_block_definitions_requires_confirm(): void
    {
        $tool = new BulkDeleteBlockDefinitionsTool();

        $result = $tool->execute([
            'items' => [['block_definition_id' => 1]],
        ], $this->context);

        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_delete_block_definitions_not_found(): void
    {
        $tool = new BulkDeleteBlockDefinitionsTool();

        $result = $tool->execute([
            'confirm' => true,
            'items' => [
                ['block_definition_id' => 99999],
            ],
        ], $this->context);

        $data = $result->getData();

        $this->assertEquals(0, $data['deleted_count']);
        $this->assertEquals(1, $data['error_count']);
    }

    public function test_bulk_delete_tool_name(): void
    {
        $tool = new BulkDeleteBlockDefinitionsTool();
        $this->assertEquals('hatch.block_definitions.BULK_DELETE', $tool->getName());
    }

    public function test_bulk_delete_tool_metadata(): void
    {
        $tool = new BulkDeleteBlockDefinitionsTool();
        $metadata = $tool->getMetadata();

        $this->assertFalse($metadata['idempotent']);
        $this->assertContains('bulk', $metadata['tags']);
    }
}
