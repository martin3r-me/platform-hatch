<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="{{ $blockDefinition->name ?? 'BlockDefinition' }}">
            @if($this->isDirty)
                <x-ui-button variant="primary" size="sm" wire:click="saveBlockDefinition">
                    <span class="flex items-center gap-2">
                        @svg('heroicon-o-check', 'w-4 h-4')
                        Speichern
                    </span>
                </x-ui-button>
            @endif
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="BlockDefinition-Einstellungen" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <x-ui-button variant="secondary" size="sm" :href="route('hatch.block-definitions.index')" wire:navigate class="w-full">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            Zurück zu BlockDefinitionen
                        </span>
                    </x-ui-button>
                </div>

                {{-- Grundkonfiguration --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Grundkonfiguration</h3>
                    <div class="space-y-3">
                        <x-ui-input-text
                            name="blockDefinition.name"
                            label="Name"
                            hint="Pflichtfeld"
                            wire:model.live.debounce.500ms="blockDefinition.name"
                            placeholder="z.B. Projektname, Budget, Kontakt-E-Mail"
                            required
                            :errorKey="'blockDefinition.name'"
                        />
                        <x-ui-input-text
                            name="blockDefinition.description"
                            label="Beschreibung"
                            hint="Optional"
                            wire:model.live.debounce.500ms="blockDefinition.description"
                            placeholder="Kurze Erklärung, was dieser Block erfasst"
                            :errorKey="'blockDefinition.description'"
                        />
                        <x-ui-input-select
                            name="blockDefinition.block_type"
                            label="Block-Typ"
                            hint="Bestimmt die Eingabeart"
                            :options="$blockTypeOptions"
                            optionValue="value"
                            optionLabel="label"
                            wire:model.live="blockDefinition.block_type"
                            :errorKey="'blockDefinition.block_type'"
                        />
                    </div>
                </div>

                {{-- Vorschau --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Vorschau</h3>
                    <div class="p-3 bg-[var(--ui-surface)] rounded border border-[var(--ui-border)]/60">
                        <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">{{ $blockDefinition->name ?: 'Feldname' }}</label>
                        @if($blockDefinition->description)
                            <p class="text-xs text-[var(--ui-muted)] mb-2">{{ $blockDefinition->description }}</p>
                        @endif

                        @switch($blockDefinition->block_type)
                            @case('text')
                            @case('email')
                            @case('phone')
                            @case('url')
                                <div class="border border-[var(--ui-border)]/60 rounded px-3 py-2 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)]">
                                    {{ $typeConfig['placeholder'] ?? 'Eingabe...' }}
                                </div>
                                @break
                            @case('long_text')
                                <div class="border border-[var(--ui-border)]/60 rounded px-3 py-2 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] min-h-[60px]">
                                    {{ $typeConfig['placeholder'] ?? 'Freitext eingeben...' }}
                                </div>
                                @break
                            @case('number')
                                <div class="border border-[var(--ui-border)]/60 rounded px-3 py-2 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] flex items-center gap-2">
                                    <span>{{ $typeConfig['placeholder'] ?? '0' }}</span>
                                    @if(!empty($typeConfig['unit']))
                                        <span class="text-xs">{{ $typeConfig['unit'] }}</span>
                                    @endif
                                </div>
                                @break
                            @case('select')
                                <div class="border border-[var(--ui-border)]/60 rounded px-3 py-2 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] flex items-center justify-between">
                                    <span>Option wählen...</span>
                                    @svg('heroicon-o-chevron-down', 'w-4 h-4')
                                </div>
                                @if(!empty($typeConfig['options']))
                                    <div class="mt-1 text-xs text-[var(--ui-muted)]">{{ count($typeConfig['options']) }} Option(en)</div>
                                @endif
                                @break
                            @case('multi_select')
                                <div class="border border-[var(--ui-border)]/60 rounded px-3 py-2 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)]">Mehrfachauswahl...</div>
                                @if(!empty($typeConfig['options']))
                                    <div class="mt-1 text-xs text-[var(--ui-muted)]">{{ count($typeConfig['options']) }} Option(en)</div>
                                @endif
                                @break
                            @case('scale')
                                <div class="flex items-center gap-2 mt-1">
                                    @if(!empty($typeConfig['labels']['min_label']))
                                        <span class="text-xs text-[var(--ui-muted)]">{{ $typeConfig['labels']['min_label'] }}</span>
                                    @endif
                                    <div class="flex-grow bg-[var(--ui-muted-5)] rounded-full h-2">
                                        <div class="bg-[var(--ui-primary)] h-2 rounded-full" style="width: 50%"></div>
                                    </div>
                                    @if(!empty($typeConfig['labels']['max_label']))
                                        <span class="text-xs text-[var(--ui-muted)]">{{ $typeConfig['labels']['max_label'] }}</span>
                                    @endif
                                </div>
                                <div class="text-xs text-center text-[var(--ui-muted)] mt-1">{{ $typeConfig['min'] ?? 1 }} – {{ $typeConfig['max'] ?? 10 }}</div>
                                @break
                            @case('rating')
                                <div class="flex items-center gap-1 mt-1">
                                    @for($i = 0; $i < ($typeConfig['max'] ?? 5); $i++)
                                        @svg('heroicon-o-star', 'w-5 h-5 text-[var(--ui-muted)]')
                                    @endfor
                                </div>
                                @break
                            @case('date')
                                <div class="border border-[var(--ui-border)]/60 rounded px-3 py-2 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] flex items-center justify-between">
                                    <span>{{ $typeConfig['format'] ?? 'Y-m-d' }}</span>
                                    @svg('heroicon-o-calendar', 'w-4 h-4')
                                </div>
                                @break
                            @case('boolean')
                                <div class="flex items-center gap-2">
                                    <div class="w-10 h-5 bg-[var(--ui-muted-5)] rounded-full border border-[var(--ui-border)]/60"></div>
                                    <span class="text-sm text-[var(--ui-muted)]">{{ $typeConfig['false_label'] ?? 'Nein' }}</span>
                                </div>
                                @break
                            @case('file')
                                <div class="border-2 border-dashed border-[var(--ui-border)]/60 rounded-lg p-4 text-center">
                                    @svg('heroicon-o-cloud-arrow-up', 'w-6 h-6 text-[var(--ui-muted)] mx-auto')
                                    <p class="text-xs text-[var(--ui-muted)] mt-1">{{ $typeConfig['allowed_types'] ?? 'pdf,jpg,png' }} · max {{ $typeConfig['max_size_mb'] ?? 10 }}MB</p>
                                </div>
                                @break
                            @case('location')
                                <div class="border border-[var(--ui-border)]/60 rounded px-3 py-2 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] flex items-center gap-2">
                                    @svg('heroicon-o-map-pin', 'w-4 h-4')
                                    <span>{{ $typeConfig['placeholder'] ?? 'Adresse eingeben...' }}</span>
                                </div>
                                @break
                            @default
                                <div class="border border-[var(--ui-border)]/60 rounded px-3 py-2 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)]">Eingabe...</div>
                        @endswitch
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Status</h3>
                    <div class="flex items-center gap-2">
                        <x-ui-badge :variant="$blockDefinition->is_active ? 'success' : 'secondary'" size="sm">
                            {{ $blockDefinition->is_active ? 'Aktiv' : 'Inaktiv' }}
                        </x-ui-badge>
                        <x-ui-button variant="secondary-outline" size="sm" wire:click="toggleActive">
                            {{ $blockDefinition->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                        </x-ui-button>
                    </div>
                </div>

                {{-- Erstellungsdaten --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Erstellungsdaten</h3>
                    <div class="space-y-1 text-sm text-[var(--ui-muted)]">
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
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Verwendung</h3>
                    @if($blockDefinition->templateBlocks->count() > 0)
                        <div class="space-y-2">
                            @foreach($blockDefinition->templateBlocks->take(3) as $block)
                                <div class="flex items-center gap-2 p-2 bg-[var(--ui-muted-5)] rounded">
                                    <span class="flex-grow text-sm">{{ $block->projectTemplate->name ?? 'Unbekanntes Template' }}</span>
                                    <x-ui-badge variant="primary" size="xs">{{ $block->sort_order }}</x-ui-badge>
                                </div>
                            @endforeach
                            @if($blockDefinition->templateBlocks->count() > 3)
                                <div class="text-xs text-[var(--ui-muted)]">+{{ $blockDefinition->templateBlocks->count() - 3 }} weitere</div>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-[var(--ui-muted)]">Noch nicht in Templates verwendet.</p>
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
        {{-- Typ-spezifische Konfiguration --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-1">Typ-Konfiguration: {{ $blockDefinition->getBlockTypeLabel() }}</h3>
            <p class="text-sm text-[var(--ui-muted)] mb-4">Passe hier die spezifischen Einstellungen für diesen Feldtyp an. Diese Werte steuern, wie das Feld in der Erhebung dargestellt und validiert wird.</p>

            @if($blockDefinition->block_type === 'text')
                <div class="space-y-3 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <x-ui-input-text name="typeConfig.placeholder" label="Placeholder" hint="Wird im leeren Feld angezeigt" wire:model.live.debounce.500ms="typeConfig.placeholder" placeholder="z.B. Geben Sie den Projektnamen ein" size="sm" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui-input-text name="typeConfig.min_length" label="Mindestlänge" hint="In Zeichen" wire:model.live.debounce.500ms="typeConfig.min_length" type="number" size="sm" />
                        <x-ui-input-text name="typeConfig.max_length" label="Maximale Länge" hint="In Zeichen" wire:model.live.debounce.500ms="typeConfig.max_length" type="number" size="sm" />
                    </div>
                </div>
            @endif

            @if($blockDefinition->block_type === 'long_text')
                <div class="space-y-3 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <x-ui-input-text name="typeConfig.placeholder" label="Placeholder" hint="Wird im leeren Feld angezeigt" wire:model.live.debounce.500ms="typeConfig.placeholder" placeholder="z.B. Beschreiben Sie Ihr Vorhaben ausführlich..." size="sm" />
                    <div class="grid grid-cols-3 gap-3">
                        <x-ui-input-text name="typeConfig.min_length" label="Mindestlänge" hint="In Zeichen" wire:model.live.debounce.500ms="typeConfig.min_length" type="number" size="sm" />
                        <x-ui-input-text name="typeConfig.max_length" label="Max. Zeichenzahl" hint="Standard: 5000" wire:model.live.debounce.500ms="typeConfig.max_length" type="number" size="sm" />
                        <x-ui-input-text name="typeConfig.rows" label="Zeilen (Höhe)" hint="Sichtbare Zeilen" wire:model.live.debounce.500ms="typeConfig.rows" type="number" size="sm" />
                    </div>
                </div>
            @endif

            @if($blockDefinition->block_type === 'email')
                <div class="p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <x-ui-input-text name="typeConfig.placeholder" label="Placeholder" hint="Beispiel-Adresse" wire:model.live.debounce.500ms="typeConfig.placeholder" placeholder="name@beispiel.de" size="sm" />
                    <p class="text-xs text-[var(--ui-muted)] mt-2">E-Mail-Format wird automatisch validiert.</p>
                </div>
            @endif

            @if($blockDefinition->block_type === 'phone')
                <div class="space-y-3 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <x-ui-input-text name="typeConfig.placeholder" label="Placeholder" hint="Beispiel-Nummer" wire:model.live.debounce.500ms="typeConfig.placeholder" placeholder="+49 123 456789" size="sm" />
                    <x-ui-input-select name="typeConfig.format" label="Erwartetes Format" hint="Legt die Validierung fest" wire:model.live.debounce.500ms="typeConfig.format" :options="collect([['value' => 'international', 'label' => 'International (+49...)'],['value' => 'national', 'label' => 'National (0123...)'],['value' => 'any', 'label' => 'Beliebig']])" optionValue="value" optionLabel="label" size="sm" />
                </div>
            @endif

            @if($blockDefinition->block_type === 'url')
                <div class="p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <x-ui-input-text name="typeConfig.placeholder" label="Placeholder" hint="Beispiel-URL" wire:model.live.debounce.500ms="typeConfig.placeholder" placeholder="https://beispiel.de" size="sm" />
                    <p class="text-xs text-[var(--ui-muted)] mt-2">URL-Format wird automatisch validiert (muss mit http:// oder https:// beginnen).</p>
                </div>
            @endif

            @if(in_array($blockDefinition->block_type, ['select', 'multi_select']))
                <div class="space-y-3">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <span class="text-sm font-medium text-[var(--ui-secondary)]">Auswahl-Optionen</span>
                            <p class="text-xs text-[var(--ui-muted)]">Definiere die Werte, aus denen der Nutzer {{ $blockDefinition->block_type === 'multi_select' ? 'mehrere auswählen' : 'einen auswählen' }} kann.</p>
                        </div>
                        <x-ui-button variant="secondary" size="sm" wire:click="addSelectOption">
                            <span class="flex items-center gap-2">@svg('heroicon-o-plus', 'w-4 h-4') Option hinzufügen</span>
                        </x-ui-button>
                    </div>
                    @if(!empty($typeConfig['options']))
                        @foreach($typeConfig['options'] as $optIndex => $option)
                            <div class="flex items-center gap-2 p-3 bg-[var(--ui-muted-5)] rounded border border-[var(--ui-border)]/40">
                                <x-ui-input-text name="typeConfig.options.{{ $optIndex }}.label" wire:model.live.debounce.500ms="typeConfig.options.{{ $optIndex }}.label" placeholder="Angezeigter Text, z.B. 'Hoch'" size="sm" class="flex-grow" />
                                <x-ui-input-text name="typeConfig.options.{{ $optIndex }}.value" wire:model.live.debounce.500ms="typeConfig.options.{{ $optIndex }}.value" placeholder="Gespeicherter Wert, z.B. 'high'" size="sm" class="flex-grow" />
                                <x-ui-button variant="danger-outline" size="sm" wire:click="removeSelectOption({{ $optIndex }})">@svg('heroicon-o-trash', 'w-4 h-4')</x-ui-button>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-[var(--ui-muted)] p-3 border border-dashed border-[var(--ui-border)]/60 rounded text-sm">Noch keine Optionen definiert. Füge mindestens eine Option hinzu.</div>
                    @endif
                </div>
            @endif

            @if($blockDefinition->block_type === 'number')
                <div class="space-y-3 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <x-ui-input-text name="typeConfig.placeholder" label="Placeholder" hint="Wird im leeren Feld angezeigt" wire:model.live.debounce.500ms="typeConfig.placeholder" placeholder="z.B. 42" size="sm" />
                    <div class="grid grid-cols-3 gap-3">
                        <x-ui-input-text name="typeConfig.min" label="Minimum" hint="Kleinster erlaubter Wert" wire:model.live.debounce.500ms="typeConfig.min" type="number" size="sm" />
                        <x-ui-input-text name="typeConfig.max" label="Maximum" hint="Grösster erlaubter Wert" wire:model.live.debounce.500ms="typeConfig.max" type="number" size="sm" />
                        <x-ui-input-text name="typeConfig.step" label="Schrittgrösse" hint="z.B. 1, 0.5, 10" wire:model.live.debounce.500ms="typeConfig.step" type="number" size="sm" />
                    </div>
                    <x-ui-input-text name="typeConfig.unit" label="Einheit" hint="Optional" wire:model.live.debounce.500ms="typeConfig.unit" placeholder="z.B. kg, EUR, Stück, %" size="sm" />
                </div>
            @endif

            @if($blockDefinition->block_type === 'scale')
                <div class="space-y-3 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <p class="text-xs text-[var(--ui-muted)]">Der Nutzer wählt einen Wert auf einer Skala. Typisch: 1–5 oder 1–10.</p>
                    <div class="grid grid-cols-3 gap-3">
                        <x-ui-input-text name="typeConfig.min" label="Startwert" hint="z.B. 1" wire:model.live.debounce.500ms="typeConfig.min" type="number" size="sm" />
                        <x-ui-input-text name="typeConfig.max" label="Endwert" hint="z.B. 10" wire:model.live.debounce.500ms="typeConfig.max" type="number" size="sm" />
                        <x-ui-input-text name="typeConfig.step" label="Schrittgrösse" hint="z.B. 1" wire:model.live.debounce.500ms="typeConfig.step" type="number" size="sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui-input-text name="typeConfig.labels.min_label" label="Beschriftung links" hint="Beim Startwert" wire:model.live.debounce.500ms="typeConfig.labels.min_label" placeholder="z.B. Trifft nicht zu" size="sm" />
                        <x-ui-input-text name="typeConfig.labels.max_label" label="Beschriftung rechts" hint="Beim Endwert" wire:model.live.debounce.500ms="typeConfig.labels.max_label" placeholder="z.B. Trifft voll zu" size="sm" />
                    </div>
                </div>
            @endif

            @if($blockDefinition->block_type === 'rating')
                <div class="space-y-3 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <p class="text-xs text-[var(--ui-muted)]">Sternebewertung — der Nutzer klickt auf 1 bis max. Sterne.</p>
                    <div class="grid grid-cols-3 gap-3">
                        <x-ui-input-text name="typeConfig.min" label="Minimum" hint="Meist 1" wire:model.live.debounce.500ms="typeConfig.min" type="number" size="sm" />
                        <x-ui-input-text name="typeConfig.max" label="Maximale Sterne" hint="z.B. 5 oder 10" wire:model.live.debounce.500ms="typeConfig.max" type="number" size="sm" />
                        <x-ui-input-text name="typeConfig.step" label="Schrittgrösse" hint="Meist 1" wire:model.live.debounce.500ms="typeConfig.step" type="number" size="sm" />
                    </div>
                </div>
            @endif

            @if($blockDefinition->block_type === 'date')
                <div class="space-y-3 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <x-ui-input-select name="typeConfig.format" label="Datumsformat" hint="Anzeige im Feld" wire:model.live.debounce.500ms="typeConfig.format" :options="collect([['value' => 'Y-m-d', 'label' => 'ISO (2025-01-31)'],['value' => 'd.m.Y', 'label' => 'DE (31.01.2025)'],['value' => 'd/m/Y', 'label' => 'EU (31/01/2025)'],['value' => 'm/d/Y', 'label' => 'US (01/31/2025)']])" optionValue="value" optionLabel="label" size="sm" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui-input-text name="typeConfig.min_date" label="Frühestes Datum" hint="Optional" wire:model.live.debounce.500ms="typeConfig.min_date" type="date" size="sm" />
                        <x-ui-input-text name="typeConfig.max_date" label="Spätestes Datum" hint="Optional" wire:model.live.debounce.500ms="typeConfig.max_date" type="date" size="sm" />
                    </div>
                </div>
            @endif

            @if($blockDefinition->block_type === 'boolean')
                <div class="space-y-3 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <p class="text-xs text-[var(--ui-muted)]">Ja/Nein-Frage — passe die Beschriftung und Darstellung an.</p>
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui-input-text name="typeConfig.true_label" label="Text für Ja" hint="Positiver Wert" wire:model.live.debounce.500ms="typeConfig.true_label" placeholder="Ja" size="sm" />
                        <x-ui-input-text name="typeConfig.false_label" label="Text für Nein" hint="Negativer Wert" wire:model.live.debounce.500ms="typeConfig.false_label" placeholder="Nein" size="sm" />
                    </div>
                    <x-ui-input-select name="typeConfig.style" label="Darstellung" hint="Visuelles Element" wire:model.live.debounce.500ms="typeConfig.style" :options="collect([['value' => 'toggle', 'label' => 'Toggle-Switch'],['value' => 'checkbox', 'label' => 'Checkbox'],['value' => 'radio', 'label' => 'Radio-Buttons']])" optionValue="value" optionLabel="label" size="sm" />
                </div>
            @endif

            @if($blockDefinition->block_type === 'file')
                <div class="space-y-3 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <x-ui-input-text name="typeConfig.allowed_types" label="Erlaubte Dateitypen" hint="Kommagetrennt" wire:model.live.debounce.500ms="typeConfig.allowed_types" placeholder="pdf,jpg,png,doc,docx" size="sm" />
                    <x-ui-input-text name="typeConfig.max_size_mb" label="Maximale Dateigrösse" hint="In Megabyte" wire:model.live.debounce.500ms="typeConfig.max_size_mb" type="number" size="sm" />
                </div>
            @endif

            @if($blockDefinition->block_type === 'location')
                <div class="space-y-3 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <x-ui-input-text name="typeConfig.placeholder" label="Placeholder" hint="Wird im leeren Feld angezeigt" wire:model.live.debounce.500ms="typeConfig.placeholder" placeholder="Adresse eingeben..." size="sm" />
                    <x-ui-input-select name="typeConfig.format" label="Eingabe-Art" hint="Was soll erfasst werden?" wire:model.live.debounce.500ms="typeConfig.format" :options="collect([['value' => 'address', 'label' => 'Vollständige Adresse'],['value' => 'city', 'label' => 'Nur Stadt'],['value' => 'country', 'label' => 'Nur Land'],['value' => 'postal_code', 'label' => 'Nur Postleitzahl']])" optionValue="value" optionLabel="label" size="sm" />
                </div>
            @endif

            @if($blockDefinition->block_type === 'custom')
                <div class="text-center text-[var(--ui-muted)] p-4 border border-dashed border-[var(--ui-border)]/60 rounded-lg text-sm">
                    Benutzerdefinierter Typ — die Konfiguration erfolgt vollständig über den KI-Prompt und das Antwort-Format weiter unten.
                </div>
            @endif
        </div>

        {{-- KI-Konfiguration --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-1">KI-Konfiguration</h3>
            <p class="text-sm text-[var(--ui-muted)] mb-4">Der KI-Prompt steuert, wie die KI den Nutzer durch diesen Block führt. Beschreibe, welche Information erfasst werden soll und wie die KI nachfragen soll.</p>
            <x-ui-input-textarea
                name="blockDefinition.ai_prompt"
                label="KI-Prompt"
                hint="Anweisungen an die KI"
                wire:model.live.debounce.500ms="blockDefinition.ai_prompt"
                placeholder="z.B. Frage den Nutzer nach seinem gewünschten Projektbudget. Akzeptiere Beträge in EUR. Bei unklaren Angaben frage nach einer konkreten Zahl."
                :errorKey="'blockDefinition.ai_prompt'"
                rows="6"
            />
        </div>

        {{-- Fallback-Fragen --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">Rückfragen / Fallback-Fragen</h3>
                <x-ui-button variant="secondary" size="sm" wire:click="addFallbackQuestion">
                    <span class="flex items-center gap-2">@svg('heroicon-o-plus', 'w-4 h-4') Frage hinzufügen</span>
                </x-ui-button>
            </div>
            <p class="text-sm text-[var(--ui-muted)] mb-4">Vordefinierte Nachfragen, die die KI stellen kann, wenn die Antwort des Nutzers unvollständig oder unklar ist. Ohne Rückfragen formuliert die KI eigene Nachfragen.</p>
            <div class="space-y-3">
                @if($blockDefinition->fallback_questions && count($blockDefinition->fallback_questions) > 0)
                    @foreach($blockDefinition->fallback_questions as $fbIndex => $fbQuestion)
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-[var(--ui-secondary)]">Frage {{ $fbIndex + 1 }}</span>
                                <x-ui-button variant="danger-outline" size="sm" wire:click="removeFallbackQuestion({{ $fbIndex }})">@svg('heroicon-o-trash', 'w-4 h-4')</x-ui-button>
                            </div>
                            <div class="space-y-2">
                                <x-ui-input-textarea name="blockDefinition.fallback_questions.{{ $fbIndex }}.question" label="Frage" hint="Text, den die KI stellt" wire:model.live.debounce.500ms="blockDefinition.fallback_questions.{{ $fbIndex }}.question" placeholder="z.B. Können Sie das Budget genauer beziffern?" rows="2" size="sm" />
                                <x-ui-input-select name="blockDefinition.fallback_questions.{{ $fbIndex }}.condition" label="Bedingung" hint="Wann wird nachgefragt?" wire:model.live.debounce.500ms="blockDefinition.fallback_questions.{{ $fbIndex }}.condition" :options="collect([['value' => 'always', 'label' => 'Immer verfügbar'],['value' => 'low_confidence', 'label' => 'Bei niedriger Konfidenz'],['value' => 'missing_info', 'label' => 'Bei fehlenden Infos'],['value' => 'unclear', 'label' => 'Bei unklarer Antwort']])" optionValue="value" optionLabel="label" size="sm" />
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center text-[var(--ui-muted)] p-4 border border-dashed border-[var(--ui-border)]/60 rounded-lg text-sm">
                        Noch keine Rückfragen definiert. Die KI wird generische Nachfragen stellen.
                    </div>
                @endif
            </div>
        </div>

        {{-- Response Format --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">Antwort-Format</h3>
                <div class="flex gap-2">
                    <x-ui-button variant="secondary" size="sm" wire:click="resetResponseFormat">
                        <span class="flex items-center gap-2">@svg('heroicon-o-trash', 'w-4 h-4') Zurücksetzen</span>
                    </x-ui-button>
                    <x-ui-button variant="secondary" size="sm" wire:click="addResponseFormat">
                        <span class="flex items-center gap-2">@svg('heroicon-o-plus', 'w-4 h-4') Format hinzufügen</span>
                    </x-ui-button>
                </div>
            </div>
            <p class="text-sm text-[var(--ui-muted)] mb-4">Lege fest, in welchem Format die KI die gesammelten Daten strukturiert zurückgeben soll. Jedes Format-Feld beschreibt einen einzelnen Datenpunkt mit Typ und Validierung.</p>
            <div class="space-y-3">
                @if($responseFormatInput && count($responseFormatInput) > 0)
                    @foreach($responseFormatInput as $index => $format)
                        <div class="p-4 border border-[var(--ui-border)]/40 rounded-lg bg-[var(--ui-muted-5)]">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-[var(--ui-secondary)]">Format {{ $index + 1 }}</span>
                                <x-ui-button variant="danger-outline" size="sm" wire:click="removeResponseFormat({{ $index }})">@svg('heroicon-o-trash', 'w-4 h-4')</x-ui-button>
                            </div>
                            <div class="space-y-3">
                                <x-ui-input-select name="response_format.{{ $index }}.type" label="Datentyp" hint="Art des Werts" wire:model.live.debounce.500ms="responseFormatInput.{{ $index }}.type" :options="collect([['value' => 'string', 'label' => 'Text'],['value' => 'number', 'label' => 'Zahl'],['value' => 'boolean', 'label' => 'Ja/Nein'],['value' => 'date', 'label' => 'Datum'],['value' => 'email', 'label' => 'E-Mail'],['value' => 'url', 'label' => 'URL'],['value' => 'array', 'label' => 'Liste'],['value' => 'object', 'label' => 'Objekt']])" optionValue="value" optionLabel="label" size="sm" />
                                <x-ui-input-text name="response_format.{{ $index }}.description" label="Beschreibung" hint="Was wird hier gespeichert?" wire:model.live.debounce.500ms="responseFormatInput.{{ $index }}.description" placeholder="z.B. Gewünschtes Projektbudget in EUR" size="sm" />
                                <x-ui-input-text name="response_format.{{ $index }}.constraints" label="Einschränkungen" hint="Optional" wire:model.live.debounce.500ms="responseFormatInput.{{ $index }}.constraints" placeholder="z.B. min: 0, max: 1000000" size="sm" />

                                {{-- Validierungen --}}
                                <div class="border-t border-[var(--ui-border)]/40 pt-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-[var(--ui-muted)]">Validierungen</span>
                                        <x-ui-button variant="secondary" size="sm" wire:click="addValidationForField({{ $index }})">
                                            <span class="flex items-center gap-2">@svg('heroicon-o-plus', 'w-4 h-4') Validierung</span>
                                        </x-ui-button>
                                    </div>
                                    @if(isset($responseFormatInput[$index]['validations']) && count($responseFormatInput[$index]['validations']) > 0)
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-xs">
                                                <thead class="bg-[var(--ui-muted-5)] border-b border-[var(--ui-border)]/40">
                                                    <tr>
                                                        <th class="text-left p-2 font-medium text-[var(--ui-muted)]">Typ</th>
                                                        <th class="text-left p-2 font-medium text-[var(--ui-muted)]">Fehlermeldung</th>
                                                        <th class="text-left p-2 font-medium text-[var(--ui-muted)]">Parameter</th>
                                                        <th class="text-left p-2 font-medium text-[var(--ui-muted)] w-16">Aktion</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($responseFormatInput[$index]['validations'] as $validationIndex => $validation)
                                                        <tr class="border-b border-[var(--ui-border)]/20">
                                                            <td class="p-2">
                                                                <x-ui-input-select name="response_format.{{ $index }}.validations.{{ $validationIndex }}.type" wire:model.live.debounce.500ms="responseFormatInput.{{ $index }}.validations.{{ $validationIndex }}.type" :options="collect([['value' => 'required', 'label' => 'Erforderlich'],['value' => 'min_length', 'label' => 'Min Länge'],['value' => 'max_length', 'label' => 'Max Länge'],['value' => 'email', 'label' => 'E-Mail'],['value' => 'url', 'label' => 'URL'],['value' => 'regex', 'label' => 'Regex'],['value' => 'numeric', 'label' => 'Nur Zahlen'],['value' => 'alpha', 'label' => 'Nur Buchstaben']])" optionValue="value" optionLabel="label" size="sm" class="min-w-32" />
                                                            </td>
                                                            <td class="p-2">
                                                                <x-ui-input-text name="response_format.{{ $index }}.validations.{{ $validationIndex }}.message" wire:model.live.debounce.500ms="responseFormatInput.{{ $index }}.validations.{{ $validationIndex }}.message" placeholder="Fehlermeldung" size="sm" class="min-w-40" />
                                                            </td>
                                                            <td class="p-2">
                                                                <x-ui-input-text name="response_format.{{ $index }}.validations.{{ $validationIndex }}.params" wire:model.live.debounce.500ms="responseFormatInput.{{ $index }}.validations.{{ $validationIndex }}.params" placeholder="z.B. 2" size="sm" class="min-w-24" />
                                                            </td>
                                                            <td class="p-2">
                                                                <x-ui-button variant="danger-outline" size="sm" wire:click="removeValidationFromField({{ $index }}, {{ $validationIndex }})" class="w-full">@svg('heroicon-o-trash', 'w-4 h-4')</x-ui-button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center text-[var(--ui-muted)] p-2 border border-dashed border-[var(--ui-border)]/60 rounded text-xs">Keine Validierungen definiert</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center text-[var(--ui-muted)] p-4 border border-dashed border-[var(--ui-border)]/60 rounded-lg">Noch keine Antwort-Formate definiert</div>
                @endif
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>
