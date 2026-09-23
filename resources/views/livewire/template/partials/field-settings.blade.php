{{--
    Einstellungen eines Feldes, generiert aus BlockTypes::definitions()[type]['settings'].
    Erwartet: $i (Index im Entwurf), $field (Entwurf), $def (BlockTypes::get), $lookups
--}}
@php
    $settings = $def['settings'] ?? [];
    $base = "draft.fields.$i.config";
    $simple = collect($settings)->reject(fn ($s) => in_array($s['kind'], ['options', 'items', 'textarea'], true));
    $lists = collect($settings)->filter(fn ($s) => in_array($s['kind'], ['options', 'items'], true));
    $texts = collect($settings)->where('kind', 'textarea');
@endphp

@if(empty($settings) && empty($def['advanced_only']))
    <p class="text-xs text-[color:var(--nx-faint)]">Für diesen Feldtyp gibt es keine weiteren Einstellungen.</p>
@endif

{{-- Listen: Antwortmöglichkeiten / Matrix-Zeilen --}}
@foreach($lists as $s)
    @php
        $rows = data_get($field['config'], $s['key'], []);
        $isItems = $s['kind'] === 'items';
        $perRow = $isItems && (data_get($field['config'], 'required_mode') === 'per_row');
    @endphp
    <div>
        <div class="mb-1.5 flex items-center justify-between gap-2">
            <span class="text-xs font-medium text-[color:var(--nx-text)]">{{ $s['label'] }}</span>
            <span class="text-xs tabular-nums text-[color:var(--nx-faint)]">{{ count($rows) }}</span>
        </div>
        <div class="space-y-1.5">
            @foreach($rows as $r => $row)
                <div wire:key="row-{{ $field['uid'] }}-{{ $s['key'] }}-{{ $r }}" class="flex items-center gap-1.5">
                    <span class="w-5 shrink-0 text-right text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $r + 1 }}</span>
                    <input type="text" wire:model.live.debounce.400ms="{{ $base }}.{{ $s['key'] }}.{{ $r }}.label"
                        placeholder="{{ $isItems ? 'z.B. Geschmack' : 'Antwort' }}"
                        class="hatch-editor-select min-w-0 flex-1 {{ $errors->has("$base.{$s['key']}.$r.label") ? '!border-[color:var(--nx-danger)]' : '' }}">
                    @if($isItems)
                        <input type="text" wire:model.live.debounce.400ms="{{ $base }}.{{ $s['key'] }}.{{ $r }}.group"
                            placeholder="Gruppe (optional)" title="Zwischenüberschrift, unter der diese Zeile steht" class="hatch-editor-select w-36 shrink-0">
                        @if($perRow)
                            <label class="inline-flex shrink-0 items-center gap-1 text-xs text-[color:var(--nx-muted)]" title="Pflicht für diese Zeile">
                                <input type="checkbox" wire:model.live="{{ $base }}.{{ $s['key'] }}.{{ $r }}.is_required" class="rounded"> Pflicht
                            </label>
                        @endif
                    @endif
                    <x-nx-button icon variant="ghost" wire:click="moveRow({{ $i }}, '{{ $s['key'] }}', {{ $r }}, -1)" title="Nach oben" :disabled="$r === 0">@svg('heroicon-o-arrow-up', 'w-3.5 h-3.5')</x-nx-button>
                    <x-nx-button icon variant="ghost" wire:click="moveRow({{ $i }}, '{{ $s['key'] }}', {{ $r }}, 1)" title="Nach unten" :disabled="$r === count($rows) - 1">@svg('heroicon-o-arrow-down', 'w-3.5 h-3.5')</x-nx-button>
                    <x-nx-button icon variant="ghost" wire:click="removeRow({{ $i }}, '{{ $s['key'] }}', {{ $r }})" title="Entfernen">@svg('heroicon-o-x-mark', 'w-3.5 h-3.5')</x-nx-button>
                </div>
            @endforeach
        </div>
        <button type="button" wire:click="addRow({{ $i }}, '{{ $s['key'] }}')"
            class="mt-1.5 inline-flex items-center gap-1 pl-6 text-xs text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)]">
            @svg('heroicon-o-plus', 'w-3.5 h-3.5')
            {{ $isItems ? 'Aspekt hinzufügen' : 'Antwort hinzufügen' }}
        </button>
    </div>
@endforeach

{{-- Einfache Einstellungen im Raster --}}
@if($simple->isNotEmpty())
    <div class="grid grid-cols-2 gap-3">
        @foreach($simple as $s)
            @php $path = $base . '.' . $s['key']; @endphp
            <div wire:key="set-{{ $field['uid'] }}-{{ $s['key'] }}" class="{{ in_array($s['kind'], ['select', 'lookup', 'toggle'], true) || ($s['help'] ?? false) ? 'col-span-2' : '' }}">
                @switch($s['kind'])
                    @case('toggle')
                        <x-nx-input-checkbox wire:model.live="{{ $path }}" :label="$s['label']" />
                        @break
                    @case('select')
                        <x-nx-input-select name="{{ $path }}" :label="$s['label']" :options="$s['choices']" wire:model.live="{{ $path }}" />
                        @break
                    @case('lookup')
                        <x-nx-input-select name="{{ $path }}" :label="$s['label']" :options="$lookups" optionValue="id" optionLabel="label"
                            nullable nullLabel="– Auswahlliste wählen –" wire:model.live="{{ $path }}" />
                        @if($lookups->isEmpty())
                            <p class="mt-1 text-xs text-[color:var(--nx-faint)]">Noch keine Auswahllisten – <a href="{{ route('hatch.lookups.index') }}" wire:navigate class="underline">hier anlegen</a>.</p>
                        @endif
                        @break
                    @case('number')
                        <x-nx-input-text type="number" name="{{ $path }}" :label="$s['label']" wire:model.live.debounce.400ms="{{ $path }}" :placeholder="$s['placeholder'] ?? null" />
                        @break
                    @default
                        <x-nx-input-text name="{{ $path }}" :label="$s['label']" wire:model.live.debounce.400ms="{{ $path }}" :placeholder="$s['placeholder'] ?? null" />
                @endswitch
                @if(!empty($s['help']))
                    <p class="mt-1 text-xs text-[color:var(--nx-faint)]">{{ $s['help'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
@endif

{{-- Längere Texte --}}
@foreach($texts as $s)
    <x-nx-input-textarea wire:key="set-{{ $field['uid'] }}-{{ $s['key'] }}" name="{{ $base }}.{{ $s['key'] }}" :label="$s['label']" rows="3"
        wire:model.live.debounce.400ms="{{ $base }}.{{ $s['key'] }}" />
@endforeach
