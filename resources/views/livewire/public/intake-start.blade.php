<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        @if($state === 'loading')
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-2xl rounded-2xl p-8 text-center">
                <div class="animate-spin w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full mx-auto mb-4"></div>
                <p class="text-gray-600 dark:text-gray-300">Erhebung wird geladen...</p>
            </div>
        @elseif($state === 'notFound')
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-2xl rounded-2xl p-8 text-center">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Link nicht gefunden</h1>
                <p class="text-gray-600 dark:text-gray-300">Dieser Erhebungs-Link ist ungueltig oder existiert nicht mehr.</p>
            </div>
        @elseif($state === 'notActive')
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-2xl rounded-2xl p-8 text-center">
                <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Erhebung nicht verfuegbar</h1>
                <p class="text-gray-600 dark:text-gray-300">Die Erhebung <strong>{{ $intakeName }}</strong> ist derzeit nicht aktiv.</p>
            </div>
        @elseif($state === 'paused')
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-2xl rounded-2xl p-8 text-center">
                <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Erhebung pausiert</h1>
                <p class="text-gray-600 dark:text-gray-300">Die Erhebung <strong>{{ $intakeName }}</strong> ist derzeit pausiert. Bitte versuchen Sie es spaeter erneut.</p>
            </div>
        @elseif($state === 'ready')
            {{-- Header --}}
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $intakeName }}</h1>
                @if($intakeDescription)
                    <p class="mt-2 text-gray-600 dark:text-gray-300">{{ $intakeDescription }}</p>
                @endif
            </div>

            <div class="space-y-4">
                {{-- Card 1: Neue Erhebung starten --}}
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-2xl rounded-2xl p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Neue Erhebung starten</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        Starten Sie eine neue Erhebung. Sie erhalten einen Token, mit dem Sie spaeter fortfahren koennen.
                    </p>
                    <button
                        wire:click="startNew"
                        wire:loading.attr="disabled"
                        class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors"
                    >
                        <span wire:loading.remove wire:target="startNew">Neue Erhebung starten</span>
                        <span wire:loading wire:target="startNew" class="inline-flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Wird erstellt...
                        </span>
                    </button>
                </div>

                {{-- Card 2: Mit Token fortfahren --}}
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-2xl rounded-2xl p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Mit Token fortfahren</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        Sie haben bereits begonnen? Geben Sie Ihren Token ein, um fortzufahren.
                    </p>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            wire:model="resumeToken"
                            wire:keydown.enter="resumeSession"
                            placeholder="XXXX-XXXX"
                            maxlength="9"
                            class="flex-1 px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg font-mono uppercase tracking-widest text-center text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                        />
                        <button
                            wire:click="resumeSession"
                            wire:loading.attr="disabled"
                            class="px-4 py-2.5 bg-gray-600 hover:bg-gray-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors"
                        >
                            <span wire:loading.remove wire:target="resumeSession">Fortfahren</span>
                            <span wire:loading wire:target="resumeSession" class="inline-flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Laden...
                            </span>
                        </button>
                    </div>
                    @if($resumeError)
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $resumeError }}</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
