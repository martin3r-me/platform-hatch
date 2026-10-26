{{--
    Vergleich aller Erhebungen einer Vorlage. Daten: TemplateAnalytics::build().
    Heatmap-Tabellen: Zellfarbe = Abstand zur Skalenmitte (rot darunter, blau darüber,
    Mitte neutral). Der Wert steht immer als Zahl in der Zelle – Farbe allein trägt nichts.
--}}
@php
    $rows = $data['rows'];
    $columns = $data['columns'];
    $fmt = fn (array $row, $value) => $value === null ? '–' : ($row['type'] === 'nps'
        ? ($value > 0 ? '+' : '') . (int) $value
        : number_format((float) $value, 1, ',', '.'));
    $what = fn (array $row) => $row['type'] === 'nps' ? 'NPS' : 'Ø';
    $tint = function (?float $tone) {
        if ($tone === null || abs($tone) < 0.05) return '';
        $pole = $tone > 0 ? '#2a78d6' : '#e34948';
        return 'background: color-mix(in oklab, ' . $pole . ' ' . (int) round(abs($tone) * 45) . '%, transparent);';
    };
    $chip = fn (bool $on) => 'rounded-full px-2.5 py-1 transition-colors ' . ($on ? 'bg-[color:var(--nx-active)] font-medium text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]');
    $th = 'whitespace-nowrap px-3 py-2 text-left text-xs font-medium text-[color:var(--nx-muted)]';
    $stickyCell = 'sticky left-0 z-10 bg-[color:var(--nx-surface)]';
    $cell = 'whitespace-nowrap px-3 py-2 text-right text-sm tabular-nums text-[color:var(--nx-text)]';
    $first = $rows ? reset($rows) : null;
    $trend = $trendKey ? $rows[$trendKey] : null;
