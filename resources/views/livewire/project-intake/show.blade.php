<div class="d-flex h-full">
    <!-- Linke Spalte -->
    <div class="flex-grow-1 d-flex flex-col">
        <!-- Header oben (fix) -->
        <div class="border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <div class="d-flex gap-1">
                <div class="d-flex">
                    <a href="{{ route('hatch.project-intakes.index') }}" class="d-flex px-3 border-right-solid border-right-1 border-right-muted underline" wire:navigate>
                        Projektierungen
                    </a>
                </div>
                <div class="flex-grow-1 text-right d-flex items-center justify-end gap-2">
                    <span>{{ $projectIntake->uuid }}</span>
                    @if($projectIntake->status === 'draft')
                        <x-ui-button 
                            variant="primary" 
                            size="sm"
                            wire:click="startProjectIntake"
                        >
                            <div class="d-flex items-center gap-2">
                                <x-heroicon-o-play class="w-4 h-4" />
                                Starten
                            </div>
                        </x-ui-button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Haupt-Content (nimmt Restplatz, scrollt) -->
        <div class="flex-grow-1 overflow-y-auto p-4">
            <!-- Project Intake Game/Logic -->
            <div class="mb-6">
                <div class="d-flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-secondary">Projektierung</h3>
                </div>
                
                <div class="space-y-6">
                    <!-- Status Overview -->
                    <div class="d-flex items-center gap-4 p-4 bg-muted-5 rounded-lg">
                        <div class="d-flex items-center gap-2">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-sm font-medium text-secondary">Status:</span>
                            @php
                                $statusColors = [
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'in_progress' => 'bg-blue-100 text-blue-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'paused' => 'bg-yellow-100 text-yellow-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                                $statusColor = $statusColors[$projectIntake->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <x-ui-badge variant="primary" size="sm">
                                {{ $statuses[$projectIntake->status] ?? $projectIntake->status }}
                            </x-ui-badge>
                        </div>
                        
                        <div class="d-flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-sm font-medium text-secondary">Template:</span>
                            @if($projectIntake->projectTemplate)
                                <span class="text-sm text-secondary">{{ $projectIntake->projectTemplate->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Template Blocks Progress -->
                    @if($projectIntake->projectTemplate && $projectIntake->projectTemplate->templateBlocks->count() > 0)
                        <div class="mb-6">
                            <h4 class="font-semibold mb-3 text-secondary">Projektierungs-Blöcke</h4>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($projectIntake->projectTemplate->templateBlocks->sortBy('sort_order') as $index => $templateBlock)
                                    @php
                                        $step = $projectIntake->intakeSteps->where('template_block_id', $templateBlock->id)->first();
                                        $status = $step ? ($step->is_completed ? 'completed' : 'in_progress') : 'pending';
                                    @endphp
                                    
                                    <x-ui-badge 
                                        :variant="$status === 'completed' ? 'success' : ($status === 'in_progress' ? 'info' : 'neutral')"
                                        size="sm"
                                    >
                                        Block {{ $index + 1 }}: {{ $templateBlock->blockDefinition->name ?? 'Unbekannt' }}
                                    </x-ui-badge>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Game Actions -->
                    @if($projectIntake->status === 'draft')
                        <div class="text-center py-8">
                            <div class="mb-4">
                                <h3 class="text-lg font-medium text-secondary mb-2">Bereit für den Start?</h3>
                                <p class="text-muted">Diese Projektierung verwendet das Template "{{ $projectIntake->projectTemplate->name ?? 'Unbekannt' }}"</p>
                            </div>
                            
                            <x-ui-button variant="primary" size="lg" wire:click="startProjectIntake">
                                <div class="d-flex items-center gap-2">
                                    <x-heroicon-o-play class="w-5 h-5" />
                                    Projektierung starten
                                </div>
                            </x-ui-button>
                        </div>
                    @elseif(in_array($projectIntake->status, ['in_progress', 'paused']))
                        <div class="space-y-4">
                            <div class="text-center py-4">
                                <h3 class="text-lg font-medium text-secondary mb-2">
                                    @if($projectIntake->status === 'paused')
                                        Projektierung pausiert
                                    @else
                                        Projektierung läuft...
                                    @endif
                                </h3>
                                <p class="text-muted">
                                    @if($projectIntake->status === 'paused')
                                        Sie können jederzeit fortfahren
                                    @else
                                        Die KI-Assistentin führt Sie durch den Prozess
                                    @endif
                                </p>
                            </div>
                            
                            <!-- Continue Intake Button -->
                            <div class="text-center">
                                <x-ui-button variant="primary" size="lg" wire:click="openIntakeModal">
                                    <div class="d-flex items-center gap-2">
                                        <x-heroicon-o-chat-bubble-left-right class="w-5 h-5" />
                                        @if($projectIntake->status === 'paused')
                                            Projektierung fortsetzen
                                        @else
                                            Projektierung öffnen
                                        @endif
                                    </div>
                                </x-ui-button>
                            </div>
                        </div>
                    @elseif($projectIntake->status === 'completed')
                        <div class="text-center py-8">
                            <div class="mb-6">
                                <div class="text-success mb-2">
                                    <x-heroicon-o-check-circle class="w-16 h-16" />
                                </div>
                                <h3 class="text-lg font-medium text-secondary mb-2">Projektierung abgeschlossen!</h3>
                                <p class="text-muted">Alle Schritte wurden erfolgreich durchlaufen</p>
                            </div>
                            
                            <!-- Aktions-Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-4xl mx-auto">
                                <!-- PDF Bericht -->
                                <div class="bg-white border border-muted rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="text-center mb-4">
                                        <x-heroicon-o-document-text class="w-12 h-12 text-primary mx-auto mb-3" />
                                        <h4 class="font-semibold text-secondary mb-2">PDF Bericht</h4>
                                        <p class="text-sm text-muted mb-4">Erstelle einen detaillierten Projektbericht als PDF</p>
                                    </div>
                                    <x-ui-button 
                                        variant="primary" 
                                        size="md" 
                                        class="w-full"
                                        wire:click="generatePdfReport"
                                    >
                                        <div class="d-flex items-center gap-2">
                                            <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                            PDF erstellen
                                        </div>
                                    </x-ui-button>
                                </div>
                                
                                <!-- Projekt anlegen -->
                                <div class="bg-white border border-muted rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="text-center mb-4">
                                        <x-heroicon-o-folder-plus class="w-12 h-12 text-success mx-auto mb-3" />
                                        <h4 class="font-semibold text-secondary mb-2">Projekt anlegen</h4>
                                        <p class="text-sm text-muted mb-4">Erstelle ein neues Projekt basierend auf den Daten</p>
                                    </div>
                                    <x-ui-button 
                                        variant="success" 
                                        size="md" 
                                        class="w-full"
                                        wire:click="createProject"
                                    >
                                        <div class="d-flex items-center gap-2">
                                            <x-heroicon-o-plus class="w-4 h-4" />
                                            Projekt erstellen
                                        </div>
                                    </x-ui-button>
                                </div>
                                
                                <!-- Aufgaben anlegen -->
                                <div class="bg-white border border-muted rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="text-center mb-4">
                                        <x-heroicon-o-list-bullet class="w-12 h-12 text-warning mx-auto mb-3" />
                                        <h4 class="font-semibold text-secondary mb-2">Aufgaben anlegen</h4>
                                        <p class="text-sm text-muted mb-4">Generiere Aufgaben basierend auf der Projektierung</p>
                                    </div>
                                    <x-ui-button 
                                        variant="warning" 
                                        size="md" 
                                        class="w-full"
                                        wire:click="createTasks"
                                    >
                                        <div class="d-flex items-center gap-2">
                                            <x-heroicon-o-check-circle class="w-4 h-4" />
                                            Aufgaben erstellen
                                        </div>
                                    </x-ui-button>
                                </div>
                            </div>
                            
                            <!-- Zusätzliche Aktionen -->
                            <div class="mt-8 pt-6 border-t border-muted">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl mx-auto">
                                    <!-- Markdown Export -->
                                    <x-ui-button 
                                        variant="secondary-outline" 
                                        size="md" 
                                        class="w-full"
                                        wire:click="exportMarkdown"
                                    >
                                        <div class="d-flex items-center gap-2">
                                            <x-heroicon-o-document class="w-4 h-4" />
                                            Markdown Export
                                        </div>
                                    </x-ui-button>
                                    
                                    <!-- Daten anzeigen -->
                                    <x-ui-button 
                                        variant="secondary-outline" 
                                        size="md" 
                                        class="w-full"
                                        wire:click="showData"
                                    >
                                        <div class="d-flex items-center gap-2">
                                            <x-heroicon-o-eye class="w-4 h-4" />
                                            Daten anzeigen
                                        </div>
                                    </x-ui-button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8 text-muted">
                            <p>Status: {{ $statuses[$projectIntake->status] ?? $projectIntake->status }}</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Project Intake Steps (wenn aktiv) -->
            @if($projectIntake->status !== 'draft')
                <div class="mb-6">
                    <div class="d-flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-secondary">Projektierungs-Schritte</h3>
                    </div>
                    
                    <div class="space-y-3">
                        @forelse($projectIntake->intakeSteps as $step)
                            <div class="bg-white border border-muted rounded-lg overflow-hidden">
                                <!-- Step Header -->
                                <div class="d-flex items-center gap-3 p-4 border-bottom border-muted">
                                    <div class="w-3 h-3 {{ $step->is_completed ? 'bg-success' : 'bg-primary' }} rounded-full"></div>
                                    <div class="flex-grow-1">
                                        <div class="font-semibold text-secondary">
                                            {{ $step->templateBlock->blockDefinition->name ?? 'Schritt' }}
                                        </div>
                                        <div class="text-sm text-muted">
                                            {{ $step->templateBlock->blockDefinition->description ?? 'Keine Beschreibung' }}
                                        </div>
                                    </div>
                                    <div class="text-sm text-muted">
                                        @if($step->is_completed)
                                            <x-heroicon-o-check-circle class="w-5 h-5 text-success" />
                                        @else
                                            {{ $step->created_at->format('H:i') }}
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Step Content (wenn completed) -->
                                @if($step->is_completed && $step->answers)
                                    <div class="p-4 bg-muted-5">
                                        <h5 class="font-medium text-secondary mb-3">Gesammelte Informationen:</h5>
                                        <div class="space-y-2">
                                            @foreach($step->answers as $key => $value)
                                                <div class="d-flex gap-3">
                                                    <div class="w-24 flex-shrink-0">
                                                        <span class="text-sm font-medium text-muted capitalize">
                                                            {{ str_replace('_', ' ', $key) }}:
                                                        </span>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <span class="text-sm text-secondary">
                                                            @if(is_array($value))
                                                                <ul class="list-disc list-inside space-y-1">
                                                                    @foreach($value as $item)
                                                                        <li>{{ $item }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            @else
                                                                {{ $value }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        
                                        @if($step->ai_interpretation)
                                            <div class="mt-4 pt-3 border-t border-muted">
                                                <h6 class="font-medium text-secondary mb-2">AI Interpretation:</h6>
                                                <div class="text-sm text-muted bg-white p-3 rounded border">
                                                    {{ $step->ai_interpretation }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @elseif(!$step->is_completed)
                                    <div class="p-4 bg-muted-5">
                                        <div class="text-sm text-muted">
                                            <x-heroicon-o-clock class="w-4 h-4 inline mr-1" />
                                            Wird noch bearbeitet...
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <p>Noch keine Schritte vorhanden</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>

        <!-- Aktivitäten (immer unten) -->
        <div x-data="{ open: false }" class="flex-shrink-0 border-t border-muted">
            <div 
                @click="open = !open" 
                class="cursor-pointer border-top-1 border-top-solid border-top-muted border-bottom-1 border-bottom-solid border-bottom-muted p-2 text-center d-flex items-center justify-center gap-1 mx-2 shadow-lg"
            >
                AKTIVITÄTEN 
                <span class="text-xs">
                    {{ $activities->count() }}
                </span>
                <x-heroicon-o-chevron-double-down 
                    class="w-3 h-3" 
                    x-show="!open"
                />
                <x-heroicon-o-chevron-double-up 
                    class="w-3 h-3" 
                    x-show="open"
                />
            </div>
            <div x-show="open" class="p-2 max-h-xs overflow-y-auto">
                @if($activities->isNotEmpty())
                    <div class="space-y-2">
                        @foreach($activities->take(5) as $activity)
                            <div class="d-flex items-start gap-2 p-2 bg-muted-5 rounded">
                                <div class="w-2 h-2 bg-blue-400 rounded-full mt-2 flex-shrink-0"></div>
                                <div class="flex-grow-1 min-w-0">
                                    <p class="text-sm text-secondary">{{ $activity->name ?? 'Aktivität' }}</p>
                                    @if($activity->properties)
                                        <p class="text-xs text-muted">{{ json_encode($activity->properties) }}</p>
                                    @endif
                                    <p class="text-xs text-muted">{{ $activity->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($activities->count() > 5)
                        <div class="mt-2 pt-2 border-t border-muted text-center">
                            <a href="#" class="text-xs text-primary hover:text-primary-dark">
                                Alle {{ $activities->count() }} Aktivitäten anzeigen
                            </a>
                        </div>
                    @endif
                @else
                    <div class="text-center text-muted p-4">
                        <p>Keine Aktivitäten vorhanden</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Rechte Spalte -->
    <div class="min-w-80 w-80 d-flex flex-col border-left-1 border-left-solid border-left-muted">
        <div class="d-flex gap-2 border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <x-heroicon-o-information-circle class="w-6 h-6"/>
            Projektierungs-Details
        </div>
        <div class="flex-grow-1 overflow-y-auto p-4">
            {{-- Navigation Buttons --}}
            <div class="d-flex flex-col gap-2 mb-4">
                <x-ui-button 
                    variant="secondary-outline" 
                    size="md" 
                    :href="route('hatch.project-intakes.index')" 
                    wire:navigate
                    class="w-full"
                >
                    <div class="d-flex items-center gap-2">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Zurück zu Projektierungen
                    </div>
                </x-ui-button>
            </div>

            {{-- Project Intake Übersicht --}}
            <div class="mb-4 p-3 bg-muted-5 rounded-lg">
                <h4 class="font-semibold mb-2 text-secondary">Projektierungs-Übersicht</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-secondary">UUID</label>
                        <div class="mt-1 text-sm text-secondary font-mono">
                            {{ $projectIntake->uuid }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-secondary">Erstellt von</label>
                        <div class="mt-1 text-sm text-secondary">
                            {{ $projectIntake->createdByUser->name ?? 'Unbekannt' }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-secondary">Erstellt am</label>
                        <div class="mt-1 text-sm text-secondary">
                            {{ $projectIntake->created_at->format('d.m.Y H:i') }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-secondary">Team</label>
                        <div class="mt-1 text-sm text-secondary">
                            {{ $projectIntake->team->name ?? 'Unbekannt' }}
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            {{-- Status Details --}}
            <div class="mb-4">
                <h4 class="font-semibold mb-2">Status</h4>
                <div class="space-y-2">
                    <x-ui-badge 
                        :variant="$projectIntake->status === 'draft' ? 'secondary' : ($projectIntake->status === 'completed' ? 'success' : 'primary')" 
                        size="sm"
                    >
                        {{ $statuses[$projectIntake->status] ?? $projectIntake->status }}
                    </x-ui-badge>
                </div>
            </div>

            <hr>

            {{-- Zeitstempel --}}
            <div class="mb-4">
                <h4 class="font-semibold mb-2">Zeitstempel</h4>
                <div class="space-y-1 text-sm">
                    @if($projectIntake->started_at)
                        <div><strong>Gestartet:</strong> {{ $projectIntake->started_at->format('d.m.Y H:i') }}</div>
                    @endif
                    @if($projectIntake->completed_at)
                        <div><strong>Abgeschlossen:</strong> {{ $projectIntake->completed_at->format('d.m.Y H:i') }}</div>
                    @endif
                </div>
            </div>

            <hr>

            {{-- Template Info --}}
            <div class="mb-4">
                <h4 class="font-semibold mb-2">Template</h4>
                <div class="space-y-2">
                    @if($projectIntake->projectTemplate)
                        <div class="d-flex items-center gap-2 p-2 bg-muted-5 rounded">
                            <span class="flex-grow-1 text-sm">{{ $projectIntake->projectTemplate->name }}</span>
                            <x-ui-badge variant="info" size="xs">{{ $projectIntake->projectTemplate->complexity_level ?? 'Standard' }}</x-ui-badge>
                        </div>
                    @else
                        <p class="text-sm text-muted">Kein Template zugewiesen.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Intake Modal -->
    <x-ui-modal 
        model="showIntakeModal" 
        size="lg" 
        :persistent="true"
        header="KI-gestützte Projektierung"
    >
        <!-- Loading Overlay für KI-Antworten -->
        <div wire:loading class="absolute inset-0 bg-white bg-opacity-75 d-flex items-center justify-center z-10 rounded-lg">
            <div class="text-center">
                <x-heroicon-o-arrow-path class="w-12 h-12 animate-spin text-primary mx-auto mb-3" />
                <p class="text-primary font-medium">KI-Assistentin denkt...</p>
                <p class="text-sm text-muted">Bitte warten Sie einen Moment</p>
            </div>
        </div>

        <!-- Fortschritts-Anzeige -->
        <div class="mb-6">
            <h4 class="font-semibold mb-3 text-secondary">Fortschritt</h4>
            <div class="d-flex flex-wrap gap-2 mb-4">
                @if($projectIntake->projectTemplate)
                    @foreach($projectIntake->projectTemplate->templateBlocks->sortBy('sort_order') as $index => $templateBlock)
                        @php
                            $step = $projectIntake->intakeSteps->where('template_block_id', $templateBlock->id)->first();
                            $status = $step ? ($step->is_completed ? 'completed' : 'in_progress') : 'pending';
                            $isCurrent = $currentBlockIndex === $index;
                        @endphp
                        
                        <x-ui-badge 
                            :variant="$status === 'completed' ? 'success' : ($status === 'in_progress' ? 'primary' : 'neutral')"
                            size="sm"
                            :icon="$status === 'completed' ? 'heroicon-o-check-circle' : ($status === 'in_progress' ? 'heroicon-o-play-circle' : 'heroicon-o-clock')"
                            :class="$isCurrent ? 'ring-2 ring-primary ring-offset-2' : ''"
                        >
                            Block {{ $index + 1 }}: {{ $templateBlock->blockDefinition->name ?? 'Unbekannt' }}
                        </x-ui-badge>
                    @endforeach
                @endif
            </div>
            
            <!-- Aktueller Block Info -->
            @if(isset($currentBlock))
                <div class="p-4 bg-primary-5 border border-primary-20 rounded-lg">
                    <h5 class="font-medium text-primary mb-2">
                        Aktueller Block: {{ $currentBlock->blockDefinition->name ?? 'Unbekannt' }}
                    </h5>
                    <p class="text-sm text-primary-80">
                        {{ $currentBlock->blockDefinition->description ?? 'Keine Beschreibung verfügbar' }}
                    </p>
                    
                    <!-- DEBUG: Block Definition Details -->
                    @if($currentBlock->blockDefinition)
                        <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                            <strong>DEBUG Block Definition:</strong><br>
                            <strong>AI Prompt:</strong> {{ $currentBlock->blockDefinition->ai_prompt ?: 'LEER' }}<br>
                            <strong>Validation Rules:</strong> {{ $currentBlock->blockDefinition->validation_rules ? (is_string($currentBlock->blockDefinition->validation_rules) ? $currentBlock->blockDefinition->validation_rules : json_encode($currentBlock->blockDefinition->validation_rules)) : 'LEER' }}<br>
                            <strong>Response Format:</strong> {{ $currentBlock->blockDefinition->response_format ? (is_string($currentBlock->blockDefinition->response_format) ? $currentBlock->blockDefinition->response_format : json_encode($currentBlock->blockDefinition->response_format)) : 'LEER' }}
                        </div>
                    @else
                        <div class="mt-3 p-2 bg-red-50 border border-red-200 rounded text-xs">
                            <strong>FEHLER:</strong> Block Definition konnte nicht geladen werden!
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Chat Interface -->
        <div class="space-y-4">
            <!-- Chat Messages -->
            <div class="space-y-3 max-h-96 overflow-y-auto" wire:poll.2s="checkForFunctionCalls">
                @if($this->aiAssistantMessages->count() > 0)
                    @foreach($this->aiAssistantMessages as $message)
                        @if($message->role === 'user')
                            <div class="d-flex justify-end">
                                <div class="max-w-xs bg-primary text-white p-3 rounded-lg">
                                    <p class="text-sm">{{ $message->content }}</p>
                                </div>
                            </div>
                        @elseif($message->role === 'assistant')
                            <div class="d-flex justify-start">
                                <div class="max-w-xs bg-muted-5 p-3 rounded-lg">
                                    <p class="text-sm text-secondary">{{ $message->content }}</p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @else
                    <div class="text-center text-muted py-8">
                        <x-heroicon-o-chat-bubble-left-right class="w-12 h-12 mx-auto mb-3 text-muted" />
                        <p>Starten Sie die Konversation mit der KI-Assistentin</p>
                    </div>
                @endif
                
                @if($this->hasActiveRun)
                    <div class="d-flex justify-start">
                        <div class="max-w-xs bg-muted-5 p-3 rounded-lg">
                            <div class="d-flex items-center gap-2">
                                <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin text-gray-500" />
                                <span class="text-sm text-gray-600">KI-Assistentin denkt nach...</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>


        </div>

        <x-slot name="footer">
            <!-- Message Input im Footer -->
            <div class="d-flex gap-2 flex-grow-1 mr-4">
                <input 
                    type="text" 
                    wire:model="message" 
                    wire:keydown.enter="sendMessage"
                    placeholder="Ihre Nachricht an die KI-Assistentin..."
                    class="flex-grow-1 px-3 py-2 border border-muted rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    :disabled="$this->hasActiveRun"
                >
                <x-ui-button 
                    variant="primary" 
                    size="md"
                    wire:click="sendMessage"
                    :disabled="strlen(trim($message)) === 0 || $this->hasActiveRun"
                >
                    <div class="d-flex items-center gap-2">
                        @if($this->hasActiveRun)
                            <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin" />
                        @else
                            <x-heroicon-o-paper-airplane class="w-4 h-4" />
                        @endif
                        <span>{{ $this->hasActiveRun ? 'Warten...' : 'Senden' }}</span>
                    </div>
                </x-ui-button>
            </div>
            
            <!-- Action Buttons -->
            <div class="d-flex gap-2">
                <x-ui-button variant="secondary-outline" size="sm" wire:click="pauseIntake" :disabled="$this->hasActiveRun">
                    <div class="d-flex items-center gap-2">
                        <x-heroicon-o-pause class="w-4 h-4" />
                        Pausieren
                    </div>
                </x-ui-button>
                <x-ui-button variant="danger-outline" size="sm" wire:click="closeIntakeModal" :disabled="$this->hasActiveRun">
                    <div class="d-flex items-center gap-2">
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                        Abbrechen
                    </div>
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</div>
