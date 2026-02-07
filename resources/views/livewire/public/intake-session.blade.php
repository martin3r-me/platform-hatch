<div class="intake-wrap min-h-screen relative overflow-hidden">

    {{-- Animated Background --}}
    <div class="fixed inset-0 -z-10" aria-hidden="true">
        <div class="intake-bg"></div>
        <div class="intake-blob intake-blob-1"></div>
        <div class="intake-blob intake-blob-2"></div>
        <div class="intake-blob intake-blob-3"></div>
        <div class="intake-blob intake-blob-4"></div>
        <div class="intake-noise"></div>
    </div>

    @if($state === 'notFound')
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="intake-glass w-full max-w-md p-10 text-center">
                <div class="w-20 h-20 rounded-full bg-red-500/10 flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white mb-3">Session nicht gefunden</h1>
                <p class="text-white/60 text-lg">Diese Session ist ungueltig oder existiert nicht mehr.</p>
            </div>
        </div>

    @elseif(in_array($state, ['ready', 'completed']))
        @php $isReadOnly = ($state === 'completed'); @endphp

        {{-- Floating Header --}}
        <header class="sticky top-0 z-50">
            <div class="intake-header-glass">
                <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h1 class="text-base font-semibold text-white truncate">{{ $intakeName }}</h1>
                    </div>
                    <div class="flex items-center gap-4 flex-shrink-0 ml-4">
                        {{-- Token Badge --}}
                        <div
                            x-data="{ copied: false }"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-white/10 hover:bg-white/15 rounded-full cursor-pointer transition-colors"
                            x-on:click="navigator.clipboard.writeText('{{ $sessionToken }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            title="Token kopieren"
                        >
                            <span class="text-xs font-mono font-semibold text-white/90 tracking-widest">{{ $sessionToken }}</span>
                            <svg x-show="!copied" class="w-3.5 h-3.5 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>

                        @if($totalBlocks > 0)
                            <span class="text-sm font-medium text-white/50">
                                {{ $currentStep + 1 }}<span class="text-white/30">/</span>{{ $totalBlocks }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Progress Bar --}}
                @if($totalBlocks > 0)
                    <div class="h-0.5 bg-white/5">
                        <div
                            class="h-full transition-all duration-700 ease-out {{ $isReadOnly ? 'intake-progress-done' : 'intake-progress' }}"
                            style="width: {{ $isReadOnly ? 100 : ($totalBlocks > 0 ? (($currentStep) / $totalBlocks) * 100 : 0) }}%"
                        ></div>
                    </div>
                @endif
            </div>
        </header>

        {{-- Completed Banner --}}
        @if($isReadOnly)
            <div class="max-w-3xl mx-auto px-6 pt-6">
                <div class="intake-glass-subtle flex items-center gap-3 px-5 py-4">
                    <div class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <p class="text-sm text-white/70">
                        Diese Erhebung wurde abgeschlossen. Ihre Antworten werden unten angezeigt.
                    </p>
                </div>
            </div>
        @else
            <div class="max-w-3xl mx-auto px-6 pt-5">
                <p class="text-xs text-white/30 text-center tracking-wide">
                    Speichern Sie Ihren Token <span class="font-mono font-semibold text-white/50">{{ $sessionToken }}</span>, um spaeter fortzufahren.
                </p>
            </div>
        @endif

        {{-- Content --}}
        <main class="max-w-3xl mx-auto px-6 py-8">
            {{-- Block-Uebersicht --}}
            @if(count($blocks) > 0)
                <div class="mb-8">
                    <div class="flex flex-wrap gap-2">
                        @foreach($blocks as $index => $block)
                            @php
                                $isActive = $index === $currentStep;
                                $isPast = $isReadOnly ? true : ($index < $currentStep);
                            @endphp
                            <button
                                type="button"
                                wire:click="{{ $isReadOnly ? 'nextBlock' : '' }}"
                                class="flex items-center gap-2 px-3.5 py-2 rounded-full text-sm transition-all
                                    {{ $isActive
                                        ? 'bg-white/20 text-white ring-1 ring-white/30'
                                        : ($isPast
                                            ? 'bg-white/8 text-white/60'
                                            : 'bg-white/5 text-white/30')
                                    }}"
                            >
                                <span class="w-5 h-5 flex items-center justify-center rounded-full text-[10px] font-bold
                                    {{ $isActive
                                        ? 'bg-white text-gray-900'
                                        : ($isPast
                                            ? 'bg-white/20 text-white/80'
                                            : 'bg-white/10 text-white/30')
                                    }}">
                                    @if($isPast && !$isActive)
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </span>
                                <span class="hidden sm:inline">{{ $block['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Aktiver Block --}}
            <div class="intake-glass">
                @if(isset($blocks[$currentStep]))
                    @php
                        $block = $blocks[$currentStep];
                        $type = $block['type'];
                        $config = $block['logic_config'] ?? [];
                    @endphp

                    <div class="p-8 pb-6 border-b border-white/10">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <span class="text-sm font-bold text-white/80">{{ $currentStep + 1 }}</span>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white">
                                    {{ $block['name'] }}
                                    @if($block['is_required'] && !$isReadOnly)
                                        <span class="text-rose-400 ml-1">*</span>
                                    @endif
                                </h2>
                                @if($block['description'])
                                    <p class="mt-2 text-white/50 leading-relaxed">
                                        {{ $block['description'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="p-8">
                        @switch($type)
                            {{-- Text --}}
                            @case('text')
                                <input
                                    type="text"
                                    wire:model="currentAnswer"
                                    placeholder="{{ $config['placeholder'] ?? 'Ihre Antwort...' }}"
                                    @if(!empty($config['maxlength'])) maxlength="{{ $config['maxlength'] }}" @endif
                                    @if($isReadOnly) disabled @endif
                                    class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}"
                                >
                                @break

                            {{-- Long Text --}}
                            @case('long_text')
                                <textarea
                                    wire:model="currentAnswer"
                                    rows="{{ $config['rows'] ?? 6 }}"
                                    placeholder="{{ $config['placeholder'] ?? 'Ihre Antwort...' }}"
                                    @if(!empty($config['maxlength'])) maxlength="{{ $config['maxlength'] }}" @endif
                                    @if($isReadOnly) disabled @endif
                                    class="intake-input resize-y {{ $isReadOnly ? 'opacity-60' : '' }}"
                                ></textarea>
                                @break

                            {{-- Email --}}
                            @case('email')
                                <input
                                    type="email"
                                    wire:model="currentAnswer"
                                    placeholder="{{ $config['placeholder'] ?? 'name@beispiel.de' }}"
                                    @if($isReadOnly) disabled @endif
                                    class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}"
                                >
                                @break

                            {{-- Phone --}}
                            @case('phone')
                                <input
                                    type="tel"
                                    wire:model="currentAnswer"
                                    placeholder="{{ $config['placeholder'] ?? '+41 ...' }}"
                                    @if($isReadOnly) disabled @endif
                                    class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}"
                                >
                                @break

                            {{-- URL --}}
                            @case('url')
                                <input
                                    type="url"
                                    wire:model="currentAnswer"
                                    placeholder="{{ $config['placeholder'] ?? 'https://...' }}"
                                    @if($isReadOnly) disabled @endif
                                    class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}"
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
                                        @if($isReadOnly) disabled @endif
                                        class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}"
                                    >
                                    @if(!empty($config['unit']))
                                        <span class="text-sm font-medium text-white/40 flex-shrink-0">{{ $config['unit'] }}</span>
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
                                    @if($isReadOnly) disabled @endif
                                    class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}"
                                >
                                @break

                            {{-- Select (Single) --}}
                            @case('select')
                                <div class="space-y-2.5">
                                    @foreach(($config['options'] ?? []) as $option)
                                        @php
                                            $optionValue = is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option;
                                            $optionLabel = is_array($option) ? ($option['label'] ?? $option['value'] ?? '') : $option;
                                            $isChosen = $currentAnswer === $optionValue;
                                        @endphp
                                        <button
                                            type="button"
                                            @if(!$isReadOnly) wire:click="setAnswer('{{ $optionValue }}')" @endif
                                            @if($isReadOnly) disabled @endif
                                            class="intake-option-card {{ $isChosen ? 'intake-option-active' : '' }} {{ $isReadOnly ? 'cursor-default' : '' }}"
                                        >
                                            <span class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors
                                                {{ $isChosen ? 'border-white' : 'border-white/20' }}">
                                                @if($isChosen)
                                                    <span class="w-2 h-2 rounded-full bg-white"></span>
                                                @endif
                                            </span>
                                            <span class="{{ $isChosen ? 'text-white' : 'text-white/70' }}">{{ $optionLabel }}</span>
                                        </button>
                                    @endforeach
                                </div>
                                @break

                            {{-- Multi Select --}}
                            @case('multi_select')
                                <div class="space-y-2.5">
                                    @foreach(($config['options'] ?? []) as $option)
                                        @php
                                            $optionValue = is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option;
                                            $optionLabel = is_array($option) ? ($option['label'] ?? $option['value'] ?? '') : $option;
                                            $isSelected = in_array($optionValue, $selectedOptions);
                                        @endphp
                                        <button
                                            type="button"
                                            @if(!$isReadOnly) wire:click="toggleOption('{{ $optionValue }}')" @endif
                                            @if($isReadOnly) disabled @endif
                                            class="intake-option-card {{ $isSelected ? 'intake-option-active' : '' }} {{ $isReadOnly ? 'cursor-default' : '' }}"
                                        >
                                            <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0 border-2 transition-colors
                                                {{ $isSelected ? 'border-white bg-white' : 'border-white/20' }}">
                                                @if($isSelected)
                                                    <svg class="w-3 h-3 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                @endif
                                            </span>
                                            <span class="{{ $isSelected ? 'text-white' : 'text-white/70' }}">{{ $optionLabel }}</span>
                                        </button>
                                    @endforeach
                                </div>
                                @break

                            {{-- Boolean --}}
                            @case('boolean')
                                @php
                                    $trueLabel = $config['true_label'] ?? 'Ja';
                                    $falseLabel = $config['false_label'] ?? 'Nein';
                                @endphp
                                <div class="grid grid-cols-2 gap-4">
                                    <button
                                        type="button"
                                        @if(!$isReadOnly) wire:click="setAnswer('true')" @endif
                                        @if($isReadOnly) disabled @endif
                                        class="intake-bool-card {{ $currentAnswer === 'true' ? 'intake-option-active' : '' }} {{ $isReadOnly ? 'cursor-default' : '' }}"
                                    >
                                        <svg class="w-10 h-10 {{ $currentAnswer === 'true' ? 'text-emerald-400' : 'text-white/20' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="text-lg font-semibold {{ $currentAnswer === 'true' ? 'text-white' : 'text-white/50' }}">{{ $trueLabel }}</span>
                                    </button>
                                    <button
                                        type="button"
                                        @if(!$isReadOnly) wire:click="setAnswer('false')" @endif
                                        @if($isReadOnly) disabled @endif
                                        class="intake-bool-card {{ $currentAnswer === 'false' ? 'intake-option-active' : '' }} {{ $isReadOnly ? 'cursor-default' : '' }}"
                                    >
                                        <svg class="w-10 h-10 {{ $currentAnswer === 'false' ? 'text-rose-400' : 'text-white/20' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        <span class="text-lg font-semibold {{ $currentAnswer === 'false' ? 'text-white' : 'text-white/50' }}">{{ $falseLabel }}</span>
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
                                        <div class="flex justify-between mb-4 text-sm text-white/40">
                                            <span>{{ $minLabel }}</span>
                                            <span>{{ $maxLabel }}</span>
                                        </div>
                                    @endif
                                    <div class="flex flex-wrap gap-2.5 justify-center">
                                        @for($i = $scaleMin; $i <= $scaleMax; $i++)
                                            <button
                                                type="button"
                                                @if(!$isReadOnly) wire:click="setAnswer('{{ $i }}')" @endif
                                                @if($isReadOnly) disabled @endif
                                                class="w-12 h-12 rounded-xl font-bold text-lg transition-all
                                                    {{ $currentAnswer === (string)$i
                                                        ? 'bg-white text-gray-900 shadow-lg shadow-white/20'
                                                        : 'bg-white/8 text-white/60'
                                                    }}
                                                    {{ $isReadOnly ? 'cursor-default' : 'hover:bg-white/15' }}"
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
                                <div class="flex gap-3 justify-center py-4">
                                    @for($i = 1; $i <= $maxStars; $i++)
                                        <button
                                            type="button"
                                            @if(!$isReadOnly) wire:click="setAnswer('{{ $i }}')" @endif
                                            @if($isReadOnly) disabled @endif
                                            class="{{ $isReadOnly ? 'cursor-default' : 'transition-transform hover:scale-125' }}"
                                        >
                                            <svg class="w-12 h-12 transition-colors {{ $i <= $currentRating ? 'text-amber-400 fill-amber-400 drop-shadow-[0_0_8px_rgba(251,191,36,0.4)]' : 'text-white/15' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.5">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                        </button>
                                    @endfor
                                </div>
                                @break

                            {{-- File (Placeholder) --}}
                            @case('file')
                                <div class="flex flex-col items-center justify-center p-10 border-2 border-dashed border-white/10 rounded-xl">
                                    <svg class="w-12 h-12 text-white/20 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <p class="text-sm text-white/30">Datei-Upload wird in einer spaeteren Version unterstuetzt.</p>
                                </div>
                                @break

                            {{-- Location --}}
                            @case('location')
                                <input
                                    type="text"
                                    wire:model="currentAnswer"
                                    placeholder="{{ $config['placeholder'] ?? 'Standort eingeben...' }}"
                                    @if($isReadOnly) disabled @endif
                                    class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}"
                                >
                                @break

                            {{-- Default --}}
                            @default
                                <textarea
                                    wire:model="currentAnswer"
                                    rows="6"
                                    placeholder="Ihre Antwort..."
                                    @if($isReadOnly) disabled @endif
                                    class="intake-input resize-y {{ $isReadOnly ? 'opacity-60' : '' }}"
                                ></textarea>
                        @endswitch
                    </div>

                    {{-- Navigation --}}
                    <div class="px-8 pb-8 flex items-center justify-between">
                        <button
                            wire:click="previousBlock"
                            wire:loading.attr="disabled"
                            @if($currentStep === 0) disabled @endif
                            class="px-5 py-2.5 text-sm font-medium rounded-xl transition-all
                                {{ $currentStep === 0
                                    ? 'text-white/15 cursor-not-allowed'
                                    : 'text-white/60 hover:text-white hover:bg-white/10'
                                }}"
                        >
                            <span wire:loading.remove wire:target="previousBlock">&larr; Zurueck</span>
                            <span wire:loading wire:target="previousBlock">...</span>
                        </button>

                        <div class="flex items-center gap-3">
                            @if(!$isReadOnly)
                                <button
                                    wire:click="saveCurrentBlock"
                                    wire:loading.attr="disabled"
                                    class="px-5 py-2.5 text-sm font-medium text-white/50 hover:text-white hover:bg-white/10 rounded-xl transition-all"
                                >
                                    <span wire:loading.remove wire:target="saveCurrentBlock">Speichern</span>
                                    <span wire:loading wire:target="saveCurrentBlock">Wird gespeichert...</span>
                                </button>
                            @endif

                            @if($currentStep < $totalBlocks - 1)
                                <button
                                    wire:click="nextBlock"
                                    wire:loading.attr="disabled"
                                    class="intake-btn-primary"
                                >
                                    <span wire:loading.remove wire:target="nextBlock">Weiter &rarr;</span>
                                    <span wire:loading wire:target="nextBlock" class="inline-flex items-center gap-2">
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </span>
                                </button>
                            @elseif(!$isReadOnly)
                                <button
                                    wire:click="submitIntake"
                                    wire:loading.attr="disabled"
                                    class="intake-btn-submit"
                                >
                                    <span wire:loading.remove wire:target="submitIntake">Abschliessen</span>
                                    <span wire:loading wire:target="submitIntake" class="inline-flex items-center gap-2">
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="p-12 text-center">
                        <p class="text-white/30">Keine Bloecke in dieser Erhebung konfiguriert.</p>
                    </div>
                @endif
            </div>
        </main>

        {{-- Footer --}}
        <footer class="max-w-3xl mx-auto px-6 pb-8 text-center">
            <p class="text-[11px] text-white/15 tracking-wider uppercase">Powered by Hatch</p>
        </footer>
    @endif
