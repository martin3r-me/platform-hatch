<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Session: {{ $intakeSession->session_token }}">
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Session-Details" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <x-ui-button variant="secondary" size="sm" :href="route('hatch.project-intakes.show', $intakeSession->projectIntake)" wire:navigate class="w-full">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            Zurück zur Erhebung
                        </span>
                    </x-ui-button>
                </div>

                {{-- Token --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Session</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="block text-xs text-[var(--ui-muted)]">Token</label>
                            <div class="font-mono text-[var(--ui-secondary)]">{{ $intakeSession->session_token }}</div>
                        </div>
                        <div>
                            <label class="block text-xs text-[var(--ui-muted)]">Status</label>
                            <div class="mt-1">
                                <x-ui-badge
                                    :variant="$intakeSession->status === 'completed' ? 'success' : 'warning'"
                                    size="sm"
                                >
                                    {{ $intakeSession->status === 'completed' ? 'Abgeschlossen' : 'Gestartet' }}
                                </x-ui-badge>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Respondent --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Respondent</h3>
                    <div class="space-y-2 text-sm">
                        @if($intakeSession->respondent_name || $intakeSession->respondent_email)
                            @if($intakeSession->respondent_name)
                                <div>
                                    <label class="block text-xs text-[var(--ui-muted)]">Name</label>
                                    <div class="text-[var(--ui-secondary)]">{{ $intakeSession->respondent_name }}</div>
                                </div>
                            @endif
                            @if($intakeSession->respondent_email)
                                <div>
                                    <label class="block text-xs text-[var(--ui-muted)]">E-Mail</label>
                                    <div class="text-[var(--ui-secondary)]">{{ $intakeSession->respondent_email }}</div>
                                </div>
                            @endif
                        @else
                            <p class="text-[var(--ui-muted)]">Anonym</p>
                        @endif
                    </div>
                </div>

                {{-- Zeitstempel --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Zeitstempel</h3>
                    <div class="space-y-1 text-sm text-[var(--ui-muted)]">
                        @if($intakeSession->started_at)
                            <div><strong>Gestartet:</strong> {{ $intakeSession->started_at->format('d.m.Y H:i') }}</div>
                        @endif
                        @if($intakeSession->completed_at)
                            <div><strong>Abgeschlossen:</strong> {{ $intakeSession->completed_at->format('d.m.Y H:i') }}</div>
                        @endif
                    </div>
                </div>

                {{-- Fortschritt --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Fortschritt</h3>
                    <div class="text-sm text-[var(--ui-secondary)]">
                        Schritt {{ $intakeSession->current_step ?? 0 }} von {{ $totalBlocks }}
                    </div>
                </div>

                {{-- Metadata --}}
                @if($intakeSession->metadata)
                    <div>
                        <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Metadata</h3>
                        <div class="space-y-2 text-sm">
                            @if(!empty($intakeSession->metadata['ip']))
                                <div>
                                    <label class="block text-xs text-[var(--ui-muted)]">IP</label>
                                    <div class="font-mono text-xs text-[var(--ui-secondary)]">{{ $intakeSession->metadata['ip'] }}</div>
                                </div>
                            @endif
                            @if(!empty($intakeSession->metadata['user_agent']))
                                <div>
                                    <label class="block text-xs text-[var(--ui-muted)]">User-Agent</label>
                                    <div class="text-xs text-[var(--ui-secondary)] break-all">{{ $intakeSession->metadata['user_agent'] }}</div>
                                </div>
                            @endif
                            @if(!empty($intakeSession->metadata['referrer']))
                                <div>
                                    <label class="block text-xs text-[var(--ui-muted)]">Referrer</label>
                                    <div class="text-xs text-[var(--ui-secondary)] break-all">{{ $intakeSession->metadata['referrer'] }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Erhebung" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                @if($intakeSession->projectIntake)
                    <div class="space-y-2 text-sm">
                        <div>
                            <label class="block text-xs text-[var(--ui-muted)]">Erhebung</label>
                            <div class="text-[var(--ui-secondary)]">{{ $intakeSession->projectIntake->name ?? '–' }}</div>
                        </div>
                        @if($intakeSession->projectIntake->projectTemplate)
                            <div>
                                <label class="block text-xs text-[var(--ui-muted)]">Template</label>
                                <div class="text-[var(--ui-secondary)]">{{ $intakeSession->projectIntake->projectTemplate->name }}</div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        {{-- Header --}}
        <div class="flex items-center gap-4 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 mb-6">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-[var(--ui-secondary)]">Session:</span>
                <span class="font-mono text-sm text-[var(--ui-secondary)]">{{ $intakeSession->session_token }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-[var(--ui-secondary)]">Status:</span>
                <x-ui-badge
                    :variant="$intakeSession->status === 'completed' ? 'success' : 'warning'"
                    size="sm"
                >
                    {{ $intakeSession->status === 'completed' ? 'Abgeschlossen' : 'Gestartet' }}
                </x-ui-badge>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-[var(--ui-secondary)]">Fortschritt:</span>
                <span class="text-sm text-[var(--ui-secondary)]">{{ $intakeSession->current_step ?? 0 }} / {{ $totalBlocks }}</span>
            </div>
        </div>

        {{-- Blocks mit Antworten --}}
        <div class="space-y-4">
            @forelse($blocks as $index => $block)
                <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)]/60 rounded-lg overflow-hidden">
                    {{-- Block Header --}}
                    <div class="flex items-center gap-3 p-4 border-b border-[var(--ui-border)]/40">
                        <div class="flex items-center justify-center w-7 h-7 rounded-full bg-[var(--ui-muted-5)] text-xs font-bold text-[var(--ui-muted)]">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-grow">
                            <div class="font-semibold text-[var(--ui-secondary)]">
                                {{ $block['name'] }}
                                @if($block['is_required'])
                                    <span class="text-red-500">*</span>
                                @endif
                            </div>
                            @if($block['description'])
                                <div class="text-sm text-[var(--ui-muted)]">{{ $block['description'] }}</div>
                            @endif
                        </div>
                        <x-ui-badge variant="secondary" size="xs">{{ $block['type_label'] }}</x-ui-badge>
                    </div>

                    {{-- Antwort --}}
                    <div class="p-4">
                        @if($block['answer'] !== null && $block['answer'] !== '' && $block['answer'] !== [])
                            @switch($block['type'])
                                @case('boolean')
                                    @php
                                        $boolVal = filter_var($block['answer'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                                        $config = $block['logic_config'] ?? [];
                                    @endphp
                                    <x-ui-badge
                                        :variant="$boolVal ? 'success' : 'danger'"
                                        size="sm"
                                    >
                                        {{ $boolVal ? ($config['true_label'] ?? 'Ja') : ($config['false_label'] ?? 'Nein') }}
                                    </x-ui-badge>
                                    @break

                                @case('multi_select')
                                    @php
                                        $selected = is_array($block['answer']) ? $block['answer'] : [$block['answer']];
                                    @endphp
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($selected as $val)
                                            <x-ui-badge variant="primary" size="sm">{{ $val }}</x-ui-badge>
                                        @endforeach
                                    </div>
                                    @break

                                @case('select')
                                    <x-ui-badge variant="primary" size="sm">{{ $block['answer'] }}</x-ui-badge>
                                    @break

                                @case('scale')
                                    @php
                                        $config = $block['logic_config'] ?? [];
                                        $min = $config['min'] ?? 1;
                                        $max = $config['max'] ?? 10;
                                        $value = intval($block['answer']);
                                        $pct = $max > $min ? (($value - $min) / ($max - $min)) * 100 : 0;
                                    @endphp
                                    <div class="flex items-center gap-3">
                                        <span class="font-bold text-[var(--ui-secondary)]">{{ $value }}</span>
                                        <div class="flex-grow bg-[var(--ui-muted-5)] rounded-full h-2 max-w-xs">
                                            <div class="bg-[var(--ui-primary)] h-2 rounded-full" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="text-xs text-[var(--ui-muted)]">{{ $min }} – {{ $max }}</span>
                                    </div>
                                    @break

                                @case('rating')
                                    @php
                                        $config = $block['logic_config'] ?? [];
                                        $maxStars = $config['max'] ?? 5;
                                        $value = intval($block['answer']);
                                    @endphp
                                    <div class="flex items-center gap-1">
                                        @for($i = 1; $i <= $maxStars; $i++)
                                            @if($i <= $value)
                                                <x-heroicon-s-star class="w-5 h-5 text-amber-400" />
                                            @else
                                                <x-heroicon-o-star class="w-5 h-5 text-[var(--ui-muted)]" />
                                            @endif
                                        @endfor
                                        <span class="ml-2 text-sm text-[var(--ui-muted)]">({{ $value }}/{{ $maxStars }})</span>
                                    </div>
                                    @break

                                @case('number')
                                    @php
                                        $config = $block['logic_config'] ?? [];
                                    @endphp
                                    <span class="text-[var(--ui-secondary)]">
                                        {{ $block['answer'] }}
                                        @if(!empty($config['unit']))
                                            <span class="text-[var(--ui-muted)]">{{ $config['unit'] }}</span>
                                        @endif
                                    </span>
                                    @break

                                @case('date')
                                    <span class="text-[var(--ui-secondary)]">{{ $block['answer'] }}</span>
                                    @break

                                @case('file')
                                    <span class="text-[var(--ui-muted)] italic">Datei-Upload wird nicht unterstützt</span>
                                    @break

                                @default
                                    {{-- text, long_text, email, phone, url, location, custom --}}
                                    <div class="text-[var(--ui-secondary)] whitespace-pre-wrap">{{ is_array($block['answer']) ? json_encode($block['answer'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $block['answer'] }}</div>
                            @endswitch
                        @else
                            <span class="text-sm text-[var(--ui-muted)] italic">Keine Antwort</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-[var(--ui-muted)]">
                    <x-heroicon-o-document-text class="w-10 h-10 mx-auto mb-2" />
                    <p>Keine Blocks in dieser Erhebung vorhanden</p>
                </div>
            @endforelse
        </div>
    </x-ui-page-container>
</x-ui-page>
