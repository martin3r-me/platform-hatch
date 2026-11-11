{{-- Ein Erhebungs-Eintrag der Sidebar: Name + Veranstaltung, geschlossene gedämpft --}}
@php
    $intakeName = app(\Platform\Hatch\Support\IntakePlaceholders::class)->render($intake->name, $intake);
    $intakeEvent = trim((string) $intake->event_reference);
    if ($intakeEvent !== '' && mb_stripos($intakeName, $intakeEvent) !== false) {
        $intakeEvent = '';   // steht schon im Namen
    }
    $intakeLabel = $intakeName . ($intakeEvent !== '' ? ' · ' . $intakeEvent : '');
@endphp
<a href="{{ route('hatch.project-intakes.show', ['projectIntake' => $intake]) }}"
   wire:navigate
   title="{{ $intakeLabel }}{{ ($closed ?? false) ? ' (geschlossen)' : '' }}"
   class="flex items-center gap-1.5 py-0.5 pl-3 pr-2 text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition truncate {{ ($closed ?? false) ? 'opacity-60' : '' }}">
    <span class="w-1 h-1 rounded-full flex-shrink-0 bg-[var(--ui-muted)] opacity-40"></span>
    <span class="truncate text-[11px]">{{ $intakeName }}@if($intakeEvent !== '')<span class="text-[var(--ui-muted)]"> · {{ $intakeEvent }}</span>@endif</span>
</a>
