<div class="intake-wrap relative overflow-hidden">

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
        <div class="absolute inset-0 backdrop-blur-[6px]"></div>
    </div>

    @if($state === 'notFound')
        <div class="flex items-center justify-center intake-fullscreen p-4">
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

    @elseif($state === 'notActive')
        <div class="flex items-center justify-center intake-fullscreen p-4">
            <div class="intake-card w-full max-w-md p-10 text-center">
                <div class="w-20 h-20 rounded-full bg-amber-50 flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-3">Erhebung nicht verfuegbar</h1>
                <p class="text-gray-500 text-lg mb-4">Diese Erhebung ist derzeit nicht verfuegbar. Bitte versuchen Sie es spaeter erneut.</p>
                <p class="text-sm text-gray-400">Ihr Token bleibt gueltig &ndash; Sie koennen spaeter fortfahren.</p>
            </div>
        </div>

    @elseif($state === 'notStarted')
        <div class="flex items-center justify-center intake-fullscreen p-4">
            <div class="intake-card w-full max-w-md p-10 text-center">
                <div class="w-20 h-20 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-3">Erhebung noch nicht gestartet</h1>
                <p class="text-gray-500 text-lg mb-4">Diese Erhebung wurde noch nicht gestartet. Bitte versuchen Sie es spaeter erneut.</p>
                <p class="text-sm text-gray-400">Ihr Token bleibt gueltig &ndash; Sie koennen spaeter fortfahren.</p>
            </div>
        </div>

    @elseif(in_array($state, ['ready', 'completed']))
        @php $isReadOnly = ($state === 'completed'); @endphp

        <div class="intake-shell">
        {{-- Header --}}
        <header class="intake-shell-header z-50">
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

        {{-- Content --}}
        <main class="intake-shell-main">

            {{-- Status Banner --}}
            @if($isReadOnly)
                <div class="mb-6">
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
                <div class="mb-6">
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
                    @if($validationError)
                        <div class="mt-3 intake-card flex items-center gap-3 px-5 py-4">
                            <div class="w-8 h-8 rounded-full bg-rose-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-rose-700">{{ $validationError }}</p>
                        </div>
                    @endif
                </div>
            @endif
            @php
                // Determine answer status for each block
                $answers = $session->answers ?? [];
                $answeredCount = 0;
                $answerableCount = 0;
                $blockStatus = []; // 'answered', 'missing', 'open', 'display'
                foreach ($blocks as $idx => $blk) {
                    $key = "block_{$blk['id']}";
                    $raw = $answers[$key] ?? '';
                    $isDisplay = in_array($blk['type'], ['info', 'section', 'calculated', 'hidden']);

                    if ($isDisplay) {
                        $blockStatus[$idx] = 'display';
                        continue;
                    }

                    $answerableCount++;
                    $hasAnswer = false;
                    if (is_string($raw) && $raw !== '' && $raw !== '[]' && $raw !== '{}') {
                        $hasAnswer = true;
                    }
                    if ($hasAnswer) {
                        $answeredCount++;
                        $blockStatus[$idx] = 'answered';
                    } elseif (!$isReadOnly && in_array($idx, $missingRequiredBlocks)) {
                        $blockStatus[$idx] = 'missing';
                    } else {
                        $blockStatus[$idx] = 'open';
                    }
                }
                $progressPct = $answerableCount > 0 ? round(($answeredCount / $answerableCount) * 100) : 0;
            @endphp

            <div class="intake-layout">
                {{-- Sidebar: Block-Uebersicht --}}
                @if(count($blocks) > 0)
                    <aside class="intake-sidebar">
                        <div class="intake-sidebar-inner" x-data="{
                            scrollToActive() {
                                this.$nextTick(() => {
                                    const el = this.$refs.pillList?.querySelector('[data-active]');
                                    if (el) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                                });
                            }
                        }" x-init="scrollToActive()" x-effect="scrollToActive()">
                            {{-- Progress header --}}
                            <div class="intake-sidebar-progress">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-[11px] font-semibold text-white/50 uppercase tracking-wider">Fortschritt</span>
                                    <span class="text-[11px] font-bold text-white/70">{{ $answeredCount }}/{{ $answerableCount }}</span>
                                </div>
                                <div class="h-1 rounded-full bg-white/10 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $progressPct === 100 ? 'intake-progress-done' : 'intake-progress' }}" style="width: {{ $progressPct }}%"></div>
                                </div>
                            </div>

                            {{-- Block list --}}
                            <div class="intake-pills" x-ref="pillList">
                                @foreach($blocks as $index => $block)
                                    @php
                                        $isActive = $index === $currentStep;
                                        $status = $blockStatus[$index] ?? 'open';
                                        $isAnswered = $status === 'answered' || $status === 'display';
                                        $isMissing = $status === 'missing';
                                    @endphp
                                    <button
                                        type="button"
                                        wire:click="goToBlock({{ $index }})"
                                        @if($isActive) data-active @endif
                                        @if(!empty($block['description'])) title="{{ $block['description'] }}" @endif
                                        class="intake-pill transition-all cursor-pointer
                                            {{ $isActive
                                                ? 'bg-white/20 text-white ring-1 ring-white/30'
                                                : ($isMissing
                                                    ? 'bg-white/[0.06] text-white/60 hover:bg-white/[0.12] ring-1 ring-rose-400/60'
                                                    : ($isAnswered
                                                        ? 'bg-white/[0.06] text-white/60 hover:bg-white/[0.12]'
                                                        : 'bg-white/[0.03] text-white/30 hover:bg-white/[0.08]'))
                                            }}"
                                    >
                                        {{-- Status icon --}}
                                        <span class="w-5 h-5 flex items-center justify-center rounded-full text-[10px] font-bold flex-shrink-0
                                            {{ $isActive
                                                ? 'bg-white text-gray-900'
                                                : ($isMissing
                                                    ? 'bg-rose-500 text-white'
                                                    : ($isAnswered
                                                        ? 'bg-emerald-500/80 text-white'
                                                        : 'bg-white/10 text-white/40'))
                                            }}">
                                            @if($isActive)
                                                {{ $index + 1 }}
                                            @elseif($isMissing)
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01"/></svg>
                                            @elseif($isAnswered)
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </span>
                                        <span class="intake-pill-text">
                                            <span class="intake-pill-name truncate block">{{ $block['name'] }}</span>
                                            @if(!empty($block['description']))
                                                <span class="intake-pill-desc truncate block">{{ $block['description'] }}</span>
                                            @endif
                                        </span>
                                    </button>
                                @endforeach
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
                                            <div class="mt-3 flex items-start gap-2.5 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <p class="text-sm text-gray-500 leading-relaxed">{{ $block['description'] }}</p>
                                            </div>
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

                                    {{-- Info (read-only) --}}
                                    @case('info')
                                        @if(!empty($config['content']))
                                            <div class="flex gap-3 p-5 bg-blue-50 border border-blue-100 rounded-xl">
                                                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <div class="text-sm text-blue-900 leading-relaxed whitespace-pre-line">{{ $config['content'] }}</div>
                                            </div>
                                        @endif
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

                                    {{-- Matrix / Likert --}}
                                    @case('matrix')
                                        @php
                                            $matrixItems = $config['items'] ?? [];
                                            $scaleMin = (int)($config['scale_min'] ?? 1);
                                            $scaleMax = (int)($config['scale_max'] ?? 5);
                                            $scaleLabels = $config['scale_labels'] ?? [];
                                        @endphp
                                        <div class="overflow-x-auto">
                                            @if(!empty($scaleLabels['min_label']) || !empty($scaleLabels['max_label']))
                                                <div class="flex justify-between mb-2 text-xs text-gray-400 px-1">
                                                    <span>{{ $scaleLabels['min_label'] ?? '' }}</span>
                                                    <span>{{ $scaleLabels['max_label'] ?? '' }}</span>
                                                </div>
                                            @endif
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr>
                                                        <th class="text-left p-2 text-gray-500 font-medium"></th>
                                                        @for($s = $scaleMin; $s <= $scaleMax; $s++)
                                                            <th class="p-2 text-center text-gray-500 font-medium">{{ $s }}</th>
                                                        @endfor
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($matrixItems as $item)
                                                        @php
                                                            $itemValue = is_array($item) ? ($item['value'] ?? $item['label'] ?? '') : $item;
                                                            $itemLabel = is_array($item) ? ($item['label'] ?? $item['value'] ?? '') : $item;
                                                        @endphp
                                                        <tr class="border-t border-gray-100">
                                                            <td class="p-3 text-gray-700">{{ $itemLabel }}</td>
                                                            @for($s = $scaleMin; $s <= $scaleMax; $s++)
                                                                <td class="p-2 text-center">
                                                                    <button
                                                                        type="button"
                                                                        @if(!$isReadOnly) wire:click="setMatrixAnswer('{{ $itemValue }}', '{{ $s }}')" @endif
                                                                        @if($isReadOnly) disabled @endif
                                                                        class="w-8 h-8 rounded-full border-2 transition-all {{ ($matrixAnswers[$itemValue] ?? '') === (string)$s ? 'bg-violet-600 border-violet-600 text-white' : 'border-gray-300 hover:border-violet-400' }}"
                                                                    >
                                                                        @if(($matrixAnswers[$itemValue] ?? '') === (string)$s)
                                                                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                        @endif
                                                                    </button>
                                                                </td>
                                                            @endfor
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @break

                                    {{-- Ranking --}}
                                    @case('ranking')
                                        @php
                                            $rankOptions = $config['options'] ?? [];
                                            $rankMap = collect($rankOptions)->keyBy(fn($o) => is_array($o) ? ($o['value'] ?? '') : $o);
                                        @endphp
                                        <div
                                            x-data="{
                                                items: @js($rankingOrder),
                                                dragIndex: null,
                                                startDrag(index) { this.dragIndex = index; },
                                                onDrop(targetIndex) {
                                                    if (this.dragIndex === null || this.dragIndex === targetIndex) return;
                                                    $wire.moveRankingItem(this.dragIndex, targetIndex);
                                                    const item = this.items.splice(this.dragIndex, 1)[0];
                                                    this.items.splice(targetIndex, 0, item);
                                                    this.dragIndex = null;
                                                }
                                            }"
                                            class="space-y-2"
                                        >
                                            <template x-for="(val, idx) in items" :key="val + '-' + idx">
                                                <div
                                                    draggable="{{ $isReadOnly ? 'false' : 'true' }}"
                                                    x-on:dragstart="startDrag(idx)"
                                                    x-on:dragover.prevent
                                                    x-on:drop.prevent="onDrop(idx)"
                                                    class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-xl {{ $isReadOnly ? '' : 'cursor-grab active:cursor-grabbing hover:border-violet-300' }} transition-colors"
                                                >
                                                    <span class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500" x-text="idx + 1"></span>
                                                    <span class="text-gray-700" x-text="@js(collect($rankOptions)->mapWithKeys(fn($o) => [is_array($o) ? ($o['value'] ?? '') : $o => is_array($o) ? ($o['label'] ?? $o['value'] ?? '') : $o])->toArray())[val] || val"></span>
                                                    @if(!$isReadOnly)
                                                        <svg class="w-4 h-4 text-gray-300 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                                    @endif
                                                </div>
                                            </template>
                                        </div>
                                        @break

                                    {{-- NPS --}}
                                    @case('nps')
                                        <div>
                                            <div class="flex justify-between mb-3 text-xs text-gray-400 px-1">
                                                <span>Überhaupt nicht wahrscheinlich</span>
                                                <span>Äusserst wahrscheinlich</span>
                                            </div>
                                            <div class="flex flex-wrap gap-2 justify-center">
                                                @for($i = 0; $i <= 10; $i++)
                                                    @php
                                                        $npsColor = $i <= 6 ? 'bg-rose-500 text-white shadow-rose-200' : ($i <= 8 ? 'bg-amber-400 text-white shadow-amber-200' : 'bg-emerald-500 text-white shadow-emerald-200');
                                                        $npsInactive = $i <= 6 ? 'bg-rose-50 text-rose-600' : ($i <= 8 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600');
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        @if(!$isReadOnly) wire:click="setAnswer('{{ $i }}')" @endif
                                                        @if($isReadOnly) disabled @endif
                                                        class="w-11 h-11 rounded-xl font-bold text-sm transition-all
                                                            {{ $currentAnswer === (string)$i ? $npsColor . ' shadow-lg' : $npsInactive }}
                                                            {{ $isReadOnly ? 'cursor-default' : 'hover:scale-110' }}"
                                                    >
                                                        {{ $i }}
                                                    </button>
                                                @endfor
                                            </div>
                                        </div>
                                        @break

                                    {{-- Dropdown --}}
                                    @case('dropdown')
                                        @php $dropdownOptions = $config['options'] ?? []; @endphp
                                        <div
                                            x-data="{
                                                open: false,
                                                search: '',
                                                searchable: {{ ($config['searchable'] ?? false) ? 'true' : 'false' }},
                                                get filteredOptions() {
                                                    const opts = @js($dropdownOptions);
                                                    if (!this.search) return opts;
                                                    return opts.filter(o => {
                                                        const label = typeof o === 'string' ? o : (o.label || o.value || '');
                                                        return label.toLowerCase().includes(this.search.toLowerCase());
                                                    });
                                                }
                                            }"
                                            x-on:click.away="open = false"
                                            class="relative"
                                        >
                                            <button
                                                type="button"
                                                @if(!$isReadOnly) x-on:click="open = !open" @endif
                                                @if($isReadOnly) disabled @endif
                                                class="intake-input flex items-center justify-between {{ $isReadOnly ? 'opacity-60' : 'cursor-pointer' }}"
                                            >
                                                <span :class="!$wire.currentAnswer ? 'text-gray-400' : 'text-gray-900'">
                                                    <template x-if="$wire.currentAnswer">
                                                        <span x-text="(() => { const o = @js($dropdownOptions).find(o => (typeof o === 'string' ? o : o.value) === $wire.currentAnswer); return o ? (typeof o === 'string' ? o : o.label) : $wire.currentAnswer; })()"></span>
                                                    </template>
                                                    <template x-if="!$wire.currentAnswer">
                                                        <span>{{ $config['placeholder'] ?? 'Bitte wählen...' }}</span>
                                                    </template>
                                                </span>
                                                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <div x-show="open" x-cloak x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                                <template x-if="searchable">
                                                    <div class="p-2 border-b border-gray-100">
                                                        <input type="text" x-model="search" placeholder="Suchen..." class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:border-violet-400 focus:ring-1 focus:ring-violet-200 outline-none">
                                                    </div>
                                                </template>
                                                <template x-for="option in filteredOptions" :key="typeof option === 'string' ? option : option.value">
                                                    <button
                                                        type="button"
                                                        x-on:click="$wire.setAnswer(typeof option === 'string' ? option : option.value); open = false; search = '';"
                                                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-violet-50 transition-colors"
                                                        :class="$wire.currentAnswer === (typeof option === 'string' ? option : option.value) ? 'bg-violet-50 text-violet-700 font-medium' : 'text-gray-700'"
                                                    >
                                                        <span x-text="typeof option === 'string' ? option : option.label"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                        @break

                                    {{-- DateTime --}}
                                    @case('datetime')
                                        <input
                                            type="datetime-local"
                                            wire:model="currentAnswer"
                                            @if(!empty($config['min_datetime'])) min="{{ $config['min_datetime'] }}" @endif
                                            @if(!empty($config['max_datetime'])) max="{{ $config['max_datetime'] }}" @endif
                                            @if($isReadOnly) disabled @endif
                                            class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}"
                                        >
                                        @break

                                    {{-- Time --}}
                                    @case('time')
                                        <input
                                            type="time"
                                            wire:model="currentAnswer"
                                            @if(!empty($config['min_time'])) min="{{ $config['min_time'] }}" @endif
                                            @if(!empty($config['max_time'])) max="{{ $config['max_time'] }}" @endif
                                            @if(!empty($config['step_minutes'])) step="{{ (int)$config['step_minutes'] * 60 }}" @endif
                                            @if($isReadOnly) disabled @endif
                                            class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}"
                                        >
                                        @break

                                    {{-- Slider --}}
                                    @case('slider')
                                        @php
                                            $sliderMin = $config['min'] ?? 0;
                                            $sliderMax = $config['max'] ?? 100;
                                            $sliderStep = $config['step'] ?? 1;
                                            $sliderUnit = $config['unit'] ?? '';
                                            $showValue = $config['show_value'] ?? true;
                                        @endphp
                                        <div x-data="{ value: $wire.entangle('currentAnswer') }" class="space-y-4">
                                            @if($showValue)
                                                <div class="text-center">
                                                    <span class="text-3xl font-bold text-violet-600" x-text="value || '{{ $sliderMin }}'"></span>
                                                    @if($sliderUnit)
                                                        <span class="text-lg text-gray-400 ml-1">{{ $sliderUnit }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                            <input
                                                type="range"
                                                x-model="value"
                                                x-on:change="$wire.set('currentAnswer', value)"
                                                min="{{ $sliderMin }}"
                                                max="{{ $sliderMax }}"
                                                step="{{ $sliderStep }}"
                                                @if($isReadOnly) disabled @endif
                                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-violet-600 {{ $isReadOnly ? 'opacity-60' : '' }}"
                                            >
                                            <div class="flex justify-between text-xs text-gray-400">
                                                <span>{{ $sliderMin }}{{ $sliderUnit ? ' ' . $sliderUnit : '' }}</span>
                                                <span>{{ $sliderMax }}{{ $sliderUnit ? ' ' . $sliderUnit : '' }}</span>
                                            </div>
                                        </div>
                                        @break

                                    {{-- Image Choice --}}
                                    @case('image_choice')
                                        @php $imgOptions = $config['options'] ?? []; $cols = $config['columns'] ?? 3; @endphp
                                        <div class="grid gap-3" style="grid-template-columns: repeat({{ $cols }}, 1fr)">
                                            @foreach($imgOptions as $imgOpt)
                                                @php
                                                    $imgValue = is_array($imgOpt) ? ($imgOpt['value'] ?? '') : $imgOpt;
                                                    $imgLabel = is_array($imgOpt) ? ($imgOpt['label'] ?? '') : $imgOpt;
                                                    $imgFileId = is_array($imgOpt) ? ($imgOpt['file_id'] ?? null) : null;
                                                    $isChosen = $currentAnswer === $imgValue;
                                                @endphp
                                                <button
                                                    type="button"
                                                    @if(!$isReadOnly) wire:click="setAnswer('{{ $imgValue }}')" @endif
                                                    @if($isReadOnly) disabled @endif
                                                    class="relative rounded-xl border-2 overflow-hidden transition-all {{ $isChosen ? 'border-violet-500 ring-2 ring-violet-200' : 'border-gray-200 hover:border-gray-300' }} {{ $isReadOnly ? 'cursor-default' : '' }}"
                                                >
                                                    <div class="aspect-square bg-gray-100 flex items-center justify-center">
                                                        @if($imgFileId)
                                                            <img src="{{ route('core.files.serve', $imgFileId) }}" alt="{{ $imgLabel }}" class="w-full h-full object-cover">
                                                        @else
                                                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                        @endif
                                                    </div>
                                                    @if($imgLabel)
                                                        <div class="p-2 text-center text-sm {{ $isChosen ? 'text-violet-700 font-medium' : 'text-gray-600' }}">{{ $imgLabel }}</div>
                                                    @endif
                                                    @if($isChosen)
                                                        <div class="absolute top-2 right-2 w-6 h-6 bg-violet-600 rounded-full flex items-center justify-center">
                                                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                        </div>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                        @break

                                    {{-- Consent --}}
                                    @case('consent')
                                        @php
                                            $consentText = $config['text'] ?? '';
                                            $consentLinkUrl = $config['link_url'] ?? '';
                                            $consentLinkLabel = $config['link_label'] ?? 'Datenschutzerklärung';
                                        @endphp
                                        <div class="space-y-4">
                                            @if($consentText)
                                                <div class="text-sm text-gray-600 leading-relaxed whitespace-pre-line p-4 bg-gray-50 rounded-xl border border-gray-100">
                                                    {{ $consentText }}
                                                </div>
                                            @endif
                                            @if($consentLinkUrl)
                                                <a href="{{ $consentLinkUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-sm text-violet-600 hover:text-violet-800">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                    {{ $consentLinkLabel }}
                                                </a>
                                            @endif
                                            <label class="flex items-center gap-3 p-4 rounded-xl border transition-colors {{ $currentAnswer === 'true' ? 'border-violet-300 bg-violet-50' : 'border-gray-200' }} {{ $isReadOnly ? 'cursor-default' : 'cursor-pointer' }}">
                                                <input
                                                    type="checkbox"
                                                    @if(!$isReadOnly) wire:click="setAnswer({{ $currentAnswer === 'true' ? '\'false\'' : '\'true\'' }})" @endif
                                                    {{ $currentAnswer === 'true' ? 'checked' : '' }}
                                                    @if($isReadOnly) disabled @endif
                                                    class="w-5 h-5 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                                >
                                                <span class="text-sm {{ $currentAnswer === 'true' ? 'text-gray-900 font-medium' : 'text-gray-600' }}">Ich stimme zu</span>
                                            </label>
                                        </div>
                                        @break

                                    {{-- Section (display-only) --}}
                                    @case('section')
                                        <div class="py-4">
                                            @if(!empty($config['title']))
                                                <h3 class="text-lg font-bold text-gray-900">{{ $config['title'] }}</h3>
                                            @endif
                                            @if(!empty($config['subtitle']))
                                                <p class="mt-1 text-gray-500">{{ $config['subtitle'] }}</p>
                                            @endif
                                            @if(!empty($config['content']))
                                                <div class="mt-3 text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $config['content'] }}</div>
                                            @endif
                                            <div class="mt-4 border-t border-gray-200"></div>
                                        </div>
                                        @break

                                    {{-- Hidden (invisible) --}}
                                    @case('hidden')
                                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl border border-gray-100">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                            <span class="text-sm text-gray-500">Dieses Feld wird automatisch ausgefüllt.</span>
                                        </div>
                                        @break

                                    {{-- Address --}}
                                    @case('address')
                                        @php $addrFields = $config['fields'] ?? ['street', 'house_number', 'zip', 'city', 'country']; @endphp
                                        @php
                                            $addrLabels = [
                                                'street' => 'Strasse',
                                                'house_number' => 'Hausnummer',
                                                'zip' => 'PLZ',
                                                'city' => 'Ort',
                                                'country' => 'Land',
                                            ];
                                            $addrPlaceholders = [
                                                'street' => 'Musterstrasse',
                                                'house_number' => '42',
                                                'zip' => '8001',
                                                'city' => 'Zürich',
                                                'country' => 'Schweiz',
                                            ];
                                        @endphp
                                        <div class="space-y-3">
                                            @if(in_array('street', $addrFields) && in_array('house_number', $addrFields))
                                                <div class="grid grid-cols-3 gap-3">
                                                    <div class="col-span-2">
                                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $addrLabels['street'] }}</label>
                                                        <input type="text" wire:model.lazy="addressFields.street" placeholder="{{ $addrPlaceholders['street'] }}" @if($isReadOnly) disabled @endif class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $addrLabels['house_number'] }}</label>
                                                        <input type="text" wire:model.lazy="addressFields.house_number" placeholder="{{ $addrPlaceholders['house_number'] }}" @if($isReadOnly) disabled @endif class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}">
                                                    </div>
                                                </div>
                                            @elseif(in_array('street', $addrFields))
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $addrLabels['street'] }}</label>
                                                    <input type="text" wire:model.lazy="addressFields.street" placeholder="{{ $addrPlaceholders['street'] }}" @if($isReadOnly) disabled @endif class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}">
                                                </div>
                                            @endif
                                            @if(in_array('zip', $addrFields) && in_array('city', $addrFields))
                                                <div class="grid grid-cols-3 gap-3">
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $addrLabels['zip'] }}</label>
                                                        <input type="text" wire:model.lazy="addressFields.zip" placeholder="{{ $addrPlaceholders['zip'] }}" @if($isReadOnly) disabled @endif class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}">
                                                    </div>
                                                    <div class="col-span-2">
                                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $addrLabels['city'] }}</label>
                                                        <input type="text" wire:model.lazy="addressFields.city" placeholder="{{ $addrPlaceholders['city'] }}" @if($isReadOnly) disabled @endif class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}">
                                                    </div>
                                                </div>
                                            @endif
                                            @if(in_array('country', $addrFields))
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $addrLabels['country'] }}</label>
                                                    <input type="text" wire:model.lazy="addressFields.country" placeholder="{{ $addrPlaceholders['country'] }}" @if($isReadOnly) disabled @endif class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}">
                                                </div>
                                            @endif
                                        </div>
                                        @break

                                    {{-- Color --}}
                                    @case('color')
                                        @php $presets = $config['presets'] ?? []; @endphp
                                        <div class="space-y-4">
                                            <div class="flex items-center gap-4">
                                                <input
                                                    type="color"
                                                    wire:model="currentAnswer"
                                                    @if($isReadOnly) disabled @endif
                                                    class="w-14 h-14 rounded-xl border border-gray-200 cursor-pointer p-1 {{ $isReadOnly ? 'opacity-60' : '' }}"
                                                >
                                                <input
                                                    type="text"
                                                    wire:model="currentAnswer"
                                                    placeholder="#000000"
                                                    maxlength="7"
                                                    @if($isReadOnly) disabled @endif
                                                    class="intake-input font-mono {{ $isReadOnly ? 'opacity-60' : '' }}"
                                                >
                                            </div>
                                            @if(!empty($presets))
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($presets as $preset)
                                                        <button
                                                            type="button"
                                                            @if(!$isReadOnly) wire:click="setAnswer('{{ $preset }}')" @endif
                                                            @if($isReadOnly) disabled @endif
                                                            class="w-8 h-8 rounded-lg border-2 transition-all {{ $currentAnswer === $preset ? 'border-gray-800 scale-110' : 'border-gray-200 hover:scale-105' }}"
                                                            style="background-color: {{ $preset }}"
                                                            title="{{ $preset }}"
                                                        ></button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        @break

                                    {{-- Lookup --}}
                                    @case('lookup')
                                        @php
                                            $lookupId = $config['lookup_id'] ?? null;
                                            $lookupMultiple = $config['multiple'] ?? false;
                                            $lookupSearchable = $config['searchable'] ?? true;
                                            $lookupPlaceholder = $config['placeholder'] ?? 'Bitte wählen...';
                                            $blockLookupOptions = $lookupOptions[$block['id']] ?? [];
                                        @endphp
                                        @if($lookupMultiple)
                                            {{-- Multi-select lookup --}}
                                            <div class="space-y-2 max-h-80 overflow-y-auto">
                                                @foreach($blockLookupOptions as $lo)
                                                    @php $loSelected = in_array($lo['value'], $selectedOptions); @endphp
                                                    <button
                                                        type="button"
                                                        @if(!$isReadOnly) wire:click="toggleOption('{{ $lo['value'] }}')" @endif
                                                        @if($isReadOnly) disabled @endif
                                                        class="intake-option-card {{ $loSelected ? 'intake-option-active' : '' }} {{ $isReadOnly ? 'cursor-default' : '' }}"
                                                    >
                                                        <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0 border-2 transition-colors {{ $loSelected ? 'border-violet-600 bg-violet-600' : 'border-gray-300' }}">
                                                            @if($loSelected)
                                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                            @endif
                                                        </span>
                                                        <span class="{{ $loSelected ? 'text-gray-900' : 'text-gray-600' }}">
                                                            @if(!empty($lo['meta']['flag'])) {{ $lo['meta']['flag'] }} @endif
                                                            {{ $lo['label'] }}
                                                        </span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @else
                                            {{-- Single-select lookup with search --}}
                                            <div
                                                x-data="{ open: false, search: '' }"
                                                x-on:click.away="open = false"
                                                class="relative"
                                            >
                                                <button type="button" @if(!$isReadOnly) x-on:click="open = !open" @endif @if($isReadOnly) disabled @endif class="intake-input flex items-center justify-between {{ $isReadOnly ? 'opacity-60' : 'cursor-pointer' }}">
                                                    <span :class="!$wire.currentAnswer ? 'text-gray-400' : 'text-gray-900'">
                                                        @if($currentAnswer)
                                                            @php $foundOpt = collect($blockLookupOptions)->firstWhere('value', $currentAnswer); @endphp
                                                            {{ $foundOpt ? ((!empty($foundOpt['meta']['flag']) ? $foundOpt['meta']['flag'] . ' ' : '') . $foundOpt['label']) : $currentAnswer }}
                                                        @else
                                                            {{ $lookupPlaceholder }}
                                                        @endif
                                                    </span>
                                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                                <div x-show="open" x-cloak x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                                    @if($lookupSearchable)
                                                        <div class="p-2 border-b border-gray-100 sticky top-0 bg-white">
                                                            <input type="text" x-model="search" placeholder="Suchen..." class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:border-violet-400 focus:ring-1 focus:ring-violet-200 outline-none">
                                                        </div>
                                                    @endif
                                                    @foreach($blockLookupOptions as $lo)
                                                        <button
                                                            type="button"
                                                            x-show="!search || '{{ strtolower($lo['label']) }}'.includes(search.toLowerCase())"
                                                            x-on:click="$wire.setAnswer('{{ $lo['value'] }}'); open = false; search = '';"
                                                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-violet-50 transition-colors {{ $currentAnswer === $lo['value'] ? 'bg-violet-50 text-violet-700 font-medium' : 'text-gray-700' }}"
                                                        >
                                                            @if(!empty($lo['meta']['flag'])) {{ $lo['meta']['flag'] }} @endif
                                                            {{ $lo['label'] }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        @break

                                    {{-- Signature --}}
                                    @case('signature')
                                        @php
                                            $sigWidth = $config['width'] ?? 400;
                                            $sigHeight = $config['height'] ?? 200;
                                            $sigPenColor = $config['pen_color'] ?? '#000000';
                                        @endphp
                                        <div
                                            x-data="{
                                                canvas: null,
                                                ctx: null,
                                                drawing: false,
                                                hasContent: !!$wire.currentAnswer,
                                                init() {
                                                    this.canvas = this.$refs.sigCanvas;
                                                    this.ctx = this.canvas.getContext('2d');
                                                    this.ctx.strokeStyle = '{{ $sigPenColor }}';
                                                    this.ctx.lineWidth = 2;
                                                    this.ctx.lineCap = 'round';
                                                    this.ctx.lineJoin = 'round';
                                                    if ($wire.currentAnswer && $wire.currentAnswer.startsWith('data:')) {
                                                        const img = new Image();
                                                        img.onload = () => this.ctx.drawImage(img, 0, 0);
                                                        img.src = $wire.currentAnswer;
                                                    }
                                                },
                                                getPos(e) {
                                                    const rect = this.canvas.getBoundingClientRect();
                                                    const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
                                                    const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;
                                                    return { x: x * (this.canvas.width / rect.width), y: y * (this.canvas.height / rect.height) };
                                                },
                                                startDraw(e) {
                                                    if ({{ $isReadOnly ? 'true' : 'false' }}) return;
                                                    e.preventDefault();
                                                    this.drawing = true;
                                                    const pos = this.getPos(e);
                                                    this.ctx.beginPath();
                                                    this.ctx.moveTo(pos.x, pos.y);
                                                },
                                                draw(e) {
                                                    if (!this.drawing) return;
                                                    e.preventDefault();
                                                    const pos = this.getPos(e);
                                                    this.ctx.lineTo(pos.x, pos.y);
                                                    this.ctx.stroke();
                                                    this.hasContent = true;
                                                },
                                                stopDraw() {
                                                    if (!this.drawing) return;
                                                    this.drawing = false;
                                                    $wire.set('currentAnswer', this.canvas.toDataURL('image/png'));
                                                },
                                                clear() {
                                                    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                                                    this.hasContent = false;
                                                    $wire.set('currentAnswer', '');
                                                }
                                            }"
                                            class="space-y-3"
                                        >
                                            <canvas
                                                x-ref="sigCanvas"
                                                width="{{ $sigWidth }}"
                                                height="{{ $sigHeight }}"
                                                x-on:mousedown="startDraw($event)"
                                                x-on:mousemove="draw($event)"
                                                x-on:mouseup="stopDraw()"
                                                x-on:mouseleave="stopDraw()"
                                                x-on:touchstart="startDraw($event)"
                                                x-on:touchmove="draw($event)"
                                                x-on:touchend="stopDraw()"
                                                class="w-full border-2 border-gray-200 rounded-xl bg-white {{ $isReadOnly ? 'opacity-60' : 'cursor-crosshair' }}"
                                                style="max-width: {{ $sigWidth }}px; aspect-ratio: {{ $sigWidth }}/{{ $sigHeight }}"
                                            ></canvas>
                                            @if(!$isReadOnly)
                                                <button type="button" x-on:click="clear()" x-show="hasContent" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1.5">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    Unterschrift löschen
                                                </button>
                                            @endif
                                        </div>
                                        @break

                                    {{-- Date Range --}}
                                    @case('date_range')
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 mb-1">Von</label>
                                                <input
                                                    type="date"
                                                    wire:model="dateRangeStart"
                                                    @if(!empty($config['min_date'])) min="{{ $config['min_date'] }}" @endif
                                                    @if(!empty($config['max_date'])) max="{{ $config['max_date'] }}" @endif
                                                    @if($isReadOnly) disabled @endif
                                                    class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}"
                                                >
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 mb-1">Bis</label>
                                                <input
                                                    type="date"
                                                    wire:model="dateRangeEnd"
                                                    @if(!empty($config['min_date'])) min="{{ $config['min_date'] }}" @endif
                                                    @if(!empty($config['max_date'])) max="{{ $config['max_date'] }}" @endif
                                                    @if($isReadOnly) disabled @endif
                                                    class="intake-input {{ $isReadOnly ? 'opacity-60' : '' }}"
                                                >
                                            </div>
                                        </div>
                                        @break

                                    {{-- Calculated (display-only) --}}
                                    @case('calculated')
                                        @php
                                            $formula = $config['formula'] ?? '';
                                            $sourceBlocks = $config['source_blocks'] ?? [];
                                            $displayFormat = $config['display_format'] ?? '{result}';
                                        @endphp
                                        <div
                                            x-data="{
                                                result: '',
                                                calculate() {
                                                    try {
                                                        let formula = '{{ addslashes($formula) }}';
                                                        const answers = @js($this->session->answers ?? []);
                                                        @foreach($sourceBlocks as $sb)
                                                            formula = formula.replace(/{block_{{ $sb }}}/g, parseFloat(answers['block_{{ $sb }}'] || '0') || 0);
                                                        @endforeach
                                                        // Safe math evaluation
                                                        const safeEval = (expr) => {
                                                            expr = expr.replace(/[^0-9+\-*/().,%\s]/g, '');
                                                            if (!expr.trim()) return 0;
                                                            return new Function('return ' + expr)();
                                                        };
                                                        const val = safeEval(formula);
                                                        const fmt = '{{ addslashes($displayFormat) }}';
                                                        this.result = fmt ? fmt.replace('{result}', Math.round(val * 100) / 100) : String(Math.round(val * 100) / 100);
                                                    } catch(e) { this.result = '—'; }
                                                },
                                                init() { this.calculate(); }
                                            }"
                                            class="flex items-center gap-3 p-5 bg-gray-50 border border-gray-200 rounded-xl"
                                        >
                                            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            <span class="text-lg font-semibold text-gray-900" x-text="result"></span>
                                        </div>
                                        @break

                                    {{-- Repeater --}}
                                    @case('repeater')
                                        @php
                                            $repeaterFields = $config['fields'] ?? [];
                                            $maxEntries = (int) ($config['max_entries'] ?? 10);
                                            $minEntries = (int) ($config['min_entries'] ?? 0);
                                            $addLabel = $config['add_label'] ?? 'Eintrag hinzufügen';
                                        @endphp
                                        <div class="space-y-4">
                                            @foreach($repeaterEntries as $entryIdx => $entry)
                                                <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl relative group">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">#{{ $entryIdx + 1 }}</span>
                                                        @if(!$isReadOnly && count($repeaterEntries) > $minEntries)
                                                            <button
                                                                type="button"
                                                                wire:click="removeRepeaterEntry({{ $entryIdx }})"
                                                                class="opacity-0 group-hover:opacity-100 transition-opacity text-red-400 hover:text-red-600 p-1 rounded"
                                                                title="Entfernen"
                                                            >
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                        @foreach($repeaterFields as $field)
                                                            @php
                                                                $fKey = $field['key'] ?? '';
                                                                $fLabel = $field['label'] ?? $fKey;
                                                                $fType = $field['type'] ?? 'text';
                                                                $fValue = $entry[$fKey] ?? '';
                                                                $inputType = match($fType) {
                                                                    'email' => 'email',
                                                                    'url' => 'url',
                                                                    'phone' => 'tel',
                                                                    'number' => 'number',
                                                                    'date' => 'date',
                                                                    'time' => 'time',
                                                                    'color' => 'color',
                                                                    default => 'text',
                                                                };
                                                            @endphp
                                                            <div class="{{ in_array($fType, ['long_text']) ? 'sm:col-span-2' : '' }}">
                                                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ $fLabel }}</label>
                                                                @if($fType === 'long_text')
                                                                    <textarea
                                                                        wire:model.lazy="repeaterEntries.{{ $entryIdx }}.{{ $fKey }}"
                                                                        rows="3"
                                                                        placeholder="{{ $fLabel }}"
                                                                        @if($isReadOnly) disabled @endif
                                                                        class="intake-input text-sm {{ $isReadOnly ? 'opacity-60' : '' }}"
                                                                    ></textarea>
                                                                @elseif($fType === 'select')
                                                                    <select
                                                                        wire:model.lazy="repeaterEntries.{{ $entryIdx }}.{{ $fKey }}"
                                                                        @if($isReadOnly) disabled @endif
                                                                        class="intake-input text-sm {{ $isReadOnly ? 'opacity-60' : '' }}"
                                                                    >
                                                                        <option value="">Bitte wählen...</option>
                                                                        @foreach($field['options'] ?? [] as $opt)
                                                                            <option value="{{ is_array($opt) ? ($opt['value'] ?? '') : $opt }}">{{ is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? '') : $opt }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                @else
                                                                    <input
                                                                        type="{{ $inputType }}"
                                                                        wire:model.lazy="repeaterEntries.{{ $entryIdx }}.{{ $fKey }}"
                                                                        placeholder="{{ $fLabel }}"
                                                                        @if($isReadOnly) disabled @endif
                                                                        class="intake-input text-sm {{ $isReadOnly ? 'opacity-60' : '' }}"
                                                                    />
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach

                                            @if(!$isReadOnly && count($repeaterEntries) < $maxEntries)
                                                <button
                                                    type="button"
                                                    wire:click="addRepeaterEntry"
                                                    class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors flex items-center justify-center gap-2"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    {{ $addLabel }}
                                                </button>
                                            @endif

                                            @if(count($repeaterEntries) > 0)
                                                <p class="text-xs text-gray-400 text-right">{{ count($repeaterEntries) }} / {{ $maxEntries }} Einträge</p>
                                            @endif
                                        </div>
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
        <footer class="intake-shell-footer">
            <p class="text-[11px] text-white/20 tracking-wider uppercase">Powered by Hatch</p>
        </footer>
        </div>{{-- /.intake-shell --}}
    @endif
