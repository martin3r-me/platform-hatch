<?php

namespace Platform\Hatch\Livewire\ProjectIntake;

use Livewire\Component;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchProjectIntakeStep;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\UuidV7;
use Platform\Hatch\Models\HatchTemplateBlock;
use Platform\AiAssistant\Services\ThreadService;
use Platform\AiAssistant\Services\RunService;

class Show extends Component
{
    public HatchProjectIntake $projectIntake;
    
    public $statuses = [
        'draft' => 'Entwurf',
        'in_progress' => 'In Bearbeitung',
        'completed' => 'Abgeschlossen',
        'paused' => 'Pausiert',
        'cancelled' => 'Abgebrochen'
    ];
    
    public $showActivities = false;
    
    // Modal und Chat Properties
    public $showIntakeModal = false;
    public $message = '';
    public $currentBlockIndex = 0;
    public $templateBlocks = [];
    public $currentBlock = null;
    
    public function mount(HatchProjectIntake $projectIntake)
    {
        $this->projectIntake = $projectIntake;
        $this->loadTemplateBlocks();
        $this->determineCurrentBlock();
    }
    
    /**
     * Template-Blöcke laden und sortieren
     */
    public function loadTemplateBlocks()
    {
        if ($this->projectIntake->projectTemplate) {
            $this->templateBlocks = $this->projectIntake->projectTemplate
                ->templateBlocks()
                ->with(['blockDefinition' => function($query) {
                    $query->select('id', 'name', 'ai_prompt', 'validation_rules', 'block_type', 'description', 'conditional_logic', 'response_format', 'fallback_questions', 'ai_behavior');
                }])
                ->orderBy('sort_order')
                ->get();
        }
    }
    
    /**
     * Aktuellen Block bestimmen
     */
    public function determineCurrentBlock()
    {
        if (empty($this->templateBlocks)) {
            return;
        }
        
        // Finde den ersten unvollständigen Block
        foreach ($this->templateBlocks as $index => $templateBlock) {
            $step = $this->projectIntake->intakeSteps
                ->where('template_block_id', $templateBlock->id)
                ->where('is_completed', true)
                ->first();
                
            if (!$step) {
                $this->currentBlockIndex = $index;
                $this->currentBlock = $templateBlock;
                break;
            }
        }
        
        // Wenn alle Blöcke vollständig sind
        if ($this->currentBlock === null) {
            $this->currentBlockIndex = count($this->templateBlocks) - 1;
            $this->currentBlock = $this->templateBlocks->last();
        }
    }
    
