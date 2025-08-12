<div class="d-flex h-full">
    <!-- Linke Spalte (Haupt-Content) -->
    <div class="flex-grow-1 d-flex flex-col">
        <!-- Header oben (fix) -->
        <div class="border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <div class="d-flex gap-1">
                <div class="d-flex">
                    <a href="{{ route('hatch.block-definitions.index') }}" class="d-flex px-3 border-right-solid border-right-1 border-right-muted underline" wire:navigate>
                        BlockDefinitionen
                    </a>
                </div>
                                        <div class="flex-grow-1 text-right d-flex items-center justify-end gap-2">
                            <span>{{ $blockDefinition->name }}</span>
                            @if($this->isDirty)
                                <x-ui-button 
                                    variant="primary" 
                                    size="sm"
                                    wire:click="saveBlockDefinition"
                                >
                                    <div class="d-flex items-center gap-2">@svg('heroicon-o-check', 'w-4 h-4') Speichern</div>
                                </x-ui-button>
                            @endif
                        </div>
            </div>
        </div>
        
        <!-- Haupt-Content (scrollt) -->
        <div class="flex-grow-1 overflow-y-auto p-4">
            {{-- KI-Konfiguration --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-secondary mb-4">KI-Konfiguration</h3>
                <div class="space-y-4">
                    <x-ui-input-textarea 
                        name="blockDefinition.ai_prompt"
                        label="KI-Prompt"
                        wire:model.live.debounce.500ms="blockDefinition.ai_prompt"
                        placeholder="Geben Sie hier detaillierte Anweisungen für die KI ein..."
                        :errorKey="'blockDefinition.ai_prompt'"
                        rows="6"
                    />
                </div>
            </div>

                    {{-- Response Format --}}
        <div class="mb-6">
            <div class="d-flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-secondary">Antwort-Format</h3>
                <div class="d-flex gap-2">
                    <x-ui-button 
                        variant="secondary" 
                        size="sm"
                        wire:click="resetResponseFormat"
                    >
                        <div class="d-flex items-center gap-2">@svg('heroicon-o-trash', 'w-4 h-4') Zurücksetzen</div>
                    </x-ui-button>
                    <x-ui-button 
                        variant="secondary" 
                        size="sm"
                        wire:click="addResponseFormat"
                    >
                        <div class="d-flex items-center gap-2">@svg('heroicon-o-plus', 'w-4 h-4') Format hinzufügen</div>
                    </x-ui-button>
                </div>
            </div>
            <div class="space-y-3">
                @if($responseFormatInput && count($responseFormatInput) > 0)
                    @foreach($responseFormatInput as $index => $format)
                        <div class="p-3 border border-muted rounded-lg bg-muted-5">
                            <div class="d-flex items-center justify-between mb-2">
                                <span class="text-sm font-medium">Format {{ $index + 1 }}</span>
                                <x-ui-button 
                                    variant="danger-outline" 
                                    size="sm"
                                    wire:click="removeResponseFormat({{ $index }})"
                                >
                                    @svg('heroicon-o-trash', 'w-4 h-4')
                                </x-ui-button>
                            </div>
                            <div class="space-y-3">
                                <x-ui-input-select
                                    name="response_format.{{ $index }}.type"
                                    label="Datentyp"
                                    wire:model.live.debounce.500ms="responseFormatInput.{{ $index }}.type"
                                    :options="collect([
                                        ['value' => 'string', 'label' => 'Text (String)'],
                                        ['value' => 'number', 'label' => 'Zahl (Number)'],
                                        ['value' => 'boolean', 'label' => 'Ja/Nein (Boolean)'],
                                        ['value' => 'date', 'label' => 'Datum (Date)'],
                                        ['value' => 'email', 'label' => 'E-Mail (Email)'],
                                        ['value' => 'url', 'label' => 'URL (URL)'],
                                        ['value' => 'array', 'label' => 'Liste (Array)'],
                                        ['value' => 'object', 'label' => 'Objekt (Object)']
                                    ])"
                                    optionValue="value"
                                    optionLabel="label"
                                    size="sm"
                                />
                                <x-ui-input-text 
                                    name="response_format.{{ $index }}.description"
                                    label="Beschreibung"
                                    wire:model.live.debounce.500ms="responseFormatInput.{{ $index }}.description"
                                    placeholder="Was soll die KI zurückgeben?"
                                    size="sm"
                                />
                                <x-ui-input-text 
                                    name="response_format.{{ $index }}.constraints"
                                    label="Einschränkungen (optional)"
                                    wire:model.live.debounce.500ms="responseFormatInput.{{ $index }}.constraints"
                                    placeholder="z.B. min: 0, max: 1000000"
                                    size="sm"
                                />

                                {{-- Validierungen für dieses Feld --}}
                                <div class="border-t border-muted pt-3">
                                    <div class="d-flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-muted">Validierungen</span>
                                        <x-ui-button 
                                            variant="secondary" 
                                            size="sm"
                                            wire:click="addValidationForField({{ $index }})"
                                        >
                                            <div class="d-flex items-center gap-2">@svg('heroicon-o-plus', 'w-4 h-4') Validierung hinzufügen</div>
                                        </x-ui-button>
                                    </div>
                                    <div class="space-y-2">
                                        @if(isset($responseFormatInput[$index]['validations']) && count($responseFormatInput[$index]['validations']) > 0)
                                            {{-- Kompakte Tabelle für Validierungen --}}
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-xs">
                                                    <thead class="bg-muted-10 border-b border-muted">
                                                        <tr>
                                                            <th class="text-left p-2 font-medium text-muted">Typ</th>
                                                            <th class="text-left p-2 font-medium text-muted">Fehlermeldung</th>
                                                            <th class="text-left p-2 font-medium text-muted">Parameter</th>
                                                            <th class="text-left p-2 font-medium text-muted w-16">Aktion</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($responseFormatInput[$index]['validations'] as $validationIndex => $validation)
                                                            <tr class="border-b border-muted-20 hover:bg-muted-5">
                                                                <td class="p-2">
                                                                    <x-ui-input-select
                                                                        name="response_format.{{ $index }}.validations.{{ $validationIndex }}.type"
                                                                        wire:model.live.debounce.500ms="responseFormatInput.{{ $index }}.validations.{{ $validationIndex }}.type"
                                                                        :options="collect([
                                                                            ['value' => 'required', 'label' => 'Erforderlich'],
                                                                            ['value' => 'min_length', 'label' => 'Min Länge'],
                                                                            ['value' => 'max_length', 'label' => 'Max Länge'],
                                                                            ['value' => 'email', 'label' => 'E-Mail'],
                                                                            ['value' => 'url', 'label' => 'URL'],
                                                                            ['value' => 'regex', 'label' => 'Regex'],
                                                                            ['value' => 'numeric', 'label' => 'Nur Zahlen'],
                                                                            ['value' => 'alpha', 'label' => 'Nur Buchstaben']
                                                                        ])"
                                                                        optionValue="value"
                                                                        optionLabel="label"
                                                                        size="sm"
                                                                        class="min-w-32"
                                                                    />
                                                                </td>
                                                                <td class="p-2">
                                                                    <x-ui-input-text 
                                                                        name="response_format.{{ $index }}.validations.{{ $validationIndex }}.message"
                                                                        wire:model.live.debounce.500ms="responseFormatInput.{{ $index }}.validations.{{ $validationIndex }}.message"
                                                                        placeholder="Fehlermeldung"
                                                                        size="sm"
                                                                        class="min-w-40"
                                                                    />
                                                                </td>
                                                                <td class="p-2">
                                                                    <x-ui-input-text 
                                                                        name="response_format.{{ $index }}.validations.{{ $validationIndex }}.params"
                                                                        wire:model.live.debounce.500ms="responseFormatInput.{{ $index }}.validations.{{ $validationIndex }}.params"
                                                                        placeholder="z.B. 2"
                                                                        size="sm"
                                                                        class="min-w-24"
                                                                    />
                                                                </td>
                                                                <td class="p-2">
                                                                    <x-ui-button 
                                                                        variant="danger-outline" 
                                                                        size="sm"
                                                                        wire:click="removeValidationFromField({{ $index }}, {{ $validationIndex }})"
                                                                        class="w-full"
                                                                    >
                                                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                                                    </x-ui-button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center text-muted p-2 border border-dashed border-muted rounded text-xs">
                                                <p>Keine Validierungen definiert</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center text-muted p-4 border border-dashed border-muted rounded-lg">
                        <p>Noch keine Antwort-Formate definiert</p>
                    </div>
                @endif
            </div>
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
                    {{-- Hier später: $blockDefinition->activities->count() --}}
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
                {{-- Hier später: <livewire:activity-log.index :model="$blockDefinition" /> --}}
                <div class="text-center text-muted p-4">
                    <p>Keine Aktivitäten vorhanden</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Rechte Spalte -->
    <div class="min-w-80 w-80 d-flex flex-col border-left-1 border-left-solid border-left-muted">
        <div class="d-flex gap-2 border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <x-heroicon-o-cog-6-tooth class="w-6 h-6"/> BlockDefinition-Einstellungen
        </div>
        <div class="flex-grow-1 overflow-y-auto p-4">
            {{-- Navigation Buttons --}}
            <div class="d-flex flex-col gap-2 mb-4">
                <x-ui-button 
                    variant="secondary-outline" 
                    size="md" 
                    :href="route('hatch.block-definitions.index')" 
                    wire:navigate
                    class="w-full"
                >
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                        Zurück zu BlockDefinitionen
                    </div>
                </x-ui-button>
            </div>

            {{-- Grundkonfiguration --}}
            <div class="mb-4 p-3 bg-muted-5 rounded-lg">
                <h4 class="font-semibold mb-2 text-secondary">Grundkonfiguration</h4>
                <div class="space-y-3">
                    <x-ui-input-text 
                        name="blockDefinition.name"
                        label="Name"
                        wire:model.live.debounce.500ms="blockDefinition.name"
                        placeholder="Block-Name eingeben"
                        required
                        :errorKey="'blockDefinition.name'"
                    />
                    
                    <x-ui-input-text 
                        name="blockDefinition.description"
                        label="Beschreibung"
                        wire:model.live.debounce.500ms="blockDefinition.description"
                        placeholder="Beschreibung eingeben"
                        :errorKey="'blockDefinition.description'"
                    />
                    
                    <x-ui-input-select
                        name="blockDefinition.block_type"
                        label="Block-Typ"
                        :options="$blockTypeOptions"
                        optionValue="value"
                        optionLabel="label"
                        wire:model.live="blockDefinition.block_type"
                        :errorKey="'blockDefinition.block_type'"
                    />
                </div>
            </div>



            {{-- Status --}}
            <div class="mb-4 p-3 bg-muted-5 rounded-lg">
                <h4 class="font-semibold mb-2 text-secondary">Status</h4>
                <div class="space-y-3">
                    <div class="d-flex items-center gap-2">
                        <x-ui-badge 
                            :variant="$blockDefinition->is_active ? 'success' : 'secondary'" 
                            size="sm"
                        >
                            {{ $blockDefinition->is_active ? 'Aktiv' : 'Inaktiv' }}
                        </x-ui-badge>
                        <x-ui-button 
                            variant="secondary-outline" 
                            size="sm"
                            wire:click="toggleActive"
                        >
                            {{ $blockDefinition->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                        </x-ui-button>
                    </div>
                </div>
            </div>

            {{-- Erstellungsdaten --}}
            <div class="mb-4 p-3 bg-muted-5 rounded-lg">
                <h4 class="font-semibold mb-2 text-secondary">Erstellungsdaten</h4>
                <div class="space-y-1 text-sm">
                    @if($blockDefinition->createdByUser)
                        <div><strong>Erstellt von:</strong> {{ $blockDefinition->createdByUser->name }}</div>
                    @endif
                    @if($blockDefinition->created_at)
                        <div><strong>Erstellt am:</strong> {{ $blockDefinition->created_at->format('d.m.Y H:i') }}</div>
                    @endif
                    @if($blockDefinition->updated_at)
                        <div><strong>Zuletzt geändert:</strong> {{ $blockDefinition->updated_at->format('d.m.Y H:i') }}</div>
                    @endif
                </div>
            </div>

            {{-- Verwendung --}}
            <div class="mb-4 p-3 bg-muted-5 rounded-lg">
                <h4 class="font-semibold mb-2 text-secondary">Verwendung</h4>
                <div class="space-y-2">
                    @if($blockDefinition->templateBlocks->count() > 0)
                        @foreach($blockDefinition->templateBlocks->take(3) as $block)
                            <div class="d-flex items-center gap-2 p-2 bg-muted-5 rounded">
                                <span class="flex-grow-1 text-sm">{{ $block->projectTemplate->name ?? 'Unbekanntes Template' }}</span>
                                <x-ui-badge variant="primary" size="xs">{{ $block->sort_order }}</x-ui-badge>
                            </div>
                        @endforeach
                        @if($blockDefinition->templateBlocks->count() > 3)
                            <div class="text-xs text-muted">+{{ $blockDefinition->templateBlocks->count() - 3 }} weitere</div>
                        @endif
                    @else
                        <p class="text-sm text-muted">Noch nicht in Templates verwendet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
