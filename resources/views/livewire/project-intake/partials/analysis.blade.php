{{--
    Auswertung einer Erhebung (Tab „Auswertung“). Daten: $analysis aus IntakeAnalytics::build().
    Balken sind reine divs mit Breite per style – keine Chart-Bibliothek, druckbar.
--}}
@php
    $a = $analysis;
    $fmt = fn ($v) => $v === null ? '–' : number_format((float) $v, 1, ',', '.');
    $bar = 'background: var(--nx-info, #1971c2); opacity: .8;';
    $period = function ($from, $to) {
        if (!$from) return '–';
        if ($from->isSameDay($to)) return $from->format('d.m.');
        return $from->isSameMonth($to) ? $from->format('d.') . '–' . $to->format('d.m.') : $from->format('d.m.') . '–' . $to->format('d.m.');
    };
    $exportUrl = route('hatch.project-intakes.export', array_filter([
        'projectIntake' => $projectIntake->id,
        'completed_only' => $analysisCompletedOnly ? 1 : null,
        'iso_year' => $this->analysisFilters()['iso_year'],
        'iso_week' => $this->analysisFilters()['iso_week'],
        'since' => $this->analysisFilters()['since'],
    ]));
    $chip = fn (bool $on) => 'rounded-full px-2.5 py-1 transition-colors ' . ($on ? 'bg-[color:var(--nx-active)] font-medium text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]');
@endphp

