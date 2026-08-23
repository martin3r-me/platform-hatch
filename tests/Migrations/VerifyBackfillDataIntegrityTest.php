<?php

namespace Platform\Hatch\Tests\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Hatch\Models\HatchIntakeSession;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectIntakeStep;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchTemplateBlock;
use Tests\TestCase;

/**
 * Tests für die Verifikations-Migration #18 (Gate vor #04–#06).
 */
class VerifyBackfillDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;
    private User $user;
    private HatchProjectTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
        $this->user = User::factory()->create();
        $this->user->teams()->attach($this->team);

        $this->template = HatchProjectTemplate::create([
            'name' => 'Testvorlage',
            'team_id' => $this->team->id,
            'created_by_user_id' => $this->user->id,
        ]);
    }

    private function makeIntake(): HatchProjectIntake
    {
        return HatchProjectIntake::create([
            'name' => 'Testerhebung',
            'team_id' => $this->team->id,
            'project_template_id' => $this->template->id,
            'created_by_user_id' => $this->user->id,
        ]);
    }

    private function runMigration(): void
    {
        $migration = require __DIR__ . '/../../database/migrations/2026_08_22_000003_verify_backfill_data_integrity.php';
        $migration->up();
    }

    private function makeBlock(array $overrides = []): HatchTemplateBlock
    {
        return HatchTemplateBlock::create(array_merge([
            'project_template_id' => $this->template->id,
            'group_uuid' => (string) \Symfony\Component\Uid\UuidV7::generate(),
            'name' => 'Frage',
            'sort_order' => 1,
            'is_required' => false,
            'block_type' => 'text',
            'team_id' => $this->team->id,
            'created_by_user_id' => $this->user->id,
        ], $overrides));
    }

    public function test_migration_passes_for_consistent_data(): void
    {
        $block = $this->makeBlock();

        $intake = $this->makeIntake();

        $session = $intake->sessions()->create([
            'answers' => ["block_{$block->id}" => 'Antwort'],
        ]);

        HatchProjectIntakeStep::create([
            'project_intake_id' => $intake->id,
            'template_block_id' => $block->id,
            'team_id' => $this->team->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->runMigration();

        // Trockenlauf: keine Datenveränderung.
        $this->assertNotNull($session->fresh());
        $this->assertNotNull($block->fresh());
    }

    public function test_migration_throws_when_block_type_invalid(): void
    {
        $block = $this->makeBlock();

        // Simuliert korrupten Bestand (z. B. Alt-Daten vor #03) direkt auf der
        // Rohspalte, da block_type über das Model normalerweise gültig ist.
        DB::table('hatch_template_blocks')->where('id', $block->id)->update(['block_type' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Datenintegritätsprüfung fehlgeschlagen/');

        $this->runMigration();
    }

    public function test_migration_throws_for_orphaned_answer_key(): void
    {
        $intake = $this->makeIntake();

        $intake->sessions()->create([
            'answers' => ['block_999999' => 'Antwort ohne Block'],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Datenintegritätsprüfung fehlgeschlagen/');

        $this->runMigration();
    }

    public function test_migration_does_not_persist_any_change_even_on_failure(): void
    {
        $intake = $this->makeIntake();

        $session = HatchIntakeSession::create([
            'project_intake_id' => $intake->id,
            'answers' => ['block_999999' => 'Antwort ohne Block'],
        ]);

        try {
            $this->runMigration();
            $this->fail('Erwartete RuntimeException wurde nicht geworfen.');
        } catch (\RuntimeException $e) {
            // erwartet
        }

        $this->assertSame(
            ['block_999999' => 'Antwort ohne Block'],
            HatchIntakeSession::findOrFail($session->id)->answers
        );
    }
}
