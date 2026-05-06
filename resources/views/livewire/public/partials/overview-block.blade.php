{{--
    Overview-Mode Block Renderer
    ----------------------------
    Erwartet:
      - $block        (assoc array: id, type, name, description, logic_config, ...)
      - $isReadOnly   (bool)
      - $compact      (bool) — wenn true: kompakte Tabellenzeilen-Darstellung,
                       z. B. innerhalb eines compact_table-Segments. Beeinflusst
                       v. a. Inputs (kleinere Buttons, Inline-Layout).
    State wird ueber die indexed Properties der Komponente gelesen:
      $answersByBlock, $selectedOptionsByBlock, $matrixAnswersByBlock, ...
--}}
@php
    $blockId = $block['id'];
    $type = $block['type'];
    $config = $block['logic_config'] ?? [];
    $compact = $compact ?? false;
@endphp

@switch($type)
    @case('text')
        <input type="text"
            wire:model="answersByBlock.{{ $blockId }}"
            placeholder="{{ $config['placeholder'] ?? 'Ihre Antwort...' }}"
            @if(!empty($config['maxlength'])) maxlength="{{ $config['maxlength'] }}" @endif
            @if($isReadOnly) disabled @endif
            class="intake-input {{ $compact ? 'intake-input-sm' : '' }} {{ $isReadOnly ? 'opacity-60' : '' }}">
        @break

    @case('long_text')
        <textarea
            wire:model="answersByBlock.{{ $blockId }}"
            rows="{{ $compact ? 2 : ($config['rows'] ?? 5) }}"
            placeholder="{{ $config['placeholder'] ?? 'Ihre Antwort...' }}"
            @if(!empty($config['maxlength'])) maxlength="{{ $config['maxlength'] }}" @endif
            @if($isReadOnly) disabled @endif
            class="intake-input {{ $compact ? 'intake-input-sm' : '' }} resize-y {{ $isReadOnly ? 'opacity-60' : '' }}"></textarea>
        @break

    @case('email')
        <input type="email" wire:model="answersByBlock.{{ $blockId }}"
            placeholder="{{ $config['placeholder'] ?? 'name@beispiel.de' }}"
            @if($isReadOnly) disabled @endif
            class="intake-input {{ $compact ? 'intake-input-sm' : '' }} {{ $isReadOnly ? 'opacity-60' : '' }}">
        @break

    @case('phone')
        <input type="tel" wire:model="answersByBlock.{{ $blockId }}"
            placeholder="{{ $config['placeholder'] ?? '+41 ...' }}"
            @if($isReadOnly) disabled @endif
            class="intake-input {{ $compact ? 'intake-input-sm' : '' }} {{ $isReadOnly ? 'opacity-60' : '' }}">
        @break

    @case('url')
        <input type="url" wire:model="answersByBlock.{{ $blockId }}"
            placeholder="{{ $config['placeholder'] ?? 'https://...' }}"
            @if($isReadOnly) disabled @endif
            class="intake-input {{ $compact ? 'intake-input-sm' : '' }} {{ $isReadOnly ? 'opacity-60' : '' }}">
        @break

    @case('number')
        <div class="flex items-center gap-2">
            <input type="number" wire:model="answersByBlock.{{ $blockId }}"
                placeholder="{{ $config['placeholder'] ?? '' }}"
                @if(isset($config['min'])) min="{{ $config['min'] }}" @endif
                @if(isset($config['max'])) max="{{ $config['max'] }}" @endif
                @if(isset($config['step'])) step="{{ $config['step'] }}" @endif
                @if($isReadOnly) disabled @endif
                class="intake-input {{ $compact ? 'intake-input-sm' : '' }} {{ $isReadOnly ? 'opacity-60' : '' }}">
            @if(!empty($config['unit']))
                <span class="text-xs font-medium text-gray-400 flex-shrink-0">{{ $config['unit'] }}</span>
            @endif
        </div>
        @break

    @case('date')
        <input type="date" wire:model="answersByBlock.{{ $blockId }}"
            @if(!empty($config['min'])) min="{{ $config['min'] }}" @endif
            @if(!empty($config['max'])) max="{{ $config['max'] }}" @endif
            @if($isReadOnly) disabled @endif
            class="intake-input {{ $compact ? 'intake-input-sm' : '' }} {{ $isReadOnly ? 'opacity-60' : '' }}">
        @break

    @case('datetime')
        <input type="datetime-local" wire:model="answersByBlock.{{ $blockId }}"
            @if(!empty($config['min_datetime'])) min="{{ $config['min_datetime'] }}" @endif
            @if(!empty($config['max_datetime'])) max="{{ $config['max_datetime'] }}" @endif
            @if($isReadOnly) disabled @endif
            class="intake-input {{ $compact ? 'intake-input-sm' : '' }} {{ $isReadOnly ? 'opacity-60' : '' }}">
        @break

    @case('time')
        <input type="time" wire:model="answersByBlock.{{ $blockId }}"
            @if(!empty($config['min_time'])) min="{{ $config['min_time'] }}" @endif
            @if(!empty($config['max_time'])) max="{{ $config['max_time'] }}" @endif
            @if(!empty($config['step_minutes'])) step="{{ (int)$config['step_minutes'] * 60 }}" @endif
            @if($isReadOnly) disabled @endif
            class="intake-input {{ $compact ? 'intake-input-sm' : '' }} {{ $isReadOnly ? 'opacity-60' : '' }}">
        @break

    @case('select')
        @php $current = $answersByBlock[$blockId] ?? ''; @endphp
        <div class="space-y-2">
            @foreach(($config['options'] ?? []) as $option)
                @php
                    $optionValue = is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option;
                    $optionLabel = is_array($option) ? ($option['label'] ?? $option['value'] ?? '') : $option;
                    $isChosen = $current === $optionValue;
                @endphp
                <button type="button"
                    @if(!$isReadOnly) wire:click="setAnswerFor('{{ $blockId }}', @js($optionValue))" @endif
                    @if($isReadOnly) disabled @endif
                    class="intake-option-card {{ $isChosen ? 'intake-option-active' : '' }} {{ $isReadOnly ? 'cursor-default' : '' }}">
                    <span class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 {{ $isChosen ? 'border-violet-600' : 'border-gray-300' }}">
                        @if($isChosen)<span class="w-2 h-2 rounded-full bg-violet-600"></span>@endif
                    </span>
                    <span class="text-sm {{ $isChosen ? 'text-gray-900' : 'text-gray-600' }}">{{ $optionLabel }}</span>
                </button>
            @endforeach
        </div>
        @break

    @case('multi_select')
        @php
            $sel = $selectedOptionsByBlock[$blockId] ?? [];
            $minSel = $config['min_selections'] ?? null;
            $maxSel = $config['max_selections'] ?? null;
            $minSel = ($minSel === '' || $minSel === null) ? null : (int) $minSel;
            $maxSel = ($maxSel === '' || $maxSel === null) ? null : (int) $maxSel;
            $selectedCount = count($sel);
            $hint = null;
            if ($minSel && $maxSel && $minSel === $maxSel) {
                $hint = "Genau {$minSel} Option(en)";
            } elseif ($minSel && $maxSel) {
                $hint = "Zwischen {$minSel} und {$maxSel} Option(en)";
            } elseif ($maxSel) {
                $hint = "Max. {$maxSel} Option(en)";
            } elseif ($minSel) {
                $hint = "Min. {$minSel} Option(en)";
            }
        @endphp
        @if($hint)
            <div class="mb-2 text-xs flex items-center justify-between text-gray-500">
                <span>{{ $hint }}</span>
                <span class="font-medium text-gray-600">{{ $selectedCount }}{{ $maxSel ? '/'.$maxSel : '' }} ausgewählt</span>
            </div>
        @endif
        <div class="space-y-2">
            @foreach(($config['options'] ?? []) as $option)
                @php
                    $optionValue = is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option;
                    $optionLabel = is_array($option) ? ($option['label'] ?? $option['value'] ?? '') : $option;
                    $isSelected = in_array($optionValue, $sel);
                    $maxReached = $maxSel !== null && $selectedCount >= $maxSel && !$isSelected;
                    $optDisabled = $isReadOnly || $maxReached;
                @endphp
                <button type="button"
                    @if(!$optDisabled) wire:click="toggleOptionFor('{{ $blockId }}', @js($optionValue))" @endif
                    @if($optDisabled) disabled aria-disabled="true" @endif
                    class="intake-option-card {{ $isSelected ? 'intake-option-active' : '' }} {{ $isReadOnly ? 'cursor-default' : '' }} {{ $maxReached ? 'opacity-40 cursor-not-allowed' : '' }}">
                    <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0 border-2 {{ $isSelected ? 'border-violet-600 bg-violet-600' : 'border-gray-300' }}">
                        @if($isSelected)
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        @endif
                    </span>
                    <span class="text-sm {{ $isSelected ? 'text-gray-900' : 'text-gray-600' }}">{{ $optionLabel }}</span>
                </button>
            @endforeach
        </div>
        @break

    @case('boolean')
        @php
            $current = $answersByBlock[$blockId] ?? '';
            $trueLabel = $config['true_label'] ?? 'Ja';
            $falseLabel = $config['false_label'] ?? 'Nein';
        @endphp
        @if($compact)
            {{-- Kompakt: Daumen hoch/runter inline --}}
            <div class="intake-thumbs">
                <button type="button"
                    @if(!$isReadOnly) wire:click="setAnswerFor('{{ $blockId }}', 'true')" @endif
                    @if($isReadOnly) disabled @endif
                    class="intake-thumb {{ $current === 'true' ? 'up-active' : '' }}"
                    title="{{ $trueLabel }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                    </svg>
                </button>
                <button type="button"
                    @if(!$isReadOnly) wire:click="setAnswerFor('{{ $blockId }}', 'false')" @endif
                    @if($isReadOnly) disabled @endif
                    class="intake-thumb {{ $current === 'false' ? 'down-active' : '' }}"
                    title="{{ $falseLabel }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06L17 4m-7 10v5a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/>
                    </svg>
                </button>
            </div>
        @else
            <div class="grid grid-cols-2 gap-3">
                <button type="button"
                    @if(!$isReadOnly) wire:click="setAnswerFor('{{ $blockId }}', 'true')" @endif
                    @if($isReadOnly) disabled @endif
                    class="intake-bool-card {{ $current === 'true' ? 'intake-option-active' : '' }} {{ $isReadOnly ? 'cursor-default' : '' }}">
                    <svg class="w-8 h-8 {{ $current === 'true' ? 'text-emerald-500' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-base font-semibold {{ $current === 'true' ? 'text-gray-900' : 'text-gray-400' }}">{{ $trueLabel }}</span>
                </button>
                <button type="button"
                    @if(!$isReadOnly) wire:click="setAnswerFor('{{ $blockId }}', 'false')" @endif
                    @if($isReadOnly) disabled @endif
                    class="intake-bool-card {{ $current === 'false' ? 'intake-option-active' : '' }} {{ $isReadOnly ? 'cursor-default' : '' }}">
                    <svg class="w-8 h-8 {{ $current === 'false' ? 'text-rose-500' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span class="text-base font-semibold {{ $current === 'false' ? 'text-gray-900' : 'text-gray-400' }}">{{ $falseLabel }}</span>
                </button>
            </div>
        @endif
        @break

    @case('scale')
        @php
            $current = $answersByBlock[$blockId] ?? '';
            $scaleMin = $config['min'] ?? 1;
            $scaleMax = $config['max'] ?? 5;
            $minLabel = $config['min_label'] ?? '';
            $maxLabel = $config['max_label'] ?? '';
        @endphp
        <div>
            @if(!$compact && ($minLabel || $maxLabel))
                <div class="flex justify-between mb-3 text-xs text-gray-400">
                    <span>{{ $minLabel }}</span>
                    <span>{{ $maxLabel }}</span>
                </div>
            @endif
            <div class="flex flex-wrap gap-2 {{ $compact ? '' : 'justify-center' }}">
                @for($i = $scaleMin; $i <= $scaleMax; $i++)
                    <button type="button"
                        @if(!$isReadOnly) wire:click="setAnswerFor('{{ $blockId }}', '{{ $i }}')" @endif
                        @if($isReadOnly) disabled @endif
                        class="{{ $compact ? 'w-8 h-8 text-sm' : 'w-10 h-10 text-base' }} rounded-lg font-bold transition-all
                            {{ $current === (string)$i ? 'bg-violet-600 text-white shadow-md' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}
                            {{ $isReadOnly ? 'cursor-default' : '' }}">
                        {{ $i }}
                    </button>
                @endfor
            </div>
        </div>
        @break

    @case('rating')
        @php
            $current = (int) ($answersByBlock[$blockId] ?? 0);
            $maxStars = $config['max'] ?? 5;
        @endphp
        <div class="flex gap-2 {{ $compact ? '' : 'justify-center py-2' }}">
            @for($i = 1; $i <= $maxStars; $i++)
                <button type="button"
                    @if(!$isReadOnly) wire:click="setAnswerFor('{{ $blockId }}', '{{ $i }}')" @endif
                    @if($isReadOnly) disabled @endif
                    class="{{ $isReadOnly ? 'cursor-default' : 'transition-transform hover:scale-110' }}">
                    <svg class="{{ $compact ? 'w-7 h-7' : 'w-10 h-10' }} transition-colors {{ $i <= $current ? 'text-amber-400 fill-amber-400' : 'text-gray-200' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.5">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </button>
            @endfor
        </div>
        @break

    @case('nps')
        @php $current = $answersByBlock[$blockId] ?? ''; @endphp
        <div>
            @if(!$compact)
                <div class="flex justify-between mb-3 text-xs text-gray-400 px-1">
                    <span>Überhaupt nicht wahrscheinlich</span>
                    <span>Äusserst wahrscheinlich</span>
                </div>
            @endif
            <div class="flex flex-wrap gap-1.5 {{ $compact ? '' : 'justify-center' }}">
                @for($i = 0; $i <= 10; $i++)
                    @php
                        $color = $i <= 6 ? 'bg-rose-500 text-white' : ($i <= 8 ? 'bg-amber-400 text-white' : 'bg-emerald-500 text-white');
                        $inactive = $i <= 6 ? 'bg-rose-50 text-rose-600' : ($i <= 8 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600');
                    @endphp
                    <button type="button"
                        @if(!$isReadOnly) wire:click="setAnswerFor('{{ $blockId }}', '{{ $i }}')" @endif
                        @if($isReadOnly) disabled @endif
                        class="{{ $compact ? 'w-8 h-8 text-xs' : 'w-10 h-10 text-sm' }} rounded-lg font-bold transition-all
                            {{ $current === (string)$i ? $color . ' shadow-md' : $inactive }}
                            {{ $isReadOnly ? 'cursor-default' : 'hover:scale-105' }}">
                        {{ $i }}
                    </button>
                @endfor
            </div>
        </div>
        @break

    @case('slider')
        @php
            $sliderMin = $config['min'] ?? 0;
            $sliderMax = $config['max'] ?? 100;
            $sliderStep = $config['step'] ?? 1;
            $sliderUnit = $config['unit'] ?? '';
            $showValue = $config['show_value'] ?? true;
        @endphp
        <div class="space-y-3">
            @if($showValue)
                <div class="text-center">
                    <span class="text-2xl font-bold text-violet-600">{{ $answersByBlock[$blockId] ?? $sliderMin }}</span>
                    @if($sliderUnit)<span class="text-base text-gray-400 ml-1">{{ $sliderUnit }}</span>@endif
                </div>
            @endif
            <input type="range" wire:model.live="answersByBlock.{{ $blockId }}"
                min="{{ $sliderMin }}" max="{{ $sliderMax }}" step="{{ $sliderStep }}"
                @if($isReadOnly) disabled @endif
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-violet-600 {{ $isReadOnly ? 'opacity-60' : '' }}">
            <div class="flex justify-between text-xs text-gray-400">
                <span>{{ $sliderMin }}{{ $sliderUnit ? ' ' . $sliderUnit : '' }}</span>
                <span>{{ $sliderMax }}{{ $sliderUnit ? ' ' . $sliderUnit : '' }}</span>
            </div>
        </div>
        @break

    @case('dropdown')
        @php
            $current = $answersByBlock[$blockId] ?? '';
            $dropdownOptions = $config['options'] ?? [];
        @endphp
        <select wire:model="answersByBlock.{{ $blockId }}"
            @if($isReadOnly) disabled @endif
            class="intake-input {{ $compact ? 'intake-input-sm' : '' }} {{ $isReadOnly ? 'opacity-60' : '' }}">
            <option value="">{{ $config['placeholder'] ?? 'Bitte wählen...' }}</option>
            @foreach($dropdownOptions as $option)
                @php
                    $val = is_array($option) ? ($option['value'] ?? '') : $option;
                    $lbl = is_array($option) ? ($option['label'] ?? $val) : $option;
                @endphp
                <option value="{{ $val }}">{{ $lbl }}</option>
            @endforeach
        </select>
        @break

    @case('matrix')
        @php
            $matrixState = $matrixAnswersByBlock[$blockId] ?? [];
            $matrixItems = $config['items'] ?? [];
            $sMin = (int)($config['scale_min'] ?? 1);
            $sMax = (int)($config['scale_max'] ?? 5);
            $sLabels = $config['scale_labels'] ?? [];
            $reqMode = $config['required_mode'] ?? 'matrix';
        @endphp
        <div class="space-y-3">
            @if(!empty($sLabels['min_label']) || !empty($sLabels['max_label']))
                <div class="flex justify-between text-xs text-gray-400 px-1">
                    <span>{{ $sMin }} = {{ $sLabels['min_label'] ?? '' }}</span>
                    <span>{{ $sMax }} = {{ $sLabels['max_label'] ?? '' }}</span>
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left p-2"></th>
                            @for($s = $sMin; $s <= $sMax; $s++)
                                <th class="p-2 text-center text-gray-500 font-medium">{{ $s }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($matrixItems as $item)
                            @php
                                $itemValue = is_array($item) ? ($item['value'] ?? $item['label'] ?? '') : $item;
                                $itemLabel = is_array($item) ? ($item['label'] ?? $item['value'] ?? '') : $item;
                                $itemRequired = is_array($item) && !empty($item['is_required']);
                            @endphp
                            <tr class="border-t border-gray-100">
                                <td class="p-2 text-gray-700">
                                    {{ $itemLabel }}
                                    @if($reqMode === 'per_row' && $itemRequired)<span class="text-rose-500 ml-1">*</span>@endif
                                </td>
                                @for($s = $sMin; $s <= $sMax; $s++)
                                    <td class="p-1 text-center">
                                        <button type="button"
                                            @if(!$isReadOnly) wire:click="setMatrixAnswerFor('{{ $blockId }}', @js($itemValue), '{{ $s }}')" @endif
                                            @if($isReadOnly) disabled @endif
                                            class="w-7 h-7 rounded-full border-2 transition-all {{ ($matrixState[$itemValue] ?? '') === (string)$s ? 'bg-violet-600 border-violet-600 text-white' : 'border-gray-300 hover:border-violet-400' }}">
                                            @if(($matrixState[$itemValue] ?? '') === (string)$s)
                                                <svg class="w-3.5 h-3.5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            @endif
                                        </button>
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @break

    @case('consent')
        @php
            $current = $answersByBlock[$blockId] ?? '';
            $consentText = $config['text'] ?? '';
            $linkUrl = $config['link_url'] ?? '';
            $linkLabel = $config['link_label'] ?? 'Datenschutzerklärung';
        @endphp
        <div class="space-y-3">
            @if($consentText)
                <div class="text-sm text-gray-600 leading-relaxed whitespace-pre-line p-3 bg-gray-50 rounded-lg border border-gray-100">{{ $consentText }}</div>
            @endif
            @if($linkUrl)
                <a href="{{ $linkUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-sm text-violet-600 hover:text-violet-800">
                    {{ $linkLabel }}
                </a>
            @endif
            <label class="flex items-center gap-3 p-3 rounded-lg border {{ $current === 'true' ? 'border-violet-300 bg-violet-50' : 'border-gray-200' }} {{ $isReadOnly ? 'cursor-default' : 'cursor-pointer' }}">
                <input type="checkbox"
                    @if(!$isReadOnly) wire:click="setAnswerFor('{{ $blockId }}', '{{ $current === 'true' ? 'false' : 'true' }}')" @endif
                    {{ $current === 'true' ? 'checked' : '' }}
                    @if($isReadOnly) disabled @endif
                    class="w-5 h-5 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                <span class="text-sm {{ $current === 'true' ? 'text-gray-900 font-medium' : 'text-gray-600' }}">Ich stimme zu</span>
            </label>
        </div>
        @break

    @case('info')
        @if(!empty($config['content']))
            <div class="flex gap-3 p-4 bg-blue-50 border border-blue-100 rounded-lg">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-900 leading-relaxed whitespace-pre-line">{{ $config['content'] }}</div>
            </div>
        @endif
        @break

    @case('section')
        <div class="py-2">
            @if(!empty($config['title']))
                <h3 class="text-base font-bold text-gray-900">{{ $config['title'] }}</h3>
            @endif
            @if(!empty($config['subtitle']))
                <p class="mt-1 text-sm text-gray-500">{{ $config['subtitle'] }}</p>
            @endif
            @if(!empty($config['content']))
                <div class="mt-2 text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $config['content'] }}</div>
            @endif
            <div class="mt-3 border-t border-gray-200"></div>
        </div>
        @break

    @case('hidden')
        {{-- Im Overview-Modus nichts rendern (Werte werden durch autoSaveHiddenFields gepflegt). --}}
        @break

    @case('calculated')
        @if(!empty($config['template']))
            <div class="text-sm text-gray-500 italic p-3 bg-gray-50 rounded-lg">{{ $config['template'] }}</div>
        @endif
        @break

    @case('color')
        @php $current = $answersByBlock[$blockId] ?? ''; @endphp
        <div class="flex items-center gap-3">
            <input type="color" wire:model="answersByBlock.{{ $blockId }}"
                @if($isReadOnly) disabled @endif
                class="w-12 h-12 rounded-lg border border-gray-200 cursor-pointer {{ $isReadOnly ? 'opacity-60' : '' }}">
            <input type="text" wire:model="answersByBlock.{{ $blockId }}"
                placeholder="#000000"
                @if($isReadOnly) disabled @endif
                class="intake-input intake-input-sm {{ $isReadOnly ? 'opacity-60' : '' }}">
        </div>
        @break

    @case('location')
        <input type="text" wire:model="answersByBlock.{{ $blockId }}"
            placeholder="{{ $config['placeholder'] ?? 'Standort eingeben...' }}"
            @if($isReadOnly) disabled @endif
            class="intake-input {{ $compact ? 'intake-input-sm' : '' }} {{ $isReadOnly ? 'opacity-60' : '' }}">
        @break

    @case('lookup')
        @php
            $multiple = $config['multiple'] ?? false;
            $opts = $lookupOptions[$blockId] ?? [];
            $current = $answersByBlock[$blockId] ?? '';
            $sel = $selectedOptionsByBlock[$blockId] ?? [];
        @endphp
        @if($multiple)
            <div class="space-y-2">
                @foreach($opts as $opt)
                    @php
                        $val = $opt['value'] ?? '';
                        $lbl = $opt['label'] ?? $val;
                        $isSelected = in_array($val, $sel);
                    @endphp
                    <button type="button"
                        @if(!$isReadOnly) wire:click="toggleOptionFor('{{ $blockId }}', @js($val))" @endif
                        @if($isReadOnly) disabled @endif
                        class="intake-option-card {{ $isSelected ? 'intake-option-active' : '' }}">
                        <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0 border-2 {{ $isSelected ? 'border-violet-600 bg-violet-600' : 'border-gray-300' }}">
                            @if($isSelected)<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>@endif
                        </span>
                        <span class="text-sm">{{ $lbl }}</span>
                    </button>
                @endforeach
            </div>
        @else
            <select wire:model="answersByBlock.{{ $blockId }}"
                @if($isReadOnly) disabled @endif
                class="intake-input {{ $compact ? 'intake-input-sm' : '' }} {{ $isReadOnly ? 'opacity-60' : '' }}">
                <option value="">{{ $config['placeholder'] ?? 'Bitte wählen...' }}</option>
                @foreach($opts as $opt)
                    <option value="{{ $opt['value'] ?? '' }}">{{ $opt['label'] ?? ($opt['value'] ?? '') }}</option>
                @endforeach
            </select>
        @endif
        @break

    @case('address')
        @php
            $fields = $config['fields'] ?? ['street','city','postal_code','country'];
            $state = $addressFieldsByBlock[$blockId] ?? [];
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($fields as $f)
                @php
                    $key = is_array($f) ? ($f['key'] ?? '') : $f;
                    $label = is_array($f) ? ($f['label'] ?? $key) : ucfirst(str_replace('_',' ',$key));
                @endphp
                @if($key !== '')
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
                        <input type="text"
                            value="{{ $state[$key] ?? '' }}"
                            wire:input="updateAddressFieldFor('{{ $blockId }}', @js($key), $event.target.value)"
                            @if($isReadOnly) disabled @endif
                            class="intake-input intake-input-sm {{ $isReadOnly ? 'opacity-60' : '' }}">
                    </div>
                @endif
            @endforeach
        </div>
        @break

    @case('date_range')
        @php
            $start = $dateRangeStartByBlock[$blockId] ?? '';
            $end = $dateRangeEndByBlock[$blockId] ?? '';
        @endphp
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Von</label>
                <input type="date"
                    value="{{ $start }}"
                    wire:input="setDateRangeStartFor('{{ $blockId }}', $event.target.value)"
                    @if($isReadOnly) disabled @endif
                    class="intake-input intake-input-sm {{ $isReadOnly ? 'opacity-60' : '' }}">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Bis</label>
                <input type="date"
                    value="{{ $end }}"
                    wire:input="setDateRangeEndFor('{{ $blockId }}', $event.target.value)"
                    @if($isReadOnly) disabled @endif
                    class="intake-input intake-input-sm {{ $isReadOnly ? 'opacity-60' : '' }}">
            </div>
        </div>
        @break

    @case('ranking')
        @php
            $rankOptions = $config['options'] ?? [];
            $order = $rankingOrderByBlock[$blockId] ?? [];
            $rankMap = collect($rankOptions)->mapWithKeys(fn($o) => [
                (is_array($o) ? ($o['value'] ?? '') : $o)
                => (is_array($o) ? ($o['label'] ?? $o['value'] ?? '') : $o)
            ])->toArray();
        @endphp
        <div class="space-y-2">
            @foreach($order as $idx => $val)
                <div class="flex items-center gap-3 p-2.5 bg-white border border-gray-200 rounded-lg">
                    <span class="w-7 h-7 rounded-md bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">{{ $idx + 1 }}</span>
                    <span class="text-sm text-gray-700 flex-1">{{ $rankMap[$val] ?? $val }}</span>
                    @if(!$isReadOnly)
                        <div class="flex gap-1">
                            <button type="button"
                                @disabled($idx === 0)
                                wire:click="moveRankingItemFor('{{ $blockId }}', {{ $idx }}, {{ $idx - 1 }})"
                                class="w-7 h-7 rounded border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30">
                                ↑
                            </button>
                            <button type="button"
                                @disabled($idx === count($order) - 1)
                                wire:click="moveRankingItemFor('{{ $blockId }}', {{ $idx }}, {{ $idx + 1 }})"
                                class="w-7 h-7 rounded border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30">
                                ↓
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @break

    @case('repeater')
        @php
            $entries = $repeaterEntriesByBlock[$blockId] ?? [];
            $fields = $config['fields'] ?? [];
            $maxEntries = (int)($config['max_entries'] ?? 10);
            $minEntries = (int)($config['min_entries'] ?? 0);
        @endphp
        <div class="space-y-3">
            @foreach($entries as $eIdx => $entry)
                <div class="p-3 border border-gray-200 rounded-lg bg-gray-50/40">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-semibold text-gray-500">Eintrag {{ $eIdx + 1 }}</span>
                        @if(!$isReadOnly && count($entries) > $minEntries)
                            <button type="button"
                                wire:click="removeRepeaterEntryFor('{{ $blockId }}', {{ $eIdx }})"
                                class="text-xs text-rose-600 hover:text-rose-700">Entfernen</button>
                        @endif
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach($fields as $f)
                            @php
                                $key = $f['key'] ?? '';
                                $label = $f['label'] ?? $key;
                                $type = $f['type'] ?? 'text';
                            @endphp
                            @if($key !== '')
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
                                    <input type="{{ $type === 'number' ? 'number' : 'text' }}"
                                        value="{{ $entry[$key] ?? '' }}"
                                        wire:input="updateRepeaterFieldFor('{{ $blockId }}', {{ $eIdx }}, @js($key), $event.target.value)"
                                        @if($isReadOnly) disabled @endif
                                        class="intake-input intake-input-sm {{ $isReadOnly ? 'opacity-60' : '' }}">
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
            @if(!$isReadOnly && count($entries) < $maxEntries)
                <button type="button"
                    wire:click="addRepeaterEntryFor('{{ $blockId }}')"
                    class="w-full py-2 text-sm text-violet-600 hover:bg-violet-50 border border-dashed border-violet-200 rounded-lg">
                    + Eintrag hinzufügen
                </button>
            @endif
        </div>
        @break

    @case('image_choice')
        @php
            $current = $answersByBlock[$blockId] ?? '';
            $imgOptions = $config['options'] ?? [];
            $cols = $config['columns'] ?? 3;
        @endphp
        <div class="grid gap-2" style="grid-template-columns: repeat({{ $cols }}, 1fr)">
            @foreach($imgOptions as $imgOpt)
                @php
                    $val = is_array($imgOpt) ? ($imgOpt['value'] ?? '') : $imgOpt;
                    $lbl = is_array($imgOpt) ? ($imgOpt['label'] ?? '') : $imgOpt;
                    $fileId = is_array($imgOpt) ? ($imgOpt['file_id'] ?? null) : null;
                    $isChosen = $current === $val;
                @endphp
                <button type="button"
                    @if(!$isReadOnly) wire:click="setAnswerFor('{{ $blockId }}', @js($val))" @endif
                    @if($isReadOnly) disabled @endif
                    class="relative rounded-lg border-2 overflow-hidden transition-all {{ $isChosen ? 'border-violet-500 ring-2 ring-violet-200' : 'border-gray-200 hover:border-gray-300' }}">
                    <div class="aspect-square bg-gray-100 flex items-center justify-center">
                        @if($fileId)
                            <img src="{{ route('core.files.serve', $fileId) }}" alt="{{ $lbl }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        @endif
                    </div>
                    @if($lbl)
                        <div class="p-1.5 text-center text-xs {{ $isChosen ? 'text-violet-700 font-medium' : 'text-gray-600' }}">{{ $lbl }}</div>
                    @endif
                </button>
            @endforeach
        </div>
        @break

    @case('file')
    @case('signature')
        <div class="p-4 border-2 border-dashed border-gray-200 rounded-lg text-center">
            <p class="text-sm text-gray-400">{{ $type === 'file' ? 'Datei-Upload' : 'Signatur' }} wird in einer spaeteren Version unterstuetzt.</p>
        </div>
        @break

    @default
        <input type="text" wire:model="answersByBlock.{{ $blockId }}"
            placeholder="Ihre Antwort..."
            @if($isReadOnly) disabled @endif
            class="intake-input {{ $compact ? 'intake-input-sm' : '' }} {{ $isReadOnly ? 'opacity-60' : '' }}">
@endswitch
