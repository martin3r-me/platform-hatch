<?php

namespace Platform\Hatch\Console\Commands;

use Illuminate\Console\Command;
use Platform\Hatch\Models\HatchProjectIntake;

/**
 * Schließt veröffentlichte Erhebungen, deren closes_at erreicht ist.
 * Die öffentlichen Seiten prüfen den Zeitpunkt zusätzlich selbst (closeIfDue),
 * damit eine Umfrage auch ohne Scheduler pünktlich keine Antworten mehr annimmt.
 */
class CloseDueIntakesCommand extends Command
{
    protected $signature = 'hatch:close-due-intakes';

    protected $description = 'Schließt Erhebungen, deren automatisches Schließdatum erreicht ist';

    public function handle(): int
    {
        $count = 0;
        HatchProjectIntake::query()
            ->where('status', HatchProjectIntake::STATUS_PUBLISHED)
            ->whereNotNull('closes_at')
            ->where('closes_at', '<=', now())
            ->each(function (HatchProjectIntake $intake) use (&$count) {
                if ($intake->closeIfDue()) {
                    $count++;
                }
            });

        if ($count > 0) {
            $this->info("{$count} Erhebung(en) automatisch geschlossen.");
        }

        return self::SUCCESS;
    }
}
