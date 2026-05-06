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
                <a href="/" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
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
                <p class="text-gray-500 text-lg mb-4">Diese Erhebung ist derzeit nicht verfuegbar.</p>
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
                <p class="text-gray-500 text-lg mb-4">Diese Erhebung wurde noch nicht gestartet.</p>
                <p class="text-sm text-gray-400">Ihr Token bleibt gueltig &ndash; Sie koennen spaeter fortfahren.</p>
            </div>
        </div>

    @elseif(in_array($state, ['ready', 'completed']))
        @php
            $isReadOnly = ($state === 'completed');
            $segments = $this->getRenderSegments();
            // Index-Map fuer missingRequiredBlocks: block-id -> array index
            $blockIndexById = [];
            foreach ($blocks as $idx => $b) {
                $blockIndexById[$b['id']] = $idx;
            }
        @endphp

        <div class="intake-shell">
            <header class="intake-shell-header z-50">
                <div class="intake-header-glass">
                    <div class="max-w-3xl lg:max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <h1 class="text-base font-semibold text-white truncate">{{ $intakeName }}</h1>
                        </div>
                        <div class="flex items-center gap-4 flex-shrink-0 ml-4">
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
                        </div>
                    </div>
                </div>
            </header>

            <main class="intake-shell-main">

                {{-- Status Banner / Greeting --}}
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
                                    <p>Vielen Dank fuer Ihre Teilnahme.</p>
                                @else
                                    <p>Diese Erhebung wurde abgeschlossen.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    @if($respondentName)
                        <div class="mb-4">
                            <div class="intake-card flex items-center gap-3 px-5 py-4">
                                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium text-gray-800">Hallo {{ $respondentName }}</span> &ndash; schoen, dass Sie da sind!
                                </p>
                            </div>
                        </div>
                    @endif
                    <p class="text-xs text-white/30 text-center tracking-wide mb-4">
                        Speichern Sie Ihren Token <span class="font-mono font-semibold text-white/50">{{ $sessionToken }}</span>, um spaeter fortzufahren.
                    </p>
                @endif

                @if($validationError)
                    <div class="mb-4 intake-card flex items-start gap-3 px-5 py-4">
                        <div class="w-8 h-8 rounded-full bg-rose-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <p class="text-sm text-rose-700">{{ $validationError }}</p>
                    </div>
                @endif

                {{-- Render alle Segmente nacheinander. --}}
                @if(count($segments) === 0)
                    <div class="intake-card p-10 text-center">
                        <p class="text-gray-400">Keine Bloecke in dieser Erhebung konfiguriert.</p>
                    </div>
                @endif

                <div class="space-y-5">
                @foreach($segments as $segment)
                    @if($segment['kind'] === 'compact_table')
                        @include('hatch::livewire.public.partials.overview-compact-table', [
                            'segment' => $segment,
                            'isReadOnly' => $isReadOnly,
                            'blockIndexById' => $blockIndexById,
                        ])
                    @else
                        @foreach($segment['groups'] as $group)
                            @php
                                $isVisible = false;
                                foreach ($group['fields'] as $f) {
                                    if ($this->isBlockVisible($f)) { $isVisible = true; break; }
                                }
                            @endphp
                            @if(!$isVisible)
                                @continue
                            @endif

                            @php
                                $headerBlock = $group['fields'][0];
                                $isMultiField = count($group['fields']) > 1;
                            @endphp

                            <div class="intake-card overflow-hidden">
                                @if(!in_array($headerBlock['type'], ['hidden']))
                                    <div class="px-6 pt-6 pb-3 border-b border-gray-100">
                                        @if(!empty($headerBlock['name']))
                                            <h2 class="text-lg font-bold text-gray-900">
                                                {{ $headerBlock['name'] }}
                                                @php
                                                    $headerRequired = $headerBlock['is_required']
                                                        || collect($group['fields'])->contains(fn($f) => $f['is_required']);
                                                @endphp
                                                @if($headerRequired && !$isReadOnly)
                                                    <span class="text-rose-500 ml-1">*</span>
                                                @endif
                                            </h2>
                                        @endif
                                        @if(!empty($headerBlock['description']))
                                            <p class="mt-2 text-sm text-gray-500 leading-relaxed">{{ $headerBlock['description'] }}</p>
                                        @endif
                                    </div>
                                @endif

                                <div class="p-6 space-y-5">
                                    @foreach($group['fields'] as $idxInGroup => $block)
                                        @if(!$this->isBlockVisible($block))
                                            @continue
                                        @endif
                                        @php
                                            $blockIdx = $blockIndexById[$block['id']] ?? null;
                                            $isMissing = $blockIdx !== null && in_array($blockIdx, $missingRequiredBlocks ?? [], true);
                                        @endphp
                                        <div class="{{ $isMissing ? 'rounded-xl ring-1 ring-rose-300/70 p-3 -m-1' : '' }}">
                                            @if($isMultiField && $idxInGroup > 0 && !empty($block['name']))
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    {{ $block['name'] }}
                                                    @if($block['is_required'] && !$isReadOnly)
                                                        <span class="text-rose-500 ml-0.5">*</span>
                                                    @endif
                                                </label>
                                            @endif
                                            @if($isMultiField && $idxInGroup > 0 && !empty($block['description']))
                                                <p class="text-xs text-gray-500 mb-2 leading-relaxed">{{ $block['description'] }}</p>
                                            @endif

                                            @include('hatch::livewire.public.partials.overview-block', [
                                                'block' => $block,
                                                'isReadOnly' => $isReadOnly,
                                                'compact' => false,
                                            ])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endforeach
                </div>

                @if(!$isReadOnly && count($segments) > 0)
                    <div class="mt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <button
                            wire:click="saveDraft"
                            wire:loading.attr="disabled"
                            class="px-5 py-2.5 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10 rounded-xl transition-all"
                        >
                            <span wire:loading.remove wire:target="saveDraft">Zwischenspeichern</span>
                            <span wire:loading wire:target="saveDraft">Wird gespeichert...</span>
                        </button>
                        <button
                            wire:click="submit"
                            wire:loading.attr="disabled"
                            class="intake-btn-submit"
                        >
                            <span wire:loading.remove wire:target="submit">Abschliessen</span>
                            <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Wird abgeschlossen...
                            </span>
                        </button>
                    </div>
                @endif
            </main>

            <footer class="intake-shell-footer">
                <p class="text-[11px] text-white/20 tracking-wider uppercase">Powered by Formulare</p>
            </footer>
        </div>
    @endif

