{{-- Sidebar für das Formulare-Modul --}}
<div
    x-data="{
        init() {
            const savedState = localStorage.getItem('formulare.showAllIntakes');
            if (savedState !== null) {
                @this.set('showAllIntakes', savedState === 'true');
            }
        }
    }"
>
    {{-- Modul Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Formulare
    </div>

    {{-- Abschnitt: Allgemein (über UI-Komponenten) --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('hatch.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('hatch.project-intakes.index')">
            @svg('heroicon-o-rocket-launch', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Erhebungen</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('hatch.templates.index')">
            @svg('heroicon-o-document-text', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Vorlagen</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('hatch.lookups.index')">
            @svg('heroicon-o-list-bullet', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Auswahllisten</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('hatch.placeholders.index')">
            @svg('heroicon-o-variable', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Platzhalter</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only für Allgemein --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('hatch.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
            <a href="{{ route('hatch.project-intakes.index') }}" wire:navigate title="Erhebungen" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-rocket-launch', 'w-5 h-5')
            </a>
            <a href="{{ route('hatch.templates.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-document-text', 'w-5 h-5')
            </a>
            <a href="{{ route('hatch.lookups.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-list-bullet', 'w-5 h-5')
            </a>
            <a href="{{ route('hatch.placeholders.index') }}" wire:navigate title="Platzhalter" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-variable', 'w-5 h-5')
            </a>
        </div>
    </div>

    {{-- Abschnitt: Erhebungen (Entity-basierte Gruppierung) --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            {{-- Entity Type Gruppen (Baum-Darstellung) --}}
            @foreach($entityTypeGroups as $typeGroup)
                <x-ui-sidebar-list wire:key="type-group-{{ $typeGroup['type_id'] }}" :label="$typeGroup['type_name']">
                    @foreach($typeGroup['entities'] as $entityNode)
                        @include('hatch::livewire.partials.sidebar-entity-node', [
                            'node' => $entityNode,
                            'typeIcon' => $typeGroup['type_icon'] ?? null,
                        ])
                    @endforeach
                </x-ui-sidebar-list>
            @endforeach

            {{-- Unverknüpfte Erhebungen --}}
            @if($unlinkedIntakes->isNotEmpty())
                <x-ui-sidebar-list label="Unverknüpft">
                    @foreach($unlinkedIntakes as $intake)
                        <div wire:key="unlinked-intake-{{ $intake->id }}">
                            @include('hatch::livewire.partials.sidebar-intake-link', ['intake' => $intake])
                        </div>
                    @endforeach
                </x-ui-sidebar-list>
            @endif

            {{-- Geschlossene Erhebungen: eigener, standardmäßig eingeklappter Bereich --}}
            @if($closedIntakes->isNotEmpty())
                <div x-data="{ open: localStorage.getItem('formulare.closedOpen') === 'true' }" class="mt-2">
                    <button type="button"
                            @click="open = !open; localStorage.setItem('formulare.closedOpen', open)"
                            class="flex w-full items-center gap-1 px-3 py-1 text-left text-xs text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors">
                        <span class="w-3 h-3 flex-shrink-0 flex items-center justify-center transition-transform" :class="open ? 'rotate-90' : ''">
                            @svg('heroicon-o-chevron-right', 'w-2.5 h-2.5')
                        </span>
                        <span>Abgeschlossen</span>
                        <span class="ml-auto text-[10px] tabular-nums opacity-60">{{ $closedIntakes->count() }}</span>
                    </button>
                    <div x-show="open" x-cloak class="flex flex-col">
                        @foreach($closedIntakes as $intake)
                            <div wire:key="closed-intake-{{ $intake->id }}">
                                @include('hatch::livewire.partials.sidebar-intake-link', ['intake' => $intake, 'closed' => true])
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Button zum Ein-/Ausblenden aller Erhebungen --}}
            @if($hasMoreIntakes)
                <div class="px-3 py-2">
                    <button
                        type="button"
                        wire:click="toggleShowAllIntakes"
                        x-on:click="localStorage.setItem('formulare.showAllIntakes', (!$wire.showAllIntakes).toString())"
                        class="flex items-center gap-2 text-xs text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                    >
                        @if($showAllIntakes)
                            @svg('heroicon-o-eye-slash', 'w-4 h-4')
                            <span>Nur meine Erhebungen</span>
                        @else
                            @svg('heroicon-o-eye', 'w-4 h-4')
                            <span>Alle Erhebungen anzeigen</span>
                        @endif
                    </button>
                </div>
            @endif

            {{-- Keine Erhebungen --}}
            @if($entityTypeGroups->isEmpty() && $unlinkedIntakes->isEmpty() && $closedIntakes->isEmpty())
                <div class="px-3 py-1 text-xs text-[var(--ui-muted)]">
                    @if($showAllIntakes)
                        Keine Erhebungen
                    @else
                        Keine eigenen Erhebungen
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
