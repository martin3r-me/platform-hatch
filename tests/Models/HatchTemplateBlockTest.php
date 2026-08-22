<?php

namespace Platform\Hatch\Tests\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchTemplateBlock;
use Symfony\Component\Uid\UuidV7;
use Tests\TestCase;

class HatchTemplateBlockTest extends TestCase
{
    use RefreshDatabase;

    /** Alle Config-Spalten müssen ohne blockDefinition-Relation über create() befüllbar sein. */
    public function test_config_columns_are_mass_assignable(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);

        $template = HatchProjectTemplate::factory()->create([
            'team_id' => $team->id,
            'created_by_user_id' => $user->id,
        ]);

        $block = $template->templateBlocks()->create([
            'name' => 'Frage',
            'sort_order' => 1,
            'is_required' => false,
            'team_id' => $team->id,
            'created_by_user_id' => $user->id,
            'block_definition_id' => null,
            'group_uuid' => (string) UuidV7::generate(),
            'block_type' => 'ai_chat',
            'logic_config' => ['foo' => 'bar'],
            'validation_rules' => ['required' => true],
            'conditional_logic' => ['if' => 'x'],
            'response_format' => ['type' => 'text'],
            'fallback_questions' => ['Was meinst du?'],
            'ai_prompt' => 'Bitte beschreibe dein Anliegen.',
            'ai_behavior' => ['tone' => 'freundlich'],
            'exit_conditions' => ['max_turns' => 5],
            'min_confidence_threshold' => 0.75,
            'max_clarification_attempts' => 2,
            'max_messages_per_block' => 10,
        ])->fresh();

        $this->assertSame('ai_chat', $block->block_type);
        $this->assertSame(['foo' => 'bar'], $block->logic_config);
        $this->assertSame(['required' => true], $block->validation_rules);
        $this->assertSame(['if' => 'x'], $block->conditional_logic);
        $this->assertSame(['type' => 'text'], $block->response_format);
        $this->assertSame(['Was meinst du?'], $block->fallback_questions);
        $this->assertSame('Bitte beschreibe dein Anliegen.', $block->ai_prompt);
        $this->assertSame(['tone' => 'freundlich'], $block->ai_behavior);
        $this->assertSame(['max_turns' => 5], $block->exit_conditions);
        $this->assertSame('0.75', $block->min_confidence_threshold);
        $this->assertSame(2, $block->max_clarification_attempts);
        $this->assertSame(10, $block->max_messages_per_block);
        $this->assertIsInt($block->max_clarification_attempts);
        $this->assertIsInt($block->max_messages_per_block);
    }
}
