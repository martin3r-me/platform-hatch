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
                @if($totalBlocks > 0)
                    <span class="text-sm text-gray-500 dark:text-gray-400 flex-shrink-0 ml-4">
                        Schritt {{ $currentStep + 1 }} von {{ $totalBlocks }}
                    </span>
                @endif
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

        {{-- Content --}}
        <main class="max-w-4xl mx-auto px-4 py-8">
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

            {{-- Aktiver Block (Placeholder) --}}
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

                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">
                            Der interaktive Erhebungsmodus wird hier integriert.
                        </p>
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
