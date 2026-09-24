{{--
    Kopfzeile der öffentlichen Umfrage (beide Modi).
    Erwartet: $intakeName, $sessionToken; optional $stepLabel (z. B. "3/10"), $progress (0–100), $done (bool)
    Mobil: nur Logo + Titel + Schritt – der Code steht darunter in der Code-Zeile.
--}}
@php $logoUrl = \Platform\Hatch\Support\PublicBranding::logoUrl(); @endphp
<header class="intake-shell-header z-50">
    <div class="intake-header-glass">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3 sm:px-6 sm:py-4 lg:max-w-5xl">
            <div class="flex min-w-0 items-center gap-3">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ \Platform\Hatch\Support\PublicBranding::appName() }}" class="h-8 w-8 flex-shrink-0 rounded-lg object-contain">
                @endif
                <h1 class="truncate text-[15px] font-semibold text-gray-900 sm:text-base">{{ $intakeName }}</h1>
            </div>
            <div class="flex flex-shrink-0 items-center gap-3">
                <button type="button"
                    x-data="{ copied: false }"
                    x-on:click="navigator.clipboard.writeText('{{ $sessionToken }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    title="Code kopieren – damit können Sie später weitermachen"
                    class="hidden items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 transition-colors hover:bg-gray-200 sm:flex">
                    <span class="font-mono text-xs font-semibold tracking-widest text-gray-700">{{ $sessionToken }}</span>
                    <svg x-show="!copied" class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
                @if(!empty($stepLabel))
                    <span class="text-sm font-medium tabular-nums text-gray-500">{{ $stepLabel }}</span>
                @endif
            </div>
        </div>
        @isset($progress)
            <div class="h-1 bg-gray-100">
                <div class="h-full transition-all duration-700 ease-out {{ !empty($done) ? 'intake-progress-done' : 'intake-progress' }}" style="width: {{ $progress }}%"></div>
            </div>
        @endisset
    </div>
</header>
