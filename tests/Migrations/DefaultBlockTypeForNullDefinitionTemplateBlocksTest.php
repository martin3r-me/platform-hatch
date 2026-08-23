<?php

namespace Platform\Hatch\Tests\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchTemplateBlock;
use Symfony\Component\Uid\UuidV7;
use Tests\TestCase;

class DefaultBlockTypeForNullDefinitionTemplateBlocksTest extends TestCase
{
    use RefreshDatabase;

    /*
     * Hinweis: Der frühere Test test_migration_defaults_block_type_for_null_definition_blocks
     * wurde mit #04 (Drop der Spalte block_definition_id) entfernt. Er rief die
     * Migration #03 (2026_08_22_000001) isoliert auf, die per whereNull('block_definition_id')
     * filtert – nach dem Spalten-Drop existiert die Spalte im finalen (RefreshDatabase-)
     * Schema nicht mehr, der Test ist damit nicht mehr lauffähig. Die Migration #03 selbst
     * läuft im Forward-Lauf weiterhin korrekt, weil sie chronologisch VOR #04 greift.
     */

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
            'block_type' => 'text',
            'group_uuid' => (string) UuidV7::generate(),
        ]);

        $this->assertSame('text', $block->fresh()->block_type);
    }
}
