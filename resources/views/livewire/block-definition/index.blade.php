<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Hatch', 'href' => route('hatch.dashboard'), 'icon' => 'rocket-launch'],
            ['label' => 'BlockDefinitionen'],
        ]">
            <x-ui-button variant="primary" size="sm" wire:click="createBlockDefinition">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neue BlockDefinition</span>
            </x-ui-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Suche" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Suche</h3>
                    <x-ui-input-text
                        name="search"
                        placeholder="BlockDefinitionen suchen..."
                        class="w-full"
                        size="sm"
                        wire:model.live.debounce.300ms="search"
                    />
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 text-sm text-[var(--ui-muted)]">Keine Aktivitäten verfügbar</div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        <div class="mb-6">
            <p class="text-sm text-[color:var(--ui-muted)]">BlockDefinitionen sind die einzelnen Bausteine einer Erhebung. Jede Definition beschreibt einen Feldtyp (z.B. Textfeld, Auswahl, Datum) und enthält Einstellungen wie Validierung, KI-Prompt und Antwort-Format. Erstelle hier die Bausteine und verwende sie anschliessend in deinen Templates.</p>
        </div>

        @if($blockDefinitions->count() === 0)
            <div class="rounded-lg border border-dashed border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-8 text-center">
                @svg('heroicon-o-puzzle-piece', 'w-12 h-12 mx-auto mb-3 text-[color:var(--ui-muted)]')
                <h3 class="text-lg font-medium text-[color:var(--ui-secondary)] mb-1">Keine BlockDefinitionen vorhanden</h3>
                <p class="text-sm text-[color:var(--ui-muted)] max-w-md mx-auto">BlockDefinitionen sind die Bausteine deiner Templates. Jede Definition beschreibt einen Feldtyp (z.B. Text, Auswahl, Datum) mit seinen Einstellungen.</p>
            </div>
        @else
            <x-ui-table compact="true">
                <x-ui-table-header>
                    <x-ui-table-header-cell compact="true">Name</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Beschreibung</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Typ</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Erstellt</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true" align="right">Aktionen</x-ui-table-header-cell>
                </x-ui-table-header>

                <x-ui-table-body>
                    @foreach($blockDefinitions as $definition)
                        <x-ui-table-row compact="true">
                            <x-ui-table-cell compact="true">
                                <div class="font-medium">{{ $definition->name }}</div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <div class="text-sm text-[color:var(--ui-muted)]">
                                    {{ Str::limit($definition->description, 60) ?: '–' }}
                                </div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge variant="secondary" size="sm">
                                    {{ $definition->getBlockTypeLabel() }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge
                                    variant="{{ $definition->is_active ? 'success' : 'secondary' }}"
                                    size="sm"
                                >
                                    {{ $definition->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[color:var(--ui-muted)]">
                                    {{ $definition->created_at->format('d.m.Y') }}
                                </span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true" align="right">
                                <x-ui-button
                                    variant="secondary"
                                    size="sm"
                                    wire:click="editBlockDefinition({{ $definition->id }})"
                                >
                                    Bearbeiten
                                </x-ui-button>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                    @endforeach
                </x-ui-table-body>
            </x-ui-table>
        @endif
    </x-ui-page-container>
</x-ui-page>
