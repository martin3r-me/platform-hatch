<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Projekt-Templates" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-input-text
                            name="search"
                            placeholder="Templates suchen..."
                            class="w-full"
                            size="sm"
                            wire:model.live.debounce.300ms="search"
                        />
                        <x-ui-button variant="secondary" size="sm" wire:click="openCreateModal" class="w-full justify-start">
                            @svg('heroicon-o-plus','w-4 h-4')
                            <span class="ml-2">Neues Template</span>
                        </x-ui-button>
                    </div>
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
            <p class="text-sm text-[color:var(--ui-muted)]">Templates legen die Struktur einer Erhebung fest. Ein Template besteht aus mehreren Blöcken (BlockDefinitionen) in einer bestimmten Reihenfolge. Konfiguriere hier Komplexität, KI-Persönlichkeit und Branchenkontext, um den Ablauf der Erhebung zu steuern.</p>
        </div>

        @if($templates->count() === 0)
            <div class="rounded-lg border border-dashed border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-8 text-center">
                @svg('heroicon-o-document-text', 'w-12 h-12 mx-auto mb-3 text-[color:var(--ui-muted)]')
                <h3 class="text-lg font-medium text-[color:var(--ui-secondary)] mb-1">Keine Templates vorhanden</h3>
                <p class="text-sm text-[color:var(--ui-muted)] max-w-md mx-auto">Templates legen die Struktur einer Erhebung fest. Erstelle ein Template und füge BlockDefinitionen als Blöcke hinzu.</p>
            </div>
        @else
            <x-ui-table compact="true">
                <x-ui-table-header>
                    <x-ui-table-header-cell compact="true">Name</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Beschreibung</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true" align="right">Aktionen</x-ui-table-header-cell>
                </x-ui-table-header>

                <x-ui-table-body>
                    @foreach($templates as $template)
                        <x-ui-table-row
                            compact="true"
                            clickable="true"
                            :href="route('hatch.templates.show', ['template' => $template->id])"
                        >
                            <x-ui-table-cell compact="true">
                                <div class="font-medium">{{ $template->name }}</div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <div class="text-sm text-[color:var(--ui-muted)]">
                                    {{ $template->description ?: '–' }}
                                </div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true" align="right">
                                <x-ui-button
                                    size="sm"
                                    variant="secondary"
                                    :href="route('hatch.templates.show', ['template' => $template->id])"
                                    wire:navigate
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

    <!-- Create Template Modal -->
    <x-ui-modal wire:model="modalShow" size="lg">
        <x-slot name="header">Template anlegen</x-slot>
        <div class="space-y-4">
            <p class="text-sm text-[var(--ui-muted)]">Ein Template definiert den Ablauf einer Erhebung. Du kannst nach dem Anlegen Blöcke hinzufügen und die Reihenfolge anpassen.</p>
            <form wire:submit.prevent="createTemplate" class="space-y-4">
                <x-ui-input-text
                    name="name"
                    label="Template-Name"
                    hint="Pflichtfeld"
                    wire:model.live="name"
                    required
                    placeholder="z.B. Standard-Projekterhebung"
                />

                <x-ui-input-select
                    name="complexity_level"
                    label="Komplexität"
                    hint="Steuert KI-Gesprächstiefe"
                    :options="$complexityLevels"
                    optionValue="name"
                    optionLabel="display_name"
                    wire:model.live="complexity_level"
                    required
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text
                        name="ai_personality"
                        label="KI-Persönlichkeit"
                        hint="Optional"
                        wire:model.live="ai_personality"
                        placeholder="z.B. freundlich, professionell"
                    />

                    <x-ui-input-text
                        name="industry_context"
                        label="Branchenkontext"
                        hint="Optional"
                        wire:model.live="industry_context"
                        placeholder="z.B. Software-Entwicklung"
                    />
                </div>

                <x-ui-input-textarea
                    name="description"
                    label="Beschreibung"
                    hint="Optional"
                    wire:model.live="description"
                    placeholder="Wofür wird dieses Template eingesetzt? Welche Art von Projekten?"
                    rows="3"
                />
            </form>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeCreateModal()">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="createTemplate">Template anlegen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
