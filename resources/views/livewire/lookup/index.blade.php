<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Lookups" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-input-text
                            name="search"
                            placeholder="Lookups suchen..."
                            class="w-full"
                            size="sm"
                            wire:model.live.debounce.300ms="search"
                        />
                        <x-ui-button variant="secondary" size="sm" wire:click="openCreateModal" class="w-full justify-start">
                            @svg('heroicon-o-plus','w-4 h-4')
                            <span class="ml-2">Neuer Lookup</span>
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
            <p class="text-sm text-[color:var(--ui-muted)]">Lookups sind vordefinierte Auswahllisten (z.B. Länder, Sprachen, benutzerdefinierte Listen), die in Block-Definitionen vom Typ "Lookup" verwendet werden können.</p>
        </div>

        {{-- Values Editor Modal --}}
        @if($editingValuesLookupId)
            @php $valLookup = \Platform\Hatch\Models\HatchLookup::find($editingValuesLookupId); @endphp
            <div class="mb-6 p-4 border border-[var(--ui-border)] rounded-lg bg-[var(--ui-surface)]">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">Werte: {{ $valLookup->label ?? '' }}</h3>
                        <p class="text-xs text-[var(--ui-muted)]">{{ count($editingValues) }} Einträge</p>
                    </div>
                    <x-ui-button variant="secondary" size="sm" wire:click="closeValues">Schliessen</x-ui-button>
                </div>

                {{-- Add new value --}}
                <div class="flex items-center gap-2 mb-4">
                    <x-ui-input-text name="newValueLabel" wire:model="newValueLabel" placeholder="Label" size="sm" class="flex-grow" />
                    <x-ui-input-text name="newValueValue" wire:model="newValueValue" placeholder="Wert (Schlüssel)" size="sm" class="flex-grow" />
                    <x-ui-button variant="primary" size="sm" wire:click="addValue">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                    </x-ui-button>
                </div>

                {{-- Values list --}}
                @if(count($editingValues) > 0)
                    <div class="max-h-96 overflow-y-auto space-y-1">
                        @foreach($editingValues as $ev)
                            <div class="flex items-center gap-2 p-2 rounded {{ $ev['is_active'] ? 'bg-[var(--ui-muted-5)]' : 'bg-red-50/50 opacity-60' }}">
                                <span class="text-xs text-[var(--ui-muted)] w-8 text-right">{{ $ev['order'] }}</span>
                                <span class="text-sm font-medium text-[var(--ui-secondary)] flex-grow">{{ $ev['label'] }}</span>
                                <span class="text-xs font-mono text-[var(--ui-muted)]">{{ $ev['value'] }}</span>
                                <button type="button" wire:click="toggleValueActive({{ $ev['id'] }})" class="text-xs {{ $ev['is_active'] ? 'text-emerald-600' : 'text-gray-400' }} hover:underline">
                                    {{ $ev['is_active'] ? 'Aktiv' : 'Inaktiv' }}
                                </button>
                                <button type="button" wire:click="deleteValue({{ $ev['id'] }})" class="text-red-400 hover:text-red-600">
                                    @svg('heroicon-o-trash', 'w-4 h-4')
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-[var(--ui-muted)] p-4 border border-dashed border-[var(--ui-border)]/60 rounded text-sm">Keine Werte vorhanden. Füge oben einen neuen Wert hinzu.</div>
                @endif
            </div>
        @endif

        {{-- Create/Edit Modal --}}
        @if($modalShow)
            <div class="mb-6 p-4 border border-[var(--ui-border)] rounded-lg bg-[var(--ui-surface)]">
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">{{ $editingLookupId ? 'Lookup bearbeiten' : 'Neuer Lookup' }}</h3>
                <div class="space-y-3">
                    <x-ui-input-text name="lookupName" label="Slug (Name)" hint="Eindeutiger Bezeichner" wire:model="lookupName" placeholder="z.B. abteilungen" size="sm" @if($editingLookupId && \Platform\Hatch\Models\HatchLookup::find($editingLookupId)?->is_system) disabled @endif />
                    <x-ui-input-text name="lookupLabel" label="Anzeigename" wire:model="lookupLabel" placeholder="z.B. Abteilungen" size="sm" />
                    <x-ui-input-text name="lookupDescription" label="Beschreibung" hint="Optional" wire:model="lookupDescription" placeholder="Kurze Beschreibung der Liste" size="sm" />
                    <div class="flex gap-2">
                        <x-ui-button variant="primary" size="sm" wire:click="saveLookup">Speichern</x-ui-button>
                        <x-ui-button variant="secondary" size="sm" wire:click="$set('modalShow', false)">Abbrechen</x-ui-button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Lookups Table --}}
        @if($lookups->count() === 0)
            <div class="rounded-lg border border-dashed border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-8 text-center">
                @svg('heroicon-o-list-bullet', 'w-12 h-12 mx-auto mb-3 text-[color:var(--ui-muted)]')
                <h3 class="text-lg font-medium text-[color:var(--ui-secondary)] mb-1">Keine Lookups vorhanden</h3>
                <p class="text-sm text-[color:var(--ui-muted)] max-w-md mx-auto">Erstelle einen Lookup oder führe den Seeder aus, um Länder und Sprachen vorzuladen.</p>
            </div>
        @else
            <x-ui-table compact="true">
                <x-ui-table-header>
                    <x-ui-table-header-cell compact="true">Name</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Anzeigename</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Beschreibung</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Werte</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">System</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true" align="right">Aktionen</x-ui-table-header-cell>
                </x-ui-table-header>

                <x-ui-table-body>
                    @foreach($lookups as $lookup)
                        <x-ui-table-row compact="true">
                            <x-ui-table-cell compact="true">
                                <span class="font-mono text-sm">{{ $lookup->name }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <div class="font-medium">{{ $lookup->label }}</div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <div class="text-sm text-[color:var(--ui-muted)]">
                                    {{ Str::limit($lookup->description, 50) ?: '–' }}
                                </div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge variant="secondary" size="sm">{{ $lookup->values_count }}</x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @if($lookup->is_system)
                                    <x-ui-badge variant="primary" size="sm">System</x-ui-badge>
                                @else
                                    <span class="text-sm text-[color:var(--ui-muted)]">–</span>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true" align="right">
                                <div class="flex gap-1 justify-end">
                                    <x-ui-button variant="secondary" size="sm" wire:click="openValues({{ $lookup->id }})">
                                        Werte
                                    </x-ui-button>
                                    <x-ui-button variant="secondary" size="sm" wire:click="openEditModal({{ $lookup->id }})">
                                        Bearbeiten
                                    </x-ui-button>
                                    @if(!$lookup->is_system)
                                        <x-ui-button variant="danger-outline" size="sm" wire:click="deleteLookup({{ $lookup->id }})" wire:confirm="Diesen Lookup wirklich löschen?">
                                            @svg('heroicon-o-trash', 'w-4 h-4')
                                        </x-ui-button>
                                    @endif
                                </div>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                    @endforeach
                </x-ui-table-body>
            </x-ui-table>
        @endif
    </x-ui-page-container>
</x-ui-page>
