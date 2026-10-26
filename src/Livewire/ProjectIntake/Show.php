<?php

namespace Platform\Hatch\Livewire\ProjectIntake;

use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Platform\Core\Contracts\CrmContactOptionsProviderInterface;
use Platform\Core\Contracts\CrmContactResolverInterface;
use Platform\Hatch\Models\HatchIntakeSession;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Support\IntakeAnalytics;
use Platform\Hatch\Support\IntakePlaceholders;
use Platform\Hatch\Support\QrCodeRenderer;

class Show extends Component
{
    use \Platform\Hatch\Livewire\Concerns\FlashesNotices;

    public HatchProjectIntake $projectIntake;

    public $statuses = [
        'draft' => 'Entwurf',
        'published' => 'Veröffentlicht',
        'closed' => 'Geschlossen',
    ];

    public $showActivities = false;

    // Hauptbereich: 'overview' | 'analysis'
    #[Url(as: 'ansicht', except: 'overview')]
    public string $tab = 'overview';

    // Filter der Auswertung
    #[Url(as: 'kw', except: '')]
    public string $analysisWeek = '';       // "2026-39"
    #[Url(as: 'zeitraum', except: 'all')]
    public string $analysisPeriod = 'all';  // all | 7 | 30
    #[Url(as: 'vollstaendig', except: false)]
    public bool $analysisCompletedOnly = false;
    public array $expandedTexts = [];       // block ids mit ausgeklappter Freitext-Liste

    // Bearbeitbare Stammdaten (Name/Beschreibung werden im Public-View angezeigt)
    public string $name = '';
    public string $description = '';
    public string $eventReference = '';

    // Automatisches Schließen: Eingabe als "Y-m-d\TH:i" in der Anzeige-Zeitzone; leer = kein Schließdatum
    public string $closesAtInput = '';
    public bool $showClosesAt = false;

    // „Als Vorlage speichern“
    public bool $showSaveAsTemplate = false;
    public string $newTemplateName = '';

    // Werte eigener Platzhalter für diese Erhebung (intake_settings.placeholder_values)
    public array $placeholderValues = [];

    // Personalisierte Session
    public bool $showPersonalizedSessionModal = false;
    public string $contactSearch = '';
    public ?int $selectedContactId = null;
    public array $contactOptions = [];
    public ?string $createdSessionUrl = null;


