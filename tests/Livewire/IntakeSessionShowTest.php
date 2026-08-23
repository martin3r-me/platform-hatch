<?php

namespace Platform\Hatch\Tests\Livewire;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Hatch\Livewire\IntakeSession\Show;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectTemplate;
use Symfony\Component\Uid\UuidV7;
use Tests\TestCase;

class IntakeSessionShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regressionstest für #13: Typ/Config müssen direkt vom template_block
     * gelesen werden, nicht mehr über die (bei manchen Blocks fehlende)
     * blockDefinition-Relation.
     */
    public function test_render_reads_type_and_config_from_template_block(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);

        $template = HatchProjectTemplate::create([
            'name' => 'Testvorlage',
            'team_id' => $team->id,
            'created_by_user_id' => $user->id,
        ]);

        $templateBlock = $template->templateBlocks()->create([
            'name' => 'Wie zufrieden bist du?',
            'description' => 'Skalierte Bewertung',
            'sort_order' => 1,
            'is_required' => true,
            'team_id' => $team->id,
            'created_by_user_id' => $user->id,
            'block_type' => 'scale',
            'logic_config' => ['min' => 1, 'max' => 5],
            'group_uuid' => (string) UuidV7::generate(),
        ]);

        $intake = HatchProjectIntake::create([
            'name' => 'Testerhebung',
            'team_id' => $team->id,
            'project_template_id' => $template->id,
            'created_by_user_id' => $user->id,
        ]);

        $session = $intake->sessions()->create([
            'status' => 'completed',
            'answers' => ["block_{$templateBlock->id}" => 4],
        ]);

        $component = new Show();
        $component->mount($session);
        $blocks = $component->render()->getData()['blocks'];

        $block = $blocks->first();

        $this->assertSame('scale', $block['type']);
        $this->assertSame('Skala (1-10, 1-5 etc.)', $block['type_label']);
        $this->assertSame(['min' => 1, 'max' => 5], $block['logic_config']);
        $this->assertSame('Wie zufrieden bist du?', $block['name']);
        $this->assertSame(4, $block['answer']);
    }
}
