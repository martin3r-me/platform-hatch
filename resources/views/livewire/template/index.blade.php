<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Formulare', 'href' => route('hatch.dashboard'), 'icon' => 'rocket-launch'],
            ['label' => 'Vorlagen'],
        ]">
            <x-nx-button variant="primary" wire:click="openCreateModal">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neue Vorlage</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" icon="heroicon-o-bolt" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <x-nx-empty icon="heroicon-o-bolt">Keine Aktivitäten verfügbar</x-nx-empty>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
    <div class="space-y-5">

    <p class="text-sm text-[color:var(--nx-muted)]">
        Vorlagen legen die Struktur einer Erhebung fest: welche Bausteine in welcher Reihenfolge abgefragt werden.
        Aus einer Vorlage können beliebig viele Erhebungen entstehen, z.&nbsp;B. eine je Standort.
    </p>

    {{-- Filter + Suche direkt über der Liste statt in einer eigenen Spalte --}}
    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs">
        <div class="flex flex-wrap items-center gap-1">
            @foreach (['all' => 'Alle · ' . $stats['total'], 'active' => 'Aktiv · ' . $stats['active'], 'inactive' => 'Inaktiv · ' . ($stats['total'] - $stats['active'])] as $val => $label)
                <button type="button" wire:click="$set('statusFilter', '{{ $val }}')"
                    class="rounded-full px-2.5 py-1 tabular-nums transition-colors {{ $statusFilter === $val ? 'bg-[color:var(--nx-active)] font-medium text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="ml-auto w-64">
            <x-nx-input-text name="search" size="sm" wire:model.live.debounce.300ms="search" placeholder="Vorlagen suchen…" />
        </div>
    </div>

    <x-nx-card flush>
        @if($templates->count() === 0)
            <x-nx-empty icon="heroicon-o-document-text">
                @if($search !== '' || $statusFilter !== 'all')
                    Keine Vorlage passt zu Suche oder Filter
                @else
                    Noch keine Vorlagen
                    <x-slot name="action">
                        <x-nx-button variant="primary" wire:click="openCreateModal">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Erste Vorlage anlegen</span>
                        </x-nx-button>
                    </x-slot>
                @endif
            </x-nx-empty>
        @else
            <x-nx-table>
                <x-nx-table-header>
                    <x-nx-table-header-cell sortable sortField="name" :currentSort="$sortField" :sortDirection="$sortDirection">Name</x-nx-table-header-cell>
                    <x-nx-table-header-cell>Darstellung</x-nx-table-header-cell>
                    <x-nx-table-header-cell align="right">Bausteine</x-nx-table-header-cell>
                    <x-nx-table-header-cell align="right">Erhebungen</x-nx-table-header-cell>
                    <x-nx-table-header-cell>Status</x-nx-table-header-cell>
                    <x-nx-table-header-cell sortable sortField="updated_at" :currentSort="$sortField" :sortDirection="$sortDirection">Geändert</x-nx-table-header-cell>
                </x-nx-table-header>
                <x-nx-table-body>
                    @foreach($templates as $template)
                        <x-nx-table-row wire:key="template-{{ $template->id }}" clickable :href="route('hatch.templates.show', ['template' => $template->id])">
                            <x-nx-table-cell class="max-w-md">
                                <a href="{{ route('hatch.templates.show', ['template' => $template->id]) }}" wire:navigate
                                   class="block truncate font-medium text-[color:var(--nx-text)] hover:underline">{{ $template->name }}</a>
                                @if($template->description)
                                    <div class="truncate text-xs text-[color:var(--nx-faint)]" title="{{ $template->description }}">{{ $template->description }}</div>
                                @endif
                            </x-nx-table-cell>
                            <x-nx-table-cell class="whitespace-nowrap text-[color:var(--nx-muted)]">
                                {{ $template->flow_mode === \Platform\Hatch\Models\HatchProjectTemplate::FLOW_MODE_OVERVIEW ? 'Gesamtübersicht' : 'Schritt für Schritt' }}
                            </x-nx-table-cell>
                            <x-nx-table-cell align="right" class="tabular-nums text-[color:var(--nx-muted)]">{{ $template->template_blocks_count }}</x-nx-table-cell>
                            <x-nx-table-cell align="right" class="tabular-nums text-[color:var(--nx-muted)]">{{ $template->project_intakes_count }}</x-nx-table-cell>
                            <x-nx-table-cell>
                                <x-nx-badge :variant="$template->is_active ? 'success' : 'neutral'" dot>{{ $template->is_active ? 'Aktiv' : 'Inaktiv' }}</x-nx-badge>
                            </x-nx-table-cell>
                            <x-nx-table-cell class="whitespace-nowrap tabular-nums text-[color:var(--nx-muted)]" title="{{ $template->updated_at?->format('d.m.Y H:i') }}">
                                {{ $template->updated_at?->diffForHumans() ?? '–' }}
                            </x-nx-table-cell>
                        </x-nx-table-row>
                    @endforeach
                </x-nx-table-body>
            </x-nx-table>
        @endif
    </x-nx-card>

    @if($templates->hasPages())
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-[color:var(--nx-faint)]">
            <span class="tabular-nums">{{ $templates->firstItem() }}–{{ $templates->lastItem() }} von {{ $templates->total() }} Vorlagen</span>
            {{ $templates->links() }}
        </div>
    @endif

    </div>
    </x-ui-page-container>

    <!-- Create Template Modal -->
    <x-ui-modal wire:model="modalShow" size="lg">
        <x-slot name="header">Vorlage anlegen</x-slot>
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
                <x-nx-button type="button" wire:click="closeCreateModal">Abbrechen</x-nx-button>
                <x-nx-button type="button" variant="primary" wire:click="createTemplate">Vorlage anlegen</x-nx-button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