<div class="space-y-5">

    {{-- Filter --}}
    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs">
        <div class="flex items-center gap-1">
            @foreach(['all' => 'Gesamter Zeitraum', '30' => 'Letzte 30 Tage', '7' => 'Letzte 7 Tage'] as $val => $label)
                <button type="button" wire:click="$set('analysisPeriod', '{{ $val }}')" class="{{ $chip($analysisPeriod === (string) $val) }}">{{ $label }}</button>
            @endforeach
        </div>
        @if(count($analysisWeeks) > 0)
            <select wire:model.live="analysisWeek" class="h-7 rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-2 text-xs text-[color:var(--nx-text)]">
                <option value="">Alle Kalenderwochen</option>
                @foreach($analysisWeeks as $w)
                    <option value="{{ $w['year'] }}-{{ $w['week'] }}">KW {{ $w['week'] }}/{{ $w['year'] }} · {{ $w['count'] }}</option>
                @endforeach
            </select>
        @endif
        <x-nx-input-checkbox wire:model.live="analysisCompletedOnly" label="nur vollständig ausgefüllte" />
        <div class="ml-auto flex items-center gap-2">
            <span wire:loading.delay class="text-[color:var(--nx-faint)]">Rechnet…</span>
            <x-nx-button :href="$exportUrl" title="Alle Antworten mit den gewählten Filtern als Tabelle für Excel">
                @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                <span>CSV-Export</span>
            </x-nx-button>
        </div>
    </div>

    {{-- Kennzahlen --}}
    <x-nx-stat-grid>
        <x-nx-stat label="Antworten" :value="(string) $a['total']" :hint="$a['last_at'] ? 'letzte ' . $a['last_at']->diffForHumans() : 'noch keine'"
            icon="heroicon-o-chat-bubble-left-right" accent="var(--nx-info)" />
        <x-nx-stat label="Vollständig" :value="(string) $a['completed']" :hint="$a['completion_rate'] . ' % der Antworten'"
            icon="heroicon-o-check-circle" :accent="$a['completed'] > 0 ? 'var(--nx-success)' : 'var(--nx-muted)'" />
        @if($a['headline'])
            <x-nx-stat :label="$a['headline']['label']"
                :value="$a['headline']['type'] === 'nps' ? (string) $a['headline']['nps'] : $fmt($a['headline']['avg']) . ' / ' . $a['headline']['max']"
                :hint="$a['headline']['type'] === 'nps' ? 'Net Promoter Score' : 'Durchschnitt'"
                icon="heroicon-o-star" accent="#f59e0b" />
        @endif
        <x-nx-stat label="Zeitraum" :value="$period($a['first_at'], $a['last_at'])"
            :hint="$a['first_at'] ? $a['first_at']->format('Y') : 'keine Antworten'" icon="heroicon-o-calendar" accent="var(--nx-muted)" />
    </x-nx-stat-grid>

    @if($a['total'] === 0)
        <x-nx-card>
            <x-nx-empty icon="heroicon-o-chart-bar">
                Für diese Auswahl gibt es noch keine Antworten
                <x-slot name="action"><span class="text-xs text-[color:var(--nx-faint)]">Sobald jemand die Umfrage ausfüllt, erscheint hier die Auswertung je Frage.</span></x-slot>
            </x-nx-empty>
        </x-nx-card>
    @else
        @foreach($a['groups'] as $gi => $group)
            <x-nx-card wire:key="an-{{ $gi }}">
                <div class="flex items-start gap-3">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)] text-xs font-semibold tabular-nums text-[color:var(--nx-muted)]">{{ $gi + 1 }}</div>
                    <div class="min-w-0 flex-1 space-y-4">
                        <div>
                            <div class="text-sm font-semibold text-[color:var(--nx-text)]">{{ $group['title'] }}</div>
                            @if($group['description'])<div class="text-xs text-[color:var(--nx-faint)]">{{ $group['description'] }}</div>@endif
                        </div>

                        @foreach($group['fields'] as $field)
                            @php $s = $field['stats']; $bid = $field['block']->id; @endphp
                            <div wire:key="an-field-{{ $bid }}" class="space-y-2">
                                <div class="flex items-center justify-between gap-2 text-xs">
                                    <span class="font-medium text-[color:var(--nx-text)]">{{ $field['label'] }}</span>
                                    <span class="tabular-nums text-[color:var(--nx-faint)]">{{ $s['answered'] }} von {{ $s['sessions'] }} beantwortet</span>
                                </div>

                                @switch($s['kind'])
                                    @case('numeric')
                                        <div class="flex flex-wrap items-end gap-6">
                                            <div>
                                                <div class="text-3xl font-semibold tabular-nums text-[color:var(--nx-text)]">
                                                    {{ $s['type'] === 'nps' ? ($s['nps'] ?? '–') : $fmt($s['avg']) }}
                                                    @if($s['type'] !== 'nps' && $s['scale_max'])<span class="text-base font-normal text-[color:var(--nx-faint)]">/ {{ $s['scale_max'] }}</span>@endif
                                                    @if($s['unit'] && $s['type'] !== 'nps')<span class="text-base font-normal text-[color:var(--nx-faint)]">{{ $s['unit'] }}</span>@endif
                                                </div>
                                                <div class="text-xs text-[color:var(--nx-faint)]">
                                                    @if($s['type'] === 'nps')
                                                        NPS · {{ $s['promoters'] ?? 0 }} Förderer, {{ $s['detractors'] ?? 0 }} Kritiker · Ø {{ $fmt($s['avg']) }}
                                                    @elseif($s['type'] === 'rating')
                                                        Durchschnitt in Sternen
                                                    @elseif(empty($s['distribution']))
                                                        Durchschnitt · min {{ $s['min_value'] ?? '–' }} · max {{ $s['max_value'] ?? '–' }}
                                                    @else
                                                        Durchschnitt
                                                    @endif
                                                </div>
                                            </div>
                                            @if(!empty($s['distribution']))
                                                @php $maxCount = max(1, max($s['distribution'])); @endphp
                                                <div class="flex-1 space-y-1" style="min-width:16rem">
                                                    @foreach(array_reverse($s['distribution'], true) as $value => $count)
                                                        <div class="flex items-center gap-2 text-xs">
                                                            <span class="w-8 shrink-0 text-right tabular-nums text-[color:var(--nx-muted)]">{{ $s['type'] === 'rating' ? $value . '★' : $value }}</span>
                                                            <div class="h-3 flex-1 rounded-full bg-[color:var(--nx-accent-soft)]">
                                                                <div class="h-3 rounded-full" style="{{ $bar }} width: {{ $count / $maxCount * 100 }}%"></div>
                                                            </div>
                                                            <span class="w-14 shrink-0 tabular-nums text-[color:var(--nx-faint)]">{{ $count }} · {{ $s['answered'] ? round($count / $s['answered'] * 100) : 0 }} %</span>
                                                        </div>
                                                    @endforeach
                                                    @if($s['min_label'] || $s['max_label'])
                                                        <div class="flex justify-between pl-10 pr-16 text-[11px] text-[color:var(--nx-faint)]"><span>{{ $s['scale_min'] }} = {{ $s['min_label'] }}</span><span>{{ $s['scale_max'] }} = {{ $s['max_label'] }}</span></div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        @break

                                    @case('choice')
                                    @case('multi')
                                        <div class="space-y-1.5">
                                            @foreach($s['options'] as $opt)
                                                <div class="flex items-center gap-2 text-sm">
                                                    <span class="w-48 shrink-0 truncate text-[color:var(--nx-text)]" title="{{ $opt['label'] }}">{{ $opt['label'] }}</span>
                                                    <div class="h-3 flex-1 rounded-full bg-[color:var(--nx-accent-soft)]">
                                                        <div class="h-3 rounded-full" style="{{ $bar }} width: {{ $opt['percent'] }}%"></div>
                                                    </div>
                                                    <span class="w-20 shrink-0 text-right text-xs tabular-nums text-[color:var(--nx-muted)]">{{ $opt['percent'] }} % · {{ $opt['count'] }}</span>
                                                </div>
                                            @endforeach
                                            @if($s['kind'] === 'multi')<p class="text-[11px] text-[color:var(--nx-faint)]">Mehrfachauswahl – Prozent bezogen auf alle, die geantwortet haben.</p>@endif
                                        </div>
                                        @break

                                    @case('matrix')
                                        @php $range = max(1, $s['scale_max'] - $s['scale_min']); $lastGroup = null; @endphp
                                        <div class="space-y-1.5">
                                            @foreach($s['items'] as $ii => $item)
                                                @if(($item['group'] ?? null) && $item['group'] !== $lastGroup)
                                                    <div class="pt-1 text-xs font-medium text-[color:var(--nx-faint)]">{{ $item['group'] }}</div>
                                                    @php $lastGroup = $item['group']; @endphp
                                                @endif
                                                <div class="flex items-center gap-2 text-sm">
                                                    <span class="w-48 shrink-0 truncate text-[color:var(--nx-text)]" title="{{ $item['label'] }}">{{ $item['label'] }}</span>
                                                    <div class="h-3 flex-1 rounded-full bg-[color:var(--nx-accent-soft)]">
                                                        @if($item['avg'] !== null)
                                                            <div class="h-3 rounded-full" style="{{ $ii === $s['lowest'] && count($s['items']) > 1 ? 'background:#f59e0b;' : $bar }} width: {{ max(3, ($item['avg'] - $s['scale_min']) / $range * 100) }}%"></div>
                                                        @endif
                                                    </div>
                                                    <span class="w-20 shrink-0 text-right text-xs tabular-nums text-[color:var(--nx-muted)]">Ø {{ $fmt($item['avg']) }} · {{ $item['count'] }}</span>
                                                </div>
                                            @endforeach
                                            <div class="flex flex-wrap justify-between gap-2 pt-1 text-[11px] text-[color:var(--nx-faint)]">
                                                <span>Skala {{ $s['scale_min'] }}{{ $s['min_label'] ? ' = ' . $s['min_label'] : '' }} bis {{ $s['scale_max'] }}{{ $s['max_label'] ? ' = ' . $s['max_label'] : '' }}</span>
                                                @if($s['lowest'] !== null && count($s['items']) > 1)
                                                    <span><span class="inline-block h-2 w-2 rounded-full align-middle" style="background:#f59e0b"></span> schwächster Punkt: {{ $s['items'][$s['lowest']]['label'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        @break

                                    @case('ranking')
                                        <ol class="space-y-1 text-sm">
                                            @foreach($s['options'] as $ri => $opt)
                                                <li class="flex items-center gap-2">
                                                    <span class="w-5 text-right text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $ri + 1 }}.</span>
                                                    <span class="flex-1 text-[color:var(--nx-text)]">{{ $opt['label'] }}</span>
                                                    <span class="text-xs tabular-nums text-[color:var(--nx-muted)]">Ø Platz {{ $fmt($opt['avg_rank']) }}</span>
                                                </li>
                                            @endforeach
                                        </ol>
                                        @break

                                    @case('text')
                                        @php
                                            $open = $expandedTexts[$bid] ?? false;
                                            $entries = $open ? $s['entries'] : array_slice($s['entries'], 0, 5);
                                        @endphp
                                        @if(empty($s['entries']))
                                            <p class="text-xs text-[color:var(--nx-faint)]">Keine Texte.</p>
                                        @else
                                            <div class="divide-y divide-[color:var(--nx-line)] rounded-[8px] border border-[color:var(--nx-line)]">
                                                @foreach($entries as $e)
                                                    <div class="flex items-start justify-between gap-3 px-3 py-2">
                                                        <p class="whitespace-pre-line text-sm text-[color:var(--nx-text)]">{{ $e['text'] }}</p>
                                                        @if($e['session_id'])
                                                            <a href="{{ route('hatch.intake-sessions.show', $e['session_id']) }}" wire:navigate class="shrink-0 text-xs tabular-nums text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)]" title="Antwort öffnen">{{ $e['at']?->format('d.m. H:i') }}</a>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                            @if(count($s['entries']) > 5)
                                                <button type="button" wire:click="toggleTexts({{ $bid }})" class="text-xs text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)]">
                                                    {{ $open ? 'Weniger anzeigen' : 'Alle ' . count($s['entries']) . ' anzeigen' }}
                                                </button>
                                            @endif
                                        @endif
                                        @break

                                    @default
                                        <p class="text-xs text-[color:var(--nx-faint)]">{{ $s['answered'] }} Antworten – Details im CSV-Export.</p>
                                @endswitch
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-nx-card>
        @endforeach
    @endif
</div>
