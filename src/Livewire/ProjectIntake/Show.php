<?php

namespace Platform\Hatch\Livewire\ProjectIntake;

use Livewire\Component;
use Platform\Core\Contracts\CrmContactOptionsProviderInterface;
use Platform\Core\Contracts\CrmContactResolverInterface;
use Platform\Hatch\Models\HatchIntakeSession;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectIntakeStep;

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

    // Personalisierte Session
    public bool $showPersonalizedSessionModal = false;
    public string $contactSearch = '';
    public ?int $selectedContactId = null;
    public array $contactOptions = [];
    public ?string $createdSessionUrl = null;

    // Aktueller Kontext (UI)
    public $currentBlockIndex = 0;
    public $templateBlocks = [];
    public $currentBlock = null;

    public function mount(HatchProjectIntake $projectIntake)
    {
        $this->projectIntake = $projectIntake;
        $this->loadTemplateBlocks();
        $this->determineCurrentBlock();
    }

    public function loadTemplateBlocks()
    {
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
        }
    }

    private function nextOpenStep(): ?HatchProjectIntakeStep
    {
        return $this->projectIntake->intakeSteps()
            ->where('is_completed', false)
            ->orderBy('id')
            ->first();
    }

    public function determineCurrentBlock()
    {
        if (empty($this->templateBlocks)) {
            return;
        }

        $this->projectIntake->loadMissing(['intakeSteps','projectTemplate.templateBlocks.blockDefinition']);

        $nextStep = $this->nextOpenStep();

        if ($nextStep) {
            $block = $this->projectIntake->projectTemplate->templateBlocks
                ->firstWhere('id', $nextStep->template_block_id);

            $this->currentBlock = $block;

            $sorted = $this->projectIntake->projectTemplate->templateBlocks->sortBy('sort_order')->values();
            $this->currentBlockIndex = $sorted->search(fn($b) => $b->id === $block->id);
            return;
        }

        $sorted = $this->projectIntake->projectTemplate->templateBlocks->sortBy('sort_order')->values();
        if ($sorted->isNotEmpty()) {
            $this->currentBlock = $sorted->last();
            $this->currentBlockIndex = max(0, $sorted->count() - 1);
        }
    }

    public function startProjectIntake()
    {
        $this->loadTemplateBlocks();
        $this->createStepsForAllBlocks();
        $this->determineCurrentBlock();

        $this->projectIntake->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->dispatch('notifications:store', [
            'title' => 'Erhebung gestartet',
            'message' => 'Die Erhebung wurde erfolgreich gestartet.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

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
            }
        }
    }

    public function pauseIntake()
    {
        $this->projectIntake->update(['status' => 'paused']);

        $this->dispatch('notifications:store', [
            'title' => 'Erhebung pausiert',
            'message' => 'Sie können später fortfahren.',
            'notice_type' => 'info',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function generatePdfReport()
    {
        $this->dispatch('notifications:store', [
            'title' => 'PDF Bericht',
            'message' => 'PDF Bericht wird generiert...',
            'notice_type' => 'info',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function createProject()
    {
        $this->dispatch('notifications:store', [
            'title' => 'Projekt erstellen',
            'message' => 'Projekt wird erstellt...',
            'notice_type' => 'info',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function createTasks()
    {
        $this->dispatch('notifications:store', [
            'title' => 'Aufgaben erstellen',
            'message' => 'Aufgaben werden erstellt...',
            'notice_type' => 'info',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function exportMarkdown()
    {
        $this->dispatch('notifications:store', [
            'title' => 'Markdown Export',
            'message' => 'Markdown wird exportiert...',
            'notice_type' => 'info',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function showData()
    {
        $this->dispatch('notifications:store', [
            'title' => 'Daten anzeigen',
            'message' => 'Daten werden geladen...',
            'notice_type' => 'info',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function generatePublicLink()
    {
        if (!$this->projectIntake->public_token) {
            $this->projectIntake->generatePublicToken();
        }

        $this->dispatch('notifications:store', [
            'title' => 'Link erstellt',
            'message' => 'Der oeffentliche Link wurde generiert.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function openPersonalizedSessionModal()
    {
        $this->showPersonalizedSessionModal = true;
        $this->contactSearch = '';
        $this->selectedContactId = null;
        $this->contactOptions = [];
        $this->createdSessionUrl = null;
    }

    public function updatedContactSearch($value)
    {
        if (strlen($value) < 2) {
            $this->contactOptions = [];
            return;
        }

        $optionsProvider = app(CrmContactOptionsProviderInterface::class);
        $this->contactOptions = $optionsProvider->options($value);
    }

    public function createPersonalizedSession()
    {
        if (!$this->selectedContactId) {
            return;
        }

        $resolver = app(CrmContactResolverInterface::class);
        $contactName = $resolver->displayName($this->selectedContactId);
        $contactEmail = $resolver->email($this->selectedContactId);

        $session = HatchIntakeSession::create([
            'project_intake_id' => $this->projectIntake->id,
            'status' => 'not_started',
            'respondent_name' => $contactName,
            'respondent_email' => $contactEmail,
            'answers' => [],
            'current_step' => 0,
        ]);

        $contact = \Platform\Crm\Models\CrmContact::find($this->selectedContactId);
        if ($contact) {
            $session->attachContact($contact);
        }

        $this->createdSessionUrl = route('hatch.public.intake-session', ['sessionToken' => $session->session_token]);

        $this->dispatch('notifications:store', [
            'title' => 'Personalisierte Session erstellt',
            'message' => "Session für {$contactName} wurde erstellt.",
            'notice_type' => 'success',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function closePersonalizedSessionModal()
    {
        $this->showPersonalizedSessionModal = false;
        $this->createdSessionUrl = null;
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

        $sessions = $this->projectIntake->sessions()
            ->orderByDesc('updated_at')
            ->get();

        return view('hatch::livewire.project-intake.show', [
            'projectIntake' => $this->projectIntake,
            'activities' => $activities,
            'sessions' => $sessions,
            'currentBlock' => $this->currentBlock,
            'currentBlockIndex' => $this->currentBlockIndex,
            'templateBlocks' => $this->templateBlocks,
        ])->layout('platform::layouts.app');
    }
}
