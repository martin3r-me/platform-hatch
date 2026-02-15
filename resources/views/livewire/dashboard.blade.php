<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Hatch Dashboard" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-button variant="secondary" size="sm" :href="route('hatch.block-definitions.index')" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-puzzle-piece','w-4 h-4')
                                BlockDefinitionen
                            </span>
                        </x-ui-button>
                        <x-ui-button variant="secondary" size="sm" :href="route('hatch.templates.index')" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-document-text','w-4 h-4')
                                Templates
                            </span>
                        </x-ui-button>
                        <x-ui-button variant="secondary" size="sm" :href="route('hatch.project-intakes.index')" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-rocket-launch','w-4 h-4')
                                Erhebungen
                            </span>
                        </x-ui-button>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Statistiken</h3>
                    <div class="space-y-3">
                        <div class="py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 flex items-center justify-between">
                            <span class="text-xs text-[var(--ui-muted)]">Templates (aktiv)</span>
                            <span class="text-lg font-bold text-[var(--ui-secondary)]">{{ $activeTemplates }}</span>
                        </div>
                        <div class="py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 flex items-center justify-between">
                            <span class="text-xs text-[var(--ui-muted)]">Block Definitionen</span>
                            <span class="text-lg font-bold text-[var(--ui-secondary)]">{{ $totalBlockDefinitions }}</span>
                        </div>
                        <div class="py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 flex items-center justify-between">
                            <span class="text-xs text-[var(--ui-muted)]">Abschlussrate</span>
                            <span class="text-lg font-bold text-[var(--ui-secondary)]">{{ $completionRate }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 text-sm text-[var(--ui-muted)]">Keine Aktivitäten verfügbar</div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        <!-- Haupt-Statistiken -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <x-ui-dashboard-tile title="Templates" :count="$activeTemplates" icon="document-text" variant="secondary" size="lg" :href="route('hatch.templates.index')" />
            <x-ui-dashboard-tile title="Block Definitionen" :count="$totalBlockDefinitions" icon="puzzle-piece" variant="secondary" size="lg" :href="route('hatch.block-definitions.index')" />
            <x-ui-dashboard-tile title="Erhebungen" :count="$totalIntakes" icon="rocket-launch" variant="secondary" size="lg" :href="route('hatch.project-intakes.index')" />
            <x-ui-dashboard-tile title="Abgeschlossen" :count="$completedIntakes" icon="check-circle" variant="secondary" size="lg" />
        </div>

        <!-- Panels -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-ui-panel title="Erhebungen nach Status">
                @php
                    $statusLabels = [
                        'draft' => ['label' => 'Entwurf', 'variant' => 'secondary'],
                        'published' => ['label' => 'Veröffentlicht', 'variant' => 'success'],
                        'closed' => ['label' => 'Geschlossen', 'variant' => 'warning'],
                    ];
                @endphp
                @if($totalIntakes > 0)
                    <div class="space-y-2">
                        @foreach($statusLabels as $status => $meta)
                            @php $count = $intakesByStatus[$status] ?? 0; @endphp
                            <div class="flex items-center justify-between p-3 rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-surface)]">
                                <x-ui-badge variant="{{ $meta['variant'] }}" size="sm">{{ $meta['label'] }}</x-ui-badge>
                                <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-sm text-[var(--ui-muted)] p-4 text-center">Noch keine Erhebungen vorhanden.</div>
                @endif
            </x-ui-panel>

            <x-ui-panel title="Letzte Erhebungen" subtitle="Top 5">
                <div class="space-y-2">
                    @forelse(($recentIntakes ?? collect())->take(5) as $intake)
                        <a href="{{ route('hatch.project-intakes.show', $intake) }}" wire:navigate
                           class="group flex items-center justify-between p-3 rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-surface)] hover:border-[var(--ui-primary)]/60 hover:bg-[var(--ui-primary-5)] transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/60 flex items-center justify-center text-xs font-semibold text-[var(--ui-secondary)]">
                                    {{ strtoupper(substr($intake->name ?? 'E', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $intake->name }}</div>
                                    <div class="text-xs text-[var(--ui-muted)]">{{ $intake->projectTemplate->name ?? '–' }} · {{ optional($intake->updated_at)->diffForHumans() }}</div>
                                </div>
                            </div>
                            @svg('heroicon-o-arrow-right', 'w-4 h-4 text-[var(--ui-muted)] group-hover:text-[var(--ui-primary)]')
                        </a>
                    @empty
                        <div class="text-sm text-[var(--ui-muted)] p-4 text-center">Noch keine Erhebungen vorhanden.</div>
                    @endforelse
                </div>
            </x-ui-panel>
        </div>
    </x-ui-page-container>
</x-ui-page>
