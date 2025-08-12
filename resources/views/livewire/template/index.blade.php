<div class="p-3">
    <h1 class="text-2xl font-bold mb-4">Projekt-Templates</h1>

    <div class="d-flex justify-between mb-4">
        <x-ui-input-text 
            name="search" 
            placeholder="Suche Templates..." 
            class="w-64"
        />
        <x-ui-button variant="primary" wire:click="openCreateModal">
            Neues Template
        </x-ui-button>
    </div>
    
    <x-ui-table compact="true">
        <x-ui-table-header>
            <x-ui-table-header-cell compact="true">Name</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true">Beschreibung</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true" align="right">Aktionen</x-ui-table-header-cell>
        </x-ui-table-header>
        
        <x-ui-table-body>
            @foreach($templates as $template)
                <x-ui-table-row compact="true">
                    <x-ui-table-cell compact="true">
                        <div class="font-medium">{{ $template->name }}</div>
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true">
                        <div class="text-sm text-muted">
                            {{ $template->description ?: 'Keine Beschreibung' }}
                        </div>
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true" align="right">
                        <x-ui-button 
                            size="sm" 
                            variant="secondary" 
                            :href="route('hatch.templates.show', ['template' => $template->id])" 
                            wire:navigate
                        >
                            Bearbeiten
                        </x-ui-button>
                    </x-ui-table-cell>
                </x-ui-table-row>
            @endforeach
        </x-ui-table-body>
    </x-ui-table>

    <!-- Create Template Modal -->
    <x-ui-modal wire:model="modalShow" size="lg">
        <x-slot name="header">Template anlegen</x-slot>
        <div class="space-y-4">
            <form wire:submit.prevent="createTemplate" class="space-y-4">
                <x-ui-input-text
                    name="name"
                    label="Template-Name"
                    wire:model.live="name"
                    required
                    placeholder="Template-Name eingeben"
                />
                
                <x-ui-input-select
                    name="complexity_level"
                    label="Komplexität"
                    :options="$complexityLevels"
                    optionValue="name"
                    optionLabel="display_name"
                    wire:model.live="complexity_level"
                    required
                />
                
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text
                        name="ai_personality"
                        label="KI-Persönlichkeit"
                        wire:model.live="ai_personality"
                        placeholder="z.B. 'Freundlich und professionell'"
                    />
                    
                    <x-ui-input-text
                        name="industry_context"
                        label="Branche"
                        wire:model.live="industry_context"
                        placeholder="z.B. 'Software-Entwicklung'"
                    />
                </div>
                
                <x-ui-input-textarea
                    name="description"
                    label="Beschreibung"
                    wire:model.live="description"
                    placeholder="Beschreibung des Templates (optional)"
                    rows="3"
                />

                <x-ui-input-select
                    name="assistant_id"
                    label="AI Assistant (optional)"
                    :options="$assistants"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Assistant auswählen –"
                    wire:model.live="assistant_id"
                />
            </form>
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeCreateModal()">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="createTemplate">Template anlegen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</div>
