<?php

namespace Platform\Hatch\Tests\Tools;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Tools\DuplicateIntakeTool;
use Tests\TestCase;

/**
 * Duplizieren einer Erhebung: gleiche Vorlage (= gleiche Fragen),
 * aber ohne Antworten, Link und Veranstaltungsnummer.
 */
class IntakeDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private HatchProjectIntake $intake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->user->teams()->attach($this->team);

        $template = HatchProjectTemplate::factory()->create([
            'team_id' => $this->team->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->intake = HatchProjectIntake::factory()->create([
            'team_id' => $this->team->id,
            'project_template_id' => $template->id,
            'name' => 'Catering-Feedback WCCB',
            'description' => 'Teilnehmer-Feedback',
            'event_reference' => 'VA-2026-0412',
            'intake_settings' => ['placeholder_values' => ['standort' => 'WCCB']],
            'status' => 'published',
            'is_active' => true,
            'started_at' => now(),
        ]);
        $this->intake->generatePublicToken();
        $this->intake->sessions()->create(['status' => 'completed', 'answers' => ['block_1' => '5']]);
    }

    public function test_duplicate_copies_structure_but_not_answers_link_or_event_reference(): void
    {
        $copy = $this->intake->duplicate($this->user->id);

        $this->assertNotSame($this->intake->id, $copy->id);
        $this->assertSame('Catering-Feedback WCCB (Kopie)', $copy->name);
        $this->assertSame('Teilnehmer-Feedback', $copy->description);
        $this->assertSame($this->intake->project_template_id, $copy->project_template_id);
        $this->assertSame(['placeholder_values' => ['standort' => 'WCCB']], $copy->intake_settings);

        $this->assertSame('draft', $copy->status);
        $this->assertFalse((bool) $copy->is_active);
        $this->assertNull($copy->public_token);
        $this->assertNull($copy->event_reference);
        $this->assertNull($copy->started_at);
        $this->assertSame(0, $copy->sessions()->count());
        $this->assertSame(1, $this->intake->sessions()->count());
    }

    public function test_tool_duplicates_with_optional_name_and_event_reference(): void
    {
        $result = (new DuplicateIntakeTool())->execute([
            'intake_id' => $this->intake->id,
            'name' => 'Catering-Feedback Borussia Park',
            'event_reference' => 'VA-2026-0500',
        ], new ToolContext(user: $this->user, team: $this->team));

        $this->assertTrue($result->success);
        $copy = HatchProjectIntake::find($result->data['intake_id']);
        $this->assertSame('Catering-Feedback Borussia Park', $copy->name);
        $this->assertSame('VA-2026-0500', $copy->event_reference);
        $this->assertSame('draft', $copy->status);
    }

    public function test_tool_refuses_intake_of_other_team(): void
    {
        $otherTeam = Team::factory()->create();
        $this->user->teams()->attach($otherTeam);

        $result = (new DuplicateIntakeTool())->execute([
            'intake_id' => $this->intake->id,
            'team_id' => $otherTeam->id,
        ], new ToolContext(user: $this->user, team: $otherTeam));

        $this->assertFalse($result->success);
        $this->assertSame(1, HatchProjectIntake::count());
    }
}
