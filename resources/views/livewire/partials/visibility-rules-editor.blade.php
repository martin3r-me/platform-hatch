@php
    /**
     * Editor für Sichtbarkeitsregeln eines Blocks.
     * Erwartet: $block (HatchTemplateBlock aktuell in Bearbeitung), $allBlocks (Collection)
     *
     * Nutzt $editingBlock als Wire-Bindung — der Editor darf nur gerendert werden,
     * wenn $editingBlockId === $block->id.
     */
    $rules = $editingBlock->visibility_rules ?? ['combinator' => 'AND', 'rules' => []];
    $ruleList = $rules['rules'] ?? [];
    $combinator = $rules['combinator'] ?? 'AND';

    // Kandidaten für Quell-Feld: alle Blocks mit kleinerem sort_order (um Vorwärts-Referenzen zu vermeiden) und != self.
    $sourceOptions = collect($allBlocks)
        ->filter(fn ($b) => $b->id !== $editingBlock->id && $b->sort_order < ($editingBlock->sort_order ?? PHP_INT_MAX))
        ->sortBy('sort_order')
        ->map(fn ($b) => [
            'id' => (string) $b->id,
            'label' => ($b->name ?: ($b->blockDefinition->name ?? 'Block ' . $b->id))
                . ' (#' . $b->sort_order . ')',
        ])
        ->values()
        ->all();

    $operatorOptions = [
        ['value' => 'equals', 'label' => 'ist gleich'],
        ['value' => 'not_equals', 'label' => 'ist ungleich'],
        ['value' => 'contains', 'label' => 'enthält'],
        ['value' => 'empty', 'label' => 'ist leer'],
        ['value' => 'not_empty', 'label' => 'ist nicht leer'],
        ['value' => 'selected', 'label' => 'ist ausgewählt (Checkbox/Multi)'],
        ['value' => 'not_selected', 'label' => 'ist nicht ausgewählt (Checkbox/Multi)'],
    ];
@endphp

<div class="p-3 border border-[var(--ui-border)]/60 rounded bg-[var(--ui-muted-5)] space-y-2">
    <div class="flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-[var(--ui-secondary)] uppercase tracking-wider">Sichtbarkeit</span>
            <p class="text-xs text-[var(--ui-muted)]">Zeige dieses Feld nur, wenn folgende Bedingungen erfüllt sind. Ohne Regel: immer sichtbar.</p>
        </div>
        @if(count($ruleList) > 1)
            <div class="flex gap-1 text-xs">
                <button type="button" wire:click="setVisibilityCombinator('AND')"
                        class="px-2 py-1 rounded border {{ $combinator === 'AND' ? 'bg-[var(--ui-primary)] text-white border-[var(--ui-primary)]' : 'border-[var(--ui-border)]' }}">
                    UND
                </button>
                <button type="button" wire:click="setVisibilityCombinator('OR')"
                        class="px-2 py-1 rounded border {{ $combinator === 'OR' ? 'bg-[var(--ui-primary)] text-white border-[var(--ui-primary)]' : 'border-[var(--ui-border)]' }}">
                    ODER
                </button>
            </div>
        @endif
    </div>

    @if(empty($sourceOptions))
        <div class="text-xs text-[var(--ui-muted)] p-2 italic">
            Keine vorherigen Felder vorhanden. Regeln können nur an zuvor angelegte Felder gebunden werden.
        </div>
    @else
        @foreach($ruleList as $ruleIndex => $rule)
            @php $op = $rule['operator'] ?? 'equals'; @endphp
            <div class="flex items-start gap-2">
                <div class="flex-grow grid grid-cols-[1fr_auto_1fr_auto] gap-2 items-start">
                    <select wire:model.live="editingBlock.visibility_rules.rules.{{ $ruleIndex }}.source_block_id"
                            class="text-sm border border-[var(--ui-border)] rounded px-2 py-1 bg-white">
                        <option value="">Quell-Feld wählen…</option>
                        @foreach($sourceOptions as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="editingBlock.visibility_rules.rules.{{ $ruleIndex }}.operator"
                            class="text-sm border border-[var(--ui-border)] rounded px-2 py-1 bg-white">
                        @foreach($operatorOptions as $opt)
                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                    @if(!in_array($op, ['empty', 'not_empty']))
                        <input type="text"
                               wire:model.live.debounce.500ms="editingBlock.visibility_rules.rules.{{ $ruleIndex }}.value"
                               placeholder="Wert"
                               class="text-sm border border-[var(--ui-border)] rounded px-2 py-1 bg-white" />
                    @else
                        <span></span>
                    @endif
                    <button type="button" wire:click="removeVisibilityRule({{ $ruleIndex }})"
                            class="text-xs text-rose-500 hover:text-rose-700 px-2">
                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                    </button>
                </div>
            </div>
        @endforeach
        <button type="button" wire:click="addVisibilityRule"
                class="text-xs text-[var(--ui-primary)] hover:underline inline-flex items-center gap-1">
            @svg('heroicon-o-plus', 'w-3 h-3') Regel hinzufügen
        </button>
    @endif
</div>
