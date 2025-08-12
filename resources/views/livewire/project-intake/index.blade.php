<div class="h-full overflow-y-auto p-6">
    {{-- Header --}}
    <div class="d-flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Projektierung</h1>
            <p class="text-gray-600 mt-1">Verwalte deine Projektierungsdurchläufe</p>
        </div>
        
        <x-ui-button 
            variant="primary" 
            size="lg"
            wire:click="openCreateModal"
        >
            <div class="d-flex items-center gap-2">
                @svg('heroicon-o-plus', 'w-5 h-5')
                Neue Projektierung
            </div>
        </x-ui-button>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="d-flex items-center gap-4">
            {{-- Suchfeld --}}
            <div class="flex-grow-1">
                <x-ui-input-text
                    name="search"
                    label="Suchen"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Projektierung suchen..."
                    class="w-full"
                />
            </div>

            {{-- Status Filter --}}
            <div class="w-48">
                <x-ui-input-select
                    name="statusFilter"
                    label="Status"
                    wire:model.live="statusFilter"
                    :options="collect($statuses)->map(function($label, $value) {
                        return ['value' => $value, 'label' => $label];
                    })->prepend(['value' => '', 'label' => 'Alle Status'])->values()"
                    optionValue="value"
                    optionLabel="label"
                    class="w-full"
                />
            </div>

            {{-- Template Filter --}}
            <div class="w-48">
                <x-ui-input-select
                    name="templateFilter"
                    label="Template"
                    wire:model.live="templateFilter"
                    :options="collect($templates)->map(function($template) {
                        return ['value' => $template->id, 'label' => $template->name];
                    })->prepend(['value' => '', 'label' => 'Alle Templates'])->values()"
                    optionValue="value"
                    optionLabel="label"
                    class="w-full"
                />
            </div>

            {{-- Filter zurücksetzen --}}
            <x-ui-button 
                variant="secondary" 
                size="sm"
                wire:click="clearFilters"
                class="self-end"
            >
                Filter zurücksetzen
            </x-ui-button>
        </div>
    </div>

    {{-- Tabelle --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        @if($projectIntakes->count() > 0)
            <x-ui-table compact="true">
                <x-ui-table-header>
                    <x-ui-table-header-cell compact="true">Name</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Template</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Erstellt von</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Erstellt am</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true" align="right">Aktionen</x-ui-table-header-cell>
                </x-ui-table-header>
                
                <x-ui-table-body>
                    @foreach($projectIntakes as $projectIntake)
                        <x-ui-table-row compact="true" wire:key="project-intake-{{ $projectIntake->id }}">
                            <x-ui-table-cell compact="true">
                                <div>
                                    <div class="font-medium">{{ $projectIntake->name }}</div>
                                    @if($projectIntake->description)
                                        <div class="text-sm text-muted">{{ Str::limit($projectIntake->description, 100) }}</div>
                                    @endif
                                </div>
                            </x-ui-table-cell>
                            
                                                            <x-ui-table-cell compact="true">
                                    @if($projectIntake->projectTemplate)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $projectIntake->projectTemplate->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </x-ui-table-cell>
                            
                            <x-ui-table-cell compact="true">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-800',
                                        'in_progress' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'paused' => 'bg-yellow-100 text-yellow-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusColor = $statusColors[$projectIntake->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                    {{ $statuses[$projectIntake->status] ?? $projectIntake->status }}
                                </span>
                            </x-ui-table-cell>
                            
                            <x-ui-table-cell compact="true">
                                <div class="text-sm text-gray-900">
                                    {{ $projectIntake->createdByUser->name ?? 'Unbekannt' }}
                                </div>
                            </x-ui-table-cell>
                            
                            <x-ui-table-cell compact="true">
                                <div class="text-sm text-gray-500">
                                    {{ $projectIntake->created_at->format('d.m.Y H:i') }}
                                </div>
                            </x-ui-table-cell>
                            
                            <x-ui-table-cell compact="true" align="right">
                                <x-ui-button 
                                    size="sm" 
                                    variant="secondary" 
                                    :href="route('hatch.project-intakes.show', ['projectIntake' => $projectIntake->id])" 
                                    wire:navigate
                                >
                                    Anzeigen
                                </x-ui-button>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                    @endforeach
                </x-ui-table-body>
            </x-ui-table>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $projectIntakes->links() }}
            </div>
        @else
            {{-- Leerer Zustand --}}
            <div class="text-center py-12">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    @svg('heroicon-o-rocket-launch', 'w-12 h-12')
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Projektierungen</h3>
                <p class="mt-1 text-sm text-gray-500">Erstelle deine erste Projektierung, um loszulegen.</p>
                <div class="mt-6">
                    <x-ui-button 
                        variant="primary" 
                        wire:click="createProjectIntake"
                    >
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Neue Projektierung
                        </div>
                    </x-ui-button>
                </div>
            </div>
        @endif
    </div>

    <!-- Create ProjectIntake Modal -->
    <x-ui-modal wire:model="modalShow" size="lg">
        <x-slot name="header">Neue Projektierung anlegen</x-slot>
        <div class="space-y-4">
            <form wire:submit.prevent="createProjectIntake" class="space-y-4">
                <x-ui-input-text
                    name="name"
                    label="Projektierungs-Name"
                    wire:model.live="name"
                    required
                    placeholder="Projektierungs-Name eingeben"
                />
                
                <x-ui-input-select
                    name="project_template_id"
                    label="Template"
                    :options="collect($templates)->map(function($template) {
                        return ['value' => $template->id, 'label' => $template->name];
                    })->values()"
                    optionValue="value"
                    optionLabel="label"
                    wire:model.live="project_template_id"
                    required
                    placeholder="Template auswählen"
                />
                
                <x-ui-input-select
                    name="status"
                    label="Status"
                    :options="collect($statuses)->map(function($label, $value) {
                        return ['value' => $value, 'label' => $label];
                    })->values()"
                    optionValue="value"
                    optionLabel="label"
                    wire:model.live="status"
                    required
                />
                
                <x-ui-input-textarea
                    name="description"
                    label="Beschreibung"
                    wire:model.live="description"
                    placeholder="Beschreibung der Projektierung (optional)"
                    rows="3"
                />
            </form>
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" wire:click="closeCreateModal">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="createProjectIntake">Projektierung anlegen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</div>
