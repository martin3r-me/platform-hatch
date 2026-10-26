<?php

namespace Platform\Hatch\Console\Commands;

use Illuminate\Console\Command;
use Platform\Hatch\Models\HatchIntakeSession;

/**
 * Löscht die technischen Daten einer Teilnahme (IP-Adresse, Browser-Kennung,
 * Herkunftsseite) nach Ablauf der Frist. Die Antworten bleiben erhalten.
 *
 * Die Frist steht in config('hatch.privacy.metadata_retention_days') und wird
 * so in den Datenschutzhinweisen genannt – beides muss zusammenpassen.
 * metadata ist verschlüsselt gespeichert, daher kein JSON-Update per SQL.
 */
class PruneSessionMetadataCommand extends Command
{
    protected $signature = 'hatch:prune-session-metadata {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Löscht IP-Adresse, Browser-Kennung und Herkunftsseite von Umfrage-Teilnahmen nach Ablauf der Aufbewahrungsfrist';

    public function handle(): int
    {
        $days = (int) config('hatch.privacy.metadata_retention_days', 30);
        $cutoff = now()->subDays($days);

        $query = HatchIntakeSession::query()
            ->whereNotNull('metadata')
            ->where('created_at', '<', $cutoff);

        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("{$count} Teilnahmen älter als {$days} Tage haben noch technische Daten.");

            return self::SUCCESS;
        }

        $query->chunkById(200, function ($sessions) {
            foreach ($sessions as $session) {
                // Über das Model, damit die Verschlüsselung greift; ohne Events (kein Activity-Log)
                $session->metadata = null;
                $session->saveQuietly();
            }
        });

        $this->info("Technische Daten von {$count} Teilnahmen gelöscht (älter als {$days} Tage).");

        return self::SUCCESS;
    }
}