@endphp

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Formulare', 'href' => route('hatch.dashboard'), 'icon' => 'rocket-launch'],
            ['label' => 'Vorlagen', 'href' => route('hatch.templates.index')],
            ['label' => $template->name, 'href' => route('hatch.templates.show', $template)],
            ['label' => 'Vergleich'],
        ]">
            <x-nx-button :href="route('hatch.templates.show', $template)" wire:navigate>
                @svg('heroicon-o-pencil-square', 'w-4 h-4')
                <span>Vorlage bearbeiten</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
    <div class="space-y-6">

        {{-- Filter --}}
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs">
            <div class="flex items-center gap-1">
                @foreach(['all' => 'Gesamter Zeitraum', '12' => 'Letzte 12 Wochen', '4' => 'Letzte 4 Wochen'] as $val => $label)
                    <button type="button" wire:click="$set('period', '{{ $val }}')" class="{{ $chip($period === (string) $val) }}">{{ $label }}</button>
                @endforeach
            </div>
            <x-nx-input-checkbox wire:model.live="completedOnly" label="nur vollständig ausgefüllte" />
            <span wire:loading.delay class="ml-auto text-[color:var(--nx-faint)]">Rechnet…</span>
        </div>

        {{-- Kennzahlen --}}
        <x-nx-stat-grid>
            <x-nx-stat label="Antworten" :value="(string) $data['total']" hint="über alle Erhebungen"
                icon="heroicon-o-chat-bubble-left-right" accent="var(--nx-info)" />
            <x-nx-stat label="Erhebungen" :value="(string) count($columns)"
                :hint="$data['intakes_without_answers'] > 0 ? 'mit Antworten, ' . $data['intakes_without_answers'] . ' ohne' : 'mit Antworten'"
                icon="heroicon-o-map-pin" accent="var(--nx-muted)" />
            <x-nx-stat label="Abschlussquote" :value="$data['completion_rate'] . ' %'" hint="bis zum Ende ausgefüllt"
                icon="heroicon-o-check-circle" :accent="$data['completion_rate'] > 0 ? 'var(--nx-success)' : 'var(--nx-muted)'" />
            @if($first && $first['cells']['all']['value'] !== null)
                <x-nx-stat :label="$first['label']"
                    :value="$fmt($first, $first['cells']['all']['value']) . ($first['type'] === 'nps' ? '' : ' / ' . (float) $first['max'])"
                    :hint="$first['type'] === 'nps' ? 'Net Promoter Score gesamt' : 'Durchschnitt gesamt'"
                    icon="heroicon-o-star" accent="#f59e0b" />
            @endif
        </x-nx-stat-grid>

        @if($data['total'] === 0)
            <x-nx-empty icon="heroicon-o-chart-bar">
                Noch keine Antworten
                <x-slot name="action">
                    <span class="text-xs text-[color:var(--nx-faint)]">
                        {{ $data['intake_count'] === 0 ? 'Diese Vorlage wird noch von keiner Erhebung genutzt.' : 'Im gewählten Zeitraum hat noch niemand eine der ' . $data['intake_count'] . ' Erhebungen beantwortet.' }}
                    </span>
                </x-slot>
            </x-nx-empty>
        @elseif(!$rows)
            <x-nx-callout variant="neutral" icon="heroicon-o-information-circle" title="Nichts zu vergleichen">
                Die Vorlage enthält keine Bewertungsfragen (Sterne, Skala, Schieberegler, NPS oder Matrix). Nur diese lassen sich als Durchschnitt nebeneinanderstellen.
            </x-nx-callout>
        @else

            {{-- Erhebungen nebeneinander --}}
            <x-nx-card flush>
                <div class="flex flex-wrap items-baseline gap-x-2 border-b border-[color:var(--nx-line)] px-4 py-3">
                    @svg('heroicon-o-table-cells', 'w-4 h-4 self-center text-[color:var(--nx-muted)]')
                    <h2 class="m-0 text-xs font-semibold text-[color:var(--nx-muted)]">Erhebungen im Vergleich</h2>
                    <span class="text-xs text-[color:var(--nx-faint)]">Durchschnitt je Frage, NPS als Score</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-[color:var(--nx-line)]">
                                <th class="{{ $th }} {{ $stickyCell }}">Frage</th>
                                <th class="{{ $th }} text-right">Gesamt</th>
                                @foreach($columns as $col)
                                    <th class="{{ $th }} max-w-[11rem] text-right align-bottom">
                                        <a href="{{ route('hatch.project-intakes.show', ['projectIntake' => $col['id'], 'ansicht' => 'analysis']) }}" wire:navigate
                                           class="block truncate text-[color:var(--nx-text)] hover:underline" title="{{ $col['name'] }} – Auswertung öffnen">{{ $col['short'] }}</a>
                                        @if($col['event_reference'])
                                            <span class="block truncate font-normal text-[color:var(--nx-faint)]">{{ $col['event_reference'] }}</span>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-[color:var(--nx-line)]">
                                <td class="{{ $stickyCell }} whitespace-nowrap px-3 py-2 text-xs text-[color:var(--nx-muted)]">Antworten</td>
                                <td class="{{ $cell }} text-[color:var(--nx-muted)]">{{ $data['total'] }}</td>
                                @foreach($columns as $col)
                                    <td class="{{ $cell }} text-[color:var(--nx-muted)]" title="{{ $col['completion_rate'] }} % vollständig">{{ $col['total'] }}</td>
                                @endforeach
                            </tr>
                            @php $lastGroup = null; @endphp
                            @foreach($rows as $key => $row)
                                @if($row['group'] && $row['group'] !== $lastGroup)
                                    <tr>
                                        <td colspan="{{ count($columns) + 2 }}" class="px-3 pt-3 pb-1 text-xs font-medium text-[color:var(--nx-faint)]">
                                            <span class="sticky left-3">{{ $row['group'] }}</span>
                                        </td>
                                    </tr>
                                @endif
                                @php $lastGroup = $row['group']; @endphp
                                <tr wire:key="cmp-{{ $key }}" class="border-b border-[color:var(--nx-line)] last:border-0">
                                    <td class="{{ $stickyCell }} max-w-[18rem] truncate px-3 py-2 text-sm text-[color:var(--nx-text)] {{ $row['group'] ? 'pl-6' : '' }}" title="{{ $row['label'] }}">{{ $row['label'] }}</td>
                                    @foreach(array_merge(['all'], array_column($columns, 'id')) as $bucket)
                                        @php $c = $row['cells'][$bucket]; @endphp
                                        <td class="{{ $cell }} {{ $bucket === 'all' ? 'font-semibold' : '' }}" style="{{ $tint($c['tone']) }}"
                                            title="{{ $what($row) }} {{ $fmt($row, $c['value']) }} aus {{ $c['n'] }} {{ $c['n'] === 1 ? 'Antwort' : 'Antworten' }}">{{ $fmt($row, $c['value']) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-[color:var(--nx-line)] px-4 py-2.5 text-xs text-[color:var(--nx-faint)]">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="inline-flex overflow-hidden rounded-sm">
                            @foreach([-1, -0.5, 0, 0.5, 1] as $t)
                                <span class="inline-block h-3 w-4" style="{{ $tint($t) ?: 'background: var(--nx-hover);' }}"></span>
                            @endforeach
                        </span>
                        unter der Skalenmitte · Mitte · darüber
                    </span>
                    <span>Zahl in der Zelle = Durchschnitt; Maus darüber zeigt die Anzahl Antworten.</span>
                </div>
            </x-nx-card>

            {{-- Verlauf nach Kalenderwoche --}}
            <x-nx-card flush>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-[color:var(--nx-line)] px-4 py-3">
                    @svg('heroicon-o-arrow-trending-up', 'w-4 h-4 text-[color:var(--nx-muted)]')
                    <h2 class="m-0 text-xs font-semibold text-[color:var(--nx-muted)]">Verlauf nach Kalenderwoche</h2>
                    <select wire:model.live="trendMetric" class="ml-auto h-7 max-w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-2 text-xs text-[color:var(--nx-text)]">
                        @foreach($rows as $key => $row)
                            <option value="{{ $key }}" @selected($key === $trendKey)>{{ $row['group'] ? $row['group'] . ' – ' : '' }}{{ $row['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                @if(count($data['weeks']) === 0)
                    <p class="m-0 px-4 py-6 text-sm text-[color:var(--nx-faint)]">Keine Antworten mit Kalenderwoche im gewählten Zeitraum.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b border-[color:var(--nx-line)]">
                                    <th class="{{ $th }} {{ $stickyCell }}">{{ $what($trend) }} {{ $trend['label'] }}</th>
                                    @foreach($data['weeks'] as $w)
                                        <th class="{{ $th }} text-right" title="KW {{ (int) substr($w['key'], 5) }}/{{ $w['year'] }} · {{ $data['week_counts'][$w['key']] }} Antworten">{{ $w['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_merge([['id' => 'all', 'name' => 'Gesamt', 'short' => 'Gesamt']], $columns) as $col)
                                    <tr wire:key="trend-{{ $trendKey }}-{{ $col['id'] }}" class="border-b border-[color:var(--nx-line)] last:border-0">
                                        <td class="{{ $stickyCell }} max-w-[18rem] truncate px-3 py-2 text-sm {{ $col['id'] === 'all' ? 'font-semibold' : '' }} text-[color:var(--nx-text)]" title="{{ $col['name'] }}">{{ $col['short'] }}</td>
                                        @foreach($data['weeks'] as $w)
                                            @php $c = $trend['trend'][$col['id']][$w['key']] ?? ['value' => null, 'n' => 0, 'tone' => null]; @endphp
                                            <td class="{{ $cell }} {{ $col['id'] === 'all' ? 'font-semibold' : '' }} {{ $c['value'] === null ? 'text-[color:var(--nx-faint)]' : '' }}" style="{{ $tint($c['tone']) }}"
                                                title="{{ $col['name'] }} · {{ $w['label'] }}: {{ $c['n'] > 0 ? $what($trend) . ' ' . $fmt($trend, $c['value']) . ' aus ' . $c['n'] . ' ' . ($c['n'] === 1 ? 'Antwort' : 'Antworten') : 'keine Antworten' }}">{{ $fmt($trend, $c['value']) }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="m-0 border-t border-[color:var(--nx-line)] px-4 py-2.5 text-xs text-[color:var(--nx-faint)]">
                        Die letzten {{ count($data['weeks']) }} Kalenderwochen mit Antworten. Wenige Antworten in einer Woche schwanken stark – die Anzahl steht im Tooltip.
                    </p>
                @endif
            </x-nx-card>
        @endif

    </div>
    </x-ui-page-container>
</x-ui-page>
