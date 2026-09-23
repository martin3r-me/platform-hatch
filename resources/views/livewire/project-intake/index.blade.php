<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Formulare', 'href' => route('hatch.dashboard'), 'icon' => 'rocket-launch'],
            ['label' => 'Erhebungen'],
        ]">
            <x-nx-button variant="primary" wire:click="openCreateModal">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neue Erhebung</span>
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

    @php
        $statusVariants = ['draft' => 'neutral', 'published' => 'success', 'closed' => 'warning'];
        $renderer = app(\Platform\Hatch\Support\IntakeStringRenderer::class);
        $filterAktiv = $search !== '' || $statusFilter !== '' || $templateFilter !== '';
    @endphp

    {{-- Kennzahlen: klickbar als Status-Filter --}}
    <x-nx-stat-grid>
        @foreach (['' => ['Gesamt', 'heroicon-o-rocket-launch', 'var(--nx-accent)', $stats['total']],
                   'draft' => ['Entwurf', 'heroicon-o-pencil-square', 'var(--nx-muted)', $stats['draft']],
                   'published' => ['Veröffentlicht', 'heroicon-o-signal', 'var(--nx-success)', $stats['published']],
                   'closed' => ['Geschlossen', 'heroicon-o-lock-closed', 'var(--nx-warning)', $stats['closed']]] as $key => [$label, $icon, $accent, $count])
            <button type="button" wire:click="setStatusFilter('{{ $key }}')" class="text-left">
                <x-nx-stat :label="$label" :value="(string) $count" :icon="$icon"
                    :accent="$count > 0 ? $accent : 'var(--nx-muted)'"
                    :class="\Illuminate\Support\Arr::toCssClasses([
                        'transition-colors hover:bg-[color:var(--nx-hover)]',
                        'ring-1 ring-[color:var(--nx-line-strong)] !bg-[color:var(--nx-active)]' => $statusFilter === $key,
                    ])" />
            </button>
        @endforeach
    </x-nx-stat-grid>

    {{-- Filter + Suche direkt über der Liste --}}
    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs">
        <div class="flex flex-wrap items-center gap-1">
            <button type="button" wire:click="$set('templateFilter', '')"
                class="rounded-full px-2.5 py-1 transition-colors {{ $templateFilter === '' ? 'bg-[color:var(--nx-active)] font-medium text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' }}">Alle Vorlagen</button>
            @foreach ($templates as $template)
                <button type="button" wire:click="$set('templateFilter', '{{ $template->id }}')"
                    class="rounded-full px-2.5 py-1 transition-colors {{ (string) $templateFilter === (string) $template->id ? 'bg-[color:var(--nx-active)] font-medium text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' }}">{{ $template->name }}</button>
            @endforeach
        </div>
        <div class="ml-auto flex items-center gap-2">
            @if($filterAktiv)
                <button type="button" wire:click="clearFilters" class="text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">Zurücksetzen</button>
            @endif
            <div class="w-64">
                <x-nx-input-text name="search" size="sm" wire:model.live.debounce.300ms="search" placeholder="Erhebungen suchen…" />
            </div>
        </div>
    </div>

    <x-nx-card flush>
        @if($projectIntakes->count() === 0)
            <x-nx-empty icon="heroicon-o-rocket-launch">
                @if($filterAktiv)
                    Keine Erhebung passt zu Suche oder Filter
                @else
                    Noch keine Erhebungen
                    <x-slot name="action">
                        <x-nx-button variant="primary" wire:click="openCreateModal">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Erste Erhebung anlegen</span>
                        </x-nx-button>
                    </x-slot>
                @endif
            </x-nx-empty>
        @else
            <x-nx-table>
                <x-nx-table-header>
                    <x-nx-table-header-cell>Name</x-nx-table-header-cell>
                    <x-nx-table-header-cell>Vorlage</x-nx-table-header-cell>
                    <x-nx-table-header-cell>Status</x-nx-table-header-cell>
                    <x-nx-table-header-cell align="right">Antworten</x-nx-table-header-cell>
                    <x-nx-table-header-cell>Erstellt</x-nx-table-header-cell>
                    <x-nx-table-header-cell align="right"><span class="sr-only">Aktionen</span></x-nx-table-header-cell>
                </x-nx-table-header>
                <x-nx-table-body>
                    @foreach($projectIntakes as $projectIntake)
                        @php $showUrl = route('hatch.project-intakes.show', ['projectIntake' => $projectIntake->id]); @endphp
                        <x-nx-table-row wire:key="project-intake-{{ $projectIntake->id }}" clickable :href="$showUrl">
                            <x-nx-table-cell class="max-w-md">
                                <a href="{{ $showUrl }}" wire:navigate class="block truncate font-medium text-[color:var(--nx-text)] hover:underline"
                                   title="{{ $projectIntake->name }}">{{ $renderer->render($projectIntake->name, $projectIntake) }}</a>
                                @if($projectIntake->description)
                                    <div class="truncate text-xs text-[color:var(--nx-faint)]" title="{{ $projectIntake->description }}">{{ $renderer->render($projectIntake->description, $projectIntake) }}</div>
                                @endif
                            </x-nx-table-cell>
                            <x-nx-table-cell class="whitespace-nowrap text-[color:var(--nx-muted)]">
                                {{ $projectIntake->projectTemplate->name ?? '–' }}
                            </x-nx-table-cell>
                            <x-nx-table-cell>
                                <x-nx-badge :variant="$statusVariants[$projectIntake->status] ?? 'neutral'" dot class="whitespace-nowrap">
                                    {{ $statuses[$projectIntake->status] ?? $projectIntake->status }}
                                </x-nx-badge>
                            </x-nx-table-cell>
                            <x-nx-table-cell align="right" class="tabular-nums text-[color:var(--nx-muted)]">{{ $projectIntake->sessions_count }}</x-nx-table-cell>
                            <x-nx-table-cell class="whitespace-nowrap">
                                <div class="tabular-nums text-[color:var(--nx-muted)]">{{ $projectIntake->created_at->format('d.m.Y') }}</div>
                                <div class="text-xs text-[color:var(--nx-faint)]">{{ $projectIntake->createdByUser->name ?? 'Unbekannt' }}</div>
                            </x-nx-table-cell>
                            <x-nx-table-cell align="right">
                                <x-nx-button icon variant="ghost" type="button" title="Erhebung löschen"
                                    onclick="event.stopPropagation()"
                                    wire:click="deleteProjectIntake('{{ $projectIntake->id }}')"
                                    wire:confirm="Erhebung wirklich löschen? Alle zugehörigen Sessions werden ebenfalls gelöscht. Diese Aktion kann nicht rückgängig gemacht werden.">
                                    @svg('heroicon-o-trash', 'w-4 h-4 text-[color:var(--nx-danger)]')
                                </x-nx-button>
                            </x-nx-table-cell>
                        </x-nx-table-row>
                    @endforeach
                </x-nx-table-body>
            </x-nx-table>
        @endif
    </x-nx-card>

    @if($projectIntakes->hasPages())
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-[color:var(--nx-faint)]">
            <span class="tabular-nums">{{ $projectIntakes->firstItem() }}–{{ $projectIntakes->lastItem() }} von {{ $projectIntakes->total() }} Erhebungen</span>
            {{ $projectIntakes->links() }}
        </div>
    @endif

    </div>
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
                <x-nx-button type="button" wire:click="closeCreateModal">Abbrechen</x-nx-button>
                <x-nx-button type="button" variant="primary" wire:click="createProjectIntake">Erhebung anlegen</x-nx-button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
