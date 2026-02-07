<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="{{ $projectIntake->name ?? 'Erhebung' }}">
            @if($projectIntake->status === 'draft')
                <x-ui-button variant="primary" size="sm" wire:click="startProjectIntake">
                    <span class="flex items-center gap-2">
                        <x-heroicon-o-play class="w-4 h-4" />
                        Starten
                    </span>
                </x-ui-button>
            @endif
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Erhebungs-Details" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <x-ui-button variant="secondary" size="sm" :href="route('hatch.project-intakes.index')" wire:navigate class="w-full">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            Zurück zu Erhebungen
                        </span>
                    </x-ui-button>
                </div>

                {{-- Übersicht --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Übersicht</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="block text-xs text-[var(--ui-muted)]">UUID</label>
                            <div class="font-mono text-[var(--ui-secondary)]">{{ $projectIntake->uuid }}</div>
                        </div>
                        <div>
                            <label class="block text-xs text-[var(--ui-muted)]">Erstellt von</label>
                            <div class="text-[var(--ui-secondary)]">{{ $projectIntake->createdByUser->name ?? 'Unbekannt' }}</div>
                        </div>
                        <div>
                            <label class="block text-xs text-[var(--ui-muted)]">Erstellt am</label>
                            <div class="text-[var(--ui-secondary)]">{{ $projectIntake->created_at->format('d.m.Y H:i') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Status</h3>
                    <x-ui-badge
                        :variant="$projectIntake->status === 'draft' ? 'secondary' : ($projectIntake->status === 'completed' ? 'success' : 'primary')"
                        size="sm"
                    >
                        {{ $statuses[$projectIntake->status] ?? $projectIntake->status }}
                    </x-ui-badge>
                </div>

                {{-- Zeitstempel --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Zeitstempel</h3>
                    <div class="space-y-1 text-sm text-[var(--ui-muted)]">
                        @if($projectIntake->started_at)
                            <div><strong>Gestartet:</strong> {{ $projectIntake->started_at->format('d.m.Y H:i') }}</div>
                        @endif
                        @if($projectIntake->completed_at)
                            <div><strong>Abgeschlossen:</strong> {{ $projectIntake->completed_at->format('d.m.Y H:i') }}</div>
                        @endif
                    </div>
                </div>

                {{-- Template Info --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Template</h3>
                    @if($projectIntake->projectTemplate)
                        <div class="flex items-center gap-2 p-2 bg-[var(--ui-muted-5)] rounded">
                            <span class="flex-grow text-sm">{{ $projectIntake->projectTemplate->name }}</span>
                            <x-ui-badge variant="info" size="xs">{{ $projectIntake->projectTemplate->complexity_level ?? 'Standard' }}</x-ui-badge>
                        </div>
                    @else
                        <p class="text-sm text-[var(--ui-muted)]">Kein Template zugewiesen.</p>
                    @endif
                </div>

                {{-- Oeffentlicher Link --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Oeffentlicher Link</h3>
                    @if($projectIntake->public_token)
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 p-2 bg-[var(--ui-muted-5)] rounded">
                                <input
                                    type="text"
                                    value="{{ $projectIntake->getPublicUrl() }}"
                                    readonly
                                    class="flex-grow text-xs font-mono bg-transparent border-none outline-none text-[var(--ui-secondary)] truncate"
                                />
                                <button
                                    type="button"
                                    onclick="navigator.clipboard.writeText('{{ $projectIntake->getPublicUrl() }}').then(() => this.querySelector('span').textContent = 'Kopiert!')"
                                    class="flex-shrink-0 text-xs text-[var(--ui-primary)] hover:underline"
                                >
                                    <span>Kopieren</span>
                                </button>
                            </div>
                            <p class="text-xs text-[var(--ui-muted)]">
                                {{ $projectIntake->sessions()->count() }} Session(s) bisher
                            </p>
                        </div>
                    @else
                        <x-ui-button variant="secondary" size="sm" wire:click="generatePublicLink" class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-link', 'w-4 h-4')
                                Link generieren
                            </span>
                        </x-ui-button>
                    @endif
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
        {{-- Status Overview --}}
        <div class="flex items-center gap-4 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 mb-6">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-[var(--ui-secondary)]">Status:</span>
                <x-ui-badge variant="primary" size="sm">
                    {{ $statuses[$projectIntake->status] ?? $projectIntake->status }}
                </x-ui-badge>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-[var(--ui-secondary)]">Template:</span>
                @if($projectIntake->projectTemplate)
                    <span class="text-sm text-[var(--ui-secondary)]">{{ $projectIntake->projectTemplate->name }}</span>
                @else
                    <span class="text-[var(--ui-muted)]">–</span>
                @endif
            </div>
        </div>

        {{-- Block Progress --}}
        @if($projectIntake->projectTemplate && $projectIntake->projectTemplate->templateBlocks->count() > 0)
            <div class="mb-6">
                <h4 class="font-semibold mb-3 text-[var(--ui-secondary)]">Erhebungs-Blöcke</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($projectIntake->projectTemplate->templateBlocks->sortBy('sort_order') as $index => $templateBlock)
                        @php
                            $step = $projectIntake->intakeSteps->where('template_block_id', $templateBlock->id)->first();
                            $status = $step ? ($step->is_completed ? 'completed' : 'in_progress') : 'pending';
                        @endphp
                        <x-ui-badge
                            :variant="$status === 'completed' ? 'success' : ($status === 'in_progress' ? 'info' : 'secondary')"
                            size="sm"
                        >
                            Block {{ $index + 1 }}: {{ $templateBlock->blockDefinition->name ?? 'Unbekannt' }}
                        </x-ui-badge>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Game Actions --}}
        @if($projectIntake->status === 'draft')
            <div class="text-center py-8 rounded-lg border border-[var(--ui-border)] bg-[var(--ui-surface)]">
                <h3 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">Bereit für den Start?</h3>
                <p class="text-[var(--ui-muted)]">Diese Erhebung verwendet das Template "{{ $projectIntake->projectTemplate->name ?? 'Unbekannt' }}"</p>
                <div class="mt-4">
                    <x-ui-button variant="primary" size="lg" wire:click="startProjectIntake">
                        <span class="flex items-center gap-2">
                            <x-heroicon-o-play class="w-5 h-5" />
                            Erhebung starten
                        </span>
                    </x-ui-button>
                </div>
            </div>
        @elseif(in_array($projectIntake->status, ['in_progress', 'paused']))
            <div class="text-center py-8 rounded-lg border border-[var(--ui-border)] bg-[var(--ui-surface)]">
                <h3 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">
                    {{ $projectIntake->status === 'paused' ? 'Erhebung pausiert' : 'Erhebung läuft...' }}
                </h3>
                <p class="text-[var(--ui-muted)] mb-4">
                    {{ $projectIntake->status === 'paused' ? 'Sie können jederzeit fortfahren' : 'Die Erhebung wird durchgeführt' }}
                </p>
                <div class="p-4 bg-[var(--ui-muted-5)] rounded-lg inline-block">
                    @svg('heroicon-o-wrench', 'w-8 h-8 mx-auto mb-2 text-[var(--ui-muted)]')
                    <p class="text-sm text-[var(--ui-muted)]">Der interaktive Erhebungsmodus wird in einer späteren Version verfügbar sein.</p>
                </div>
            </div>
        @elseif($projectIntake->status === 'completed')
            <div class="text-center py-8 mb-6">
                <div class="text-green-500 mb-2">
                    <x-heroicon-o-check-circle class="w-16 h-16 mx-auto" />
                </div>
                <h3 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">Erhebung abgeschlossen!</h3>
                <p class="text-[var(--ui-muted)]">Alle Schritte wurden erfolgreich durchlaufen</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <x-ui-panel>
                    <div class="text-center">
                        <x-heroicon-o-document-text class="w-10 h-10 text-[var(--ui-primary)] mx-auto mb-3" />
                        <h4 class="font-semibold text-[var(--ui-secondary)] mb-2">PDF Bericht</h4>
                        <x-ui-button variant="primary" size="sm" class="w-full" wire:click="generatePdfReport">PDF erstellen</x-ui-button>
                    </div>
                </x-ui-panel>
                <x-ui-panel>
                    <div class="text-center">
                        <x-heroicon-o-folder-plus class="w-10 h-10 text-green-500 mx-auto mb-3" />
                        <h4 class="font-semibold text-[var(--ui-secondary)] mb-2">Projekt anlegen</h4>
                        <x-ui-button variant="secondary" size="sm" class="w-full" wire:click="createProject">Projekt erstellen</x-ui-button>
                    </div>
                </x-ui-panel>
                <x-ui-panel>
                    <div class="text-center">
                        <x-heroicon-o-list-bullet class="w-10 h-10 text-amber-500 mx-auto mb-3" />
                        <h4 class="font-semibold text-[var(--ui-secondary)] mb-2">Aufgaben anlegen</h4>
                        <x-ui-button variant="secondary" size="sm" class="w-full" wire:click="createTasks">Aufgaben erstellen</x-ui-button>
                    </div>
                </x-ui-panel>
            </div>

            <div class="flex gap-4 justify-center">
                <x-ui-button variant="secondary-outline" size="sm" wire:click="exportMarkdown">
                    <span class="flex items-center gap-2">@svg('heroicon-o-document', 'w-4 h-4') Markdown Export</span>
                </x-ui-button>
                <x-ui-button variant="secondary-outline" size="sm" wire:click="showData">
                    <span class="flex items-center gap-2">@svg('heroicon-o-eye', 'w-4 h-4') Daten anzeigen</span>
                </x-ui-button>
            </div>
        @endif

        {{-- Steps --}}
        @if($projectIntake->status !== 'draft')
            <div class="mt-8">
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Erhebungs-Schritte</h3>
                <div class="space-y-3">
                    @forelse($projectIntake->intakeSteps as $step)
                        <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)]/60 rounded-lg overflow-hidden">
                            <div class="flex items-center gap-3 p-4">
                                <div class="w-3 h-3 {{ $step->is_completed ? 'bg-green-500' : 'bg-blue-500' }} rounded-full"></div>
                                <div class="flex-grow">
                                    <div class="font-semibold text-[var(--ui-secondary)]">
                                        {{ $step->templateBlock->blockDefinition->name ?? 'Schritt' }}
                                    </div>
                                    <div class="text-sm text-[var(--ui-muted)]">
                                        {{ $step->templateBlock->blockDefinition->description ?? '' }}
                                    </div>
                                </div>
                                <div class="text-sm text-[var(--ui-muted)]">
                                    @if($step->is_completed)
                                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                                    @else
                                        <x-heroicon-o-clock class="w-5 h-5" />
                                    @endif
                                </div>
                            </div>

                            @if($step->is_completed && $step->answers)
                                <div class="p-4 bg-[var(--ui-muted-5)] border-t border-[var(--ui-border)]/40">
                                    <h5 class="font-medium text-[var(--ui-secondary)] mb-3">Gesammelte Informationen:</h5>
                                    <div class="space-y-2">
                                        @foreach($step->answers as $key => $value)
                                            <div class="flex gap-3">
                                                <span class="text-sm font-medium text-[var(--ui-muted)] w-24 flex-shrink-0 capitalize">{{ str_replace('_', ' ', $key) }}:</span>
                                                <span class="text-sm text-[var(--ui-secondary)]">
                                                    @if(is_array($value))
                                                        {{ implode(', ', $value) }}
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif(!$step->is_completed)
                                <div class="p-4 bg-[var(--ui-muted-5)] border-t border-[var(--ui-border)]/40">
                                    <span class="text-sm text-[var(--ui-muted)] flex items-center gap-1">
                                        <x-heroicon-o-clock class="w-4 h-4" />
                                        Wird noch bearbeitet...
                                    </span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-4 text-[var(--ui-muted)]">
                            <p>Noch keine Schritte vorhanden</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
        {{-- Sessions --}}
        <div class="mt-8">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">Eingegangene Sessions</h3>
                    <x-ui-badge variant="secondary" size="sm">{{ $sessions->count() }}</x-ui-badge>
                </div>
                <x-ui-button variant="primary" size="sm" wire:click="openPersonalizedSessionModal">
                    <span class="flex items-center gap-2">
                        @svg('heroicon-o-user-plus', 'w-4 h-4')
                        Personalisierte Session
                    </span>
                </x-ui-button>
            </div>

            @if($sessions->isNotEmpty())
                @php
                    $totalBlocks = $projectIntake->projectTemplate?->templateBlocks?->count() ?? 0;
                @endphp
                <div class="overflow-x-auto border border-[var(--ui-border)]/60 rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-[var(--ui-muted-5)] border-b border-[var(--ui-border)]/40">
                            <tr>
                                <th class="text-left p-3 font-medium text-[var(--ui-muted)]">Token</th>
                                <th class="text-left p-3 font-medium text-[var(--ui-muted)]">Respondent</th>
                                <th class="text-left p-3 font-medium text-[var(--ui-muted)]">Kontakt</th>
                                <th class="text-left p-3 font-medium text-[var(--ui-muted)]">Status</th>
                                <th class="text-left p-3 font-medium text-[var(--ui-muted)]">Fortschritt</th>
                                <th class="text-left p-3 font-medium text-[var(--ui-muted)]">Link</th>
                                <th class="text-left p-3 font-medium text-[var(--ui-muted)]">Gestartet</th>
                                <th class="text-left p-3 font-medium text-[var(--ui-muted)]">Abgeschlossen</th>
                                <th class="text-left p-3 font-medium text-[var(--ui-muted)]">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sessions as $session)
                                <tr class="border-b border-[var(--ui-border)]/20 hover:bg-[var(--ui-muted-5)]/50">
                                    <td class="p-3">
                                        <a href="{{ route('hatch.intake-sessions.show', $session) }}" wire:navigate class="font-mono text-[var(--ui-primary)] hover:underline">
                                            {{ $session->session_token }}
                                        </a>
                                    </td>
                                    <td class="p-3 text-[var(--ui-secondary)]">
                                        @if($session->respondent_name || $session->respondent_email)
                                            <div>{{ $session->respondent_name ?? '' }}</div>
                                            @if($session->respondent_email)
                                                <div class="text-xs text-[var(--ui-muted)]">{{ $session->respondent_email }}</div>
                                            @endif
                                        @else
                                            <span class="text-[var(--ui-muted)]">Anonym</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-[var(--ui-secondary)]">
                                        @php
                                            $contact = $session->contacts()->first();
                                        @endphp
                                        @if($contact)
                                            <a href="{{ $contact->url ?? '#' }}" class="text-[var(--ui-primary)] hover:underline text-sm">
                                                {{ $contact->display_name ?? $contact->name ?? '–' }}
                                            </a>
                                        @else
                                            <span class="text-[var(--ui-muted)]">–</span>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <x-ui-badge
                                            :variant="$session->status === 'completed' ? 'success' : 'warning'"
                                            size="sm"
                                        >
                                            {{ $session->status === 'completed' ? 'Abgeschlossen' : 'Gestartet' }}
                                        </x-ui-badge>
                                    </td>
                                    <td class="p-3 text-[var(--ui-secondary)]">
                                        @php
                                            $answeredBlocks = is_array($session->answers) ? count($session->answers) : 0;
                                        @endphp
                                        {{ $answeredBlocks }} / {{ $totalBlocks }}
                                    </td>
                                    <td class="p-3">
                                        @php
                                            $sessionUrl = route('hatch.public.intake-session', ['sessionToken' => $session->session_token]);
                                        @endphp
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs font-mono text-[var(--ui-muted)] truncate max-w-[120px]">{{ $sessionUrl }}</span>
                                            <button
                                                type="button"
                                                onclick="navigator.clipboard.writeText('{{ $sessionUrl }}').then(() => { this.querySelector('svg').classList.add('text-green-500'); setTimeout(() => this.querySelector('svg').classList.remove('text-green-500'), 1500) })"
                                                class="flex-shrink-0 text-[var(--ui-muted)] hover:text-[var(--ui-primary)]"
                                                title="Link kopieren"
                                            >
                                                @svg('heroicon-o-clipboard-document', 'w-4 h-4 transition-colors')
                                            </button>
                                        </div>
                                    </td>
                                    <td class="p-3 text-[var(--ui-muted)]">
                                        {{ $session->started_at?->format('d.m.Y H:i') ?? '–' }}
                                    </td>
                                    <td class="p-3 text-[var(--ui-muted)]">
                                        {{ $session->completed_at?->format('d.m.Y H:i') ?? '–' }}
                                    </td>
                                    <td class="p-3">
                                        <button
                                            type="button"
                                            wire:click="deleteSession('{{ $session->id }}')"
                                            wire:confirm="Session wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden."
                                            class="text-red-500 hover:text-red-700"
                                            title="Session löschen"
                                        >
                                            @svg('heroicon-o-trash', 'w-4 h-4')
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-6 text-[var(--ui-muted)] border border-dashed border-[var(--ui-border)]/60 rounded-lg">
                    <x-heroicon-o-inbox class="w-8 h-8 mx-auto mb-2 text-[var(--ui-muted)]" />
                    <p class="text-sm">Noch keine Sessions eingegangen</p>
                </div>
            @endif
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
                    <x-ui-button variant="secondary" size="sm" wire:click="closePersonalizedSessionModal">
                        Schliessen
                    </x-ui-button>
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
                    <x-ui-button variant="secondary" size="sm" wire:click="closePersonalizedSessionModal">
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button variant="primary" size="sm" wire:click="createPersonalizedSession" :disabled="!$selectedContactId">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-link', 'w-4 h-4')
                            Session erstellen
                        </span>
                    </x-ui-button>
                </div>
            @endif
        </div>
    </x-ui-modal>
</x-ui-page>
