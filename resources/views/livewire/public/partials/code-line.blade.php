{{-- Code zum späteren Weitermachen – antippbar zum Kopieren (mobil der einzige Ort dafür). --}}
<div class="mb-4 flex justify-center">
    <button type="button"
        x-data="{ copied: false }"
        x-on:click="navigator.clipboard.writeText('{{ $sessionToken }}'); copied = true; setTimeout(() => copied = false, 2000)"
        title="Mit diesem Code können Sie später an derselben Stelle weitermachen"
        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1 text-xs text-gray-500 transition-colors hover:bg-white/70">
        <span>Ihr Code:</span>
        <span class="font-mono font-semibold tracking-wider text-gray-700">{{ $sessionToken }}</span>
        <span x-show="!copied" class="text-indigo-600">· kopieren</span>
        <span x-show="copied" x-cloak class="text-emerald-600">kopiert ✓</span>
    </button>
</div>
