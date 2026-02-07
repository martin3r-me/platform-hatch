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
        {{-- Stat-Karten --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <button wire:click="setStatusFilter('')"
                class="p-4 rounded-lg border text-left transition-colors {{ $statusFilter === '' ? 'border-[var(--ui-primary)] bg-[var(--ui-primary)]/5' : 'border-[var(--ui-border)] bg-[var(--ui-surface)] hover:border-[var(--ui-primary)]/50' }}">
                <div class="text-xs font-medium text-[var(--ui-muted)] uppercase tracking-wider">Gesamt</div>
                <div class="text-2xl font-bold text-[var(--ui-secondary)] mt-1">{{ $stats['total'] }}</div>
            </button>
            <button wire:click="setStatusFilter('draft')"
                class="p-4 rounded-lg border text-left transition-colors {{ $statusFilter === 'draft' ? 'border-[var(--ui-primary)] bg-[var(--ui-primary)]/5' : 'border-[var(--ui-border)] bg-[var(--ui-surface)] hover:border-[var(--ui-primary)]/50' }}">
                <div class="text-xs font-medium text-[var(--ui-muted)] uppercase tracking-wider">Entwurf</div>
                <div class="text-2xl font-bold text-[var(--ui-secondary)] mt-1">{{ $stats['draft'] }}</div>
            </button>
            <button wire:click="setStatusFilter('in_progress')"
                class="p-4 rounded-lg border text-left transition-colors {{ $statusFilter === 'in_progress' ? 'border-[var(--ui-primary)] bg-[var(--ui-primary)]/5' : 'border-[var(--ui-border)] bg-[var(--ui-surface)] hover:border-[var(--ui-primary)]/50' }}">
                <div class="text-xs font-medium text-[var(--ui-muted)] uppercase tracking-wider">Aktiv</div>
                <div class="text-2xl font-bold text-[var(--ui-secondary)] mt-1">{{ $stats['in_progress'] }}</div>
            </button>
            <button wire:click="setStatusFilter('completed')"
                class="p-4 rounded-lg border text-left transition-colors {{ $statusFilter === 'completed' ? 'border-[var(--ui-primary)] bg-[var(--ui-primary)]/5' : 'border-[var(--ui-border)] bg-[var(--ui-surface)] hover:border-[var(--ui-primary)]/50' }}">
                <div class="text-xs font-medium text-[var(--ui-muted)] uppercase tracking-wider">Fertig</div>
                <div class="text-2xl font-bold text-[var(--ui-secondary)] mt-1">{{ $stats['completed'] }}</div>
            </button>
        </div>

        @if($projectIntakes->count() === 0)
            <div class="rounded-lg border border-dashed border-[var(--ui-border)] bg-[var(--ui-surface)] p-12 text-center">
                @svg('heroicon-o-rocket-launch', 'w-16 h-16 mx-auto mb-4 text-[var(--ui-muted)]/60')
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Keine Erhebungen vorhanden</h3>
                <p class="text-sm text-[var(--ui-muted)] max-w-md mx-auto mb-5">Erhebungen sammeln Informationen basierend auf einem Template. Wähle ein Template und starte die erste Erhebung.</p>
                <x-ui-button variant="primary" size="sm" wire:click="openCreateModal">
                    <span class="flex items-center gap-2">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        Neue Erhebung anlegen
                    </span>
                </x-ui-button>
            </div>
        @else
            <x-ui-table compact="true">
                <x-ui-table-header>
                    <x-ui-table-header-cell compact="true">Name</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Template</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Sessions</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Erstellt von</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Erstellt am</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true" align="right"></x-ui-table-header-cell>
                </x-ui-table-header>

                <x-ui-table-body>
                    @foreach($projectIntakes as $projectIntake)
                        @php
                            $statusVariants = [
                                'draft' => 'secondary',
                                'in_progress' => 'primary',
                                'completed' => 'success',
                                'paused' => 'warning',
                                'cancelled' => 'danger',
                            ];
                            $statusColors = [
                                'draft' => 'bg-gray-400',
                                'in_progress' => 'bg-blue-500',
                                'completed' => 'bg-green-500',
                                'paused' => 'bg-amber-500',
                                'cancelled' => 'bg-red-500',
                            ];
                        @endphp
                        <x-ui-table-row
                            compact="true"
                            clickable="true"
                            :href="route('hatch.project-intakes.show', ['projectIntake' => $projectIntake->id])"
                            wire:key="project-intake-{{ $projectIntake->id }}"
                        >
                            <x-ui-table-cell compact="true">
                                <div class="flex items-center gap-3">
                                    <div class="w-1 h-8 rounded-full {{ $statusColors[$projectIntake->status] ?? 'bg-gray-400' }} flex-shrink-0"></div>
                                    <div>
                                        <div class="font-medium text-[var(--ui-secondary)]">{{ $projectIntake->name }}</div>
                                        @if($projectIntake->description)
                                            <div class="text-xs text-[var(--ui-muted)]">{{ Str::limit($projectIntake->description, 80) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @if($projectIntake->projectTemplate)
                                    <x-ui-badge variant="secondary" size="sm">{{ $projectIntake->projectTemplate->name }}</x-ui-badge>
                                @else
                                    <span class="text-[var(--ui-muted)]">–</span>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge variant="{{ $statusVariants[$projectIntake->status] ?? 'secondary' }}" size="sm">
                                    {{ $statuses[$projectIntake->status] ?? $projectIntake->status }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge variant="secondary" size="sm">{{ $projectIntake->sessions_count }}</x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm">{{ $projectIntake->createdByUser->name ?? 'Unbekannt' }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $projectIntake->created_at->format('d.m.Y H:i') }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true" align="right">
                                <div class="flex items-center gap-2 justify-end">
                                    <button
                                        type="button"
                                        wire:click.stop="deleteProjectIntake('{{ $projectIntake->id }}')"
                                        wire:confirm="Erhebung wirklich löschen? Alle zugehörigen Sessions werden ebenfalls gelöscht. Diese Aktion kann nicht rückgängig gemacht werden."
                                        class="text-[var(--ui-muted)] hover:text-red-500 transition-colors"
                                        title="Erhebung löschen"
                                    >
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                    </button>
                                    <a
                                        href="{{ route('hatch.project-intakes.show', ['projectIntake' => $projectIntake->id]) }}"
                                        wire:navigate
                                        class="text-[var(--ui-muted)] hover:text-[var(--ui-primary)] transition-colors"
                                        title="Anzeigen"
                                    >
                                        @svg('heroicon-o-chevron-right', 'w-5 h-5')
                                    </a>
                                </div>
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
