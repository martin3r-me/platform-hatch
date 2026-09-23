<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Formulare', 'href' => route('hatch.dashboard'), 'icon' => 'rocket-launch'],
            ['label' => 'Platzhalter'],
        ]">
            <x-nx-button variant="primary" wire:click="openCreate">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neuer Platzhalter</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" icon="heroicon-o-bolt" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <x-nx-empty icon="heroicon-o-bolt">Keine Aktivitäten verfügbar</x-nx-empty>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
    <div class="space-y-6">

    <p class="text-sm text-[color:var(--nx-muted)]">
        Platzhalter setzt man in Name und Beschreibung einer Erhebung als Baustein ein. Respondenten sehen dort den aktuellen Wert.
        Eigene Platzhalter haben einen Standardwert für das ganze Team, den jede Erhebung mit einem eigenen Wert überschreiben kann,
        z.&nbsp;B. „Standort“ = WCCB.
    </p>

    {{-- Anlegen / Bearbeiten --}}
    @if($formOpen)
        <x-nx-panel :title="$editingId ? 'Platzhalter bearbeiten' : 'Neuer Platzhalter'">
            <x-slot name="actions">
                <x-nx-button icon variant="ghost" wire:click="closeForm" title="Schließen">
                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                </x-nx-button>
            </x-slot>
            <div class="grid gap-3 md:grid-cols-2">
                <x-nx-input-text name="label" label="Name" hint="so erscheint der Baustein" wire:model.live.debounce.300ms="label" placeholder="z.B. Standort" required />
                @if($editingId)
                    <x-nx-input-text name="key" label="Kürzel" hint="nach dem Anlegen fest" wire:model="key" disabled />
                @else
                    <x-nx-input-text name="key" label="Kürzel" hint="für MCP / Import" wire:model.blur="key" placeholder="z.B. standort" />
                @endif
                <x-nx-input-text name="defaultValue" label="Standardwert" hint="optional, pro Erhebung überschreibbar" wire:model="defaultValue" placeholder="z.B. WCCB" />
                <x-nx-input-text name="description" label="Erklärung" hint="optional, erscheint im Einfügen-Menü" wire:model="description" placeholder="z.B. Name der Einrichtung" />
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <x-nx-button wire:click="closeForm">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
            </div>
        </x-nx-panel>
    @endif

    {{-- Eigene Platzhalter --}}
    <x-nx-section icon="heroicon-o-pencil-square" title="Eigene Platzhalter" :hint="(string) $customs->count()"
        description="Feste Werte, die ihr selbst pflegt">
        <x-nx-card flush>
            @if($customs->isEmpty())
                <x-nx-empty icon="heroicon-o-variable">
                    Noch keine eigenen Platzhalter
                    <x-slot name="action">
                        <x-nx-button variant="primary" wire:click="openCreate">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Ersten Platzhalter anlegen</span>
                        </x-nx-button>
                    </x-slot>
                </x-nx-empty>
            @else
                <x-nx-table>
                    <x-nx-table-header>
                        <x-nx-table-header-cell>Name</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Standardwert</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Kürzel</x-nx-table-header-cell>
                        <x-nx-table-header-cell align="right"><span class="sr-only">Aktionen</span></x-nx-table-header-cell>
                    </x-nx-table-header>
                    <x-nx-table-body>
                        @foreach($customs as $p)
                            <x-nx-table-row wire:key="ph-{{ $p->id }}">
                                <x-nx-table-cell>
                                    <span class="hatch-ph-chip-static">{{ $p->label }}</span>
                                    @if($p->description)
                                        <div class="mt-0.5 text-xs text-[color:var(--nx-faint)]">{{ $p->description }}</div>
                                    @endif
                                </x-nx-table-cell>
                                <x-nx-table-cell class="text-[color:var(--nx-text)]">
                                    @if($p->default_value !== null && $p->default_value !== '')
                                        {{ $p->default_value }}
                                    @else
                                        <span class="text-[color:var(--nx-faint)]">leer, nur pro Erhebung</span>
                                    @endif
                                </x-nx-table-cell>
                                <x-nx-table-cell class="font-mono text-xs text-[color:var(--nx-faint)]">{{ $p->token() }}</x-nx-table-cell>
                                <x-nx-table-cell align="right">
                                    <div class="inline-flex items-center gap-0.5">
                                        <x-nx-button icon variant="ghost" wire:click="openEdit({{ $p->id }})" title="Bearbeiten">
                                            @svg('heroicon-o-pencil-square', 'w-4 h-4')
                                        </x-nx-button>
                                        <x-nx-button icon variant="ghost" wire:click="delete({{ $p->id }})"
                                            wire:confirm="Platzhalter „{{ $p->label }}“ löschen? Texte, die ihn nutzen, zeigen danach den Code {{ $p->token() }} statt eines Werts."
                                            title="Löschen">
                                            @svg('heroicon-o-trash', 'w-4 h-4 text-[color:var(--nx-danger)]')
                                        </x-nx-button>
                                    </div>
                                </x-nx-table-cell>
                            </x-nx-table-row>
                        @endforeach
                    </x-nx-table-body>
                </x-nx-table>
            @endif
        </x-nx-card>
    </x-nx-section>

    {{-- Eingebaute Platzhalter --}}
    <x-nx-section icon="heroicon-o-cpu-chip" title="Automatische Platzhalter" :hint="(string) $builtins->count()"
        description="Werden bei jedem Aufruf berechnet und lassen sich nicht bearbeiten">
        <x-nx-card flush>
            <x-nx-table>
                <x-nx-table-header>
                    <x-nx-table-header-cell>Name</x-nx-table-header-cell>
                    <x-nx-table-header-cell>Heute</x-nx-table-header-cell>
                    <x-nx-table-header-cell>Kürzel</x-nx-table-header-cell>
                </x-nx-table-header>
                <x-nx-table-body>
                    @foreach($builtins as $p)
                        <x-nx-table-row wire:key="ph-builtin-{{ $p['key'] }}">
                            <x-nx-table-cell>
                                <span class="hatch-ph-chip-static">{{ $p['label'] }}</span>
                                <div class="mt-0.5 text-xs text-[color:var(--nx-faint)]">{{ $p['description'] }}</div>
                            </x-nx-table-cell>
                            <x-nx-table-cell class="tabular-nums text-[color:var(--nx-text)]">{{ $p['example'] }}</x-nx-table-cell>
                            <x-nx-table-cell class="font-mono text-xs text-[color:var(--nx-faint)]">{{ $p['token'] }}</x-nx-table-cell>
                        </x-nx-table-row>
                    @endforeach
                </x-nx-table-body>
            </x-nx-table>
        </x-nx-card>
    </x-nx-section>

    </div>
    </x-ui-page-container>

    <style>
        .hatch-ph-chip-static {
            display: inline-flex; align-items: center; padding: 0 .45rem; border-radius: 9999px;
            font-size: .75rem; line-height: 1.25rem; font-weight: 500; white-space: nowrap;
            color: var(--nx-info); background: rgba(25, 113, 194, .12);
        }
    </style>
</x-ui-page>
