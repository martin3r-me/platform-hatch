<?php

namespace Platform\Hatch\Livewire\Concerns;

/**
 * Benachrichtigung für die NÄCHSTE Seite vormerken.
 *
 * Nach dem Löschen + Redirect darf die alte Seite keine Benachrichtigung mehr
 * dispatchen: Livewire schickt den Folge-Request mit der Route der alten Seite
 * (z. B. /hatch/templates/7), deren Model-Binding dann ins Leere läuft → kurzer 404.
 * Stattdessen: flashNotice() vor dem Redirect, die Zielseite bindet
 * hatch::livewire.partials.flashed-notice ein.
 */
trait FlashesNotices
{
    protected function flashNotice(array $payload): void
    {
        session()->flash('hatch.notice', $payload);
    }
}
