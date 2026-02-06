<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Erhebungen" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-input-text
                            name="search"
                            placeholder="Erhebungen suchen..."
                            class="w-full"
                            size="sm"
                            wire:model.live.debounce.300ms="search"
                        />
                        <x-ui-button variant="secondary" size="sm" wire:click="openCreateModal" class="w-full justify-start">
                            @svg('heroicon-o-plus','w-4 h-4')
                            <span class="ml-2">Neue Erhebung</span>
                        </x-ui-button>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Filter</h3>
                    <div class="space-y-3">
                        <x-ui-input-select
                            name="statusFilter"
                            label="Status"
                            hint="Liste filtern"
                            :options="collect($statuses)->map(function($label, $value) {
                                return ['value' => $value, 'label' => $label];
                            })->values()"
                            optionValue="value"
                            optionLabel="label"
                            :nullable="true"
                            nullLabel="– Alle –"
                            size="sm"
                            wire:model.live="statusFilter"
                        />
                        <x-ui-input-select
                            name="templateFilter"
                            label="Template"
                            hint="Liste filtern"
                            :options="collect($templates)->map(function($template) {
                                return ['value' => $template->id, 'label' => $template->name];
                            })->values()"
                            optionValue="value"
                            optionLabel="label"
                            :nullable="true"
                            nullLabel="– Alle –"
                            size="sm"
                            wire:model.live="templateFilter"
                        />
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
            <p class="text-sm text-[color:var(--ui-muted)]">Erhebungen führen Nutzer durch die Blöcke eines Templates und sammeln strukturiert Informationen. Jede Erhebung basiert auf einem Template und durchläuft dessen Blöcke der Reihe nach. Filtere nach Status oder Template, um bestehende Erhebungen zu finden.</p>
        </div>

        @if($projectIntakes->count() === 0)
            <div class="rounded-lg border border-dashed border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-8 text-center">
                @svg('heroicon-o-rocket-launch', 'w-12 h-12 mx-auto mb-3 text-[color:var(--ui-muted)]')
                <h3 class="text-lg font-medium text-[color:var(--ui-secondary)] mb-1">Keine Erhebungen vorhanden</h3>
                <p class="text-sm text-[color:var(--ui-muted)] max-w-md mx-auto">Erhebungen sammeln Informationen basierend auf einem Template. Wähle ein Template und starte die erste Erhebung.</p>
            </div>
        @else
            <x-ui-table compact="true">
                <x-ui-table-header>
                    <x-ui-table-header-cell compact="true">Name</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Template</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Erstellt von</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Erstellt am</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true" align="right">Aktionen</x-ui-table-header-cell>
                </x-ui-table-header>

                <x-ui-table-body>
                    @foreach($projectIntakes as $projectIntake)
                        <x-ui-table-row
                            compact="true"
                            clickable="true"
                            :href="route('hatch.project-intakes.show', ['projectIntake' => $projectIntake->id])"
                            wire:key="project-intake-{{ $projectIntake->id }}"
                        >
                            <x-ui-table-cell compact="true">
                                <div class="font-medium">{{ $projectIntake->name }}</div>
                                @if($projectIntake->description)
                                    <div class="text-xs text-[color:var(--ui-muted)]">{{ Str::limit($projectIntake->description, 100) }}</div>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @if($projectIntake->projectTemplate)
                                    <x-ui-badge variant="secondary" size="sm">{{ $projectIntake->projectTemplate->name }}</x-ui-badge>
                                @else
                                    <span class="text-[color:var(--ui-muted)]">–</span>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @php
                                    $statusVariants = [
                                        'draft' => 'secondary',
                                        'in_progress' => 'primary',
                                        'completed' => 'success',
                                        'paused' => 'warning',
                                        'cancelled' => 'danger',
                                    ];
                                @endphp
                                <x-ui-badge variant="{{ $statusVariants[$projectIntake->status] ?? 'secondary' }}" size="sm">
                                    {{ $statuses[$projectIntake->status] ?? $projectIntake->status }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm">{{ $projectIntake->createdByUser->name ?? 'Unbekannt' }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[color:var(--ui-muted)]">{{ $projectIntake->created_at->format('d.m.Y H:i') }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true" align="right">
                                <x-ui-button
                                    size="sm"
                                    variant="secondary"
                                    :href="route('hatch.project-intakes.show', ['projectIntake' => $projectIntake->id])"
                                    wire:navigate
                                >
                                    Anzeigen
                                </x-ui-button>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                    @endforeach
                </x-ui-table-body>
            </x-ui-table>

            <div class="mt-4">
                {{ $projectIntakes->links() }}
            </div>
        @endif
    </x-ui-page-container>

    <!-- Create Modal -->
    <x-ui-modal wire:model="modalShow" size="lg">
        <x-slot name="header">Neue Erhebung anlegen</x-slot>
        <div class="space-y-4">
            <p class="text-sm text-[var(--ui-muted)]">Eine Erhebung führt einen Nutzer durch die Blöcke eines Templates und sammelt die Antworten. Wähle ein Template als Grundlage.</p>
            <form wire:submit.prevent="createProjectIntake" class="space-y-4">
                <x-ui-input-text
                    name="name"
                    label="Name"
                    hint="Pflichtfeld"
                    wire:model.live="name"
                    required
                    placeholder="z.B. Projekt Alpha – Ersterhebung"
                />

                <x-ui-input-select
                    name="project_template_id"
                    label="Template"
                    hint="Bestimmt die Blöcke"
                    :options="collect($templates)->map(function($template) {
                        return ['value' => $template->id, 'label' => $template->name];
                    })->values()"
                    optionValue="value"
                    optionLabel="label"
                    wire:model.live="project_template_id"
                    required
                    placeholder="Template auswählen"
                />

                <x-ui-input-select
                    name="status"
                    label="Status"
                    hint="Meist 'Entwurf'"
                    :options="collect($statuses)->map(function($label, $value) {
                        return ['value' => $value, 'label' => $label];
                    })->values()"
                    optionValue="value"
                    optionLabel="label"
                    wire:model.live="status"
                    required
                />

                <x-ui-input-textarea
                    name="description"
                    label="Beschreibung"
                    hint="Optional"
                    wire:model.live="description"
                    placeholder="Zusätzliche Notizen oder Kontext zur Erhebung"
                    rows="3"
                />
            </form>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" wire:click="closeCreateModal">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="createProjectIntake">Erhebung anlegen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
