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
        @elseif($state === 'notStarted')
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-2xl rounded-2xl p-8 text-center">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Erhebung noch nicht gestartet</h1>
                <p class="text-gray-600 dark:text-gray-300">Die Erhebung <strong>{{ $intakeName }}</strong> wurde noch nicht gestartet. Bitte versuchen Sie es spaeter erneut.</p>
            </div>
        @elseif($state === 'ready')
            {{-- Begrüßung --}}
            <div class="text-center mb-8">
                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Herzlich willkommen zur Umfrage</p>
                <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">„{{ $intakeName }}“</h1>
                @if($intakeDescription)
                    <p class="mt-3 text-gray-600 dark:text-gray-300">{{ $intakeDescription }}</p>
                @endif
            </div>

            <div class="space-y-4">
                {{-- Card 1: Umfrage starten --}}
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-2xl rounded-2xl p-6">
                    <p class="text-gray-700 dark:text-gray-200 mb-4">
                        Vielen Dank, dass Sie sich einen Moment Zeit nehmen. Ihr Feedback hilft uns, besser zu werden.
                        Klicken Sie auf <strong>„Umfrage starten“</strong>, um zu beginnen.
                    </p>
                    <button
                        wire:click="startNew"
                        wire:loading.attr="disabled"
                        class="w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold rounded-lg transition-colors"
                    >
                        <span wire:loading.remove wire:target="startNew">Umfrage starten</span>
                        <span wire:loading.inline-flex wire:target="startNew" class="items-center justify-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Wird gestartet...
                        </span>
                    </button>
                    <p class="mt-4 flex items-start gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4 flex-shrink-0 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Ihre Antworten werden vertraulich behandelt. Nach dem Start erhalten Sie einen Code, mit dem Sie später an derselben Stelle weitermachen können.</span>
                    </p>
                </div>

                {{-- Card 2: Mit Token fortfahren --}}
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-2xl rounded-2xl p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Schon begonnen?</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        Geben Sie Ihren Code ein, um dort weiterzumachen, wo Sie aufgehört haben.
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
                            <span wire:loading.inline-flex wire:target="resumeSession" class="items-center justify-center gap-2">
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
