<div class="d-flex h-full">
    <!-- Linke Spalte -->
    <div class="flex-grow-1 d-flex flex-col">
        <!-- Header oben (fix) -->
        <div class="border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <div class="d-flex gap-1">
                <div class="d-flex">
                    <a href="{{ route('hatch.templates.index') }}" class="d-flex px-3 border-right-solid border-right-1 border-right-muted underline" wire:navigate>
                        Templates
                    </a>
                </div>
                                            <div class="flex-grow-1 text-right d-flex items-center justify-end gap-2">
                                <span>{{ $template?->name ?? 'Template wird geladen...' }}</span>
                                @if($this->isDirty)
                                    <x-ui-button 
                                        variant="primary" 
                                        size="sm"
                                        wire:click="saveTemplate"
                                    >
                                        <div class="d-flex items-center gap-2">
                                            @svg('heroicon-o-check', 'w-4 h-4')
                                            Speichern
                                        </div>
                                    </x-ui-button>
                                @endif
                            </div>
            </div>
        </div>

        <!-- Haupt-Content (nimmt Restplatz, scrollt) -->
        <div class="flex-grow-1 overflow-y-auto p-4">
            

            
                                    {{-- Template-Blöcke --}}
                        <div class="mb-6">
                            <div class="d-flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-secondary">Template-Blöcke</h3>
                                <x-ui-button 
                                    variant="primary" 
                                    size="sm"
                                    wire:click="addBlock"
                                >
                                    <div class="d-flex items-center gap-2">
                                        @svg('heroicon-o-plus', 'w-4 h-4')
                                        Block hinzufügen
                                    </div>
                                </x-ui-button>
                            </div>

                            @if($template?->templateBlocks?->count() > 0)
                                <div wire:sortable="updateBlockOrder" class="space-y-3">
                                    @foreach($template->templateBlocks->sortBy('sort_order') as $block)
                                        <div wire:sortable.item="{{ $block->id }}" 
                                             class="bg-white border border-muted rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                                            
                                            <div class="d-flex items-center justify-between">
                                                <div class="d-flex items-center gap-3 flex-grow-1">
                                                    <div wire:sortable.handle class="cursor-move text-muted hover:text-primary">
                                                        <x-heroicon-o-bars-3 class="w-5 h-5" />
                                                    </div>
                                                    
                                                    @if($editingBlockId === $block->id)
                                                        <div class="flex-grow-1 space-y-2">
                                                            <x-ui-input-text 
                                                                name="editingBlock.name"
                                                                label="Block-Name"
                                                                wire:model.live.debounce.500ms="editingBlock.name"
                                                                placeholder="Block-Name eingeben"
                                                                class="w-full"
                                                            />
                                                            <x-ui-input-text 
                                                                name="editingBlock.description"
                                                                label="Beschreibung"
                                                                wire:model.live.debounce.500ms="editingBlock.description"
                                                                placeholder="Beschreibung eingeben"
                                                                class="w-full"
                                                            />
                                                            <x-ui-input-select
                                                                name="editingBlock.block_definition_id"
                                                                label="BlockDefinition"
                                                                wire:model.live.debounce.500ms="editingBlock.block_definition_id"
                                                                :options="$blockDefinitionOptions"
                                                                optionValue="id"
                                                                optionLabel="name"
                                                                placeholder="BlockDefinition auswählen"
                                                                class="w-full"
                                                            />
                                                            <div class="d-flex gap-2">
                                                                <x-ui-button 
                                                                    variant="primary" 
                                                                    size="sm"
                                                                    wire:click="saveBlock"
                                                                >
                                                                    Speichern
                                                                </x-ui-button>
                                                                <x-ui-button 
                                                                    variant="secondary" 
                                                                    size="sm"
                                                                    wire:click="cancelEditingBlock"
                                                                >
                                                                    Abbrechen
                                                                </x-ui-button>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="flex-grow-1">
                                                            <h4 class="font-medium text-gray-900">{{ $block->name }}</h4>
                                                            @if($block->description)
                                                                <p class="text-sm text-gray-600 mt-1">{{ $block->description }}</p>
                                                            @endif
                                                            <div class="d-flex items-center gap-2 mt-2">
                                                                <span class="text-xs text-muted">Sortierung: {{ $block->sort_order }}</span>
                                                                @if($block->is_required)
                                                                    <x-ui-badge variant="warning" size="xs">Pflicht</x-ui-badge>
                                                                @else
                                                                    <x-ui-badge variant="secondary" size="xs">Optional</x-ui-badge>
                                                                @endif
                                                                @if($block->blockDefinition)
                                                                    <x-ui-badge variant="info" size="xs">{{ $block->blockDefinition->name }}</x-ui-badge>
                                                                @else
                                                                    <x-ui-badge variant="secondary" size="xs">Keine Definition</x-ui-badge>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                @if($editingBlockId !== $block->id)
                                                    <div class="d-flex gap-2">
                                                        <x-ui-button 
                                                            variant="secondary" 
                                                            size="sm"
                                                            wire:click="startEditingBlock({{ $block->id }})"
                                                        >
                                                            <x-heroicon-o-pencil class="w-4 h-4" />
                                                        </x-ui-button>
                                                        <x-ui-button 
                                                            variant="danger" 
                                                            size="sm"
                                                            wire:click="deleteBlock({{ $block->id }})"
                                                            onclick="return confirm('Block wirklich löschen?')"
                                                        >
                                                            <x-heroicon-o-trash class="w-4 h-4" />
                                                        </x-ui-button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8 text-muted">
                                    <x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                                    <h4 class="text-lg font-medium text-gray-500">Keine Blöcke vorhanden</h4>
                                    <p class="text-gray-400">Füge den ersten Block hinzu, um dein Template zu konfigurieren.</p>
                                </div>
                            @endif
                        </div>
        </div>

        <!-- Aktivitäten (immer unten) -->
        <div x-data="{ open: false }" class="flex-shrink-0 border-t border-muted">
            <div 
                @click="open = !open" 
                class="cursor-pointer border-top-1 border-top-solid border-top-muted border-bottom-1 border-bottom-solid border-bottom-muted p-2 text-center d-flex items-center justify-center gap-1 mx-2 shadow-lg"
            >
                AKTIVITÄTEN 
                <span class="text-xs">
                    {{-- Hier später: $template->activities->count() --}}
                    0
                </span>
                <x-heroicon-o-chevron-double-down 
                    class="w-3 h-3" 
                    x-show="!open"
                />
                <x-heroicon-o-chevron-double-up 
                    class="w-3 h-3" 
                    x-show="open"
                />
            </div>
            <div x-show="open" class="p-2 max-h-xs overflow-y-auto">
                {{-- Hier später: <livewire:activity-log.index :model="$template" /> --}}
                <div class="text-center text-muted p-4">
                    <p>Keine Aktivitäten vorhanden</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rechte Spalte -->
    <div class="min-w-80 w-80 d-flex flex-col border-left-1 border-left-solid border-left-muted">

        <div class="d-flex gap-2 border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <x-heroicon-o-cog-6-tooth class="w-6 h-6"/>
            Template-Einstellungen
        </div>
        <div class="flex-grow-1 overflow-y-auto p-4">

            {{-- Navigation Buttons --}}
            <div class="d-flex flex-col gap-2 mb-4">
                <x-ui-button 
                    variant="secondary-outline" 
                    size="md" 
                    :href="route('hatch.templates.index')" 
                    wire:navigate
                    class="w-full"
                >
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                        Zurück zu Templates
                    </div>
                </x-ui-button>
            </div>

                                    {{-- Template-Übersicht --}}
                        <div class="mb-4 p-3 bg-muted-5 rounded-lg">
                            <h4 class="font-semibold mb-2 text-secondary">Template-Übersicht</h4>
                            <div class="space-y-3">
                                <x-ui-input-text 
                                    name="template.name"
                                    label="Name"
                                    wire:model.live.debounce.500ms="template.name"
                                    placeholder="Template-Name eingeben"
                                    required
                                    :errorKey="'template.name'"
                                />
                                
                                <x-ui-input-text 
                                    name="template.description"
                                    label="Beschreibung"
                                    wire:model.live.debounce.500ms="template.description"
                                    placeholder="Beschreibung eingeben"
                                    :errorKey="'template.description'"
                                />
                                
                                <x-ui-input-select
                                    name="template.complexity_level"
                                    label="Komplexität"
                                    :options="$complexityLevels"
                                    optionValue="name"
                                    optionLabel="display_name"
                                    wire:model.live="template.complexity_level"
                                    :errorKey="'template.complexity_level'"
                                />
                                
                                <x-ui-input-text 
                                    name="template.ai_personality"
                                    label="KI-Persönlichkeit"
                                    wire:model.live.debounce.500ms="template.ai_personality"
                                    placeholder="z.B. freundlich, professionell, kreativ"
                                    :errorKey="'template.ai_personality'"
                                />
                                
                                <x-ui-input-text 
                                    name="template.industry_context"
                                    label="Branche"
                                    wire:model.live.debounce.500ms="template.industry_context"
                                    placeholder="z.B. IT, Marketing, Finanzen"
                                    :errorKey="'template.industry_context'"
                                />
                            </div>
                        </div>

            <hr>

            {{-- Template-Status --}}
            <div class="mb-4">
                <h4 class="font-semibold mb-2">Status</h4>
                <div class="space-y-2">
                                    <x-ui-badge 
                    :variant="$template?->is_active ? 'success' : 'secondary'"
                    size="sm"
                >
                    {{ $template?->is_active ? 'Aktiv' : 'Inaktiv' }}
                </x-ui-badge>
                </div>
            </div>

            <hr>

            {{-- Erstellungsdaten --}}
            <div class="mb-4">
                <h4 class="font-semibold mb-2">Erstellungsdaten</h4>
                <div class="space-y-1 text-sm">
                                @if($template?->createdByUser)
                <div><strong>Erstellt von:</strong> {{ $template->createdByUser->name }}</div>
            @endif
            @if($template?->created_at)
                <div><strong>Erstellt am:</strong> {{ $template->created_at->format('d.m.Y H:i') }}</div>
            @endif
            @if($template?->updated_at)
                <div><strong>Zuletzt geändert:</strong> {{ $template->updated_at->format('d.m.Y H:i') }}</div>
            @endif
                </div>
            </div>

            <hr>

            {{-- AI Assistant --}}
            <div class="mb-4">
                <h4 class="font-semibold mb-2">AI Assistant</h4>
                <div class="space-y-2">
                                @php
                $assignedAssistant = $template?->assignedAiAssistant?->first();
            @endphp
                    @if($assignedAssistant)
                        <div class="d-flex items-center gap-2 p-2 bg-muted-5 rounded">
                            <x-heroicon-o-cpu-chip class="w-4 h-4 text-primary" />
                            <span class="flex-grow-1 text-sm">{{ $assignedAssistant->name }}</span>
                            <x-ui-badge variant="success" size="xs">Zugewiesen</x-ui-badge>
                        </div>
                    @else
                        <p class="text-sm text-muted">Kein AI Assistant zugewiesen.</p>
                    @endif
                    
                    <x-ui-input-select
                        name="assistant_id"
                        label="AI Assistant auswählen"
                        wire:model.live="assistant_id"
                        :options="$availableAssistants"
                        optionValue="id"
                        optionLabel="name"
                        placeholder="AI Assistant auswählen..."
                        class="w-full"
                    />
                </div>
            </div>

            <hr>

            {{-- Blöcke --}}
            <div class="mb-4">
                <h4 class="font-semibold mb-2">Blöcke</h4>
                <div class="space-y-2">
                    @if($template?->templateBlocks?->count() > 0)
                        @foreach($template->templateBlocks->take(3) as $block)
                            <div class="d-flex items-center gap-2 p-2 bg-muted-5 rounded">
                                <span class="flex-grow-1 text-sm">{{ $block->blockDefinition->name ?? 'Unbekannter Block' }}</span>
                                <x-ui-badge variant="primary" size="xs">{{ $block->sort_order }}</x-ui-badge>
                            </div>
                        @endforeach
                        @if($template->templateBlocks->count() > 3)
                            <div class="text-xs text-muted">+{{ $template->templateBlocks->count() - 3 }} weitere</div>
                        @endif
                    @else
                        <p class="text-sm text-muted">Noch keine Blöcke konfiguriert.</p>
                    @endif
                    <x-ui-button size="sm" variant="secondary-outline" class="w-full">
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Block hinzufügen
                        </div>
                    </x-ui-button>
                </div>
            </div>

        </div>
    </div>
</div>
