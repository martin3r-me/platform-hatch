<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    @if($state === 'notFound')
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="w-full max-w-lg bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-2xl rounded-2xl p-8 text-center">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Session nicht gefunden</h1>
                <p class="text-gray-600 dark:text-gray-300">Diese Session ist ungueltig oder existiert nicht mehr.</p>
            </div>
        </div>

    @elseif($state === 'completed')
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="w-full max-w-lg bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-2xl rounded-2xl p-8 text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Vielen Dank!</h1>
                <p class="text-gray-600 dark:text-gray-300">Ihre Antworten wurden erfolgreich uebermittelt.</p>
                @if($intakeName)
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ $intakeName }}</p>
                @endif
            </div>
        </div>

    @elseif($state === 'ready')
        {{-- Sticky Header --}}
        <header class="sticky top-0 z-50 bg-white/70 dark:bg-gray-900/70 backdrop-blur-md border-b border-gray-200/50 dark:border-gray-700/50">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white truncate">{{ $intakeName }}</h1>
                <div class="flex items-center gap-3 flex-shrink-0 ml-4">
                    {{-- Token Badge with Copy --}}
                    <div
                        x-data="{ copied: false }"
                        class="flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        x-on:click="navigator.clipboard.writeText('{{ $sessionToken }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        title="Token kopieren"
                    >
                        <span class="text-xs text-gray-500 dark:text-gray-400">Token:</span>
                        <span class="text-xs font-mono font-semibold text-gray-700 dark:text-gray-200 tracking-wider">{{ $sessionToken }}</span>
                        <svg x-show="!copied" class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>

                    @if($totalBlocks > 0)
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $currentStep + 1 }} / {{ $totalBlocks }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Fortschrittsbalken --}}
            @if($totalBlocks > 0)
                <div class="h-1 bg-gray-100 dark:bg-gray-800">
                    <div
                        class="h-full bg-gradient-to-r from-indigo-500 to-blue-500 transition-all duration-500"
                        style="width: {{ $totalBlocks > 0 ? (($currentStep) / $totalBlocks) * 100 : 0 }}%"
                    ></div>
                </div>
            @endif
        </header>

        {{-- Token Hint --}}
        <div class="max-w-4xl mx-auto px-4 pt-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                Speichern Sie Ihren Token <span class="font-mono font-semibold">{{ $sessionToken }}</span>, um spaeter fortzufahren.
            </p>
        </div>

        {{-- Content --}}
        <main class="max-w-4xl mx-auto px-4 py-6">
            {{-- Block-Uebersicht --}}
            @if(count($blocks) > 0)
                <div class="mb-8">
                    <div class="flex flex-wrap gap-2">
                        @foreach($blocks as $index => $block)
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm
                                {{ $index === $currentStep
                                    ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 ring-2 ring-indigo-500/30'
                                    : ($index < $currentStep
                                        ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400')
                                }}">
                                <span class="w-5 h-5 flex items-center justify-center rounded-full text-xs font-bold
                                    {{ $index === $currentStep
                                        ? 'bg-indigo-500 text-white'
                                        : ($index < $currentStep
                                            ? 'bg-green-500 text-white'
                                            : 'bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-300')
                                    }}">
                                    @if($index < $currentStep)
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </span>
                                <span>{{ $block['name'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Aktiver Block --}}
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-2xl rounded-2xl overflow-hidden">
                @if(isset($blocks[$currentStep]))
                    @php
                        $block = $blocks[$currentStep];
                        $type = $block['type'];
                        $config = $block['logic_config'] ?? [];
                    @endphp

                    <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $block['name'] }}
                            @if($block['is_required'])
                                <span class="text-red-500 ml-1">*</span>
                            @endif
                        </h2>
                        @if($block['description'])
                            <p class="mt-1 text-gray-600 dark:text-gray-300">
                                {{ $block['description'] }}
                            </p>
                        @endif
                    </div>

                    <div class="p-6">
                        @switch($type)
                            {{-- Text --}}
                            @case('text')
                                <input
                                    type="text"
                                    wire:model="currentAnswer"
                                    placeholder="{{ $config['placeholder'] ?? 'Ihre Antwort...' }}"
                                    @if(!empty($config['maxlength'])) maxlength="{{ $config['maxlength'] }}" @endif
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                >
                                @break

                            {{-- Long Text --}}
                            @case('long_text')
                                <textarea
                                    wire:model="currentAnswer"
                                    rows="{{ $config['rows'] ?? 8 }}"
                                    placeholder="{{ $config['placeholder'] ?? 'Ihre Antwort...' }}"
                                    @if(!empty($config['maxlength'])) maxlength="{{ $config['maxlength'] }}" @endif
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-y"
                                ></textarea>
                                @break

                            {{-- Email --}}
                            @case('email')
                                <input
                                    type="email"
                                    wire:model="currentAnswer"
                                    placeholder="{{ $config['placeholder'] ?? 'name@beispiel.de' }}"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                >
                                @break

                            {{-- Phone --}}
                            @case('phone')
                                <input
                                    type="tel"
                                    wire:model="currentAnswer"
                                    placeholder="{{ $config['placeholder'] ?? '+41 ...' }}"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                >
                                @break

                            {{-- URL --}}
                            @case('url')
                                <input
                                    type="url"
                                    wire:model="currentAnswer"
                                    placeholder="{{ $config['placeholder'] ?? 'https://...' }}"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                >
                                @break

                            {{-- Number --}}
                            @case('number')
                                <div class="flex items-center gap-3">
                                    <input
                                        type="number"
                                        wire:model="currentAnswer"
                                        placeholder="{{ $config['placeholder'] ?? '' }}"
                                        @if(isset($config['min'])) min="{{ $config['min'] }}" @endif
                                        @if(isset($config['max'])) max="{{ $config['max'] }}" @endif
                                        @if(isset($config['step'])) step="{{ $config['step'] }}" @endif
                                        class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                    >
                                    @if(!empty($config['unit']))
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 flex-shrink-0">{{ $config['unit'] }}</span>
                                    @endif
                                </div>
                                @break

                            {{-- Date --}}
                            @case('date')
                                <input
                                    type="date"
                                    wire:model="currentAnswer"
                                    @if(!empty($config['min'])) min="{{ $config['min'] }}" @endif
                                    @if(!empty($config['max'])) max="{{ $config['max'] }}" @endif
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                >
                                @break

                            {{-- Select (Single) - Radio Cards --}}
                            @case('select')
                                <div class="space-y-2">
                                    @foreach(($config['options'] ?? []) as $option)
                                        @php
                                            $optionValue = is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option;
                                            $optionLabel = is_array($option) ? ($option['label'] ?? $option['value'] ?? '') : $option;
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="setAnswer('{{ $optionValue }}')"
                                            class="w-full flex items-center gap-3 px-4 py-3 rounded-lg border-2 transition-all text-left
                                                {{ $currentAnswer === $optionValue
                                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 ring-2 ring-indigo-500/20'
                                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 bg-white dark:bg-gray-700'
                                                }}"
                                        >
                                            <span class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                                {{ $currentAnswer === $optionValue ? 'border-indigo-500' : 'border-gray-300 dark:border-gray-500' }}">
                                                @if($currentAnswer === $optionValue)
                                                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                                @endif
                                            </span>
                                            <span class="text-gray-900 dark:text-white">{{ $optionLabel }}</span>
                                        </button>
                                    @endforeach
                                </div>
                                @break

                            {{-- Multi Select - Checkbox Cards --}}
                            @case('multi_select')
                                <div class="space-y-2">
                                    @foreach(($config['options'] ?? []) as $option)
                                        @php
                                            $optionValue = is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option;
                                            $optionLabel = is_array($option) ? ($option['label'] ?? $option['value'] ?? '') : $option;
                                            $isSelected = in_array($optionValue, $selectedOptions);
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="toggleOption('{{ $optionValue }}')"
                                            class="w-full flex items-center gap-3 px-4 py-3 rounded-lg border-2 transition-all text-left
                                                {{ $isSelected
                                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 ring-2 ring-indigo-500/20'
                                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 bg-white dark:bg-gray-700'
                                                }}"
                                        >
                                            <span class="w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0
                                                {{ $isSelected ? 'border-indigo-500 bg-indigo-500' : 'border-gray-300 dark:border-gray-500' }}">
                                                @if($isSelected)
                                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                @endif
                                            </span>
                                            <span class="text-gray-900 dark:text-white">{{ $optionLabel }}</span>
                                        </button>
                                    @endforeach
                                </div>
                                @break

                            {{-- Boolean - Ja/Nein Buttons --}}
                            @case('boolean')
                                @php
                                    $trueLabel = $config['true_label'] ?? 'Ja';
                                    $falseLabel = $config['false_label'] ?? 'Nein';
                                @endphp
                                <div class="grid grid-cols-2 gap-4">
                                    <button
                                        type="button"
                                        wire:click="setAnswer('true')"
                                        class="flex flex-col items-center justify-center gap-2 px-6 py-6 rounded-xl border-2 transition-all
                                            {{ $currentAnswer === 'true'
                                                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 ring-2 ring-indigo-500/20'
                                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 bg-white dark:bg-gray-700'
                                            }}"
                                    >
                                        <svg class="w-8 h-8 {{ $currentAnswer === 'true' ? 'text-indigo-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="text-lg font-medium {{ $currentAnswer === 'true' ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200' }}">{{ $trueLabel }}</span>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="setAnswer('false')"
                                        class="flex flex-col items-center justify-center gap-2 px-6 py-6 rounded-xl border-2 transition-all
                                            {{ $currentAnswer === 'false'
                                                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 ring-2 ring-indigo-500/20'
                                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 bg-white dark:bg-gray-700'
                                            }}"
                                    >
                                        <svg class="w-8 h-8 {{ $currentAnswer === 'false' ? 'text-indigo-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        <span class="text-lg font-medium {{ $currentAnswer === 'false' ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200' }}">{{ $falseLabel }}</span>
                                    </button>
                                </div>
                                @break

                            {{-- Scale --}}
                            @case('scale')
                                @php
                                    $scaleMin = $config['min'] ?? 1;
                                    $scaleMax = $config['max'] ?? 10;
                                    $minLabel = $config['min_label'] ?? '';
                                    $maxLabel = $config['max_label'] ?? '';
                                @endphp
                                <div>
                                    @if($minLabel || $maxLabel)
                                        <div class="flex justify-between mb-3 text-sm text-gray-500 dark:text-gray-400">
                                            <span>{{ $minLabel }}</span>
                                            <span>{{ $maxLabel }}</span>
                                        </div>
                                    @endif
                                    <div class="flex flex-wrap gap-2 justify-center">
                                        @for($i = $scaleMin; $i <= $scaleMax; $i++)
                                            <button
                                                type="button"
                                                wire:click="setAnswer('{{ $i }}')"
                                                class="w-12 h-12 rounded-lg border-2 font-bold text-lg transition-all
                                                    {{ $currentAnswer === (string)$i
                                                        ? 'border-indigo-500 bg-indigo-500 text-white ring-2 ring-indigo-500/20'
                                                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200'
                                                    }}"
                                            >
                                                {{ $i }}
                                            </button>
                                        @endfor
                                    </div>
                                </div>
                                @break

                            {{-- Rating (Stars) --}}
                            @case('rating')
                                @php
                                    $maxStars = $config['max'] ?? 5;
                                    $currentRating = (int) $currentAnswer;
                                @endphp
                                <div class="flex gap-2 justify-center">
                                    @for($i = 1; $i <= $maxStars; $i++)
                                        <button
                                            type="button"
                                            wire:click="setAnswer('{{ $i }}')"
                                            class="transition-transform hover:scale-110"
                                        >
                                            <svg class="w-10 h-10 {{ $i <= $currentRating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                        </button>
                                    @endfor
                                </div>
                                @break

                            {{-- File (Placeholder) --}}
                            @case('file')
                                <div class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                                    <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Datei-Upload wird in einer spaeteren Version unterstuetzt.</p>
                                </div>
                                @break

                            {{-- Location (simplified text input) --}}
                            @case('location')
                                <input
                                    type="text"
                                    wire:model="currentAnswer"
                                    placeholder="{{ $config['placeholder'] ?? 'Standort eingeben...' }}"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                >
                                @break

                            {{-- Custom / Default - Textarea Fallback --}}
                            @default
                                <textarea
                                    wire:model="currentAnswer"
                                    rows="8"
                                    placeholder="Ihre Antwort..."
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-y"
                                ></textarea>
                        @endswitch
                    </div>

                    {{-- Navigation --}}
                    <div class="px-6 pb-6 flex items-center justify-between">
                        <button
                            wire:click="previousBlock"
                            wire:loading.attr="disabled"
                            @if($currentStep === 0) disabled @endif
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors
                                {{ $currentStep === 0
                                    ? 'text-gray-400 dark:text-gray-500 cursor-not-allowed'
                                    : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700'
                                }}"
                        >
                            <span wire:loading.remove wire:target="previousBlock">&larr; Zurueck</span>
                            <span wire:loading wire:target="previousBlock">Laden...</span>
                        </button>

                        <div class="flex items-center gap-2">
                            <button
                                wire:click="saveCurrentBlock"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                <span wire:loading.remove wire:target="saveCurrentBlock">Speichern</span>
                                <span wire:loading wire:target="saveCurrentBlock">Wird gespeichert...</span>
                            </button>

                            @if($currentStep < $totalBlocks - 1)
                                <button
                                    wire:click="nextBlock"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors"
                                >
                                    <span wire:loading.remove wire:target="nextBlock">Weiter &rarr;</span>
                                    <span wire:loading wire:target="nextBlock" class="inline-flex items-center gap-2">
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        Laden...
                                    </span>
                                </button>
                            @else
                                <button
                                    wire:click="submitIntake"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors"
                                >
                                    <span wire:loading.remove wire:target="submitIntake">Abschliessen</span>
                                    <span wire:loading wire:target="submitIntake" class="inline-flex items-center gap-2">
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        Wird abgeschlossen...
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="p-8 text-center">
                        <p class="text-gray-500 dark:text-gray-400">Keine Bloecke in dieser Erhebung konfiguriert.</p>
                    </div>
                @endif
            </div>
        </main>
    @endif
</div>