</div>

<style>
    /* ═══════════════════════════════════════════
       Intake Session Styles — White Card Design
       ═══════════════════════════════════════════ */

    .intake-wrap {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        min-height: 100vh;
        min-height: 100dvh;
    }

    /* Fullscreen centering for error/status screens */
    .intake-fullscreen {
        min-height: 100vh;
        min-height: 100dvh;
    }

    /* ── Shell: Header + Main + Footer = exactly viewport ── */
    .intake-shell {
        display: flex;
        flex-direction: column;
        height: 100vh;
        height: 100dvh;
        overflow: hidden;
    }

    .intake-shell-header {
        flex-shrink: 0;
    }

    .intake-shell-main {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 2rem 1.5rem;
    }

    .intake-shell-main > * {
        max-width: 48rem;
        margin-left: auto;
        margin-right: auto;
    }

    @media (min-width: 1024px) {
        .intake-shell-main > * {
            max-width: 72rem;
        }
    }

    .intake-shell-footer {
        flex-shrink: 0;
        padding: 0.75rem 1.5rem;
        text-align: center;
    }

    /* Smooth scrollbar for main area */
    .intake-shell-main {
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,0.1) transparent;
    }

    .intake-shell-main::-webkit-scrollbar {
        width: 6px;
    }

    .intake-shell-main::-webkit-scrollbar-track {
        background: transparent;
    }

    .intake-shell-main::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.1);
        border-radius: 6px;
    }

    .intake-shell-main::-webkit-scrollbar-thumb:hover {
        background: rgba(255,255,255,0.2);
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
            align-items: flex-start;
        }

        .intake-sidebar {
            width: 260px;
            flex-shrink: 0;
        }

        .intake-sidebar-inner {
            position: sticky;
            top: 0;
            max-height: calc(100vh - 12rem);
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
            padding: 0;
            overflow: hidden;
        }
    }

    /* ── Sidebar Progress ── */
    .intake-sidebar-progress {
        padding: 12px 14px 8px;
        flex-shrink: 0;
    }

    @media (max-width: 1023px) {
        .intake-sidebar-progress {
            display: none;
        }
    }

    /* ── Pills ── */
    .intake-pills {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        gap: 0.375rem;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: 0 4px 4px;
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

    .intake-pill-text {
        display: none;
        min-width: 0;
    }

    .intake-pill-name {
        font-size: 0.8125rem;
        line-height: 1.3;
    }

    .intake-pill-desc {
        font-size: 0.6875rem;
        opacity: 0.5;
        line-height: 1.3;
    }

    @media (min-width: 640px) {
        .intake-pill-text {
            display: block;
        }
    }

    @media (min-width: 1024px) {
        .intake-pills {
            flex-direction: column;
            flex-wrap: nowrap;
            overflow-x: visible;
            overflow-y: auto;
            flex: 1;
            min-height: 0;
            padding: 4px 10px 10px;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.15) transparent;
        }

        .intake-pills::-webkit-scrollbar {
            display: block;
            width: 4px;
        }

        .intake-pills::-webkit-scrollbar-track {
            background: transparent;
        }

        .intake-pills::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.15);
            border-radius: 4px;
        }

        .intake-pills::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.25);
        }

        .intake-pill {
            flex-shrink: 0;
            width: 100%;
            border-radius: 12px;
        }

        .intake-pill-text {
            display: block;
        }
    }
</style>
