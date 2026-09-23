<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Formulare', 'href' => route('hatch.dashboard'), 'icon' => 'rocket-launch'],
            ['label' => 'Auswahllisten'],
        ]">
            <x-nx-button variant="primary" wire:click="openCreateModal">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neue Auswahlliste</span>
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
        Auswahllisten sind vordefinierte Antwortmöglichkeiten (z.&nbsp;B. Länder, Sprachen, Abteilungen),
        die in Bausteinen vom Typ „Auswahlliste“ verwendet werden.
    </p>

    {{-- Anlegen / Bearbeiten --}}
    @if($modalShow)
        @php $isSystem = $editingLookupId && \Platform\Hatch\Models\HatchLookup::find($editingLookupId)?->is_system; @endphp
        <x-nx-panel :title="$editingLookupId ? 'Auswahlliste bearbeiten' : 'Neue Auswahlliste'">
            <x-slot name="actions">
                <x-nx-button icon variant="ghost" wire:click="$set('modalShow', false)" title="Schließen">
                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                </x-nx-button>
            </x-slot>
            <div class="grid gap-3 md:grid-cols-3">
                <x-nx-input-text name="lookupLabel" label="Anzeigename" wire:model="lookupLabel" placeholder="z.B. Abteilungen" required />
                @if($isSystem)
                    <x-nx-input-text name="lookupName" label="Kürzel" hint="System, nicht änderbar" wire:model="lookupName" disabled />
                @else
                    <x-nx-input-text name="lookupName" label="Kürzel" hint="eindeutig" wire:model="lookupName" placeholder="z.B. abteilungen" />
                @endif
                <x-nx-input-text name="lookupDescription" label="Beschreibung" hint="optional" wire:model="lookupDescription" placeholder="Kurze Beschreibung der Liste" />
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <x-nx-button wire:click="$set('modalShow', false)">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="saveLookup">Speichern</x-nx-button>
            </div>
        </x-nx-panel>
    @endif

    {{-- Werte einer Liste --}}
    @if($editingValuesLookupId)
        @php $valLookup = \Platform\Hatch\Models\HatchLookup::find($editingValuesLookupId); @endphp
        <x-nx-panel :title="'Werte: ' . ($valLookup->label ?? '')" :subtitle="count($editingValues) . ' Einträge'" flush>
            <x-slot name="actions">
                <x-nx-button icon variant="ghost" wire:click="closeValues" title="Schließen">
                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                </x-nx-button>
            </x-slot>

            <div class="flex items-end gap-2 border-b border-[color:var(--nx-line)] p-4">
                <div class="flex-1"><x-nx-input-text name="newValueLabel" label="Anzeige" size="sm" wire:model="newValueLabel" wire:keydown.enter="addValue" placeholder="z.B. Buchhaltung" /></div>
                <div class="flex-1"><x-nx-input-text name="newValueValue" label="Wert" hint="optional" size="sm" wire:model="newValueValue" wire:keydown.enter="addValue" placeholder="z.B. buchhaltung" /></div>
                <x-nx-button variant="primary" wire:click="addValue">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Hinzufügen</span>
                </x-nx-button>
            </div>

            @if(count($editingValues) > 0)
                <div class="max-h-96 divide-y divide-[color:var(--nx-line)] overflow-y-auto">
                    @foreach($editingValues as $ev)
                        <div wire:key="lookup-value-{{ $ev['id'] }}" class="flex items-center gap-3 px-4 py-2 {{ $ev['is_active'] ? '' : 'opacity-50' }}">
                            <span class="w-6 shrink-0 text-right text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $ev['order'] }}</span>
                            <span class="min-w-0 flex-1 truncate text-sm text-[color:var(--nx-text)]">{{ $ev['label'] }}</span>
                            <span class="shrink-0 font-mono text-xs text-[color:var(--nx-faint)]">{{ $ev['value'] }}</span>
                            <button type="button" wire:click="toggleValueActive({{ $ev['id'] }})" title="Aktiv/Inaktiv umschalten">
                                <x-nx-badge :variant="$ev['is_active'] ? 'success' : 'neutral'" dot>{{ $ev['is_active'] ? 'Aktiv' : 'Inaktiv' }}</x-nx-badge>
                            </button>
                            <x-nx-button icon variant="ghost" wire:click="deleteValue({{ $ev['id'] }})" title="Wert löschen">
                                @svg('heroicon-o-trash', 'w-4 h-4 text-[color:var(--nx-danger)]')
                            </x-nx-button>
                        </div>
                    @endforeach
                </div>
            @else
                <x-nx-empty icon="heroicon-o-list-bullet">Noch keine Werte. Oben den ersten hinzufügen.</x-nx-empty>
            @endif
        </x-nx-panel>
    @endif

    {{-- Suche direkt über der Liste --}}
    <div class="flex items-center justify-end">
        <div class="w-64">
            <x-nx-input-text name="search" size="sm" wire:model.live.debounce.300ms="search" placeholder="Auswahllisten suchen…" />
        </div>
    </div>

    <x-nx-card flush>
        @if($lookups->count() === 0)
            <x-nx-empty icon="heroicon-o-list-bullet">
                @if($search !== '')
                    Keine Auswahlliste passt zur Suche
                @else
                    Noch keine Auswahllisten
                    <x-slot name="action">
                        <x-nx-button variant="primary" wire:click="openCreateModal">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Erste Auswahlliste anlegen</span>
                        </x-nx-button>
                    </x-slot>
                @endif
            </x-nx-empty>
        @else
            <x-nx-table>
                <x-nx-table-header>
                    <x-nx-table-header-cell>Name</x-nx-table-header-cell>
                    <x-nx-table-header-cell>Kürzel</x-nx-table-header-cell>
                    <x-nx-table-header-cell align="right">Werte</x-nx-table-header-cell>
                    <x-nx-table-header-cell>Typ</x-nx-table-header-cell>
                    <x-nx-table-header-cell align="right"><span class="sr-only">Aktionen</span></x-nx-table-header-cell>
                </x-nx-table-header>
                <x-nx-table-body>
                    @foreach($lookups as $lookup)
                        <x-nx-table-row wire:key="lookup-{{ $lookup->id }}">
                            <x-nx-table-cell class="max-w-md">
                                <button type="button" wire:click="openValues({{ $lookup->id }})" class="block max-w-full truncate text-left font-medium text-[color:var(--nx-text)] hover:underline">{{ $lookup->label }}</button>
                                @if($lookup->description)
                                    <div class="truncate text-xs text-[color:var(--nx-faint)]" title="{{ $lookup->description }}">{{ $lookup->description }}</div>
                                @endif
                            </x-nx-table-cell>
                            <x-nx-table-cell class="font-mono text-xs text-[color:var(--nx-muted)]">{{ $lookup->name }}</x-nx-table-cell>
                            <x-nx-table-cell align="right" class="tabular-nums text-[color:var(--nx-muted)]">{{ $lookup->values_count }}</x-nx-table-cell>
                            <x-nx-table-cell>
                                <x-nx-badge :variant="$lookup->is_system ? 'info' : 'neutral'">{{ $lookup->is_system ? 'System' : 'Eigene' }}</x-nx-badge>
                            </x-nx-table-cell>
                            <x-nx-table-cell align="right">
                                <div class="inline-flex items-center gap-0.5">
                                    <x-nx-button icon variant="ghost" wire:click="openValues({{ $lookup->id }})" title="Werte bearbeiten">
                                        @svg('heroicon-o-queue-list', 'w-4 h-4')
                                    </x-nx-button>
                                    <x-nx-button icon variant="ghost" wire:click="openEditModal({{ $lookup->id }})" title="Bearbeiten">
                                        @svg('heroicon-o-pencil-square', 'w-4 h-4')
                                    </x-nx-button>
                                    @if(!$lookup->is_system)
                                        <x-nx-button icon variant="ghost" wire:click="deleteLookup({{ $lookup->id }})" wire:confirm="Diese Auswahlliste wirklich löschen?" title="Löschen">
                                            @svg('heroicon-o-trash', 'w-4 h-4 text-[color:var(--nx-danger)]')
                                        </x-nx-button>
                                    @endif
                                </div>
                            </x-nx-table-cell>
                        </x-nx-table-row>
                    @endforeach
                </x-nx-table-body>
            </x-nx-table>
        @endif
    </x-nx-card>

    </div>
    </x-ui-page-container>
</x-ui-page>
