<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Formulare', 'href' => route('hatch.dashboard'), 'icon' => 'rocket-launch'],
            ['label' => 'Erhebungen', 'href' => route('hatch.project-intakes.index')],
            ['label' => $projectIntake->name ?? 'Erhebung'],
        ]">
            @if($projectIntake->status === 'draft')
                <x-nx-button variant="primary" wire:click="publishIntake">
                    @svg('heroicon-o-rocket-launch', 'w-4 h-4')
                    <span>Veröffentlichen</span>
                </x-nx-button>
            @endif
            <x-nx-button
                icon
                variant="danger"
                title="Erhebung löschen"
                wire:click="deleteProjectIntake"
                wire:confirm="Erhebung wirklich löschen? Alle zugehörigen Sessions werden ebenfalls gelöscht. Diese Aktion kann nicht rückgängig gemacht werden."
            >
                @svg('heroicon-o-trash', 'w-4 h-4')
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Erhebung" width="w-80" :defaultOpen="true" side="left">
            @php
                $statusVariant = match($projectIntake->status) {
                    'published' => 'success',
                    'closed' => 'warning',
                    default => 'neutral',
                };
                $sessionCount = $projectIntake->sessions()->count();
                $publicUrl = $projectIntake->getPublicUrl();
                $gruppe = 'flex flex-col gap-3 border-t border-[color:var(--nx-line)] p-4';
                $ueberschrift = 'text-xs font-medium tracking-wide text-[color:var(--nx-faint)]';
                $zeile = 'flex items-center justify-between gap-3 text-sm';
                $zeileLabel = 'shrink-0 text-xs text-[color:var(--nx-muted)]';
            @endphp

            {{-- Status + die eine Aktion, die gerade Sinn ergibt --}}
            <div class="flex flex-col gap-3 p-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="{{ $ueberschrift }}">Status</span>
                    <x-nx-badge :variant="$statusVariant" dot>{{ $statuses[$projectIntake->status] ?? $projectIntake->status }}</x-nx-badge>
                </div>
                @if($projectIntake->status === 'draft')
                    <x-nx-button variant="primary" wire:click="publishIntake" class="w-full">
                        @svg('heroicon-o-rocket-launch', 'w-4 h-4')
                        <span>Veröffentlichen</span>
                    </x-nx-button>
                @elseif($projectIntake->status === 'published')
                    <x-nx-button wire:click="closeIntake" class="w-full">
                        @svg('heroicon-o-lock-closed', 'w-4 h-4')
                        <span>Schließen</span>
                    </x-nx-button>
                @elseif($projectIntake->status === 'closed')
                    <div class="grid grid-cols-2 gap-2">
                        <x-nx-button variant="primary" wire:click="reopenIntake">
                            @svg('heroicon-o-arrow-path', 'w-4 h-4')
                            <span>Wieder öffnen</span>
                        </x-nx-button>
                        <x-nx-button wire:click="unpublishIntake">
                            @svg('heroicon-o-pencil', 'w-4 h-4')
                            <span>Entwurf</span>
                        </x-nx-button>
                    </div>
                @endif
            </div>

            {{-- Was Respondenten sehen --}}
            <div class="{{ $gruppe }}">
                <span class="{{ $ueberschrift }}">Öffentliche Anzeige</span>
                <x-nx-input-text
                    name="name"
                    label="Name"
                    wire:model.live.debounce.500ms="name"
                    required
                    :errorKey="'name'"
                />
                <x-nx-input-textarea
                    name="description"
                    label="Beschreibung"
                    hint="optional"
                    wire:model.live.debounce.500ms="description"
                    rows="3"
                    placeholder="z.B. Wie hat Ihnen das Catering gefallen?"
                    :errorKey="'description'"
                />
            </div>

            {{-- Teilen: Link + QR-Code --}}
            <div class="{{ $gruppe }}">
                <div class="flex items-center justify-between gap-2">
                    <span class="{{ $ueberschrift }}">Teilen</span>
                    @if($publicUrl)
                        <span class="text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $sessionCount }} {{ $sessionCount === 1 ? 'Antwort' : 'Antworten' }}</span>
                    @endif
                </div>

                @if($publicUrl)
                    <div x-data="{ kopiert: false, qrOpen: false }" class="flex flex-col gap-2">
                        <div class="flex items-center gap-1 rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] py-1 pl-2.5 pr-1">
                            <span class="min-w-0 flex-1 truncate font-mono text-xs text-[color:var(--nx-muted)]" title="{{ $publicUrl }}">{{ $publicUrl }}</span>
                            <x-nx-button icon variant="ghost" type="button" title="Link kopieren"
                                x-on:click="navigator.clipboard.writeText(@js($publicUrl)).then(() => { kopiert = true; setTimeout(() => kopiert = false, 1500) })">
                                <span x-show="!kopiert">@svg('heroicon-o-clipboard-document', 'w-4 h-4')</span>
                                <span x-show="kopiert" x-cloak style="color: var(--nx-success);">@svg('heroicon-o-check', 'w-4 h-4')</span>
                            </x-nx-button>
                            <x-nx-button icon variant="ghost" :href="$publicUrl" target="_blank" rel="noopener" title="Im neuen Tab öffnen">
                                @svg('heroicon-o-arrow-top-right-on-square', 'w-4 h-4')
                            </x-nx-button>
                        </div>

                        <x-nx-button type="button" class="w-full" x-on:click="qrOpen = !qrOpen">
                            @svg('heroicon-o-qr-code', 'w-4 h-4')
                            <span x-text="qrOpen ? 'QR-Code ausblenden' : 'QR-Code anzeigen'">QR-Code anzeigen</span>
                        </x-nx-button>

                        <div x-show="qrOpen" x-cloak class="flex flex-col gap-2">
                            <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-white p-3">
                                <img
                                    src="data:image/svg+xml;base64,{{ base64_encode(app(\Platform\Hatch\Support\QrCodeRenderer::class)->svg($publicUrl)) }}"
                                    alt="QR-Code für den öffentlichen Link"
                                    class="h-auto w-full"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <x-nx-button wire:click="downloadQrCode('png')" title="Für E-Mail & Office">
                                    @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                                    <span>PNG</span>
                                </x-nx-button>
                                <x-nx-button wire:click="downloadQrCode('svg')" title="Für den Druck, verlustfrei skalierbar">
                                    @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                                    <span>SVG</span>
                                </x-nx-button>
                            </div>
                            <p class="text-xs text-[color:var(--nx-faint)]">SVG für den Druck, PNG für E-Mail &amp; Office.</p>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-[color:var(--nx-muted)]">Noch kein öffentlicher Link. Danach gibt es auch den QR-Code zum Download.</p>
                    <x-nx-button wire:click="generatePublicLink" class="w-full">
                        @svg('heroicon-o-link', 'w-4 h-4')
                        <span>Link generieren</span>
                    </x-nx-button>
                @endif
            </div>

            {{-- Stammdaten --}}
            <div class="{{ $gruppe }}">
                <span class="{{ $ueberschrift }}">Details</span>
                <div class="flex flex-col gap-2">
                    <div class="{{ $zeile }}">
                        <span class="{{ $zeileLabel }}">Vorlage</span>
                        @if($projectIntake->projectTemplate)
                            <a href="{{ route('hatch.templates.show', $projectIntake->projectTemplate) }}" wire:navigate
                               class="min-w-0 truncate text-[color:var(--nx-text)] hover:underline">{{ $projectIntake->projectTemplate->name }}</a>
                        @else
                            <span class="text-[color:var(--nx-faint)]">–</span>
                        @endif
                    </div>
                    <div class="{{ $zeile }}">
                        <span class="{{ $zeileLabel }}">Erstellt von</span>
                        <span class="min-w-0 truncate text-[color:var(--nx-text)]">{{ $projectIntake->createdByUser->name ?? 'Unbekannt' }}</span>
                    </div>
                    <div class="{{ $zeile }}">
                        <span class="{{ $zeileLabel }}">Erstellt am</span>
                        <span class="tabular-nums text-[color:var(--nx-text)]">{{ $projectIntake->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                    @if($projectIntake->started_at)
                        <div class="{{ $zeile }}">
                            <span class="{{ $zeileLabel }}">Gestartet</span>
                            <span class="tabular-nums text-[color:var(--nx-text)]">{{ $projectIntake->started_at->format('d.m.Y H:i') }}</span>
                        </div>
                    @endif
                    @if($projectIntake->completed_at)
                        <div class="{{ $zeile }}">
                            <span class="{{ $zeileLabel }}">Abgeschlossen</span>
                            <span class="tabular-nums text-[color:var(--nx-text)]">{{ $projectIntake->completed_at->format('d.m.Y H:i') }}</span>
                        </div>
                    @endif
                    <div class="{{ $zeile }}">
                        <span class="{{ $zeileLabel }}">UUID</span>
                        <span class="min-w-0 truncate font-mono text-xs text-[color:var(--nx-faint)]" title="{{ $projectIntake->uuid }}">{{ $projectIntake->uuid }}</span>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                @if($activities->isNotEmpty())
                    <div class="space-y-2">
                        @foreach($activities->take(10) as $activity)
                            <div class="flex items-start gap-2 p-2 bg-[var(--ui-muted-5)] rounded">
                                <div class="w-2 h-2 bg-blue-400 rounded-full mt-2 flex-shrink-0"></div>
                                <div class="flex-grow min-w-0">
                                    <p class="text-sm text-[var(--ui-secondary)]">{{ $activity->name ?? 'Aktivität' }}</p>
                                    <p class="text-xs text-[var(--ui-muted)]">{{ $activity->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-sm text-[var(--ui-muted)]">Keine Aktivitäten vorhanden</div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
    <div class="space-y-6">

        @php
            $isRecurring = str_contains((string) $projectIntake->name, '{{iso_')
                || str_contains((string) $projectIntake->description, '{{iso_');
            $totalBlocks = $projectIntake->projectTemplate?->templateBlocks?->count() ?? 0;
            $sessionTotal = $sessions->count();
            $sessionDone = $sessions->where('status', 'completed')->count();
            $sessionRate = $sessionTotal > 0 ? round($sessionDone / $sessionTotal * 100) : 0;
            $lastSession = $sessions->sortByDesc('started_at')->first();
        @endphp

        {{-- Zustand der Erhebung: ein Satz, keine zweite Aktionsleiste —
             die Aktionen stehen in der Seitenleiste. --}}
        @if($projectIntake->status === 'draft')
            <x-nx-callout variant="neutral" icon="heroicon-o-pencil-square" title="Entwurf">
                Die Erhebung ist noch nicht öffentlich. Nach dem Veröffentlichen nimmt der Link Antworten entgegen.
            </x-nx-callout>
        @elseif($projectIntake->status === 'published')
            <x-nx-callout variant="success" icon="heroicon-o-signal" title="Erhebung ist live">
                Der öffentliche Link nimmt Antworten entgegen.
            </x-nx-callout>
        @elseif($projectIntake->status === 'closed')
            <x-nx-callout variant="warning" icon="heroicon-o-lock-closed" title="Erhebung geschlossen">
                Es werden keine neuen Antworten mehr angenommen.
            </x-nx-callout>
        @endif

        {{-- Live-Vorschau: wenn name/description Platzhalter wie {{iso_week}} nutzen,
             zeigen wir hier den gerenderten Wert. Damit ist sofort sichtbar, was
             Respondenten gerade sehen würden. --}}
        @if($isRecurring)
            @php
                $renderer = app(\Platform\Hatch\Support\IntakeStringRenderer::class);
                $renderedName = $renderer->render($projectIntake->name, $projectIntake);
                $renderedDescription = $renderer->render($projectIntake->description, $projectIntake);
            @endphp
            <x-nx-card>
                <div class="flex items-center gap-2 text-xs font-medium text-[color:var(--nx-muted)]">
                    @svg('heroicon-o-eye', 'w-4 h-4')
                    <span>Aktuelle Live-Anzeige</span>
                </div>
                <div class="mt-2 text-base font-semibold text-[color:var(--nx-text)]">{{ $renderedName }}</div>
                @if($renderedDescription)
                    <div class="mt-0.5 text-sm text-[color:var(--nx-muted)]">{{ $renderedDescription }}</div>
                @endif
                <div class="mt-2 text-xs text-[color:var(--nx-faint)]">
                    Platzhalter (@{{iso_week}} etc.) werden bei jedem Aufruf neu ausgewertet — der Link bleibt derselbe.
                </div>
            </x-nx-card>
        @endif

        {{-- Kennzahlen --}}
        <x-nx-stat-grid>
            <x-nx-stat label="Antworten" :value="(string) $sessionTotal" hint="begonnen"
                icon="heroicon-o-chat-bubble-left-right" accent="var(--nx-info)" />
            <x-nx-stat label="Vollständig" :value="(string) $sessionDone" hint="bis zum Ende ausgefüllt"
                icon="heroicon-o-check-circle" :accent="$sessionDone > 0 ? 'var(--nx-success)' : 'var(--nx-muted)'" />
            <x-nx-stat label="Abschlussquote" :value="$sessionRate . ' %'" hint="der Antworten"
                icon="heroicon-o-chart-pie" accent="var(--nx-accent)" />
            <x-nx-stat label="Letzte Antwort"
                :value="$lastSession?->started_at?->format('d.m.') ?? '–'"
                :hint="$lastSession?->started_at?->diffForHumans() ?? 'noch keine'"
                icon="heroicon-o-clock" accent="var(--nx-muted)" />
        </x-nx-stat-grid>

        {{-- Bausteine der Vorlage --}}
        @if($totalBlocks > 0)
            <x-nx-section icon="heroicon-o-squares-2x2" title="Bausteine" :hint="(string) $totalBlocks"
                description="Aus der Vorlage „{{ $projectIntake->projectTemplate->name }}“">
                <div class="flex flex-wrap gap-1.5">
                    @foreach($projectIntake->projectTemplate->templateBlocks->sortBy('sort_order')->values() as $index => $templateBlock)
                        <x-nx-badge>
                            <span class="tabular-nums text-[color:var(--nx-faint)]">{{ $index + 1 }}</span>
                            {{ $templateBlock->name ?? 'Unbekannt' }}
                        </x-nx-badge>
                    @endforeach
                </div>
            </x-nx-section>
        @endif

        {{-- Schritte: nur zeigen, wenn es welche gibt — der Umfrage-Flow
             arbeitet über Sessions, Schritte sind die Intake-weite Variante. --}}
        @if($projectIntake->status !== 'draft' && $projectIntake->intakeSteps->isNotEmpty())
            <x-nx-section icon="heroicon-o-list-bullet" title="Erhebungs-Schritte" :hint="(string) $projectIntake->intakeSteps->count()">
                <x-nx-card flush>
                    <div class="divide-y divide-[color:var(--nx-line)]">
                        @foreach($projectIntake->intakeSteps as $step)
                            <div class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($step->is_completed)
                                        @svg('heroicon-o-check-circle', 'w-4 h-4 shrink-0', ['style' => 'color: var(--nx-success)'])
                                    @else
                                        @svg('heroicon-o-clock', 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm text-[color:var(--nx-text)]">{{ $step->templateBlock->name ?? 'Schritt' }}</div>
                                        @if($step->templateBlock?->description)
                                            <div class="truncate text-xs text-[color:var(--nx-faint)]">{{ $step->templateBlock->description }}</div>
                                        @endif
                                    </div>
                                    <x-nx-badge :variant="$step->is_completed ? 'success' : 'neutral'">{{ $step->is_completed ? 'Erledigt' : 'Offen' }}</x-nx-badge>
                                </div>
                                @if($step->is_completed && $step->answers)
                                    <div class="mt-2 space-y-1 pl-7">
                                        @foreach($step->answers as $key => $value)
                                            <div class="flex gap-3 text-xs">
                                                <span class="w-28 shrink-0 capitalize text-[color:var(--nx-muted)]">{{ str_replace('_', ' ', $key) }}</span>
                                                <span class="text-[color:var(--nx-text)]">{{ is_array($value) ? implode(', ', $value) : $value }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-nx-card>
            </x-nx-section>
        @endif

        {{-- Weiterverarbeitung nach dem Schließen --}}
        @if($projectIntake->status === 'closed')
            <x-nx-section icon="heroicon-o-arrow-right-circle" title="Weiterverarbeiten">
                <div class="flex flex-wrap gap-2">
                    <x-nx-button wire:click="generatePdfReport">@svg('heroicon-o-document-text', 'w-4 h-4')<span>PDF-Bericht</span></x-nx-button>
                    <x-nx-button wire:click="createProject">@svg('heroicon-o-folder-plus', 'w-4 h-4')<span>Projekt anlegen</span></x-nx-button>
                    <x-nx-button wire:click="createTasks">@svg('heroicon-o-list-bullet', 'w-4 h-4')<span>Aufgaben anlegen</span></x-nx-button>
                    <x-nx-button wire:click="exportMarkdown">@svg('heroicon-o-document', 'w-4 h-4')<span>Markdown-Export</span></x-nx-button>
                    <x-nx-button wire:click="showData">@svg('heroicon-o-eye', 'w-4 h-4')<span>Daten anzeigen</span></x-nx-button>
                </div>
            </x-nx-section>
        @endif

        {{-- Eingegangene Antworten --}}
        <x-nx-card flush>
            <div class="flex items-center gap-2 border-b border-[color:var(--nx-line)] px-4 py-3">
                @svg('heroicon-o-inbox-stack', 'w-4 h-4 text-[color:var(--nx-muted)]')
                <h2 class="m-0 text-xs font-semibold text-[color:var(--nx-muted)]">Eingegangene Antworten</h2>
                <span class="text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $sessionTotal }}</span>
                <div class="ml-auto">
                    <x-nx-button wire:click="openPersonalizedSessionModal" title="Einen persönlichen Link für einen CRM-Kontakt erzeugen">
                        @svg('heroicon-o-user-plus', 'w-4 h-4')
                        <span>Personalisierte Session</span>
                    </x-nx-button>
                </div>
            </div>

            @if($sessions->isNotEmpty())
                <x-nx-table>
                    <x-nx-table-header>
                        <x-nx-table-header-cell>Code</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Respondent</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Status</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Fortschritt</x-nx-table-header-cell>
                        @if($isRecurring)
                            <x-nx-table-header-cell>KW</x-nx-table-header-cell>
                        @endif
                        <x-nx-table-header-cell>Gestartet</x-nx-table-header-cell>
                        <x-nx-table-header-cell align="right"><span class="sr-only">Aktionen</span></x-nx-table-header-cell>
                    </x-nx-table-header>
                    <x-nx-table-body>
                        @foreach($sessions as $session)
                            @php
                                $contact = $session->contacts()->first();
                                $answeredBlocks = is_array($session->answers) ? count($session->answers) : 0;
                                $sessionUrl = route('hatch.public.intake-session', ['sessionToken' => $session->session_token]);
                            @endphp
                            <x-nx-table-row wire:key="session-{{ $session->id }}">
                                <x-nx-table-cell>
                                    <a href="{{ route('hatch.intake-sessions.show', $session) }}" wire:navigate class="whitespace-nowrap font-mono text-[color:var(--nx-text)] hover:underline">{{ $session->session_token }}</a>
                                </x-nx-table-cell>
                                <x-nx-table-cell>
                                    @if($contact)
                                        <a href="{{ $contact->url ?? '#' }}" class="hover:underline">{{ $contact->display_name ?? $contact->name ?? '–' }}</a>
                                    @elseif($session->respondent_name || $session->respondent_email)
                                        <div>{{ $session->respondent_name ?? '' }}</div>
                                        @if($session->respondent_email)
                                            <div class="text-xs text-[color:var(--nx-faint)]">{{ $session->respondent_email }}</div>
                                        @endif
                                    @else
                                        <span class="text-[color:var(--nx-faint)]">Anonym</span>
                                    @endif
                                </x-nx-table-cell>
                                <x-nx-table-cell>
                                    <x-nx-badge :variant="$session->status === 'completed' ? 'success' : 'warning'" dot class="whitespace-nowrap">
                                        {{ $session->status === 'completed' ? 'Abgeschlossen' : 'Gestartet' }}
                                    </x-nx-badge>
                                    @if($session->completed_at)
                                        <div class="mt-0.5 whitespace-nowrap text-xs tabular-nums text-[color:var(--nx-faint)]">am {{ $session->completed_at->format('d.m. H:i') }}</div>
                                    @endif
                                </x-nx-table-cell>
                                <x-nx-table-cell class="whitespace-nowrap tabular-nums text-[color:var(--nx-muted)]">{{ $totalBlocks > 0 ? $answeredBlocks . ' / ' . $totalBlocks : $answeredBlocks }}</x-nx-table-cell>
                                @if($isRecurring)
                                    <x-nx-table-cell class="whitespace-nowrap tabular-nums text-[color:var(--nx-muted)]">
                                        @if($session->iso_week && $session->iso_year)
                                            KW {{ str_pad($session->iso_week, 2, '0', STR_PAD_LEFT) }}/{{ substr((string) $session->iso_year, -2) }}
                                        @else
                                            –
                                        @endif
                                    </x-nx-table-cell>
                                @endif
                                <x-nx-table-cell class="whitespace-nowrap tabular-nums text-[color:var(--nx-muted)]" title="{{ $session->started_at?->format('d.m.Y H:i') }}">{{ $session->started_at?->format('d.m. H:i') ?? '–' }}</x-nx-table-cell>
                                <x-nx-table-cell align="right">
                                    <div class="inline-flex items-center gap-0.5" x-data="{ kopiert: false }">
                                        <x-nx-button icon variant="ghost" type="button" title="Link zu dieser Session kopieren"
                                            x-on:click="navigator.clipboard.writeText(@js($sessionUrl)).then(() => { kopiert = true; setTimeout(() => kopiert = false, 1500) })">
                                            <span x-show="!kopiert">@svg('heroicon-o-link', 'w-4 h-4')</span>
                                            <span x-show="kopiert" x-cloak style="color: var(--nx-success);">@svg('heroicon-o-check', 'w-4 h-4')</span>
                                        </x-nx-button>
                                        <x-nx-button icon variant="ghost" type="button" title="Session löschen"
                                            wire:click="deleteSession('{{ $session->id }}')"
                                            wire:confirm="Session wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.">
                                            @svg('heroicon-o-trash', 'w-4 h-4 text-[color:var(--nx-danger)]')
                                        </x-nx-button>
                                    </div>
                                </x-nx-table-cell>
                            </x-nx-table-row>
                        @endforeach
                    </x-nx-table-body>
                </x-nx-table>
            @else
                <x-nx-empty icon="heroicon-o-inbox">
                    Noch keine Antworten eingegangen
                    <x-slot name="action">
                        <span class="text-xs text-[color:var(--nx-faint)]">Teile den Link oder QR-Code aus der Seitenleiste.</span>
                    </x-slot>
                </x-nx-empty>
            @endif
        </x-nx-card>

    </div>
    </x-ui-page-container>

    {{-- Modal: Personalisierte Session erstellen --}}
    <x-ui-modal wire:model="showPersonalizedSessionModal" title="Personalisierte Session erstellen" maxWidth="lg">
        <div class="space-y-4">
            @if($createdSessionUrl)
                {{-- Ergebnis: Link anzeigen --}}
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex items-center gap-2 mb-2">
                        @svg('heroicon-o-check-circle', 'w-5 h-5 text-green-500')
                        <span class="font-medium text-green-700 dark:text-green-400">Session erfolgreich erstellt</span>
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <input
                            type="text"
                            value="{{ $createdSessionUrl }}"
                            readonly
                            class="flex-grow text-sm font-mono bg-white dark:bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded px-3 py-2 text-[var(--ui-secondary)]"
                        />
                        <button
                            type="button"
                            onclick="navigator.clipboard.writeText('{{ $createdSessionUrl }}').then(() => this.querySelector('span').textContent = 'Kopiert!')"
                            class="flex-shrink-0 px-3 py-2 text-sm text-[var(--ui-primary)] hover:underline"
                        >
                            <span>Kopieren</span>
                        </button>
                    </div>
                </div>
                <div class="flex justify-end">
                    <x-nx-button wire:click="closePersonalizedSessionModal">Schließen</x-nx-button>
                </div>
            @else
                {{-- Kontakt-Suche --}}
                <div>
                    <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">CRM-Kontakt suchen</label>
                    <x-ui-input-text
                        name="contactSearch"
                        wire:model.live.debounce.300ms="contactSearch"
                        placeholder="Name oder E-Mail eingeben..."
                    />
                </div>

                @if(!empty($contactOptions))
                    <div>
                        <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Kontakt auswählen</label>
                        <x-ui-input-select name="selectedContactId" wire:model="selectedContactId">
                            <option value="">-- Kontakt wählen --</option>
                            @foreach($contactOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </x-ui-input-select>
                    </div>
                @elseif(strlen($contactSearch) >= 2)
                    <p class="text-sm text-[var(--ui-muted)]">Keine Kontakte gefunden.</p>
                @endif

                <div class="flex justify-end gap-2 pt-2">
                    <x-nx-button wire:click="closePersonalizedSessionModal">Abbrechen</x-nx-button>
                    <x-nx-button variant="primary" wire:click="createPersonalizedSession" :disabled="!$selectedContactId">
                        @svg('heroicon-o-link', 'w-4 h-4')
                        <span>Session erstellen</span>
                    </x-nx-button>
                </div>
            @endif
        </div>
    </x-ui-modal>
</x-ui-page>
