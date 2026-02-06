{{-- Sidebar für das Hatch-Modul --}}
<div>
    {{-- Modul Header --}}
    <x-sidebar-module-header module-name="Hatch" />

    {{-- Abschnitt: Übersicht --}}
    <div>
        <h4 x-show="!collapsed" class="px-4 py-3 text-xs tracking-wide font-semibold text-[color:var(--ui-muted)] uppercase">Übersicht</h4>

        {{-- Dashboard --}}
        <a href="{{ route('hatch.dashboard') }}"
           class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname === '/' ||
               window.location.pathname.endsWith('/hatch') ||
               window.location.pathname.endsWith('/hatch/') ||
               (window.location.pathname.split('/').length === 1 && window.location.pathname === '/')
                   ? 'bg-[color:var(--ui-primary)] text-[color:var(--ui-on-primary)] shadow'
                   : 'text-[color:var(--ui-secondary)] hover:bg-[color:var(--ui-primary-5)] hover:text-[color:var(--ui-primary)]',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-chart-bar class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Dashboard</span>
        </a>
    </div>

    {{-- Abschnitt: Builder --}}
    <div>
        <h4 x-show="!collapsed" class="px-4 py-3 text-xs tracking-wide font-semibold text-[color:var(--ui-muted)] uppercase">Builder</h4>

        {{-- BlockDefinitionen --}}
        <a href="{{ route('hatch.block-definitions.index') }}"
           class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname.includes('/block-definitions')
                   ? 'bg-[color:var(--ui-primary)] text-[color:var(--ui-on-primary)] shadow'
                   : 'text-[color:var(--ui-secondary)] hover:bg-[color:var(--ui-primary-5)] hover:text-[color:var(--ui-primary)]',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-puzzle-piece class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">BlockDefinitionen</span>
        </a>

        {{-- Templates --}}
        <a href="{{ route('hatch.templates.index') }}"
           class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname.includes('/templates')
                   ? 'bg-[color:var(--ui-primary)] text-[color:var(--ui-on-primary)] shadow'
                   : 'text-[color:var(--ui-secondary)] hover:bg-[color:var(--ui-primary-5)] hover:text-[color:var(--ui-primary)]',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-document-text class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Templates</span>
        </a>
    </div>

    {{-- Abschnitt: Erhebungen --}}
    <div>
        <h4 x-show="!collapsed" class="px-4 py-3 text-xs tracking-wide font-semibold text-[color:var(--ui-muted)] uppercase">Erhebungen</h4>

        {{-- Erhebungen --}}
        <a href="{{ route('hatch.project-intakes.index') }}"
           class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname.includes('/project-intakes')
                   ? 'bg-[color:var(--ui-primary)] text-[color:var(--ui-on-primary)] shadow'
                   : 'text-[color:var(--ui-secondary)] hover:bg-[color:var(--ui-primary-5)] hover:text-[color:var(--ui-primary)]',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-rocket-launch class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Alle Erhebungen</span>
        </a>
    </div>
</div>
