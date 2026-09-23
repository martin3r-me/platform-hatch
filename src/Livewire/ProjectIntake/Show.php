<?php

namespace Platform\Hatch\Livewire\ProjectIntake;

use Illuminate\Support\Str;
use Livewire\Component;
use Platform\Core\Contracts\CrmContactOptionsProviderInterface;
use Platform\Core\Contracts\CrmContactResolverInterface;
use Platform\Hatch\Models\HatchIntakeSession;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectIntakeStep;
use Platform\Hatch\Support\IntakePlaceholders;
use Platform\Hatch\Support\QrCodeRenderer;

class Show extends Component
{
    public HatchProjectIntake $projectIntake;

    public $statuses = [
        'draft' => 'Entwurf',
        'published' => 'Veröffentlicht',
        'closed' => 'Geschlossen',
    ];

    public $showActivities = false;

    // Bearbeitbare Stammdaten (Name/Beschreibung werden im Public-View angezeigt)
    public string $name = '';
    public string $description = '';

    // Werte eigener Platzhalter für diese Erhebung (intake_settings.placeholder_values)
    public array $placeholderValues = [];

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
        $this->name = (string) $projectIntake->name;
        $this->description = (string) $projectIntake->description;
        $this->placeholderValues = $projectIntake->intake_settings['placeholder_values'] ?? [];
        $this->loadTemplateBlocks();
        $this->determineCurrentBlock();
    }

    public function updatedName()
    {
        $this->validateOnly('name', ['name' => 'required|string|max:255']);
        $this->projectIntake->update(['name' => trim($this->name)]);
    }

    public function updatedDescription()
    {
        $this->validateOnly('description', ['description' => 'nullable|string|max:2000']);
        $description = trim($this->description);
        $this->projectIntake->update(['description' => $description === '' ? null : $description]);
    }

    public function updatedPlaceholderValues($value, $key)
    {
        $customs = app(IntakePlaceholders::class)->customs($this->projectIntake->team_id);
        if (!array_key_exists($key, $customs)) {
            unset($this->placeholderValues[$key]);
            return;
        }

        $value = trim((string) $value);
        $settings = $this->projectIntake->intake_settings ?? [];
        if ($value === '') {
            unset($settings['placeholder_values'][$key]);
            unset($this->placeholderValues[$key]);
        } else {
            $settings['placeholder_values'][$key] = mb_substr($value, 0, 500);
        }
        if (empty($settings['placeholder_values'])) {
            unset($settings['placeholder_values']);
        }

        $this->projectIntake->update(['intake_settings' => $settings ?: null]);

        // Vorschau/Einfügen-Menü der Baustein-Felder mit dem neuen Wert auffrischen
        $this->dispatch('hatch-placeholder-catalog', catalog: app(IntakePlaceholders::class)->catalog($this->projectIntake));
    }

    public function loadTemplateBlocks()
    {
        if ($this->projectIntake->projectTemplate) {
            $this->templateBlocks = $this->projectIntake->projectTemplate
                ->templateBlocks()
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

        $this->projectIntake->loadMissing(['intakeSteps','projectTemplate.templateBlocks']);

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

    public function publishIntake()
    {
        $this->loadTemplateBlocks();
        $this->createStepsForAllBlocks();
        $this->determineCurrentBlock();

        $this->projectIntake->publish();

        $this->dispatch('notifications:store', [
            'title' => 'Erhebung veröffentlicht',
            'message' => 'Die Erhebung ist jetzt live und nimmt Antworten entgegen.',
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
                    'is_completed'        => false,
                    'team_id'             => auth()->user()->current_team_id,
                    'created_by_user_id'  => auth()->id(),
                ]);
            }
        }
    }

    public function closeIntake()
    {
        $this->projectIntake->close();

        $this->dispatch('notifications:store', [
            'title' => 'Erhebung geschlossen',
            'message' => 'Die Erhebung nimmt keine neuen Antworten mehr entgegen.',
            'notice_type' => 'info',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function unpublishIntake()
    {
        $this->projectIntake->unpublish();

        $this->dispatch('notifications:store', [
            'title' => 'Erhebung zurückgezogen',
            'message' => 'Die Erhebung ist wieder im Entwurf-Modus.',
            'notice_type' => 'info',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
    }

    public function reopenIntake()
    {
        $this->projectIntake->publish();

        $this->dispatch('notifications:store', [
            'title' => 'Erhebung erneut veröffentlicht',
            'message' => 'Die Erhebung ist wieder live.',
            'notice_type' => 'success',
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

    public function downloadQrCode(string $format = 'png')
    {
        $url = $this->projectIntake->getPublicUrl();

        if (!$url || !in_array($format, QrCodeRenderer::FORMATS, true)) {
            return null;
        }

        $renderer = app(QrCodeRenderer::class);
        $content = $format === 'svg' ? $renderer->svg($url) : $renderer->png($url);
        $filename = 'qr-' . Str::slug(app(IntakePlaceholders::class)->render($this->projectIntake->name, $this->projectIntake) ?: 'erhebung') . '.' . $format;

        return response()->streamDownload(
            fn () => print($content),
            $filename,
            ['Content-Type' => $format === 'svg' ? 'image/svg+xml' : 'image/png']
        );
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

    public function deleteProjectIntake()
    {
        if ($this->projectIntake->team_id !== auth()->user()->current_team_id) {
            return;
        }

        $this->projectIntake->delete();

        $this->dispatch('notifications:store', [
            'title' => 'Erhebung gelöscht',
            'message' => 'Die Erhebung und alle zugehörigen Sessions wurden gelöscht.',
            'notice_type' => 'success',
        ]);

        return redirect()->route('hatch.project-intakes.index');
    }

    public function deleteSession(string $sessionId)
    {
        $session = HatchIntakeSession::findOrFail($sessionId);

        if ($session->project_intake_id !== $this->projectIntake->id) {
            return;
        }

        $session->delete();

        $this->dispatch('notifications:store', [
            'title' => 'Session gelöscht',
            'message' => 'Die Session wurde erfolgreich gelöscht.',
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
            'intakeSteps.templateBlock'
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
            'placeholderCatalog' => app(IntakePlaceholders::class)->catalog($this->projectIntake),
            'customPlaceholders' => app(IntakePlaceholders::class)->customs($this->projectIntake->team_id),
        ])->layout('platform::layouts.app');
    }
}
