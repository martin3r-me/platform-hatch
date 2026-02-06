<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="{{ $template?->name ?? 'Template' }}">
            @if($this->isDirty)
                <x-ui-button variant="primary" size="sm" wire:click="saveTemplate">
                    <span class="flex items-center gap-2">
                        @svg('heroicon-o-check', 'w-4 h-4')
                        Speichern
                    </span>
                </x-ui-button>
            @endif
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Template-Einstellungen" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <x-ui-button variant="secondary" size="sm" :href="route('hatch.templates.index')" wire:navigate class="w-full">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            Zurück zu Templates
                        </span>
                    </x-ui-button>
                </div>

                {{-- Template-Übersicht --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Grundkonfiguration</h3>
                    <div class="space-y-3">
                        <x-ui-input-text
                            name="template.name"
                            label="Name"
                            hint="Pflichtfeld"
                            wire:model.live.debounce.500ms="template.name"
                            placeholder="z.B. Standard-Projekterhebung"
                            required
                            :errorKey="'template.name'"
                        />
                        <x-ui-input-text
                            name="template.description"
                            label="Beschreibung"
                            hint="Optional"
                            wire:model.live.debounce.500ms="template.description"
                            placeholder="Wofür wird dieses Template verwendet?"
                            :errorKey="'template.description'"
                        />
                        <x-ui-input-select
                            name="template.complexity_level"
                            label="Komplexität"
                            hint="Beeinflusst KI-Tiefe"
                            :options="$complexityLevels"
                            optionValue="name"
                            optionLabel="display_name"
                            wire:model.live="template.complexity_level"
                            :errorKey="'template.complexity_level'"
                        />
                        <x-ui-input-text
                            name="template.ai_personality"
                            label="KI-Persönlichkeit"
                            hint="Ton der KI"
                            wire:model.live.debounce.500ms="template.ai_personality"
                            placeholder="z.B. freundlich und professionell"
                            :errorKey="'template.ai_personality'"
                        />
                        <x-ui-input-text
                            name="template.industry_context"
                            label="Branchenkontext"
                            hint="Für Fachbegriffe"
                            wire:model.live.debounce.500ms="template.industry_context"
                            placeholder="z.B. Software-Entwicklung, Marketing"
                            :errorKey="'template.industry_context'"
                        />
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Status</h3>
                    <x-ui-badge
                        :variant="$template?->is_active ? 'success' : 'secondary'"
                        size="sm"
                    >
                        {{ $template?->is_active ? 'Aktiv' : 'Inaktiv' }}
                    </x-ui-badge>
                </div>

                {{-- Erstellungsdaten --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Erstellungsdaten</h3>
                    <div class="space-y-1 text-sm text-[var(--ui-muted)]">
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

                {{-- Blöcke Übersicht --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Blöcke</h3>
                    @if($template?->templateBlocks?->count() > 0)
                        <div class="space-y-2">
                            @foreach($template->templateBlocks->take(3) as $block)
                                <div class="flex items-center gap-2 p-2 bg-[var(--ui-muted-5)] rounded">
                                    <span class="flex-grow text-sm">{{ $block->blockDefinition->name ?? 'Unbekannter Block' }}</span>
                                    <x-ui-badge variant="primary" size="xs">{{ $block->sort_order }}</x-ui-badge>
                                </div>
                            @endforeach
                            @if($template->templateBlocks->count() > 3)
                                <div class="text-xs text-[var(--ui-muted)]">+{{ $template->templateBlocks->count() - 3 }} weitere</div>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-[var(--ui-muted)]">Noch keine Blöcke konfiguriert.</p>
                    @endif
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 text-sm text-[var(--ui-muted)]">Keine Aktivitäten vorhanden</div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        {{-- Template-Blöcke --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">Template-Blöcke</h3>
                <x-ui-button variant="primary" size="sm" wire:click="addBlock">
                    <span class="flex items-center gap-2">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        Block hinzufügen
                    </span>
                </x-ui-button>
            </div>
            <p class="text-sm text-[var(--ui-muted)] mb-4">Blöcke bestimmen, welche Informationen in einer Erhebung erfasst werden. Ziehe Blöcke per Drag & Drop in die gewünschte Reihenfolge.</p>

            @if($template?->templateBlocks?->count() > 0)
                <div wire:sortable="updateBlockOrder" class="space-y-3">
                    @foreach($template->templateBlocks->sortBy('sort_order') as $block)
                        <div wire:sortable.item="{{ $block->id }}"
                             class="bg-[var(--ui-surface)] border border-[var(--ui-border)]/60 rounded-lg p-4 hover:border-[var(--ui-primary)]/60 transition-colors">

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3 flex-grow">
                                    <div wire:sortable.handle class="cursor-move text-[var(--ui-muted)] hover:text-[var(--ui-primary)]">
                                        <x-heroicon-o-bars-3 class="w-5 h-5" />
                                    </div>

                                    @if($editingBlockId === $block->id)
                                        <div class="flex-grow space-y-2">
                                            <x-ui-input-text
                                                name="editingBlock.name"
                                                label="Block-Name"
                                                hint="Anzeigename im Template"
                                                wire:model.live.debounce.500ms="editingBlock.name"
                                                placeholder="z.B. Projektdetails, Kontaktdaten"
                                                class="w-full"
                                            />
                                            <x-ui-input-text
                                                name="editingBlock.description"
                                                label="Beschreibung"
                                                hint="Optional"
                                                wire:model.live.debounce.500ms="editingBlock.description"
                                                placeholder="Kurze Erklärung für den Nutzer"
                                                class="w-full"
                                            />
                                            <x-ui-input-select
                                                name="editingBlock.block_definition_id"
                                                label="BlockDefinition"
                                                hint="Verknüpfter Feldtyp"
                                                wire:model.live.debounce.500ms="editingBlock.block_definition_id"
                                                :options="$blockDefinitionOptions"
                                                optionValue="id"
                                                optionLabel="name"
                                                placeholder="BlockDefinition auswählen"
                                                class="w-full"
                                            />
                                            <div class="flex gap-2">
                                                <x-ui-button variant="primary" size="sm" wire:click="saveBlock">Speichern</x-ui-button>
                                                <x-ui-button variant="secondary" size="sm" wire:click="cancelEditingBlock">Abbrechen</x-ui-button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex-grow">
                                            <h4 class="font-medium text-[var(--ui-secondary)]">{{ $block->name }}</h4>
                                            @if($block->description)
                                                <p class="text-sm text-[var(--ui-muted)] mt-1">{{ $block->description }}</p>
                                            @endif
                                            <div class="flex items-center gap-2 mt-2">
                                                <span class="text-xs text-[var(--ui-muted)]">Sortierung: {{ $block->sort_order }}</span>
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
                                    <div class="flex gap-2">
                                        <x-ui-button variant="secondary" size="sm" wire:click="startEditingBlock({{ $block->id }})">
                                            <x-heroicon-o-pencil class="w-4 h-4" />
                                        </x-ui-button>
                                        <x-ui-button variant="danger" size="sm" wire:click="deleteBlock({{ $block->id }})" onclick="return confirm('Block wirklich löschen?')">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </x-ui-button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 rounded-lg border border-dashed border-[var(--ui-border)] bg-[var(--ui-surface)]">
                    <x-heroicon-o-puzzle-piece class="w-12 h-12 mx-auto mb-3 text-[var(--ui-muted)]" />
                    <h4 class="text-lg font-medium text-[var(--ui-secondary)] mb-1">Noch keine Blöcke konfiguriert</h4>
                    <p class="text-sm text-[var(--ui-muted)] max-w-md mx-auto">Blöcke definieren die Schritte einer Erhebung. Erstelle zuerst BlockDefinitionen und füge sie dann hier als Blöcke hinzu.</p>
                </div>
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>