</div>

<style>
    /* ═══════════════════════════════════════════
       WeTransfer-inspired Intake Session Styles
       ═══════════════════════════════════════════ */

    .intake-wrap {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    /* ── Background ── */
    .intake-bg {
        position: fixed;
        inset: 0;
        background: #0f0a1a;
        z-index: -10;
    }

    .intake-blob {
        position: fixed;
        border-radius: 50%;
        filter: blur(120px);
        opacity: 0.6;
        z-index: -5;
        will-change: transform;
    }

    .intake-blob-1 {
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, #7c3aed 0%, #6d28d9 40%, transparent 70%);
        top: -15%;
        left: -10%;
        animation: intake-drift-1 20s ease-in-out infinite;
    }

    .intake-blob-2 {
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, #ec4899 0%, #db2777 40%, transparent 70%);
        bottom: -10%;
        right: -8%;
        animation: intake-drift-2 25s ease-in-out infinite;
    }

    .intake-blob-3 {
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, #06b6d4 0%, #0891b2 40%, transparent 70%);
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        animation: intake-drift-3 18s ease-in-out infinite;
    }

    .intake-blob-4 {
        width: 350px;
        height: 350px;
        background: radial-gradient(circle, #f59e0b 0%, #d97706 40%, transparent 70%);
        top: 10%;
        right: 15%;
        opacity: 0.3;
        animation: intake-drift-4 22s ease-in-out infinite;
    }

    .intake-noise {
        position: fixed;
        inset: 0;
        z-index: -4;
        opacity: 0.03;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        background-repeat: repeat;
        background-size: 256px 256px;
    }

    @keyframes intake-drift-1 {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(60px, 40px) scale(1.1); }
        66% { transform: translate(-30px, 70px) scale(0.95); }
    }

    @keyframes intake-drift-2 {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(-50px, -40px) scale(1.15); }
        66% { transform: translate(40px, -60px) scale(0.9); }
    }

    @keyframes intake-drift-3 {
        0%, 100% { transform: translate(-50%, -50%) scale(1); }
        33% { transform: translate(-40%, -60%) scale(1.2); }
        66% { transform: translate(-60%, -40%) scale(0.85); }
    }

    @keyframes intake-drift-4 {
        0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
        50% { transform: translate(-40px, 50px) scale(1.1) rotate(10deg); }
    }

    /* ── Glass Effects ── */
    .intake-glass {
        background: rgba(255, 255, 255, 0.06);
        backdrop-filter: blur(40px);
        -webkit-backdrop-filter: blur(40px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.05) inset,
            0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .intake-glass-subtle {
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
    }

    .intake-header-glass {
        background: rgba(15, 10, 26, 0.6);
        backdrop-filter: blur(30px);
        -webkit-backdrop-filter: blur(30px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    /* ── Progress Bars ── */
    .intake-progress {
        background: linear-gradient(90deg, #7c3aed, #a855f7, #ec4899);
        box-shadow: 0 0 12px rgba(124, 58, 237, 0.5);
    }

    .intake-progress-done {
        background: linear-gradient(90deg, #10b981, #34d399, #6ee7b7);
        box-shadow: 0 0 12px rgba(16, 185, 129, 0.5);
    }

    /* ── Form Inputs ── */
    .intake-input {
        width: 100%;
        padding: 14px 18px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 14px;
        color: white;
        font-size: 15px;
        outline: none;
        transition: all 0.2s ease;
    }

    .intake-input::placeholder {
        color: rgba(255, 255, 255, 0.25);
    }

    .intake-input:focus {
        border-color: rgba(124, 58, 237, 0.5);
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15), 0 0 20px rgba(124, 58, 237, 0.1);
        background: rgba(255, 255, 255, 0.08);
    }

    .intake-input:disabled {
        cursor: not-allowed;
    }

    /* Date input icon color fix */
    .intake-input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1) opacity(0.4);
    }

    /* ── Option Cards ── */
    .intake-option-card {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 18px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.03);
        text-align: left;
        transition: all 0.2s ease;
    }

    .intake-option-card:not(:disabled):hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.15);
    }

    .intake-option-active {
        background: rgba(124, 58, 237, 0.15) !important;
        border-color: rgba(124, 58, 237, 0.4) !important;
        box-shadow: 0 0 20px rgba(124, 58, 237, 0.1);
    }

    /* ── Boolean Cards ── */
    .intake-bool-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 32px 24px;
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.03);
        transition: all 0.2s ease;
    }

    .intake-bool-card:not(:disabled):hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.15);
    }

    /* ── Buttons ── */
    .intake-btn-primary {
        padding: 10px 24px;
        background: rgba(124, 58, 237, 0.8);
        color: white;
        font-size: 14px;
        font-weight: 600;
        border-radius: 14px;
        transition: all 0.2s ease;
        backdrop-filter: blur(10px);
    }

    .intake-btn-primary:hover {
        background: rgba(124, 58, 237, 1);
        box-shadow: 0 0 30px rgba(124, 58, 237, 0.3);
    }

    .intake-btn-primary:disabled {
        opacity: 0.5;
    }

    .intake-btn-submit {
        padding: 10px 24px;
        background: rgba(16, 185, 129, 0.8);
        color: white;
        font-size: 14px;
        font-weight: 600;
        border-radius: 14px;
        transition: all 0.2s ease;
        backdrop-filter: blur(10px);
    }

    .intake-btn-submit:hover {
        background: rgba(16, 185, 129, 1);
        box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
    }

    .intake-btn-submit:disabled {
        opacity: 0.5;
    }

    /* ── Responsive ── */
    @media (max-width: 640px) {
        .intake-blob-1 { width: 350px; height: 350px; }
        .intake-blob-2 { width: 300px; height: 300px; }
        .intake-blob-3 { width: 250px; height: 250px; }
        .intake-blob-4 { display: none; }
    }
</style>
