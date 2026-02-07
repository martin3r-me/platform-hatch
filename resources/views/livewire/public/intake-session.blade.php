<div class="intake-wrap min-h-screen relative overflow-hidden">

    {{-- Background Image --}}
    @php
        $bgFiles = glob(public_path('images/bg-images/*.{jpeg,jpg,png,webp}'), GLOB_BRACE);
        $bgImage = !empty($bgFiles) ? basename($bgFiles[array_rand($bgFiles)]) : null;
    @endphp
    <div class="fixed inset-0 -z-10" aria-hidden="true">
        <div class="intake-bg"></div>
        @if($bgImage)
            <img src="{{ asset('images/bg-images/' . $bgImage) }}"
                 class="absolute inset-0 w-full h-full object-cover"
                 alt="" loading="eager">
        @endif
        <div class="absolute inset-0 bg-gradient-to-br from-black/50 via-black/30 to-black/50"></div>
        <div class="absolute inset-0 backdrop-blur-[2px]"></div>
    </div>

    @if($state === 'notFound')
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="intake-card w-full max-w-md p-10 text-center">
                <div class="w-20 h-20 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-3">Session nicht gefunden</h1>
                <p class="text-gray-500 text-lg mb-6">Diese Session ist ungueltig oder existiert nicht mehr.</p>
                <a href="/"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/>
                    </svg>
                    Zur Startseite
                </a>
            </div>
        </div>

    @elseif(in_array($state, ['ready', 'completed']))
        @php $isReadOnly = ($state === 'completed'); @endphp

        {{-- Floating Header --}}
        <header class="sticky top-0 z-50">
            <div class="intake-header-glass">
                <div class="max-w-3xl lg:max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
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

        {{-- Status Banner --}}
        @if($isReadOnly)
            <div class="max-w-3xl lg:max-w-6xl mx-auto px-6 pt-6">
                <div class="intake-card flex items-center gap-3 px-5 py-4">
                    <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="text-sm text-gray-600">
                        @if($respondentName)
                            <p class="font-medium text-gray-800">Hallo {{ $respondentName }}!</p>
                            <p>Vielen Dank fuer Ihre Teilnahme. Diese Erhebung wurde abgeschlossen &ndash; Ihre Antworten werden unten angezeigt.</p>
                        @else
                            <p>Diese Erhebung wurde abgeschlossen. Ihre Antworten werden unten angezeigt.</p>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="max-w-3xl lg:max-w-6xl mx-auto px-6 pt-5">
                @if($respondentName)
                    <div class="intake-card flex items-center gap-3 px-5 py-4 mb-3">
                        <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium text-gray-800">Hallo {{ $respondentName }}</span> &ndash; schoen, dass Sie da sind!
                        </p>
                    </div>
                @endif
                <p class="text-xs text-white/30 text-center tracking-wide">
                    Speichern Sie Ihren Token <span class="font-mono font-semibold text-white/50">{{ $sessionToken }}</span>, um spaeter fortzufahren.
                </p>
            </div>
        @endif

        {{-- Content --}}
        <main class="max-w-3xl lg:max-w-6xl mx-auto px-6 py-8">
            @php
                $windowStart = max(0, $currentStep - 5);
                $windowEnd = min(count($blocks) - 1, $currentStep + 5);
                $windowBlocks = array_slice($blocks, $windowStart, $windowEnd - $windowStart + 1, true);
            @endphp

            <div class="intake-layout">
                {{-- Sidebar: Block-Uebersicht (Pills) --}}
                @if(count($blocks) > 0)
                    <aside class="intake-sidebar">
                        <div class="intake-sidebar-inner">
                            <div class="intake-pills">
                                {{-- Leading indicator --}}
                                @if($windowStart > 0)
                                    <button
                                        type="button"
                                        wire:click="goToBlock(0)"
                                        class="intake-pill intake-pill-indicator"
                                    >
                                        <span class="font-bold">1</span>
                                        <span class="text-white/25">...</span>
                                    </button>
                                @endif

                                @foreach($windowBlocks as $index => $block)
                                    @php
                                        $isActive = $index === $currentStep;
                                        $isPast = $isReadOnly ? true : ($index < $currentStep);
                                    @endphp
                                    <button
                                        type="button"
                                        wire:click="goToBlock({{ $index }})"
                                        class="intake-pill transition-all cursor-pointer
                                            {{ $isActive
                                                ? 'bg-white/20 text-white ring-1 ring-white/30'
                                                : ($isPast
                                                    ? 'bg-white/8 text-white/60 hover:bg-white/15'
                                                    : 'bg-white/5 text-white/30 hover:bg-white/10')
                                            }}"
                                    >
                                        <span class="w-5 h-5 flex items-center justify-center rounded-full text-[10px] font-bold flex-shrink-0
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
                                        <span class="intake-pill-name truncate">{{ $block['name'] }}</span>
                                    </button>
                                @endforeach

                                {{-- Trailing indicator --}}
                                @if($windowEnd < count($blocks) - 1)
                                    <button
                                        type="button"
                                        wire:click="goToBlock({{ count($blocks) - 1 }})"
                                        class="intake-pill intake-pill-indicator"
                                    >
                                        <span class="text-white/25">...</span>
                                        <span class="font-bold">{{ count($blocks) }}</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </aside>
                @endif

                {{-- Content --}}
                <div class="intake-content">
                    <div class="intake-card">
                        @if(isset($blocks[$currentStep]))
                            @php
                                $block = $blocks[$currentStep];
                                $type = $block['type'];
                                $config = $block['logic_config'] ?? [];
                            @endphp

                            <div class="p-8 pb-6 border-b border-gray-100">
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span class="text-sm font-bold text-gray-600">{{ $currentStep + 1 }}</span>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-gray-900">
                                            {{ $block['name'] }}
                                            @if($block['is_required'] && !$isReadOnly)
                                                <span class="text-rose-500 ml-1">*</span>
                                            @endif
                                        </h2>
                                        @if($block['description'])
                                            <p class="mt-2 text-gray-500 leading-relaxed">
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
                                                <span class="text-sm font-medium text-gray-400 flex-shrink-0">{{ $config['unit'] }}</span>
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
                                                        {{ $isChosen ? 'border-violet-600' : 'border-gray-300' }}">
                                                        @if($isChosen)
                                                            <span class="w-2 h-2 rounded-full bg-violet-600"></span>
                                                        @endif
                                                    </span>
                                                    <span class="{{ $isChosen ? 'text-gray-900' : 'text-gray-600' }}">{{ $optionLabel }}</span>
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
                                                        {{ $isSelected ? 'border-violet-600 bg-violet-600' : 'border-gray-300' }}">
                                                        @if($isSelected)
                                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                        @endif
                                                    </span>
                                                    <span class="{{ $isSelected ? 'text-gray-900' : 'text-gray-600' }}">{{ $optionLabel }}</span>
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
                                                <svg class="w-10 h-10 {{ $currentAnswer === 'true' ? 'text-emerald-500' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span class="text-lg font-semibold {{ $currentAnswer === 'true' ? 'text-gray-900' : 'text-gray-400' }}">{{ $trueLabel }}</span>
                                            </button>
                                            <button
                                                type="button"
                                                @if(!$isReadOnly) wire:click="setAnswer('false')" @endif
                                                @if($isReadOnly) disabled @endif
                                                class="intake-bool-card {{ $currentAnswer === 'false' ? 'intake-option-active' : '' }} {{ $isReadOnly ? 'cursor-default' : '' }}"
                                            >
                                                <svg class="w-10 h-10 {{ $currentAnswer === 'false' ? 'text-rose-500' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                <span class="text-lg font-semibold {{ $currentAnswer === 'false' ? 'text-gray-900' : 'text-gray-400' }}">{{ $falseLabel }}</span>
                                            </button>
                                        </div>
                                        @break

                                    {{-- Scale --}}
                                    @case('scale')
                                        @php
                                            $scaleMin = $config['min'] ?? 1;
                                            $scaleMax = $config['max'] ?? 5;
                                            $minLabel = $config['min_label'] ?? '';
                                            $maxLabel = $config['max_label'] ?? '';
                                        @endphp
                                        <div>
                                            @if($minLabel || $maxLabel)
                                                <div class="flex justify-between mb-4 text-sm text-gray-400">
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
                                                                ? 'bg-violet-600 text-white shadow-lg shadow-violet-200'
                                                                : 'bg-gray-100 text-gray-500'
                                                            }}
                                                            {{ $isReadOnly ? 'cursor-default' : 'hover:bg-gray-200' }}"
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
                                                    <svg class="w-12 h-12 transition-colors {{ $i <= $currentRating ? 'text-amber-400 fill-amber-400 drop-shadow-[0_0_8px_rgba(251,191,36,0.4)]' : 'text-gray-200' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.5">
                                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                    </svg>
                                                </button>
                                            @endfor
                                        </div>
                                        @break

                                    {{-- File (Placeholder) --}}
                                    @case('file')
                                        <div class="flex flex-col items-center justify-center p-10 border-2 border-dashed border-gray-200 rounded-xl">
                                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                            </svg>
                                            <p class="text-sm text-gray-400">Datei-Upload wird in einer spaeteren Version unterstuetzt.</p>
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
                                            ? 'text-gray-300 cursor-not-allowed'
                                            : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100'
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
                                            class="px-5 py-2.5 text-sm font-medium text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-all"
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
                                <p class="text-gray-400">Keine Bloecke in dieser Erhebung konfiguriert.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="max-w-3xl lg:max-w-6xl mx-auto px-6 pb-8 text-center">
            <p class="text-[11px] text-white/20 tracking-wider uppercase">Powered by Hatch</p>
        </footer>
    @endif
