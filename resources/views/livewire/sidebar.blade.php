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
        <x-ui-sidebar-item :href="route('hatch.block-definitions.index')">
            @svg('heroicon-o-puzzle-piece', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Bausteine</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('hatch.templates.index')">
            @svg('heroicon-o-document-text', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Vorlagen</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('hatch.lookups.index')">
            @svg('heroicon-o-list-bullet', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Auswahllisten</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only für Allgemein --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('hatch.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
            <a href="{{ route('hatch.block-definitions.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-puzzle-piece', 'w-5 h-5')
            </a>
            <a href="{{ route('hatch.templates.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-document-text', 'w-5 h-5')
            </a>
            <a href="{{ route('hatch.lookups.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-list-bullet', 'w-5 h-5')
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
                        <a wire:key="unlinked-intake-{{ $intake->id }}"
                           href="{{ route('hatch.project-intakes.show', ['projectIntake' => $intake]) }}"
                           wire:navigate
                           title="{{ $intake->name }}"
                           class="flex items-center gap-1.5 py-0.5 pl-3 pr-2 text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition truncate">
                            <span class="w-1 h-1 rounded-full flex-shrink-0 bg-[var(--ui-muted)] opacity-40"></span>
                            <span class="truncate text-[11px]">{{ $intake->name }}</span>
                        </a>
                    @endforeach
                </x-ui-sidebar-list>
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
            @if($entityTypeGroups->isEmpty() && $unlinkedIntakes->isEmpty())
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
