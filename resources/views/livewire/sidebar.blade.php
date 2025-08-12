{{-- Sidebar für das Hatch-Modul --}}
<div>
    {{-- Abschnitt: Allgemein --}}
    <div>
        <h4 x-show="!collapsed" class="p-3 text-sm italic text-secondary uppercase">Allgemein</h4>

        {{-- Dashboard --}}
        <a href="{{ route('hatch.dashboard') }}"
           class="relative d-flex items-center p-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname === '/' || 
               window.location.pathname.endsWith('/hatch') || 
               window.location.pathname.endsWith('/hatch/') ||
               (window.location.pathname.split('/').length === 1 && window.location.pathname === '/')
                   ? 'bg-primary text-on-primary shadow-md'
                   : 'text-black hover:bg-primary-10 hover:text-primary hover:shadow-md',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-chart-bar class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Dashboard</span>
        </a>

        {{-- BlockDefinitionen --}}
        <a href="{{ route('hatch.block-definitions.index') }}"
           class="relative d-flex items-center p-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname.includes('/block-definitions') || 
               window.location.pathname.endsWith('/block-definitions') ||
               window.location.pathname.endsWith('/block-definitions/')
                   ? 'bg-primary text-on-primary shadow-md'
                   : 'text-black hover:bg-primary-10 hover:text-primary hover:shadow-md',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-puzzle-piece class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">BlockDefinitionen</span>
        </a>

        {{-- Templates --}}
        <a href="{{ route('hatch.templates.index') }}"
           class="relative d-flex items-center p-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname.includes('/templates') || 
               window.location.pathname.endsWith('/templates') ||
               window.location.pathname.endsWith('/templates/')
                   ? 'bg-primary text-on-primary shadow-md'
                   : 'text-black hover:bg-primary-10 hover:text-primary hover:shadow-md',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-document-text class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Templates</span>
        </a>

        {{-- Projektierung --}}
        <a href="{{ route('hatch.project-intakes.index') }}"
           class="relative d-flex items-center p-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname.includes('/project-intakes') || 
               window.location.pathname.endsWith('/project-intakes') ||
               window.location.pathname.endsWith('/project-intakes/')
                   ? 'bg-primary text-on-primary shadow-md'
                   : 'text-black hover:bg-primary-10 hover:text-primary hover:shadow-md',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-rocket-launch class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Projektierung</span>
        </a>
    </div>
</div>