</div>

<style>
    /* ═══════════════════════════════════════════
       Intake Session Styles — White Card Design
       ═══════════════════════════════════════════ */

    .intake-wrap {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    /* ── Background ── */
    .intake-bg {
        position: fixed;
        inset: 0;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        z-index: -10;
    }

    /* ── White Content Card ── */
    .intake-card {
        background: white;
        border-radius: 24px;
        border: 1px solid rgba(0, 0, 0, 0.06);
        box-shadow:
            0 4px 6px -1px rgba(0, 0, 0, 0.05),
            0 25px 50px -12px rgba(0, 0, 0, 0.15);
    }

    /* ── Glass Effects (Header, Banner, Pills — stay on image) ── */
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

    /* ── Form Inputs (inside white card) ── */
    .intake-input {
        width: 100%;
        padding: 14px 18px;
        background: white;
        border: 1px solid #d1d5db;
        border-radius: 14px;
        color: #111827;
        font-size: 15px;
        outline: none;
        transition: all 0.2s ease;
    }

    .intake-input::placeholder {
        color: #9ca3af;
    }

    .intake-input:focus {
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        background: white;
    }

    .intake-input:disabled {
        cursor: not-allowed;
        background: #f9fafb;
    }

    /* Date input icon color fix for white bg */
    .intake-input[type="date"]::-webkit-calendar-picker-indicator {
        filter: none;
    }

    /* ── Option Cards (inside white card) ── */
    .intake-option-card {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 18px;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        background: white;
        text-align: left;
        transition: all 0.2s ease;
    }

    .intake-option-card:not(:disabled):hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    .intake-option-active {
        background: rgba(124, 58, 237, 0.05) !important;
        border-color: rgba(124, 58, 237, 0.4) !important;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.08);
    }

    /* ── Boolean Cards (inside white card) ── */
    .intake-bool-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 32px 24px;
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        background: white;
        transition: all 0.2s ease;
    }

    .intake-bool-card:not(:disabled):hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    /* ── Buttons ── */
    .intake-btn-primary {
        padding: 10px 24px;
        background: #7c3aed;
        color: white;
        font-size: 14px;
        font-weight: 600;
        border-radius: 14px;
        transition: all 0.2s ease;
    }

    .intake-btn-primary:hover {
        background: #6d28d9;
        box-shadow: 0 4px 14px rgba(124, 58, 237, 0.3);
    }

    .intake-btn-primary:disabled {
        opacity: 0.5;
    }

    .intake-btn-submit {
        padding: 10px 24px;
        background: #10b981;
        color: white;
        font-size: 14px;
        font-weight: 600;
        border-radius: 14px;
        transition: all 0.2s ease;
    }

    .intake-btn-submit:hover {
        background: #059669;
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
    }

    .intake-btn-submit:disabled {
        opacity: 0.5;
    }

    /* ── Two-Column Layout ── */
    .intake-layout {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .intake-content {
        min-width: 0;
        flex: 1;
    }

    @media (min-width: 1024px) {
        .intake-layout {
            flex-direction: row;
            gap: 2rem;
        }

        .intake-sidebar {
            width: 260px;
            flex-shrink: 0;
        }

        .intake-sidebar-inner {
            position: sticky;
            top: 5.5rem;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
            padding: 10px;
        }
    }

    /* ── Pills ── */
    .intake-pills {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        gap: 0.5rem;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .intake-pills::-webkit-scrollbar {
        display: none;
    }

    .intake-pill {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.875rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        flex-shrink: 0;
    }

    .intake-pill-indicator {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.4);
        background: rgba(255, 255, 255, 0.05);
    }

    .intake-pill-indicator:hover {
        color: rgba(255, 255, 255, 0.6);
        background: rgba(255, 255, 255, 0.1);
    }

    .intake-pill-name {
        display: none;
    }

    @media (min-width: 640px) {
        .intake-pill-name {
            display: inline;
        }
    }

    @media (min-width: 1024px) {
        .intake-pills {
            flex-direction: column;
            overflow-x: visible;
            overflow-y: auto;
            max-height: calc(100vh - 8rem);
        }

        .intake-pill {
            flex-shrink: initial;
            width: 100%;
            border-radius: 12px;
        }

        .intake-pill-name {
            display: inline;
        }
    }
</style>
