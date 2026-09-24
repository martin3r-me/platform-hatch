{{--
    Dank-Karte nach dem Abschließen (beide Modi).
    Erwartet: $completionMessage (Text aus der Vorlage oder null), $respondentName (optional)
--}}
<div class="intake-card mb-6 px-6 py-8 text-center sm:px-10 sm:py-10">
    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50">
        <svg class="h-8 w-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h2 class="text-2xl font-bold text-gray-900">Vielen Dank{{ !empty($respondentName) ? ', ' . $respondentName : '' }}!</h2>
    <p class="mx-auto mt-3 max-w-md whitespace-pre-line leading-relaxed text-gray-600">{{ $completionMessage ?: 'Ihre Antworten sind angekommen. Ihr Feedback hilft uns, noch besser zu werden.' }}</p>
    <p class="mt-6 text-xs text-gray-400">Sie können dieses Fenster jetzt schließen.</p>
</div>
