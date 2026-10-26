{{--
    Vorgemerkte Benachrichtigung (FlashesNotices::flashNotice) auf dieser Seite anzeigen.
    Erst nach der Initialisierung aller Komponenten auslösen – die Benachrichtigungs-
    Komponente steht im Layout weiter unten und würde ein früheres Event verpassen.
--}}
@if($hatchNotice = session('hatch.notice'))
    <div x-data x-init="setTimeout(() => Livewire.dispatch('notifications:store', { payload: {{ \Illuminate\Support\Js::from($hatchNotice) }} }), 50)"></div>
@endif
