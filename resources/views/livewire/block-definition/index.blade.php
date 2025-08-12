<div class="p-3">
    <h1 class="text-2xl font-bold mb-4">BlockDefinitionen</h1>

    <div class="d-flex justify-between mb-4">
        <x-ui-input-text 
            name="search" 
            placeholder="Suche BlockDefinitionen..." 
            class="w-64"
        />
        <x-ui-button variant="primary" wire:click="createBlockDefinition">
            Neue BlockDefinition
        </x-ui-button>
    </div>
    
    @if($blockDefinitions->count() > 0)
        {{-- Tabelle der BlockDefinitionen --}}
        <x-ui-table compact="true">
                <x-ui-table-header>
                    <x-ui-table-header-cell compact="true">Name</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Beschreibung</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Typ</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Erstellt</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true" align="right">Aktionen</x-ui-table-header-cell>
                </x-ui-table-header>
                
                <x-ui-table-body>
                    @foreach($blockDefinitions as $definition)
                        <x-ui-table-row compact="true">
                            <x-ui-table-cell compact="true">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $definition->name }}
                                </div>
                            </x-ui-table-cell>
                            
                            <x-ui-table-cell compact="true">
                                <div class="text-sm text-gray-900">
                                    {{ Str::limit($definition->description, 60) ?: '-' }}
                                </div>
                            </x-ui-table-cell>
                            
                            <x-ui-table-cell compact="true">
                                <x-ui-badge variant="secondary" size="sm">
                                    {{ $definition->getBlockTypeLabel() }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            
                            <x-ui-table-cell compact="true">
                                <x-ui-badge 
                                    variant="{{ $definition->is_active ? 'success' : 'secondary' }}" 
                                    size="sm"
                                >
                                    {{ $definition->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-gray-500">
                                    {{ $definition->created_at->format('d.m.Y') }}
                                </span>
                            </x-ui-table-cell>
                            
                            <x-ui-table-cell compact="true" align="right">
                                <x-ui-button 
                                    variant="secondary" 
                                    size="sm"
                                    wire:click="editBlockDefinition({{ $definition->id }})"
                                >
                                    <div class="d-flex items-center gap-2">
                                        <x-heroicon-o-pencil class="w-4 h-4" />
                                        Bearbeiten
                                    </div>
                                </x-ui-button>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                    @endforeach
                </x-ui-table-body>
            </x-ui-table>
        @else
            {{-- Leerer Zustand --}}
            <div class="text-center py-8 text-gray-500">
                <p>Keine BlockDefinitionen vorhanden.</p>
            </div>
        @endif
    </div>
</div>