<style>
    .intake-wrap {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        height: 100vh;
        height: 100dvh;
        overflow: hidden;
    }
    .intake-fullscreen { height: 100%; }
    .intake-shell { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
    .intake-shell-header { flex-shrink: 0; }
    .intake-shell-main {
        flex: 1; min-height: 0; overflow-y: auto; padding: 2rem 1.5rem;
        scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;
    }
    .intake-shell-main > * { max-width: 48rem; margin-left: auto; margin-right: auto; }
    @media (min-width: 1024px) {
        .intake-shell-main > * { max-width: 56rem; }
    }
    .intake-shell-main::-webkit-scrollbar { width: 6px; }
    .intake-shell-main::-webkit-scrollbar-track { background: transparent; }
    .intake-shell-main::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 6px; }
    .intake-shell-main::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
    .intake-shell-footer { flex-shrink: 0; padding: 0.75rem 1.5rem; text-align: center; }

    .intake-bg {
        position: fixed; inset: 0;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        z-index: -10;
    }
    .intake-card {
        background: white; border-radius: 24px;
        border: 1px solid rgba(0, 0, 0, 0.06);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    }
    .intake-header-glass {
        background: rgba(15, 10, 26, 0.6);
        backdrop-filter: blur(30px);
        -webkit-backdrop-filter: blur(30px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .intake-input {
        width: 100%; padding: 12px 16px;
        background: white; border: 1px solid #d1d5db;
        border-radius: 12px; color: #111827; font-size: 14px;
        outline: none; transition: all 0.2s ease;
    }
    .intake-input::placeholder { color: #9ca3af; }
    .intake-input:focus {
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }
    .intake-input:disabled { cursor: not-allowed; background: #f9fafb; }

    .intake-input-sm {
        padding: 8px 12px; font-size: 13px; border-radius: 10px;
    }

    .intake-option-card {
        width: 100%; display: flex; align-items: center; gap: 14px;
        padding: 12px 16px; border-radius: 12px;
        border: 1px solid #e5e7eb; background: white;
        text-align: left; transition: all 0.2s ease;
    }
    .intake-option-card:not(:disabled):hover { background: #f9fafb; border-color: #d1d5db; }
    .intake-option-active {
        background: rgba(124, 58, 237, 0.05) !important;
        border-color: rgba(124, 58, 237, 0.4) !important;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.08);
    }
    .intake-bool-card {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 8px; padding: 24px 20px; border-radius: 16px;
        border: 1px solid #e5e7eb; background: white;
        transition: all 0.2s ease;
    }
    .intake-bool-card:not(:disabled):hover { background: #f9fafb; border-color: #d1d5db; }

    .intake-btn-submit {
        padding: 10px 24px; background: #10b981; color: white;
        font-size: 14px; font-weight: 600; border-radius: 14px;
        transition: all 0.2s ease;
    }
    .intake-btn-submit:hover { background: #059669; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); }
    .intake-btn-submit:disabled { opacity: 0.5; }

    /* Compact Table (Mo-Fr Wochenfeedback) */
    .intake-compact-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .intake-compact-table th {
        font-size: 12px; font-weight: 600;
        color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em;
        text-align: left; padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
    }
    .intake-compact-table td {
        padding: 12px;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
    }
    .intake-compact-table tr:last-child td { border-bottom: none; }
    .intake-compact-table .row-label {
        font-weight: 600; color: #374151;
        white-space: nowrap;
    }

    .intake-thumbs {
        display: inline-flex; gap: 8px;
    }
    .intake-thumb {
        width: 40px; height: 40px;
        border-radius: 12px; border: 1px solid #e5e7eb;
        background: white; display: flex; align-items: center; justify-content: center;
        transition: all 0.15s ease;
    }
    .intake-thumb:not(:disabled):hover { background: #f9fafb; }
    .intake-thumb.up-active {
        background: #10b981; border-color: #10b981; color: white;
    }
    .intake-thumb.down-active {
        background: #ef4444; border-color: #ef4444; color: white;
    }
</style>
</div>
