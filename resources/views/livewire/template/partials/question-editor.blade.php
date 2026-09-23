{{--
    Seitenpanel zum Bearbeiten einer Frage (Gruppe). Bindet an $draft der
    Template\Show-Komponente; geschrieben wird erst mit „Speichern“.
    Die Vorschau nutzt den echten Public-Renderer, nur ohne Interaktion.
--}}
@php
    $isOverview = ($template->flow_mode ?? 'block_flow') === 'overview';
    $multi = count($draft['fields']) > 1;
    $operatorOptions = [
        'equals' => 'ist gleich', 'not_equals' => 'ist ungleich', 'contains' => 'enthält',
        'empty' => 'ist leer', 'not_empty' => 'ist nicht leer',
        'selected' => 'ist ausgewählt', 'not_selected' => 'ist nicht ausgewählt',
    ];
@endphp

<div class="hatch-drawer-overlay" x-data x-on:keydown.escape.window="$wire.cancelEditor()">
    <div class="hatch-drawer-backdrop" wire:click="cancelEditor"></div>

    <div class="hatch-drawer">
        {{-- Kopf --}}
        <div class="flex items-center justify-between gap-3 border-b border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-5 py-3">
            <div class="min-w-0">
                <div class="text-xs text-[color:var(--nx-faint)]">{{ ($draft['is_new'] ?? false) ? 'Neue Frage' : 'Frage bearbeiten' }}</div>
                <div class="truncate text-sm font-semibold text-[color:var(--nx-text)]">{{ $draft['title'] ?: 'Ohne Titel' }}</div>
            </div>
            <x-nx-button icon variant="ghost" wire:click="cancelEditor" title="Schließen">@svg('heroicon-o-x-mark', 'w-4 h-4')</x-nx-button>
        </div>

        <div class="hatch-drawer-body space-y-5 px-5 py-5">

            {{-- Vorschau --}}
            <div>
                <div class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-[color:var(--nx-faint)]">
                    @svg('heroicon-o-eye', 'w-3.5 h-3.5')
                    So sieht es für Respondenten aus
                </div>
                <div class="hatch-preview rounded-[10px] border border-[color:var(--nx-line)] bg-white p-5 text-gray-900" inert>
                    @if($draft['title'])
                        <div class="text-base font-bold text-gray-900">
                            {{ $draft['title'] }}
                            @if(collect($draft['fields'])->contains(fn ($f) => $f['is_required']))<span class="ml-1 text-rose-500">*</span>@endif
                        </div>
                    @endif
                    @if($draft['description'])
                        <p class="mt-1 text-sm leading-relaxed text-gray-500">{{ $draft['description'] }}</p>
                    @endif
                    <div class="mt-4 space-y-5">
                        @foreach($previewBlocks as $pi => $pb)
                            <div wire:key="preview-{{ $draft['fields'][$pi]['uid'] }}-{{ $pb['type'] }}">
                                @if($pi > 0 && $pb['name'])
                                    <div class="mb-2 text-sm font-medium text-gray-700">{{ $pb['name'] }}@if($pb['is_required'])<span class="ml-0.5 text-rose-500">*</span>@endif</div>
                                @endif
                                @include('hatch::livewire.public.partials.overview-block', [
                                    'block' => $pb,
                                    'isReadOnly' => false,
                                    'compact' => false,
                                    'answersByBlock' => [], 'selectedOptionsByBlock' => [], 'matrixAnswersByBlock' => [],
                                    'rankingOrderByBlock' => [], 'repeaterEntriesByBlock' => [], 'addressFieldsByBlock' => [],
                                    'dateRangeStartByBlock' => [], 'dateRangeEndByBlock' => [], 'lookupOptions' => [],
                                ])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Frage --}}
            <div class="space-y-3">
                <x-nx-input-text name="draft.title" label="Frage" required wire:model.live.debounce.400ms="draft.title" placeholder="z.B. Wie zufrieden waren Sie mit dem Essen?" :errorKey="'draft.title'" />
                <x-nx-input-textarea name="draft.description" label="Erklärung" hint="optional, steht unter der Frage" rows="2"
                    wire:model.live.debounce.400ms="draft.description" placeholder="z.B. 1 = gar nicht, 5 = sehr" />
                @if($isOverview)
                    <div>
                        <x-nx-input-checkbox wire:model.live="draft.display_compact" label="Als kompakte Tabellenzeile darstellen" />
                        <p class="mt-0.5 pl-6 text-xs text-[color:var(--nx-faint)]">Gleich aufgebaute Fragen hintereinander (z. B. Mo–Fr) werden zu einer gemeinsamen Tabelle.</p>
                    </div>
                @endif
            </div>

            {{-- Felder --}}
            @foreach($draft['fields'] as $i => $field)
                @php $def = \Platform\Hatch\Support\BlockTypes::get($field['type']); @endphp
                <div wire:key="field-{{ $field['uid'] }}" class="rounded-[10px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
                    <div class="flex items-center gap-2 border-b border-[color:var(--nx-line)] px-4 py-2.5">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[6px] bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]">@svg($def['icon'], 'w-4 h-4')</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-[color:var(--nx-text)]">{{ $multi ? ($i === 0 ? 'Feld 1' : 'Feld ' . ($i + 1)) . ' · ' : '' }}{{ $def['label'] }}</div>
                            <div class="truncate text-xs text-[color:var(--nx-faint)]">{{ $def['hint'] }}</div>
                        </div>
                        <x-nx-button size="sm" wire:click="openTypePicker('change:{{ $i }}')">Typ ändern</x-nx-button>
                        @if($i > 0)
                            <x-nx-button icon variant="ghost" wire:click="moveField({{ $i }}, -1)" title="Nach oben" :disabled="$i === 1">@svg('heroicon-o-arrow-up', 'w-4 h-4')</x-nx-button>
                            <x-nx-button icon variant="ghost" wire:click="moveField({{ $i }}, 1)" title="Nach unten" :disabled="$i === count($draft['fields']) - 1">@svg('heroicon-o-arrow-down', 'w-4 h-4')</x-nx-button>
                            <x-nx-button icon variant="ghost" wire:click="removeField({{ $i }})" title="Feld entfernen">@svg('heroicon-o-trash', 'w-4 h-4 text-[color:var(--nx-danger)]')</x-nx-button>
                        @endif
                    </div>

                    <div class="space-y-4 p-4">
                        @if($i > 0)
                            <x-nx-input-text name="draft.fields.{{ $i }}.label" label="Beschriftung" hint="optional" wire:model.live.debounce.400ms="draft.fields.{{ $i }}.label" placeholder="z.B. Bitte begründen" />
                        @endif

                        @if(!in_array($def['type'], ['info', 'section', 'hidden', 'calculated'], true))
                            <x-nx-input-checkbox wire:model.live="draft.fields.{{ $i }}.is_required" label="Pflichtfeld" />
                        @endif

                        @include('hatch::livewire.template.partials.field-settings', ['i' => $i, 'field' => $field, 'def' => $def])

                        {{-- Sichtbarkeit --}}
                        @php $rules = $field['visibility']['rules'] ?? []; @endphp
                        <details class="group rounded-[8px] border border-[color:var(--nx-line)]" @if(!empty($rules) || $errors->has("draft.fields.$i.visibility")) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2 text-xs font-medium text-[color:var(--nx-muted)]">
                                <span class="inline-flex items-center gap-1.5">@svg('heroicon-o-eye-slash', 'w-3.5 h-3.5') Nur anzeigen, wenn … @if(!empty($rules))<x-nx-badge variant="info">{{ count($rules) }}</x-nx-badge>@endif</span>
                                @svg('heroicon-o-chevron-down', 'w-4 h-4 transition-transform group-open:rotate-180')
                            </summary>
                            <div class="space-y-2 border-t border-[color:var(--nx-line)] p-3">
                                @if(empty($sourceOptions))
                                    <p class="text-xs text-[color:var(--nx-faint)]">Bedingungen lassen sich nur an Fragen weiter oben knüpfen – diese Frage ist die erste.</p>
                                @else
                                    @if(count($rules) > 1)
                                        <div class="flex items-center gap-1 text-xs">
                                            <span class="text-[color:var(--nx-faint)]">Es müssen</span>
                                            @foreach(['AND' => 'alle', 'OR' => 'eine'] as $comb => $cl)
                                                <button type="button" wire:click="$set('draft.fields.{{ $i }}.visibility.combinator', '{{ $comb }}')"
                                                    class="rounded-full px-2 py-0.5 {{ ($field['visibility']['combinator'] ?? 'AND') === $comb ? 'bg-[color:var(--nx-active)] font-medium text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' }}">{{ $cl }}</button>
                                            @endforeach
                                            <span class="text-[color:var(--nx-faint)]">Bedingungen zutreffen</span>
                                        </div>
                                    @endif
                                    @foreach($rules as $r => $rule)
                                        <div wire:key="rule-{{ $field['uid'] }}-{{ $r }}" class="flex items-center gap-1.5">
                                            <select wire:model.live="draft.fields.{{ $i }}.visibility.rules.{{ $r }}.source_block_id" class="hatch-editor-select min-w-0 flex-1">
                                                <option value="">Frage wählen…</option>
                                                @foreach($sourceOptions as $so)<option value="{{ $so['id'] }}">{{ $so['label'] }}</option>@endforeach
                                            </select>
                                            <select wire:model.live="draft.fields.{{ $i }}.visibility.rules.{{ $r }}.operator" class="hatch-editor-select w-36 shrink-0">
                                                @foreach($operatorOptions as $ov => $ol)<option value="{{ $ov }}">{{ $ol }}</option>@endforeach
                                            </select>
                                            @if(!in_array($rule['operator'] ?? 'equals', ['empty', 'not_empty'], true))
                                                <input type="text" wire:model.live.debounce.400ms="draft.fields.{{ $i }}.visibility.rules.{{ $r }}.value" placeholder="Wert" class="hatch-editor-select w-28 shrink-0">
                                            @endif
                                            <x-nx-button icon variant="ghost" wire:click="removeRule({{ $i }}, {{ $r }})" title="Bedingung entfernen">@svg('heroicon-o-x-mark', 'w-4 h-4')</x-nx-button>
                                        </div>
                                    @endforeach
                                    <button type="button" wire:click="addRule({{ $i }})" class="inline-flex items-center gap-1 text-xs text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)]">@svg('heroicon-o-plus', 'w-3.5 h-3.5') Bedingung hinzufügen</button>
                                @endif
                                @error("draft.fields.$i.visibility")<p class="text-xs text-[color:var(--nx-danger)]">{{ $message }}</p>@enderror
                            </div>
                        </details>

                        {{-- Erweitert --}}
                        <details class="group rounded-[8px] border border-[color:var(--nx-line)]" @if($errors->has("draft.fields.$i.config_json") || !empty($def['advanced_only'])) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2 text-xs font-medium text-[color:var(--nx-muted)]">
                                <span class="inline-flex items-center gap-1.5">@svg('heroicon-o-code-bracket', 'w-3.5 h-3.5') Erweitert</span>
                                @svg('heroicon-o-chevron-down', 'w-4 h-4 transition-transform group-open:rotate-180')
                            </summary>
                            <div class="space-y-3 border-t border-[color:var(--nx-line)] p-3">
                                @if(!empty($def['advanced_only']))
                                    <x-nx-callout variant="neutral">{{ $def['advanced_only'] }}</x-nx-callout>
                                @endif
                                <div>
                                    <div class="mb-1 flex items-center justify-between gap-2">
                                        <label class="text-xs font-medium text-[color:var(--nx-text)]">Konfiguration (JSON)</label>
                                        <button type="button" wire:click="applyJson({{ $i }})" class="text-xs text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)]">Übernehmen</button>
                                    </div>
                                    <textarea wire:model="draft.fields.{{ $i }}.config_json" rows="5" spellcheck="false"
                                        class="block w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-3 py-2 font-mono text-xs text-[color:var(--nx-text)] focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]"
                                        placeholder='{"placeholder": "…"}'></textarea>
                                    @error("draft.fields.$i.config_json")<p class="mt-1 text-xs text-[color:var(--nx-danger)]">{{ $message }}</p>@enderror
                                    <p class="mt-1 text-xs text-[color:var(--nx-faint)]">Für Einstellungen ohne eigenes Feld. „Übernehmen“ ersetzt die Einstellungen oben.</p>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            @endforeach

            <button type="button" wire:click="openTypePicker('add')"
                class="flex w-full items-center justify-center gap-2 rounded-[8px] border border-dashed border-[color:var(--nx-line-strong)] p-2.5 text-sm text-[color:var(--nx-muted)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]">
                @svg('heroicon-o-plus', 'w-4 h-4')
                Weiteres Feld zu dieser Frage
            </button>
        </div>

        {{-- Fuß --}}
        <div class="flex items-center justify-between gap-2 border-t border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-5 py-3">
            <span class="text-xs text-[color:var(--nx-faint)]">Änderungen gelten für alle Erhebungen mit dieser Vorlage.</span>
            <div class="flex gap-2">
                <x-nx-button wire:click="cancelEditor">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="saveEditor">
                    <span wire:loading.remove wire:target="saveEditor">Speichern</span>
                    <span wire:loading wire:target="saveEditor">Speichert…</span>
                </x-nx-button>
            </div>
        </div>
    </div>
</div>

