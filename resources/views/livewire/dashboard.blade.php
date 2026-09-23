<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Formulare" icon="heroicon-o-rocket-launch" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Formulare', 'icon' => 'rocket-launch'],
            ['label' => 'Dashboard'],
        ]">
            <x-nx-button :href="route('hatch.templates.index')" wire:navigate>
                @svg('heroicon-o-document-text', 'w-4 h-4')
                <span>Vorlagen</span>
            </x-nx-button>
            <x-nx-button variant="primary" :href="route('hatch.project-intakes.index')" wire:navigate>
                @svg('heroicon-o-rocket-launch', 'w-4 h-4')
                <span>Erhebungen</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" icon="heroicon-o-bolt" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <x-nx-empty icon="heroicon-o-bolt">Keine Aktivitäten verfügbar</x-nx-empty>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
    <div class="space-y-6">

    @php
        $published = $intakesByStatus['published'] ?? 0;
        $drafts = $intakesByStatus['draft'] ?? 0;
        $closed = $intakesByStatus['closed'] ?? 0;
        $sessionRate = $totalSessions > 0 ? round($completedSessions / $totalSessions * 100) : 0;
    @endphp

    {{-- Kennzahlen --}}
    <x-nx-stat-grid>
        <x-nx-stat label="Vorlagen" :value="$activeTemplates . ' / ' . $totalTemplates"
            hint="aktiv" icon="heroicon-o-document-text" accent="var(--nx-accent)"
            :href="route('hatch.templates.index')" wire:navigate />
        <x-nx-stat label="Erhebungen" :value="(string) $totalIntakes"
            :hint="$published . ' live · ' . $drafts . ' Entwurf'"
            icon="heroicon-o-rocket-launch" accent="var(--nx-accent)"
            :href="route('hatch.project-intakes.index')" wire:navigate />
        <x-nx-stat label="Antworten" :value="(string) $totalSessions"
            :hint="$completedSessions . ' vollständig ausgefüllt'"
            icon="heroicon-o-chat-bubble-left-right" accent="var(--nx-info)" />
        <x-nx-stat label="Abschlussquote" :value="$sessionRate . ' %'"
            hint="der begonnenen Antworten"
            icon="heroicon-o-check-circle"
            :accent="$totalSessions > 0 ? 'var(--nx-success)' : 'var(--nx-muted)'" />
    </x-nx-stat-grid>

    {{-- Erhebungen nach Status --}}
    <x-nx-section icon="heroicon-o-signal" title="Erhebungen nach Status">
        <x-nx-stat-grid cols="3">
            <x-nx-stat label="Entwurf" :value="(string) $drafts" icon="heroicon-o-pencil-square" accent="var(--nx-muted)" />
            <x-nx-stat label="Veröffentlicht" :value="(string) $published" icon="heroicon-o-play-circle"
                :accent="$published > 0 ? 'var(--nx-success)' : 'var(--nx-muted)'" />
            <x-nx-stat label="Geschlossen" :value="(string) $closed" icon="heroicon-o-lock-closed"
                :accent="$closed > 0 ? 'var(--nx-warning)' : 'var(--nx-muted)'" />
        </x-nx-stat-grid>
    </x-nx-section>

    {{-- Letzte Erhebungen --}}
    <x-nx-card flush>
        <div class="flex items-center gap-2 border-b border-[color:var(--nx-line)] px-4 py-3">
            @svg('heroicon-o-queue-list', 'w-4 h-4 text-[color:var(--nx-muted)]')
            <h2 class="m-0 text-xs font-semibold text-[color:var(--nx-muted)]">Letzte Erhebungen</h2>
            <span class="text-xs text-[color:var(--nx-faint)]">zuletzt bearbeitet</span>
            <a href="{{ route('hatch.project-intakes.index') }}" wire:navigate class="ml-auto text-xs text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">Alle</a>
        </div>

        @if($recentIntakes->isNotEmpty())
            <div class="divide-y divide-[color:var(--nx-line)]">
                @foreach($recentIntakes as $intake)
                    @php
                        $variant = match($intake->status) {
                            'published' => 'success',
                            'closed' => 'warning',
                            default => 'neutral',
                        };
                    @endphp
                    <x-nx-list-item
                        wire:key="dash-intake-{{ $intake->id }}"
                        icon="heroicon-o-rocket-launch"
                        :title="$intake->display_name"
                        :subtitle="($intake->projectTemplate->name ?? '–') . ' · ' . optional($intake->updated_at)->diffForHumans()"
                        :href="route('hatch.project-intakes.show', $intake)"
                    >
                        <x-slot name="trailing">
                            <span class="text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $intake->sessions_count }} {{ $intake->sessions_count === 1 ? 'Antwort' : 'Antworten' }}</span>
                            <x-nx-badge :variant="$variant">{{ ['draft' => 'Entwurf', 'published' => 'Veröffentlicht', 'closed' => 'Geschlossen'][$intake->status] ?? $intake->status }}</x-nx-badge>
                        </x-slot>
                    </x-nx-list-item>
                @endforeach
            </div>
        @else
            <x-nx-empty icon="heroicon-o-rocket-launch">
                Noch keine Erhebungen
                <x-slot name="action">
                    <span class="text-xs text-[color:var(--nx-faint)]">Lege eine Erhebung aus einer Vorlage an, dann erscheint sie hier.</span>
                </x-slot>
            </x-nx-empty>
        @endif
    </x-nx-card>

    </div>
    </x-ui-page-container>
</x-ui-page>
