<?php

namespace Platform\Hatch\Livewire\Concerns;

/**
 * Seitenkontext fürs Core-Kontext-Panel (Reiter „Verknüpfungen“ usw.), sofern
 * der Core der Host-App ihn schon kennt.
 *
 * HasPageContext kam im Core erst am 2026-10-21. Ältere Hosts (z. B. demo) hätten
 * sonst einen Fatal Error beim Laden der Erhebungsseite – dort entfällt nur das
 * Panel. Die Komponente definiert pageContext()/pageContextOptions() wie gewohnt.
 */
if (trait_exists(\Platform\Core\Livewire\Concerns\HasPageContext::class)) {
    trait ProvidesPageContext
    {
        use \Platform\Core\Livewire\Concerns\HasPageContext;
    }
} else {
    trait ProvidesPageContext
    {
    }
}