    public function mount(HatchProjectIntake $projectIntake)
    {
        $this->projectIntake = $projectIntake;
        $this->name = (string) $projectIntake->name;
        $this->description = (string) $projectIntake->description;
        $this->eventReference = (string) $projectIntake->event_reference;
        $this->closesAtInput = $projectIntake->closes_at
            ? $projectIntake->closes_at->copy()->setTimezone(config('hatch.display_timezone', 'Europe/Berlin'))->format('Y-m-d\TH:i')
            : '';
        $this->showClosesAt = $this->closesAtInput !== '';
        $this->placeholderValues = $projectIntake->intake_settings['placeholder_values'] ?? [];
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

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['overview', 'analysis'], true) ? $tab : 'overview';
    }

    public function toggleTexts(int $blockId): void
    {
        $this->expandedTexts[$blockId] = !($this->expandedTexts[$blockId] ?? false);
    }

    /** Filter der Auswertung – auch für den CSV-Export (gleiche Auswahl). */
    public function analysisFilters(): array
    {
        [$year, $week] = array_pad(array_map('intval', explode('-', $this->analysisWeek)), 2, null);

        return [
            'completed_only' => $this->analysisCompletedOnly,
            'iso_year' => $this->analysisWeek !== '' ? $year : null,
            'iso_week' => $this->analysisWeek !== '' ? $week : null,
            'since' => in_array($this->analysisPeriod, ['7', '30'], true) ? now()->subDays((int) $this->analysisPeriod)->startOfDay()->toDateTimeString() : null,
        ];
    }

    public function updatedClosesAtInput()
    {
        $this->resetErrorBag('closesAtInput');
        $value = trim($this->closesAtInput);

        if ($value === '') {
            $this->projectIntake->update(['closes_at' => null]);

            return;
        }

        try {
            $local = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $value, config('hatch.display_timezone', 'Europe/Berlin'));
        } catch (\Throwable $e) {
            $this->addError('closesAtInput', 'Bitte Datum und Uhrzeit vollständig angeben.');

            return;
        }

        if ($local->isPast()) {
            $this->addError('closesAtInput', 'Der Zeitpunkt liegt in der Vergangenheit.');

            return;
        }

        $this->projectIntake->update(['closes_at' => $local->copy()->setTimezone(config('app.timezone'))]);
    }

    private function syncClosesAtInput(): void
    {
        $this->closesAtInput = $this->projectIntake->closes_at
            ? $this->projectIntake->closes_at->copy()->setTimezone(config('hatch.display_timezone', 'Europe/Berlin'))->format('Y-m-d\TH:i')
            : '';
        $this->showClosesAt = $this->closesAtInput !== '';
    }

    public function removeClosesAt(): void
    {
        $this->closesAtInput = '';
        $this->showClosesAt = false;
        $this->resetErrorBag('closesAtInput');
        $this->projectIntake->update(['closes_at' => null]);
    }

    public function updatedEventReference()
    {
        $this->validateOnly('eventReference', ['eventReference' => 'nullable|string|max:100']);
        $value = trim($this->eventReference);
        $this->projectIntake->update(['event_reference' => $value === '' ? null : $value]);
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

    public function publishIntake()
    {
        $this->projectIntake->publish();
        $this->syncClosesAtInput();

        $this->dispatch('notifications:store', [
            'title' => 'Erhebung veröffentlicht',
            'message' => 'Die Erhebung ist jetzt live und nimmt Antworten entgegen.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $this->projectIntake->id,
        ]);
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
        $this->syncClosesAtInput();

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

    public function openSaveAsTemplate(): void
    {
        // Platzhalter wie {{iso_week}} gehören zur Erhebung, nicht in einen Vorlagen-Namen
        $name = trim(preg_replace('/\s*\{\{\w+\}\}\s*/', ' ', (string) $this->projectIntake->name));
        // übrig gebliebene Reste wie „– KW /“ am Ende entfernen
        do {
            $before = $name;
            $name = trim(preg_replace(['/[\s–\-\/:.,]+$/u', '/\b(KW|Woche|Jahr)$/iu'], '', $name));
        } while ($name !== $before && $name !== '');
        $this->newTemplateName = $name ?: (string) $this->projectIntake->projectTemplate?->name;
        $this->resetErrorBag('newTemplateName');
        $this->showSaveAsTemplate = true;
    }

    public function saveAsTemplate()
    {
        if ((int) $this->projectIntake->team_id !== (int) auth()->user()->current_team_id) {
            return;
        }
        $this->validate(['newTemplateName' => 'required|string|max:255'], [], ['newTemplateName' => 'Name']);

        $source = $this->projectIntake->projectTemplate;
        if (!$source) {
            $this->addError('newTemplateName', 'Diese Erhebung hat keine Vorlage mit Fragen.');

            return;
        }

        $intakeName = app(IntakePlaceholders::class)->render($this->projectIntake->name, $this->projectIntake);
        $template = $source->duplicateAs(
            trim($this->newTemplateName),
            auth()->id(),
            'Erstellt aus der Erhebung „' . $intakeName . '“ am ' . now()->setTimezone(config('hatch.display_timezone', 'Europe/Berlin'))->format('d.m.Y') . '.'
        );

        $this->dispatch('notifications:store', [
            'title' => 'Vorlage erstellt',
            'message' => 'Die Fragen sind als eigene Vorlage gespeichert und lassen sich dort unabhängig bearbeiten.',
            'notice_type' => 'success',
            'noticable_type' => \Platform\Hatch\Models\HatchProjectTemplate::class,
            'noticable_id' => $template->id,
        ]);

        return redirect()->route('hatch.templates.show', $template);
    }

    public function duplicateIntake()
    {
        if ((int) $this->projectIntake->team_id !== (int) auth()->user()->current_team_id) {
            return;
        }

        $copy = $this->projectIntake->duplicate(auth()->id());

        $this->dispatch('notifications:store', [
            'title' => 'Erhebung dupliziert',
            'message' => 'Die Kopie ist als Entwurf angelegt – Name anpassen und veröffentlichen.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $copy->id,
        ]);

        return redirect()->route('hatch.project-intakes.show', $copy);
    }

    public function deleteProjectIntake()
    {
        if ($this->projectIntake->team_id !== auth()->user()->current_team_id) {
            return;
        }

        $intakeId = $this->projectIntake->id;
        $this->projectIntake->delete();

        // noticable_* ist Pflicht in notifications_notices – auf die gelöschte Erhebung verweisen.
        // Meldung erst auf der Zielseite – siehe FlashesNotices
        $this->flashNotice([
            'title' => 'Erhebung gelöscht',
            'message' => 'Die Erhebung und alle zugehörigen Antworten wurden gelöscht.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $intakeId,
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
        $this->projectIntake->load(['projectTemplate.templateBlocks']);

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
            'placeholderCatalog' => app(IntakePlaceholders::class)->catalog($this->projectIntake),
            'customPlaceholders' => app(IntakePlaceholders::class)->customs($this->projectIntake->team_id),
            'analysis' => $this->tab === 'analysis' ? app(IntakeAnalytics::class)->build($this->projectIntake, $this->analysisFilters()) : null,
            'analysisWeeks' => $this->tab === 'analysis' ? app(IntakeAnalytics::class)->weeks($this->projectIntake) : [],
        ])->layout('platform::layouts.app');
    }
}
