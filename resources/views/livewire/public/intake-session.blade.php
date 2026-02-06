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
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $blocks[$currentStep]['name'] }}
                        </h2>
                        @if($blocks[$currentStep]['description'])
                            <p class="mt-1 text-gray-600 dark:text-gray-300">
                                {{ $blocks[$currentStep]['description'] }}
                            </p>
                        @endif
                    </div>

                    <div class="p-6">
                        <textarea
                            wire:model="currentAnswer"
                            rows="8"
                            placeholder="Ihre Antwort..."
                            class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-y"
                        ></textarea>
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
                                    wire:click="saveCurrentBlock"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors"
                                >
                                    <span wire:loading.remove wire:target="saveCurrentBlock">Abschliessen</span>
                                    <span wire:loading wire:target="saveCurrentBlock" class="inline-flex items-center gap-2">
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        Wird gespeichert...
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
