<?php

namespace Platform\Hatch\Tests\Support;

use Carbon\CarbonImmutable;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Support\IsoWeekResolver;
use Tests\TestCase;

class IsoWeekResolverTest extends TestCase
{
    private IsoWeekResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new IsoWeekResolver();
    }

    public function test_default_uses_iso_standard_for_monday(): void
    {
        // 2026-05-04 ist ein Montag, KW 19
        $result = $this->resolver->resolve(null, CarbonImmutable::create(2026, 5, 4, 9, 0));
        $this->assertSame(2026, $result['iso_year']);
        $this->assertSame(19, $result['iso_week']);
    }

    public function test_default_uses_iso_standard_for_sunday(): void
    {
        // Sonntag noch zur laufenden ISO-Woche (KW 18 endet So 23:59)
        $result = $this->resolver->resolve(null, CarbonImmutable::create(2026, 5, 3, 23, 0));
        $this->assertSame(2026, $result['iso_year']);
        $this->assertSame(18, $result['iso_week']);
    }

    public function test_iso_year_rollover_at_year_boundary(): void
    {
        // 2025-12-31 ist Mittwoch → ISO-Woche 1 von 2026 (ISO-Standard)
        $result = $this->resolver->resolve(null, CarbonImmutable::create(2025, 12, 31, 12, 0));
        $this->assertSame(2026, $result['iso_year']);
        $this->assertSame(1, $result['iso_week']);
    }

    public function test_cutoff_saturday_noon_rolls_over_early(): void
    {
        $intake = $this->intakeWithCutoff('saturday', '12:00');

        // Samstag 2026-05-02, 11:00 → noch laufende KW 18
        $before = $this->resolver->resolve($intake, CarbonImmutable::create(2026, 5, 2, 11, 0));
        $this->assertSame(18, $before['iso_week']);

        // Samstag 2026-05-02, 12:00 → schon kommende KW 19
        $atCutoff = $this->resolver->resolve($intake, CarbonImmutable::create(2026, 5, 2, 12, 0));
        $this->assertSame(19, $atCutoff['iso_week']);

        // Sonntag 2026-05-03, 18:00 → ebenfalls bereits KW 19
        $after = $this->resolver->resolve($intake, CarbonImmutable::create(2026, 5, 3, 18, 0));
        $this->assertSame(19, $after['iso_week']);
    }

    public function test_disabled_cutoff_falls_back_to_iso(): void
    {
        $intake = new HatchProjectIntake();
        $intake->intake_settings = [
            'week_cutoff' => [
                'enabled' => false,
                'rollover_weekday' => 'saturday',
            ],
        ];

        // Samstag 14:00 — mit Cutoff wäre das KW 19, ohne (disabled) bleibt es KW 18.
        $result = $this->resolver->resolve($intake, CarbonImmutable::create(2026, 5, 2, 14, 0));
        $this->assertSame(18, $result['iso_week']);
    }

    public function test_invalid_time_format_defaults_to_midnight(): void
    {
        $intake = $this->intakeWithCutoff('saturday', 'not-a-time');

        // Default "00:00" → Samstag 00:01 ist bereits jenseits des Cutoffs → KW 19
        $result = $this->resolver->resolve($intake, CarbonImmutable::create(2026, 5, 2, 0, 1));
        $this->assertSame(19, $result['iso_week']);
    }

    public function test_invalid_weekday_falls_back_to_iso(): void
    {
        $intake = $this->intakeWithCutoff('blursday', '12:00');

        // Samstag 14:00 — bei ungültigem Weekday greift ISO-Standard.
        $result = $this->resolver->resolve($intake, CarbonImmutable::create(2026, 5, 2, 14, 0));
        $this->assertSame(18, $result['iso_week']);
    }

    private function intakeWithCutoff(string $weekday, string $time): HatchProjectIntake
    {
        $intake = new HatchProjectIntake();
        $intake->intake_settings = [
            'week_cutoff' => [
                'enabled' => true,
                'rollover_weekday' => $weekday,
                'rollover_time' => $time,
            ],
        ];
        return $intake;
    }
}
