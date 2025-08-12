<?php

namespace Platform\Hatch\Livewire\ProjectIntake;

use Livewire\Component;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectIntakeStep;
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

    // Modal & Chat
    public $showIntakeModal = false;
    public $message = '';

    // Aktueller Kontext (UI)
    public $currentBlockIndex = 0;
    public $templateBlocks = [];
    public $currentBlock = null;

    // WICHTIG: Den tatsächlich bearbeiteten Step halten
    public ?int $currentStepId = null;

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
        \Log::channel('project_intake')->info('[loadTemplateBlocks] start', [
            'project_intake_id' => $this->projectIntake->id,
        ]);

        if ($this->projectIntake->projectTemplate) {
            $this->templateBlocks = $this->projectIntake->projectTemplate
                ->templateBlocks()
                ->with(['blockDefinition' => function($query) {
                    $query->select(
                        'id','name','ai_prompt','validation_rules','block_type','description',
                        'conditional_logic','response_format','fallback_questions','ai_behavior'
                    );
                }])
                ->orderBy('sort_order')
                ->get()
                ->values();

            \Log::channel('project_intake')->info('[loadTemplateBlocks] loaded', [
                'blocks_count' => $this->templateBlocks->count(),
                'block_ids' => $this->templateBlocks->pluck('id')->all(),
            ]);
        } else {
            \Log::channel('project_intake')->warning('[loadTemplateBlocks] no projectTemplate attached');
        }
    }

    /**
     * Den **nächsten offenen Step** ermitteln (Single Source of Truth)
     */
    private function nextOpenStep(): ?HatchProjectIntakeStep
    {
        return $this->projectIntake->intakeSteps()
            ->where('is_completed', false)
            ->orderBy('id')
            ->first();
    }

    /**
     * Aktuellen Block/Step bestimmen – strikt über Steps und mit Logging
     */
    public function determineCurrentBlock()
    {
        \Log::channel('project_intake')->info('[determineCurrentBlock] start', [
            'project_intake_id' => $this->projectIntake->id,
        ]);

        if (empty($this->templateBlocks)) {
            \Log::channel('project_intake')->warning('[determineCurrentBlock] no templateBlocks');
            return;
        }

        // Frische Daten holen, damit Steps aktuell sind
        $this->projectIntake->unsetRelation('intakeSteps');
        $this->projectIntake->loadMissing(['intakeSteps','projectTemplate.templateBlocks.blockDefinition']);

        $nextStep = $this->nextOpenStep();

        if ($nextStep) {
            $this->currentStepId = $nextStep->id;

            // Finde den zugehörigen Template-Block
            $block = $this->projectIntake->projectTemplate->templateBlocks
                ->firstWhere('id', $nextStep->template_block_id);

            $this->currentBlock = $block;

            $sorted = $this->projectIntake->projectTemplate->templateBlocks->sortBy('sort_order')->values();
            $this->currentBlockIndex = $sorted->search(fn($b) => $b->id === $block->id);

            \Log::channel('project_intake')->info('[determineCurrentBlock] using next open step', [
                'current_step_id' => $this->currentStepId,
                'template_block_id' => $block?->id,
                'block_name' => $block?->blockDefinition?->name,
                'block_index' => $this->currentBlockIndex,
            ]);

            return;
        }

        // Alles fertig – Anzeige auf letzten Block setzen
        $sorted = $this->projectIntake->projectTemplate->templateBlocks->sortBy('sort_order')->values();
        if ($sorted->isNotEmpty()) {
            $this->currentStepId = null;
            $this->currentBlock = $sorted->last();
            $this->currentBlockIndex = max(0, $sorted->count() - 1);
        }

        \Log::channel('project_intake')->info('[determineCurrentBlock] no open steps left', [
            'current_block_id' => $this->currentBlock?->id,
            'block_name' => $this->currentBlock?->blockDefinition?->name,
            'block_index' => $this->currentBlockIndex,
        ]);
    }

    public function startProjectIntake()
    {
        \Log::channel('project_intake')->info('[startProjectIntake] start', [
            'project_intake_id' => $this->projectIntake->id,
        ]);

        $this->openIntakeModal();

        $this->loadTemplateBlocks();
        $this->createStepsForAllBlocks();
        $this->determineCurrentBlock();

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

        \Log::channel('project_intake')->info('[startProjectIntake] done', [
            'status' => 'in_progress',
        ]);
    }

    /**
     * AI Assistant Thread initialisieren
     */
    private function initializeAiAssistantThread()
    {
        $template = $this->projectIntake->projectTemplate;
        if (!$template) throw new \Exception('Kein Template für diese Projektierung zugewiesen.');

        $assignedAssistant = $template->assignedAiAssistant->first();
        if (!$assignedAssistant) throw new \Exception('Kein AI Assistant für dieses Template zugewiesen.');

        $existingThread = $this->projectIntake->activeAiAssistantThread;
        if ($existingThread) {
            $thread = $existingThread;
            \Log::channel('project_intake')->info('[initializeAiAssistantThread] reusing thread', [
                'thread_id' => $thread->id,
                'openai_thread_id' => $thread->openai_thread_id,
            ]);
        } else {
            $threadService = app(ThreadService::class);
            $openaiThreadId = $threadService->createThread([
                'metadata' => [
                    'template_name' => $template->name,
                    'project_intake_id' => (string) $this->projectIntake->id,
                    'current_block_index' => (string) $this->currentBlockIndex,
                ]
            ]);

            $threadTitle = $this->buildThreadTitle($template);

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

            \Log::channel('project_intake')->info('[initializeAiAssistantThread] created thread', [
                'thread_id' => $thread->id,
                'openai_thread_id' => $thread->openai_thread_id,
            ]);
        }

        $this->sendSystemMessage($thread);
        $this->createInitialRun($thread);
    }

    /**
     * Ersten Run erstellen
     */
    private function createInitialRun($thread)
    {
        $runService = app(RunService::class);

        try {
            $currentBlockFunction = null;
            if ($this->currentBlock && $this->currentBlock->blockDefinition) {
                $currentBlockFunction = $this->convertResponseFormatToFunction($this->currentBlock->blockDefinition);
            }



            $runData = [];
            if ($currentBlockFunction) {
                $runData['tools'] = [$currentBlockFunction];
            }
            // Hilfreich fürs Debugging: Step/Block in Run-Metadaten
            $runData['metadata'] = [
                'project_intake_id'    => (string) $this->projectIntake->id,
                'current_step_id'      => (string) optional(
                    $this->projectIntake->intakeSteps()
                        ->where('template_block_id', $this->currentBlock->id)
                        ->where('is_completed', false)
                        ->first()
                )->id,
                'template_block_id'    => (string) $this->currentBlock->id,
                'template_block_name'  => $this->currentBlock->blockDefinition->name ?? 'Unknown',
            ];

            \Log::channel('project_intake')->info('[createInitialRun] creating run', [
                'openai_thread_id' => $thread->openai_thread_id,
                'assistant_id' => $thread->assistant->openai_assistant_id,
                'metadata' => $runData['metadata'],
                'has_tools' => (bool)$currentBlockFunction
            ]);

            $runId = $runService->createRun(
                $thread->openai_thread_id,
                $thread->assistant->openai_assistant_id,
                $runData
            );

            $thread->runs()->create([
                'openai_run_id' => $runId,
                'assistant_id' => $thread->assistant_id,
                'status' => 'queued',
                'type' => 'initial_run',
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);

            \Log::channel('project_intake')->info('[createInitialRun] run created', [
                'openai_run_id' => $runId,
            ]);
        } catch (\Exception $e) {
            \Log::channel('project_intake')->error('[createInitialRun] error', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * System Message mit Block-Konfiguration senden
     */
    private function sendSystemMessage($thread)
    {
        if (!$this->currentBlock || !$this->currentBlock->blockDefinition) {
            \Log::channel('project_intake')->warning('[sendSystemMessage] no currentBlock or blockDefinition');
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

        $thread->messages()->create([
            'openai_message_id' => $openaiMessageId,
            'role' => 'user',
            'content' => $systemMessage,
            'team_id' => auth()->user()->current_team_id,
            'created_by_user_id' => auth()->id(),
        ]);

        \Log::channel('project_intake')->info('[sendSystemMessage] sent', [
            'thread_id' => $thread->id,
            'openai_message_id' => $openaiMessageId,
            'block_name' => $blockDefinition->name,
            'current_step_id' => $this->currentStepId,
        ]);
    }

    private function buildSystemMessage($template, $blockDefinition)
    {
        $message  = "Du bist ein KI-Assistent für die Projektierung.\n\n";
        $message .= "Template: {$template->name}\n";
        $message .= "Aktueller Block: {$blockDefinition->name}\n\n";

        if ($template->ai_personality) $message .= "Persönlichkeit: {$template->ai_personality}\n";
        if ($template->industry_context) $message .= "Branche: {$template->industry_context}\n";

        $message .= "\nDeine Aufgabe:\n";
        $message .= $blockDefinition->ai_prompt ?? "Sammle die erforderlichen Informationen für diesen Block.";

        if ($blockDefinition->response_format && is_array($blockDefinition->response_format)) {
            $message .= "\n\nDu musst folgende Informationen sammeln:\n";
            foreach ($blockDefinition->response_format as $field) {
                $message .= "- " . $field['description'] . "\n";
            }
        }

        return $message;
    }

    private function buildThreadTitle($template)
    {
        $title = "Hatch: ";
        $title .= $this->projectIntake->name ?? 'Projektierung';
        if ($template->name) $title .= " ({$template->name})";
        $title .= " - " . now()->format('d.m.Y H:i');
        return $title;
    }

    private function convertResponseFormatToFunction($blockDefinition)
    {
        if (!$blockDefinition->response_format || !is_array($blockDefinition->response_format)) return null;

        $properties = [];
        $required = [];

        foreach ($blockDefinition->response_format as $field) {
            $fieldName = $this->generateFieldName($field['description'] ?? 'field');
            $fieldType = $this->mapFieldType($field['type'] ?? 'string');

            $properties[$fieldName] = [
                'type' => $fieldType,
                'description' => $field['description'] ?? 'Feld',
            ];

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

    private function generateFieldName($description)
    {
        $description = strtolower($description);
        $words = explode(' ', $description);
        $name = implode('_', array_slice($words, 0, 3));
        return preg_replace('/[^a-z0-9_]/', '', $name) ?: 'field';
    }

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

    public function openIntakeModal()
    {
        $this->showIntakeModal = true;
        $this->determineCurrentBlock();
        \Log::channel('project_intake')->info('[openIntakeModal] opened', [
            'current_step_id' => $this->currentStepId,
            'current_block_id' => $this->currentBlock?->id,
        ]);
    }

    public function closeIntakeModal()
    {
        $this->showIntakeModal = false;
        $this->message = '';
        \Log::channel('project_intake')->info('[closeIntakeModal] closed');
    }

    /**
     * Nachricht senden
     */
    public function sendMessage()
    {
        if (empty(trim($this->message))) return;

        $thread = $this->projectIntake->activeAiAssistantThread;
        if (!$thread) {
            $this->dispatch('notifications:store', [
                'title' => 'Fehler',
                'message' => 'AI Assistant Thread nicht gefunden.',
                'notice_type' => 'error',
                'noticable_type' => \Platform\Core\Models\User::class,
                'noticable_id' => auth()->id(),
            ]);
            \Log::channel('project_intake')->error('[sendMessage] no active thread');
            return;
        }

        $this->sendUserMessageToAssistant($thread);
        $this->message = '';
        $this->dispatch('message-sent');
    }

    private function sendUserMessageToAssistant($thread)
    {
        $threadService = app(ThreadService::class);
        $runService = app(RunService::class);

        try {
            $messageId = $threadService->createMessage(
                $thread->openai_thread_id,
                'user',
                $this->message
            );

            $thread->messages()->create([
                'openai_message_id' => $messageId,
                'role' => 'user',
                'content' => $this->message,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);

            $currentBlockFunction = null;
            if ($this->currentBlock && $this->currentBlock->blockDefinition) {
                $currentBlockFunction = $this->convertResponseFormatToFunction($this->currentBlock->blockDefinition);
            }

            $runData = [];
            if ($currentBlockFunction) {
                $runData['tools'] = [$currentBlockFunction];
            }
            $runData['metadata'] = [
                'project_intake_id'   => (string)$this->projectIntake->id,
                'current_step_id'     => $this->currentStepId ? (string)$this->currentStepId : null,
                'template_block_id'   => $this->currentBlock?->id ? (string)$this->currentBlock->id : null,
                'template_block_name' => $this->currentBlock?->blockDefinition?->name,
            ];

            \Log::channel('project_intake')->info('[sendUserMessageToAssistant] creating run', [
                'metadata' => $runData['metadata'],
                'has_tools' => (bool)$currentBlockFunction
            ]);

            $runId = $runService->createRun(
                $thread->openai_thread_id,
                $thread->assistant->openai_assistant_id,
                $runData
            );

            $thread->runs()->create([
                'openai_run_id' => $runId,
                'assistant_id' => $thread->assistant_id,
                'status' => 'queued',
                'type' => 'message_creation',
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);

            \Log::channel('project_intake')->info('[sendUserMessageToAssistant] run created', [
                'openai_run_id' => $runId,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notifications:store', [
                'title' => 'Fehler beim Senden',
                'message' => 'Die Nachricht konnte nicht gesendet werden: ' . $e->getMessage(),
                'notice_type' => 'error',
                'noticable_type' => \Platform\Core\Models\User::class,
                'noticable_id' => auth()->id(),
            ]);
            \Log::channel('project_intake')->error('[sendUserMessageToAssistant] error', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getAiAssistantMessagesProperty()
    {
        $thread = \Platform\AiAssistant\Models\AiAssistantThread::where('context_type', HatchProjectIntake::class)
            ->where('context_id', $this->projectIntake->id)
            ->where('status', 'active')
            ->first();

        if (!$thread) return collect();

        return $thread->messages()->orderBy('created_at')->get();
    }

    public function getHasActiveRunProperty()
    {
        $thread = \Platform\AiAssistant\Models\AiAssistantThread::where('context_type', HatchProjectIntake::class)
            ->where('context_id', $this->projectIntake->id)
            ->where('status', 'active')
            ->first();

        if (!$thread) return false;

        return $thread->runs()
            ->whereIn('status', ['queued','in_progress','requires_action'])
            ->exists();
    }

    /**
     * Polling-Hook: prüft Function Calls und triggert Block-Completion
     */
    public function checkForFunctionCalls()
    {
        // Aktiven Thread zur Intake holen
        $thread = \Platform\AiAssistant\Models\AiAssistantThread::where('context_type', HatchProjectIntake::class)
            ->where('context_id', $this->projectIntake->id)
            ->where('status', 'active')
            ->first();

        if (!$thread) {
            \Log::channel('project_intake')->info('[checkForFunctionCalls] no active thread', [
                'project_intake_id' => $this->projectIntake->id,
            ]);
            return;
        }

        // Relevante Runs holen (funktion-call pending / requires_action)
        $functionCallRuns = $thread->runs()
            ->whereIn('status', ['function_called', 'requires_action'])
            ->get();

        \Log::channel('project_intake')->info('[checkForFunctionCalls] runs', [
            'project_intake_id' => $this->projectIntake->id,
            'count'             => $functionCallRuns->count(),
            'statuses'          => $functionCallRuns->pluck('status', 'openai_run_id')->toArray(),
        ]);

        $didProcessAnything = false;

        foreach ($functionCallRuns as $run) {
            \Log::channel('project_intake')->info('[checkForFunctionCalls] processing run', [
                'project_intake_id' => $this->projectIntake->id,
                'openai_run_id'     => $run->openai_run_id,
                'status'            => $run->status,
                'run_db_id'         => $run->id,
            ]);

            // 1) requires_action => RunService macht die schwere Arbeit (liest remote tool_calls, submit tool outputs, markiert Step, etc.)
            if ($run->status === 'requires_action') {
                try {
                    $runService = app(RunService::class);
                    $runService->processCompletedRun($run);
                    $didProcessAnything = true;

                    \Log::channel('project_intake')->info('[checkForFunctionCalls] requires_action processed, wait for next poll', [
                        'project_intake_id' => $this->projectIntake->id,
                        'openai_run_id'     => $run->openai_run_id,
                    ]);
                } catch (\Throwable $e) {
                    \Log::channel('project_intake')->error('[checkForFunctionCalls] error processing requires_action', [
                        'project_intake_id' => $this->projectIntake->id,
                        'openai_run_id'     => $run->openai_run_id,
                        'error'             => $e->getMessage(),
                    ]);
                }

                // Nächster Run im Poll
                continue;
            }

            // 2) Lokaler Pfad: Ein Run steht auf function_called; wir suchen den passenden ToolCall,
            //    den wir noch NICHT verarbeitet haben (status=pending).
            $toolCall = \Platform\AiAssistant\Models\AiAssistantToolCall::where('run_id', $run->id)
                ->where('type', 'function')
                ->where('function_name', 'complete_block')
                ->where('status', 'pending') // wichtig: nur unbehandelte tool calls
                ->first();

            if ($toolCall) {
                \Log::channel('project_intake')->info('[checkForFunctionCalls] pending tool call found', [
                    'project_intake_id' => $this->projectIntake->id,
                    'run_db_id'         => $run->id,
                    'openai_run_id'     => $run->openai_run_id,
                    'tool_call_id'      => $toolCall->id,
                    'arguments'         => $toolCall->function_arguments,
                ]);

                try {
                    // Controller-interne Completion (setzt Step fertig und triggert ggf. next block run)
                    $this->handleBlockCompletion($toolCall->function_arguments);

                    // Run lokal auf archived setzen (damit UI weiß, es gibt keinen aktiven lokalen Run mehr)
                    $run->update(['status' => 'archived']);

                    // ToolCall auf processed setzen, damit wir ihn nicht nochmal anfassen
                    $toolCall->update(['status' => 'processed']);

                    $didProcessAnything = true;

                    \Log::channel('project_intake')->info('[checkForFunctionCalls] tool call processed', [
                        'project_intake_id' => $this->projectIntake->id,
                        'tool_call_id'      => $toolCall->id,
                        'run_db_id'         => $run->id,
                    ]);
                } catch (\Throwable $e) {
                    \Log::channel('project_intake')->error('[checkForFunctionCalls] error handling tool call', [
                        'project_intake_id' => $this->projectIntake->id,
                        'tool_call_id'      => $toolCall->id,
                        'run_db_id'         => $run->id,
                        'error'             => $e->getMessage(),
                    ]);
                }
            } else {
                \Log::channel('project_intake')->info('[checkForFunctionCalls] no pending tool call for run', [
                    'project_intake_id' => $this->projectIntake->id,
                    'run_db_id'         => $run->id,
                    'openai_run_id'     => $run->openai_run_id,
                ]);
            }
        }

        // --- Finale UI/State-Korrektur nach der Verarbeitung ---
        // Intake frisch laden (wichtig nach Step-Updates)
        $this->projectIntake->refresh();

        $remaining = $this->projectIntake->intakeSteps()
            ->where('is_completed', false)
            ->count();

        \Log::channel('project_intake')->info('[checkForFunctionCalls] post-process remaining steps', [
            'project_intake_id' => $this->projectIntake->id,
            'remaining'         => $remaining,
            'status'            => $this->projectIntake->status,
            'did_process'       => $didProcessAnything,
        ]);

        // Wenn wirklich keine offenen Steps mehr existieren: Intake sauber finalisieren (inkl. Modal schließen)
        if ($remaining === 0) {
            $this->completeProjectIntake();
            return;
        }
    }

    /**
     * Block-Completion – nutzt $currentStepId für exakte Zuordnung
     */
    private function handleBlockCompletion(array $arguments)
    {
        \Log::channel('project_intake')->info('[handleBlockCompletion] start', [
            'project_intake_id' => $this->projectIntake->id,
            'current_step_id' => $this->currentStepId,
            'current_block_id' => $this->currentBlock?->id,
            'arguments' => $arguments,
        ]);

        // 1) Sicher den Step holen, den wir aktiv hatten
        $step = null;
        if ($this->currentStepId) {
            $step = HatchProjectIntakeStep::where('id', $this->currentStepId)
                ->where('project_intake_id', $this->projectIntake->id)
                ->first();
        }

        // Fallback falls currentStepId nicht gesetzt/gefunden
        if (!$step && $this->currentBlock) {
            $step = $this->projectIntake->intakeSteps()
                ->where('template_block_id', $this->currentBlock->id)
                ->where('is_completed', false)
                ->orderBy('id')
                ->first();
            \Log::channel('project_intake')->warning('[handleBlockCompletion] fallback step resolution', [
                'fallback_step_id' => $step?->id,
            ]);
        }

        if ($step) {
            $step->update([
                'is_completed' => true,
                'completed_at' => now(),
                'answers' => $arguments,
            ]);
            \Log::channel('project_intake')->info('[handleBlockCompletion] step completed', [
                'step_id' => $step->id,
                'template_block_id' => $step->template_block_id,
            ]);
        } else {
            \Log::channel('project_intake')->error('[handleBlockCompletion] no step found to complete');
        }

        // Cache invalidieren
        $this->currentStepId = null;
        $this->projectIntake->unsetRelation('intakeSteps');
        $this->projectIntake->refresh();

        // Weiter / Abschluss
        $next = $this->nextOpenStep();
        if (!$next) {
            \Log::channel('project_intake')->info('[handleBlockCompletion] no open steps left → complete intake');
            $this->completeProjectIntake();
        } else {
            \Log::channel('project_intake')->info('[handleBlockCompletion] open steps remain → start next block run', [
                'next_step_id' => $next->id,
                'next_template_block_id' => $next->template_block_id,
            ]);
            $this->startNextBlockRun();
        }
    }

    private function waitForRunCompletion($thread, int $maxSeconds = 30)
    {
        $elapsed = 0;
        while (true) {
            $thread->unsetRelation('runs');
            $thread->refresh();

            $activeLocal = $thread->runs()
                ->whereNotIn('status', ['archived','function_processed','cancelled','failed','completed','expired'])
                ->exists();

            if (!$activeLocal) {
                \Log::channel('project_intake')->info('[waitForRunCompletion] no active runs');
                return;
            }

            if ($elapsed >= $maxSeconds) {
                \Log::channel('project_intake')->warning('[waitForRunCompletion] timeout', [
                    'open_runs' => $thread->runs()->whereNotIn('status', ['archived','function_processed','cancelled','failed','completed','expired'])->pluck('status','openai_run_id')->toArray(),
                ]);
                return;
            }

            \Log::channel('project_intake')->info('[waitForRunCompletion] still active, sleeping…', [
                'elapsed' => $elapsed,
            ]);
            sleep(2);
            $elapsed += 2;
        }
    }

    /**
     * Neuen Run für den **nächsten** Step/Block starten
     */
    private function startNextBlockRun()
    {
        $thread = \Platform\AiAssistant\Models\AiAssistantThread::where('context_type', HatchProjectIntake::class)
            ->where('context_id', $this->projectIntake->id)
            ->where('status', 'active')
            ->first();
        if (!$thread) {
            \Log::channel('project_intake')->warning('[startNextBlockRun] no active thread');
            return;
        }

        $this->waitForRunCompletion($thread, 30);

        // Frische Steps
        $this->projectIntake->unsetRelation('intakeSteps');
        $this->projectIntake->refresh();

        $nextStep = $this->nextOpenStep();
        if (!$nextStep) {
            \Log::channel('project_intake')->info('[startNextBlockRun] no open steps → complete intake');
            $this->completeProjectIntake();
            return;
        }

        $this->currentStepId = $nextStep->id;

        $nextTemplateBlock = $this->projectIntake->projectTemplate->templateBlocks
            ->firstWhere('id', $nextStep->template_block_id);

        if (!$nextTemplateBlock) {
            \Log::channel('project_intake')->error('[startNextBlockRun] next template block not found', [
                'template_block_id' => $nextStep->template_block_id,
            ]);
            return;
        }

        $this->currentBlock = $nextTemplateBlock;
        $blocks = $this->projectIntake->projectTemplate->templateBlocks->sortBy('sort_order')->values();
        $this->currentBlockIndex = $blocks->search(fn($b) => $b->id === $nextTemplateBlock->id);

        \Log::channel('project_intake')->info('[startNextBlockRun] context set', [
            'current_step_id' => $this->currentStepId,
            'current_block_id' => $this->currentBlock->id,
            'current_block_index' => $this->currentBlockIndex,
            'current_block_name' => $this->currentBlock->blockDefinition->name ?? 'Unknown',
        ]);

        // System-Message + Run
        $this->sendSystemMessage($thread);
        $this->createInitialRun($thread);

        \Log::channel('project_intake')->info('[startNextBlockRun] next block run started');
    }

    /**
     * Steps für alle Blocks anlegen
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
                    'template_block_id'   => $block->id,
                    'block_definition_id' => $block->block_definition_id,
                    'is_completed'        => false,
                    'team_id'             => auth()->user()->current_team_id,
                    'created_by_user_id'  => auth()->id(),
                ]);

                \Log::channel('project_intake')->info('[createStepsForAllBlocks] step created', [
                    'template_block_id' => $block->id,
                    'block_name' => $block->blockDefinition->name ?? 'Unknown',
                ]);
            }
        }
    }

    // In Platform\Hatch\Livewire\ProjectIntake\Show

    private function completeProjectIntake()
    {
        // falls schon completed (z.B. vom RunService), kein Doppel-Update
        $alreadyCompleted = $this->projectIntake->status === 'completed';

        if (!$alreadyCompleted) {
            // finale Message nur senden, wenn noch nicht completed
            $this->sendFinalMessage();

            $this->projectIntake->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        \Log::channel('project_intake')->info('[finalize] intake completed -> closing modal', [
            'project_intake_id' => $this->projectIntake->id,
            'already_completed' => $alreadyCompleted,
        ]);


        $this->closeIntakeModal();
    }

    private function sendFinalMessage()
    {
        $thread = \Platform\AiAssistant\Models\AiAssistantThread::where('context_type', HatchProjectIntake::class)
            ->where('context_id', $this->projectIntake->id)
            ->where('status', 'active')
            ->first();
        if (!$thread) return;

        $finalMessage = "Perfekt! Wir haben alle erforderlichen Informationen gesammelt. Die Projektierung ist nun abgeschlossen. Vielen Dank für Ihre Zeit und die detaillierten Angaben. Falls Sie weitere Fragen haben oder Änderungen benötigen, können Sie sich gerne melden.";

        $threadService = app(ThreadService::class);
        $runService = app(RunService::class);

        try {
            $messageId = $threadService->createMessage(
                $thread->openai_thread_id,
                'user',
                $finalMessage
            );

            $thread->messages()->create([
                'openai_message_id' => $messageId,
                'role' => 'user',
                'content' => $finalMessage,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);

            $runId = $runService->createRun(
                $thread->openai_thread_id,
                $thread->assistant->openai_assistant_id,
                ['metadata' => [
                    'project_intake_id' => (string)$this->projectIntake->id,
                    'final_message' => true,
                ]]
            );

            $thread->runs()->create([
                'openai_run_id' => $runId,
                'assistant_id' => $thread->assistant_id,
                'status' => 'queued',
                'type' => 'final_message',
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);

            \Log::channel('project_intake')->info('[sendFinalMessage] sent');
        } catch (\Exception $e) {
            \Log::channel('project_intake')->error('[sendFinalMessage] error', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function pauseIntake()
    {
        $this->projectIntake->update(['status' => 'paused']);

        $this->dispatch('notifications:store', [
            'title' => 'Projektierung pausiert',
            'message' => 'Sie können später fortfahren.',
            'notice_type' => 'info',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);

        $this->closeIntakeModal();

        \Log::channel('project_intake')->info('[pauseIntake] paused');
    }

    public function generatePdfReport()
    {
        \Log::channel('project_intake')->info('[generatePdfReport] requested', [
            'project_intake_id' => $this->projectIntake->id,
        ]);

        $this->dispatch('notifications:store', [
            'title' => 'PDF Bericht',
            'message' => 'PDF Bericht wird generiert...',
            'notice_type' => 'info',
            'noticable_type' => \Platform\Hatch\Models\HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function createProject()
    {
        \Log::channel('project_intake')->info('[createProject] requested', [
            'project_intake_id' => $this->projectIntake->id,
        ]);

        $this->dispatch('notifications:store', [
            'title' => 'Projekt erstellen',
            'message' => 'Projekt wird erstellt...',
            'notice_type' => 'info',
            'noticable_type' => \Platform\Hatch\Models\HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function createTasks()
    {
        \Log::channel('project_intake')->info('[createTasks] requested', [
            'project_intake_id' => $this->projectIntake->id,
        ]);

        $this->dispatch('notifications:store', [
            'title' => 'Aufgaben erstellen',
            'message' => 'Aufgaben werden erstellt...',
            'notice_type' => 'info',
            'noticable_type' => \Platform\Hatch\Models\HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function exportMarkdown()
    {
        \Log::channel('project_intake')->info('[exportMarkdown] requested', [
            'project_intake_id' => $this->projectIntake->id,
        ]);

        $this->dispatch('notifications:store', [
            'title' => 'Markdown Export',
            'message' => 'Markdown wird exportiert...',
            'notice_type' => 'info',
            'noticable_type' => \Platform\Hatch\Models\HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function showData()
    {
        \Log::channel('project_intake')->info('[showData] requested', [
            'project_intake_id' => $this->projectIntake->id,
        ]);

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