    public function startProjectIntake()
    {
        // Modal öffnen
        $this->openIntakeModal();

        // Template Blocks laden und aktuellen Block bestimmen
        $this->loadTemplateBlocks();
        $this->determineCurrentBlock();
        
        // Steps für alle Blöcke erstellen
        $this->createStepsForAllBlocks();
        
        // AI Assistant Thread erstellen/abrufen
        $this->initializeAiAssistantThread();
        
        $this->projectIntake->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
        
        $this->dispatch('notifications:store', [
            'title' => 'Projektierung gestartet',
            'message' => 'Die Projektierung wurde erfolgreich gestartet.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
        
        
    }
    
    /**
     * AI Assistant Thread für diese Projektierung initialisieren
     */
    private function initializeAiAssistantThread()
    {
        $template = $this->projectIntake->projectTemplate;
        if (!$template) {
            throw new \Exception('Kein Template für diese Projektierung zugewiesen.');
        }
        
        // AI Assistant manuell laden
        $assignedAssistant = $template->assignedAiAssistant->first();
        if (!$assignedAssistant) {
            throw new \Exception('Kein AI Assistant für dieses Template zugewiesen.');
        }
        
        // Prüfen ob bereits ein Thread existiert
        $existingThread = $this->projectIntake->activeAiAssistantThread;
        if ($existingThread) {
            $thread = $existingThread;
        } else {
            // Neuen Thread bei OpenAI erstellen
            $threadService = app(ThreadService::class);
            $openaiThreadId = $threadService->createThread([
                'metadata' => [
                    'template_name' => $template->name,
                    'project_intake_id' => (string) $this->projectIntake->id,
                    'current_block_index' => (string) $this->currentBlockIndex,
                ]
            ]);
            
            // Thread-Titel erstellen
            $threadTitle = $this->buildThreadTitle($template);
            
            // Thread lokal erstellen
            $thread = $this->projectIntake->aiAssistantThreads()->create([
                'openai_thread_id' => $openaiThreadId,
                'assistant_id' => $assignedAssistant->id,
                'title' => $threadTitle,
                'status' => 'active',
                'context_metadata' => [
                    'template_name' => $template->name,
                    'project_intake_id' => $this->projectIntake->id,
                    'current_block_index' => $this->currentBlockIndex,
                ],
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
                'ownership_type' => 'team',
            ]);
        }
        
        // System Message mit aktueller Block-Konfiguration senden
        $this->sendSystemMessage($thread);
        
        // Ersten Run erstellen damit Assistant antwortet
        $this->createInitialRun($thread);
    }
    

    
    /**
     * Ersten Run erstellen damit Assistant antwortet
     */
    private function createInitialRun($thread)
    {
        $runService = app(RunService::class);
        
        try {
                    // Aktuelle Block-Function für den Run erstellen
        $currentBlockFunction = null;
        if ($this->currentBlock && $this->currentBlock->blockDefinition) {
            $currentBlockFunction = $this->convertResponseFormatToFunction($this->currentBlock->blockDefinition);
            
                    \Log::channel('project_intake')->info('Creating initial run with block function', [
            'block_name' => $this->currentBlock->blockDefinition->name,
            'function_name' => $currentBlockFunction['function']['name'] ?? 'unknown',
            'project_intake_id' => $this->projectIntake->id,
        ]);
        } else {
            \Log::channel('project_intake')->warning('No current block or block definition found for initial run', [
                'current_block' => $this->currentBlock?->id,
                'project_intake_id' => $this->projectIntake->id,
            ]);
        }
        
        $runData = [];
        if ($currentBlockFunction) {
            $runData['tools'] = [$currentBlockFunction];
        }
            
            // Run bei OpenAI erstellen
            $runId = $runService->createRun(
                $thread->openai_thread_id,
                $thread->assistant->openai_assistant_id,
                $runData
            );
            
            // Run lokal speichern
            $thread->runs()->create([
                'openai_run_id' => $runId,
                'assistant_id' => $thread->assistant_id,
                'status' => 'queued',
                'type' => 'initial_run',
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);
            
        } catch (\Exception $e) {
            logger()->error('Initial Run Error', [
                'error' => $e->getMessage(),
                'thread_id' => $thread->id
            ]);
        }
    }
    
    /**
     * System Message mit Block-Konfiguration senden
     */
    private function sendSystemMessage($thread)
    {
        if (!$this->currentBlock || !$this->currentBlock->blockDefinition) {
            return;
        }
        
        $template = $this->projectIntake->projectTemplate;
        $blockDefinition = $this->currentBlock->blockDefinition;
        
        $systemMessage = $this->buildSystemMessage($template, $blockDefinition);
        
        $threadService = app(ThreadService::class);
        $openaiMessageId = $threadService->createMessage(
            $thread->openai_thread_id,
            'user',
            $systemMessage
        );
        
        // Message lokal speichern
        $thread->messages()->create([
            'openai_message_id' => $openaiMessageId,
            'role' => 'user',
            'content' => $systemMessage,
            'team_id' => auth()->user()->current_team_id,
            'created_by_user_id' => auth()->id(),
        ]);
    }
    
    /**
     * System Message für aktuellen Block erstellen
     */
    private function buildSystemMessage($template, $blockDefinition)
    {
        $message = "Du bist ein KI-Assistent für die Projektierung.\n\n";
        $message .= "Template: {$template->name}\n";
        $message .= "Aktueller Block: {$blockDefinition->name}\n\n";
        
        if ($template->ai_personality) {
            $message .= "Persönlichkeit: {$template->ai_personality}\n";
        }
        
        if ($template->industry_context) {
            $message .= "Branche: {$template->industry_context}\n";
        }
        
        $message .= "\nDeine Aufgabe:\n";
        $message .= $blockDefinition->ai_prompt ?? "Sammle die erforderlichen Informationen für diesen Block.";
        
        // Spezifische Felder aus dem response_format hinzufügen
        if ($blockDefinition->response_format && is_array($blockDefinition->response_format)) {
            $message .= "\n\nDu musst folgende Informationen sammeln:\n";
            foreach ($blockDefinition->response_format as $field) {
                $message .= "- " . $field['description'] . "\n";
            }
        }
        
        return $message;
    }
    
    /**
     * Thread-Titel für die Projektierung erstellen
     */
    private function buildThreadTitle($template)
    {
        $title = "Hatch: ";
        $title .= $this->projectIntake->name ?? 'Projektierung';
        
        if ($template->name) {
            $title .= " ({$template->name})";
        }
        
        $title .= " - " . now()->format('d.m.Y H:i');
        
        return $title;
    }
    
    /**
     * Response Format in OpenAI Function Schema konvertieren
     */
    private function convertResponseFormatToFunction($blockDefinition)
    {
        if (!$blockDefinition->response_format || !is_array($blockDefinition->response_format)) {
            return null;
        }
        
        $properties = [];
        $required = [];
        
        foreach ($blockDefinition->response_format as $field) {
            $fieldName = $this->generateFieldName($field['description'] ?? 'field');
            $fieldType = $this->mapFieldType($field['type'] ?? 'string');
            
            $properties[$fieldName] = [
                'type' => $fieldType,
                'description' => $field['description'] ?? 'Feld',
            ];
            
            // Prüfen ob Feld required ist
            if (isset($field['validations']) && is_array($field['validations'])) {
                foreach ($field['validations'] as $validation) {
                    if ($validation['type'] === 'required') {
                        $required[] = $fieldName;
                        break;
                    }
                }
            }
        }
        
        return [
            'type' => 'function',
            'function' => [
                'name' => 'complete_block',
                'description' => "Block '{$blockDefinition->name}' abschließen wenn alle erforderlichen Daten gesammelt wurden",
                'parameters' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                ]
            ]
        ];
    }
    
    /**
     * Feldname aus Beschreibung generieren
     */
    private function generateFieldName($description)
    {
        // Dynamische Feldnamen aus der Beschreibung generieren
        $description = strtolower($description);
        
        // Erste Wörter als snake_case
        $words = explode(' ', $description);
        $name = implode('_', array_slice($words, 0, 3)); // Max 3 Wörter
        return preg_replace('/[^a-z0-9_]/', '', $name) ?: 'field';
    }
    
    /**
     * Feldtyp für OpenAI Schema mappen
     */
    private function mapFieldType($type)
    {
        $typeMap = [
            'string' => 'string',
            'number' => 'number',
            'boolean' => 'boolean',
            'date' => 'string',
            'email' => 'string',
            'url' => 'string',
            'array' => 'array',
            'object' => 'object',
        ];
        
        return $typeMap[$type] ?? 'string';
    }
    
    /**
     * Intake Modal öffnen
     */
    public function openIntakeModal()
    {
        $this->showIntakeModal = true;
        $this->determineCurrentBlock();
    }
    
    /**
     * Intake Modal schließen
     */
    public function closeIntakeModal()
    {
        $this->showIntakeModal = false;
        $this->message = '';
    }
    
    /**
     * Nachricht senden und an AI Assistant weiterleiten
     */
    public function sendMessage()
    {
        if (empty(trim($this->message))) {
            return;
        }

        // AI Assistant Thread abrufen
        $thread = $this->projectIntake->activeAiAssistantThread;
        if (!$thread) {
            $this->dispatch('notifications:store', [
                'title' => 'Fehler',
                'message' => 'AI Assistant Thread nicht gefunden.',
                'notice_type' => 'error',
                'noticable_type' => \Platform\Core\Models\User::class,
                'noticable_id' => auth()->id(),
            ]);
            return;
        }

        // User Message an AI Assistant senden
        $this->sendUserMessageToAssistant($thread);
        
        // Message-Input leeren
        $this->message = '';
        
        // Event für UI-Update
        $this->dispatch('message-sent');
    }
    
    /**
     * User Message an AI Assistant senden
     */
    private function sendUserMessageToAssistant($thread)
    {
        $threadService = app(ThreadService::class);
        $runService = app(RunService::class);
        
        try {
            // User Message erstellen
            $messageId = $threadService->createMessage(
                $thread->openai_thread_id,
                'user',
                $this->message
            );
            
            // Message lokal speichern
            $thread->messages()->create([
                'openai_message_id' => $messageId,
                'role' => 'user',
                'content' => $this->message,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);
            
            // Aktuelle Block-Function für den Run erstellen
            $currentBlockFunction = null;
            if ($this->currentBlock && $this->currentBlock->blockDefinition) {
                $currentBlockFunction = $this->convertResponseFormatToFunction($this->currentBlock->blockDefinition);
                
                \Log::channel('project_intake')->info('Creating run with block function', [
                    'block_name' => $this->currentBlock->blockDefinition->name,
                    'function_name' => $currentBlockFunction['function']['name'] ?? 'unknown',
                    'project_intake_id' => $this->projectIntake->id,
                ]);
            } else {
                \Log::channel('project_intake')->warning('No current block or block definition found', [
                    'current_block' => $this->currentBlock?->id,
                    'project_intake_id' => $this->projectIntake->id,
                ]);
            }
            
            $runData = [];
            if ($currentBlockFunction) {
                $runData['tools'] = [$currentBlockFunction];
            }
            
            // Run erstellen für AI Antwort
            $runId = $runService->createRun(
                $thread->openai_thread_id,
                $thread->assistant->openai_assistant_id,
                $runData
            );
            
            // Run lokal speichern
            $thread->runs()->create([
                'openai_run_id' => $runId,
                'assistant_id' => $thread->assistant_id,
                'status' => 'queued',
                'type' => 'message_creation',
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('notifications:store', [
                'title' => 'Fehler beim Senden',
                'message' => 'Die Nachricht konnte nicht gesendet werden: ' . $e->getMessage(),
                'notice_type' => 'error',
                'noticable_type' => \Platform\Core\Models\User::class,
                'noticable_id' => auth()->id(),
            ]);
        }
    }
    
    /**
     * AI Assistant Messages für das Chat-Interface laden
     */
    public function getAiAssistantMessagesProperty()
    {
        // Direkt über die Datenbank abfragen statt über Beziehungen
        $thread = \Platform\AiAssistant\Models\AiAssistantThread::where('context_type', HatchProjectIntake::class)
            ->where('context_id', $this->projectIntake->id)
            ->where('status', 'active')
            ->first();
            
        if (!$thread) {
            return collect();
        }
        
        return $thread->messages()
            ->orderBy('created_at')
            ->get();
    }
    
    /**
     * Prüfen ob ein aktiver Run läuft
     */
    public function getHasActiveRunProperty()
    {
        // Direkt über die Datenbank abfragen statt über Beziehungen
        $thread = \Platform\AiAssistant\Models\AiAssistantThread::where('context_type', HatchProjectIntake::class)
            ->where('context_id', $this->projectIntake->id)
            ->where('status', 'active')
            ->first();
            
        if (!$thread) {
            return false;
        }
        
        return $thread->runs()
            ->whereIn('status', ['queued', 'in_progress', 'requires_action'])
            ->exists();
    }
    
    /**
     * Prüft auf Function Calls und verarbeitet Block-Completion
     */
    public function checkForFunctionCalls()
    {
        $thread = \Platform\AiAssistant\Models\AiAssistantThread::where('context_type', HatchProjectIntake::class)
            ->where('context_id', $this->projectIntake->id)
            ->where('status', 'active')
            ->first();
            
        if (!$thread) {
            \Log::channel('project_intake')->info('No active thread found for project intake', [
                'project_intake_id' => $this->projectIntake->id,
            ]);
            return;
        }
        
        // Runs mit Function Calls finden (nur aktive Runs, nicht completed/archived)
        $functionCallRuns = $thread->runs()
            ->whereIn('status', ['function_called', 'requires_action'])
            ->get();
            
        \Log::channel('project_intake')->info('Found function call runs', [
            'count' => $functionCallRuns->count(),
            'runs' => $functionCallRuns->pluck('status', 'openai_run_id')->toArray(),
        ]);
            
        foreach ($functionCallRuns as $run) {
            \Log::channel('project_intake')->info('Processing run', [
                'run_id' => $run->openai_run_id,
                'status' => $run->status,
            ]);
            
            if ($run->status === 'requires_action') {
                \Log::channel('project_intake')->info('Processing requires_action run', [
                    'run_id' => $run->openai_run_id,
                ]);
                
                // Run Service aufrufen um requires_action zu verarbeiten
                $runService = app(\Platform\AiAssistant\Services\RunService::class);
                $runService->processCompletedRun($run);
                
                // Run ist noch aktiv - warten bis nächster Poll
                continue;
            }
            

            
            // Tool Call für diesen Run finden
            $toolCall = \Platform\AiAssistant\Models\AiAssistantToolCall::where('run_id', $run->id)
                ->where('type', 'function')
                ->where('function_name', 'complete_block')
                ->where('status', 'completed')
                ->first();
                
            if ($toolCall) {
                \Log::channel('project_intake')->info('Found completed tool call', [
                    'tool_call_id' => $toolCall->id,
                    'function_name' => $toolCall->function_name,
                ]);
                
                \Log::channel('project_intake')->info('=== BLOCK COMPLETION START ===', [
                    'project_intake_id' => $this->projectIntake->id,
                    'tool_call_id' => $toolCall->id,
                    'function_arguments' => $toolCall->function_arguments,
                ]);
                
                \Log::channel('project_intake')->info('Current state BEFORE handleBlockCompletion', [
                    'current_block_id' => $this->currentBlock?->id,
                    'current_block_name' => $this->currentBlock?->blockDefinition?->name ?? 'Unknown',
                    'current_block_index' => $this->currentBlockIndex,
                ]);
                
                $this->handleBlockCompletion($toolCall->function_arguments);
                
                \Log::channel('project_intake')->info('All intake steps after handleBlockCompletion', [
                    'steps' => $this->projectIntake->intakeSteps->map(function($step) {
                        return [
                            'id' => $step->id,
                            'template_block_id' => $step->template_block_id,
                            'is_completed' => $step->is_completed,
                            'completed_at' => $step->completed_at,
                        ];
                    })->toArray(),
                ]);
                
                \Log::channel('project_intake')->info('=== BLOCK COMPLETION END ===');
                
                // Run als archived markieren (damit kein aktiver Run mehr da ist)
                $run->update(['status' => 'archived']);
                
                // Tool Call als verarbeitet markieren
                $toolCall->update(['status' => 'processed']);
            } else {
                \Log::channel('project_intake')->info('No completed tool call found for run', [
                    'run_id' => $run->id,
                ]);
            }
        }
    }
    
    /**
     * Prüft einen spezifischen Run auf Tool Calls
     */
    private function checkForToolCallsInRun($run)
    {
        \Log::channel('project_intake')->info('Checking for tool calls in run', [
            'run_id' => $run->openai_run_id,
            'run_status' => $run->status,
        ]);
        
        // Tool Call für diesen Run finden
        $toolCall = \Platform\AiAssistant\Models\AiAssistantToolCall::where('run_id', $run->id)
            ->where('type', 'function')
            ->where('function_name', 'complete_block')
            ->where('status', 'completed')
            ->first();
            
        if ($toolCall) {
            \Log::channel('project_intake')->info('Found completed tool call in run', [
                'tool_call_id' => $toolCall->id,
                'function_name' => $toolCall->function_name,
                'run_id' => $run->openai_run_id,
            ]);
            
            $this->handleBlockCompletion($toolCall->function_arguments);
            
            // Run als verarbeitet markieren
            $run->update(['status' => 'function_processed']);
            
            // Tool Call als verarbeitet markieren
            $toolCall->update(['status' => 'processed']);
        } else {
            \Log::channel('project_intake')->info('No completed tool call found in run', [
                'run_id' => $run->openai_run_id,
            ]);
        }
    }
    
    /**
     * Verarbeitet Block-Completion
     */
    private function handleBlockCompletion(array $arguments)
    {
        \Log::channel('project_intake')->info('handleBlockCompletion called', [
            'project_intake_id' => $this->projectIntake->id,
            'arguments' => $arguments,
            'current_block' => $this->currentBlock?->id,
        ]);
        
        // Aktuellen Block finden
        $currentBlock = $this->currentBlock;
        if (!$currentBlock) {
            \Log::channel('project_intake')->warning('No current block found in handleBlockCompletion');
            return;
        }
        
        // Step als abgeschlossen markieren
        $step = $this->projectIntake->intakeSteps()
            ->where('template_block_id', $currentBlock->id)
            ->where('is_completed', false)
            ->first();
            
        if ($step) {
            $step->update([
                'is_completed' => true,
                'completed_at' => now(),
                'answers' => $arguments,
            ]);
            
            \Log::channel('project_intake')->info('Step marked as completed', [
                'step_id' => $step->id,
                'template_block_id' => $currentBlock->id,
            ]);
        } else {
            \Log::channel('project_intake')->warning('No step found to mark as completed', [
                'template_block_id' => $currentBlock->id,
            ]);
        }
        
        // Prüfen ob es der letzte Block ist
        $isLast = $this->isLastBlock();
        \Log::channel('project_intake')->info('Checking if last block', [
            'is_last_block' => $isLast,
            'current_block_name' => $currentBlock->blockDefinition->name ?? 'Unknown',
        ]);
        
        if ($isLast) {
            \Log::channel('project_intake')->info('Completing project intake');
            $this->completeProjectIntake();
        } else {
            \Log::channel('project_intake')->info('Starting next block run');
            $this->startNextBlockRun();
        }
        
        \Log::channel('project_intake')->info('Block completed', [
            'project_intake_id' => $this->projectIntake->id,
            'block_name' => $currentBlock->blockDefinition->name ?? 'Unknown',
            'arguments' => $arguments,
            'is_last_block' => $isLast,
        ]);
    }
    
    /**
     * Prüft ob es der letzte Block ist
     */
    private function isLastBlock(): bool
    {
        $templateBlocks = $this->projectIntake->projectTemplate->templateBlocks->sortBy('sort_order');
        $currentIndex = $templateBlocks->search(function($block) {
            return $block->id === $this->currentBlock->id;
        });
        
        return $currentIndex === $templateBlocks->count() - 1;
    }
    
    // Alle Exit-Logik wurde entfernt - nur Dialog bleibt!
    // Der Chat bleibt offen und der AI-Assistent führt proaktiv durch die Fragen
    
    /**
     * Aktuellen Block als abgeschlossen markieren
     */
    private function completeCurrentBlock()
    {
        $step = HatchProjectIntakeStep::where('project_intake_id', $this->projectIntake->id)
            ->where('template_block_id', $this->currentBlock->id)
            ->first();
            
        if ($step) {
            $step->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        }
        
        // Zum nächsten Block wechseln
        $this->moveToNextBlock();
    }
    
    /**
     * Wechselt zum nächsten Block
     */
    private function moveToNextBlock()
    {
        \Log::channel('project_intake')->info('moveToNextBlock called', [
            'project_intake_id' => $this->projectIntake->id,
            'current_block_id' => $this->currentBlock?->id,
        ]);
        
        $templateBlocks = $this->projectIntake->projectTemplate->templateBlocks->sortBy('sort_order');
        $currentIndex = $templateBlocks->search(function($block) {
            return $block->id === $this->currentBlock->id;
        });
        
        \Log::channel('project_intake')->info('Found current block index', [
            'current_index' => $currentIndex,
            'total_blocks' => $templateBlocks->count(),
        ]);
        
        if ($currentIndex !== false && $currentIndex < $templateBlocks->count() - 1) {
            $nextBlock = $templateBlocks[$currentIndex + 1];
            $this->currentBlockIndex = $nextBlock->sort_order;
            $this->currentBlock = $nextBlock;
            
            \Log::channel('project_intake')->info('Found next block', [
                'next_block_id' => $nextBlock->id,
                'next_block_name' => $nextBlock->blockDefinition->name ?? 'Unknown',
                'next_block_sort_order' => $nextBlock->sort_order,
            ]);
            
            // Neuen Step erstellen falls nötig
            $this->ensureStepExists($nextBlock);
            
            // Neuen Run für den nächsten Block starten
            $this->startNextBlockRun();
            
            \Log::channel('project_intake')->info('Moved to next block', [
                'project_intake_id' => $this->projectIntake->id,
                'next_block_name' => $nextBlock->blockDefinition->name ?? 'Unknown',
                'next_block_index' => $this->currentBlockIndex,
            ]);
        } else {
            \Log::channel('project_intake')->warning('No next block found', [
                'current_index' => $currentIndex,
                'total_blocks' => $templateBlocks->count(),
            ]);
        }
    }
    
    /**
     * Erstellt Steps für alle Blöcke des Templates
     */
    private function createStepsForAllBlocks()
    {
        $templateBlocks = $this->projectIntake->projectTemplate->templateBlocks->sortBy('sort_order');
        
        foreach ($templateBlocks as $block) {
            $existingStep = $this->projectIntake->intakeSteps()
                ->where('template_block_id', $block->id)
                ->first();
                
            if (!$existingStep) {
                $this->projectIntake->intakeSteps()->create([
                    'template_block_id' => $block->id,
                    'block_definition_id' => $block->block_definition_id,
                    'sort_order' => $block->sort_order,
                    'is_completed' => false,
                    'team_id' => auth()->user()->current_team_id,
                    'created_by_user_id' => auth()->id(),
                ]);
                
                \Log::channel('project_intake')->info('Created step for block', [
                    'block_id' => $block->id,
                    'block_name' => $block->blockDefinition->name ?? 'Unknown',
                    'project_intake_id' => $this->projectIntake->id,
                ]);
            }
        }
    }
    
    /**
     * Stellt sicher, dass ein Step für den Block existiert
     */
    private function ensureStepExists($block)
    {
        $existingStep = $this->projectIntake->intakeSteps()
            ->where('template_block_id', $block->id)
            ->first();
            
        if (!$existingStep) {
            $this->projectIntake->intakeSteps()->create([
                'template_block_id' => $block->id,
                'block_definition_id' => $block->block_definition_id,
                'sort_order' => $block->sort_order,
                'is_completed' => false,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);
            
            \Log::channel('project_intake')->info('Created new step for block', [
                'block_id' => $block->id,
                'block_name' => $block->blockDefinition->name ?? 'Unknown',
                'project_intake_id' => $this->projectIntake->id,
            ]);
        }
    }
    
    /**
     * Warten bis alle Runs abgeschlossen sind
     */
    private function waitForRunCompletion($thread)
    {
        $waitTime = 0;
        
        while (true) {
            // Prüfen ob noch ein aktiver Run läuft (alle außer archived und function_processed)
            $activeRun = $thread->runs()
                ->whereNotIn('status', ['archived', 'function_processed'])
                ->first();
                
            if (!$activeRun) {
                \Log::channel('project_intake')->info('No active runs found, proceeding to next block', [
                    'total_wait_time' => $waitTime,
                ]);
                return;
            }
            
            \Log::channel('project_intake')->info('Waiting for run completion', [
                'run_id' => $activeRun->openai_run_id,
                'status' => $activeRun->status,
                'wait_time' => $waitTime,
                'all_runs' => $thread->runs()->pluck('status', 'openai_run_id')->toArray(),
            ]);
            
            sleep(2);
            $waitTime++;
        }
    }
    
    /**
     * Startet einen neuen Run für den nächsten Block
     */
    private function startNextBlockRun()
    {
        $thread = \Platform\AiAssistant\Models\AiAssistantThread::where('context_type', HatchProjectIntake::class)
            ->where('context_id', $this->projectIntake->id)
            ->where('status', 'active')
            ->first();
            
        if (!$thread) {
            return;
        }
        
        // Warten bis kein aktiver Run mehr läuft
        $this->waitForRunCompletion($thread);
        
        // Nächsten uncompleted Step finden und Block wechseln
        $nextStep = $this->projectIntake->intakeSteps()
            ->where('is_completed', false)
            ->orderBy('id')
            ->first();
            
        if ($nextStep) {
            // Template Block für diesen Step laden
            $nextTemplateBlock = $this->projectIntake->projectTemplate->templateBlocks
                ->where('id', $nextStep->template_block_id)
                ->first();
                
            if ($nextTemplateBlock) {
                $oldBlockId = $this->currentBlock?->id;
                $oldBlockName = $this->currentBlock?->blockDefinition?->name ?? 'Unknown';
                
                $this->currentBlock = $nextTemplateBlock;
                $this->currentBlockIndex = $nextTemplateBlock->sort_order;
                
                \Log::channel('project_intake')->info('Block switch in startNextBlockRun', [
                    'old_block_id' => $oldBlockId,
                    'old_block_name' => $oldBlockName,
                    'new_block_id' => $this->currentBlock->id,
                    'new_block_name' => $this->currentBlock->blockDefinition->name ?? 'Unknown',
                    'new_block_index' => $this->currentBlockIndex,
                ]);
            }
        }
        
        // System Message für den neuen Block senden
        $this->sendSystemMessage($thread);
        
        // Neuen Run mit Block-Function erstellen
        $this->createInitialRun($thread);
        
        \Log::channel('project_intake')->info('Started next block run', [
            'project_intake_id' => $this->projectIntake->id,
            'block_name' => $this->currentBlock->blockDefinition->name ?? 'Unknown',
        ]);
    }
    
    /**
     * Projektierung abschließen
     */
    private function completeProjectIntake()
    {
        // Finale Nachricht an den Assistant senden
        $this->sendFinalMessage();
        
        $this->projectIntake->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        $this->dispatch('notifications:store', [
            'title' => 'Projektierung abgeschlossen',
            'message' => 'Alle Blöcke wurden erfolgreich bearbeitet.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
        
        $this->closeIntakeModal();
    }
    
    /**
     * Sendet finale Nachricht an den Assistant
     */
    private function sendFinalMessage()
    {
        $thread = \Platform\AiAssistant\Models\AiAssistantThread::where('context_type', HatchProjectIntake::class)
            ->where('context_id', $this->projectIntake->id)
            ->where('status', 'active')
            ->first();
            
        if (!$thread) {
            return;
        }
        
        $finalMessage = "Perfekt! Wir haben alle erforderlichen Informationen gesammelt. Die Projektierung ist nun abgeschlossen. Vielen Dank für Ihre Zeit und die detaillierten Angaben. Falls Sie weitere Fragen haben oder Änderungen benötigen, können Sie sich gerne melden.";
        
        $threadService = app(\Platform\AiAssistant\Services\ThreadService::class);
        $runService = app(\Platform\AiAssistant\Services\RunService::class);
        
        try {
            // Finale Nachricht senden
            $messageId = $threadService->createMessage(
                $thread->openai_thread_id,
                'user',
                $finalMessage
            );
            
            // Message lokal speichern
            $thread->messages()->create([
                'openai_message_id' => $messageId,
                'role' => 'user',
                'content' => $finalMessage,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);
            
            // Finalen Run erstellen (ohne Functions)
            $runId = $runService->createRun(
                $thread->openai_thread_id,
                $thread->assistant->openai_assistant_id
            );
            
            $thread->runs()->create([
                'openai_run_id' => $runId,
                'assistant_id' => $thread->assistant_id,
                'status' => 'queued',
                'type' => 'final_message',
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);
            
            logger()->info('Final message sent', [
                'project_intake_id' => $this->projectIntake->id,
            ]);
            
        } catch (\Exception $e) {
            logger()->error('Final message error', [
                'error' => $e->getMessage(),
                'project_intake_id' => $this->projectIntake->id,
            ]);
        }
    }
    
    /**
     * Intake pausieren
     */
    public function pauseIntake()
    {
        $this->projectIntake->update([
            'status' => 'paused',
        ]);
        
        $this->dispatch('notifications:store', [
            'title' => 'Projektierung pausiert',
            'message' => 'Sie können später fortfahren.',
            'notice_type' => 'info',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
        
        $this->closeIntakeModal();
    }

    /**
     * PDF Bericht generieren
     */
    public function generatePdfReport()
    {
        \Log::channel('project_intake')->info('Generating PDF report', [
            'project_intake_id' => $this->projectIntake->id,
        ]);
        
        // TODO: PDF Generation implementieren
        $this->dispatch('notifications:store', [
            'title' => 'PDF Bericht',
            'message' => 'PDF Bericht wird generiert...',
            'notice_type' => 'info',
            'noticable_type' => \Platform\Hatch\Models\HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }
    
    /**
     * Projekt erstellen
     */
    public function createProject()
    {
        \Log::channel('project_intake')->info('Creating project from intake', [
            'project_intake_id' => $this->projectIntake->id,
        ]);
        
        // TODO: Projekt erstellen implementieren
        $this->dispatch('notifications:store', [
            'title' => 'Projekt erstellen',
            'message' => 'Projekt wird erstellt...',
            'notice_type' => 'info',
            'noticable_type' => \Platform\Hatch\Models\HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }
    
    /**
     * Aufgaben erstellen
     */
    public function createTasks()
    {
        \Log::channel('project_intake')->info('Creating tasks from intake', [
            'project_intake_id' => $this->projectIntake->id,
        ]);
        
        // TODO: Aufgaben erstellen implementieren
        $this->dispatch('notifications:store', [
            'title' => 'Aufgaben erstellen',
            'message' => 'Aufgaben werden erstellt...',
            'notice_type' => 'info',
            'noticable_type' => \Platform\Hatch\Models\HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }
    
    /**
     * Markdown Export
     */
    public function exportMarkdown()
    {
        \Log::channel('project_intake')->info('Exporting markdown', [
            'project_intake_id' => $this->projectIntake->id,
        ]);
        
        // TODO: Markdown Export implementieren
        $this->dispatch('notifications:store', [
            'title' => 'Markdown Export',
            'message' => 'Markdown wird exportiert...',
            'notice_type' => 'info',
            'noticable_type' => \Platform\Hatch\Models\HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }
    
    /**
     * Daten anzeigen
     */
    public function showData()
    {
        \Log::channel('project_intake')->info('Showing intake data', [
            'project_intake_id' => $this->projectIntake->id,
        ]);
        
        // TODO: Daten anzeigen implementieren
        $this->dispatch('notifications:store', [
            'title' => 'Daten anzeigen',
            'message' => 'Daten werden geladen...',
            'notice_type' => 'info',
            'noticable_type' => \Platform\Hatch\Models\HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function render()
    {
        // Beziehungen mit allen Details laden
        $this->projectIntake->load([
            'projectTemplate',
            'intakeSteps.templateBlock.blockDefinition'
        ]);
        
        $activities = $this->projectIntake->activities()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('hatch::livewire.project-intake.show', [
            'projectIntake' => $this->projectIntake,
            'activities' => $activities,
            'currentBlock' => $this->currentBlock,
            'currentBlockIndex' => $this->currentBlockIndex,
            'templateBlocks' => $this->templateBlocks,
        ])->layout('platform::layouts.app');
    }
}
