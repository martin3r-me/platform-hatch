<?php

namespace Platform\Hatch\Tests\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchTemplateBlock;
use Symfony\Component\Uid\UuidV7;
use Tests\TestCase;

class DefaultBlockTypeForNullDefinitionTemplateBlocksTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simuliert einen typlosen Placeholder-Block, wie er vor dieser Regel
     * (per Template\Show::addQuestionGroup) ohne block_type angelegt wurde,
     * und prüft, dass die Migration #03 ihn auf den Default-Typ 'text' hebt.
     */
    public function test_migration_defaults_block_type_for_null_definition_blocks(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);

        $template = HatchProjectTemplate::factory()->create([
            'team_id' => $team->id,
            'created_by_user_id' => $user->id,
        ]);

        $blockId = DB::table('hatch_template_blocks')->insertGetId([
            'uuid' => (string) UuidV7::generate(),
            'project_template_id' => $template->id,
            'block_definition_id' => null,
            'group_uuid' => (string) UuidV7::generate(),
            'name' => 'Neue Abfrage',
            'sort_order' => 1,
            'is_required' => false,
            'block_type' => null,
            'team_id' => $team->id,
            'created_by_user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require __DIR__ . '/../../database/migrations/2026_08_22_000001_default_block_type_for_null_definition_template_blocks.php';
        $migration->up();

        $this->assertSame('text', DB::table('hatch_template_blocks')->find($blockId)->block_type);
    }

    /** block_type muss über HatchTemplateBlock::create() mass-assignable sein. */
    public function test_block_type_is_mass_assignable(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);

        $template = HatchProjectTemplate::factory()->create([
            'team_id' => $team->id,
            'created_by_user_id' => $user->id,
        ]);

        $block = $template->templateBlocks()->create([
            'name' => 'Neue Abfrage',
            'sort_order' => 1,
            'is_required' => false,
            'team_id' => $team->id,
            'created_by_user_id' => $user->id,
            'block_definition_id' => null,
            'block_type' => 'text',
            'group_uuid' => (string) UuidV7::generate(),
        ]);

        $this->assertSame('text', $block->fresh()->block_type);
    }
}
