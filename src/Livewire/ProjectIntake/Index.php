<?php

namespace Platform\Hatch\Livewire\ProjectIntake;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Support\IntakeEntityLinks;
use Platform\Hatch\Support\IntakePlaceholders;

class Index extends Component
{
    use \Platform\Hatch\Livewire\Concerns\FlashesNotices;

    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $templateFilter = '';
    
    public $templates;
    public $statuses = [
        'draft' => 'Entwurf',
        'published' => 'Veröffentlicht',
        'closed' => 'Geschlossen',
    ];

    // Create Modal Properties
    public $modalShow = false;
    public $name = '';
    public $description = '';
    public $project_template_id = '';
    public $status = 'draft';

    // Optional: Organisationseinheit (z. B. Standort), mit der die neue Erhebung verknüpft wird
    public ?int $entityId = null;
    public string $entitySearch = '';

    public function mount()
    {
        $teamId = auth()->user()->current_team_id;

        $this->templates = HatchProjectTemplate::where('is_active', true)
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Setze das erste Template als Preset, falls vorhanden
        if ($this->templates->isNotEmpty()) {
            $this->project_template_id = $this->templates->first()->id;
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedTemplateFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->templateFilter = '';
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->modalShow = true;
        $this->resetCreateForm();
    }

    public function closeCreateModal()
    {
        $this->modalShow = false;
        $this->resetCreateForm();
    }

    public function resetCreateForm()
    {
        $this->name = '';
        $this->description = '';
        // Setze das erste Template als Preset, falls vorhanden
        $this->project_template_id = $this->templates->isNotEmpty() ? $this->templates->first()->id : '';
        $this->status = 'draft';
        $this->entityId = null;
        $this->entitySearch = '';
        $this->resetErrorBag();
    }

    public function selectEntity(int $id): void
    {
        $this->entityId = $id;
        $this->entitySearch = '';
    }

    public function clearEntity(): void
    {
        $this->entityId = null;
    }

    public function createProjectIntake()
    {
        $teamId = auth()->user()->current_team_id;

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_template_id' => [
                'required',
                'exists:hatch_project_templates,id',
                function ($attribute, $value, $fail) use ($teamId) {
                    $template = HatchProjectTemplate::where('id', $value)
                        ->where('team_id', $teamId)
                        ->where('is_active', true)
                        ->first();
                    if (!$template) {
                        $fail('Das ausgewählte Template gehört nicht zu deinem Team oder ist nicht aktiv.');
                    }
                },
            ],
        ]);

        $links = app(IntakeEntityLinks::class);
        if ($this->entityId && !$links->canLink(auth()->user(), $this->entityId)) {
            $this->addError('entityId', 'Mit dieser Organisationseinheit darfst du nicht verknüpfen.');

            return;
        }

        $projectIntake = HatchProjectIntake::create([
            'name' => $this->name,
            'description' => $this->description,
            'project_template_id' => $this->project_template_id,
            'status' => 'draft',
            'is_active' => false,
            'team_id' => auth()->user()->current_team_id,
            'created_by_user_id' => auth()->id(),
        ]);

        if ($this->entityId) {
            $links->link($projectIntake, $this->entityId, auth()->user());
        }

        $this->closeCreateModal();

        $this->dispatch('notifications:store', [
            'title' => 'Erhebung angelegt',
            'message' => 'Die Erhebung ist als Entwurf angelegt.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $projectIntake->id,
        ]);
    }

    public function duplicateProjectIntake(string $id)
    {
        $intake = HatchProjectIntake::where('team_id', auth()->user()->current_team_id)->find($id);
        if (!$intake) {
            return;
        }

        $copy = $intake->duplicate(auth()->id());

        return redirect()->route('hatch.project-intakes.show', $copy);
    }

    public function deleteProjectIntake(string $id)
    {
        $intake = HatchProjectIntake::find($id);

        if (!$intake || $intake->team_id !== auth()->user()->current_team_id) {
            return;
        }

        $intakeId = $intake->id;
        $intake->delete();

        // noticable_* ist Pflicht in notifications_notices – auf die gelöschte Erhebung verweisen
        $this->dispatch('notifications:store', [
            'title' => 'Erhebung gelöscht',
            'message' => 'Die Erhebung und alle zugehörigen Antworten wurden gelöscht.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $intakeId,
        ]);
    }

    public function setStatusFilter(string $status)
    {
        $this->statusFilter = $this->statusFilter === $status ? '' : $status;
        $this->resetPage();
    }

    public function render()
    {
        $teamId = auth()->user()->current_team_id;

        // Stat-Zahlen (unabhaengig von Filtern)
        $statsQuery = HatchProjectIntake::where('team_id', $teamId);
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'draft' => (clone $statsQuery)->where('status', 'draft')->count(),
            'published' => (clone $statsQuery)->where('status', 'published')->count(),
            'closed' => (clone $statsQuery)->where('status', 'closed')->count(),
        ];

        $query = HatchProjectIntake::query()
            ->with(['projectTemplate', 'createdByUser'])
            ->withCount(['sessions' => fn ($q) => $q->withAnswers()])
            ->where('team_id', $teamId);

        // Suchfilter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('event_reference', 'like', '%' . $this->search . '%');
            });
        }

        // Statusfilter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Templatefilter
        if ($this->templateFilter) {
            $query->where('project_template_id', $this->templateFilter);
        }

        $projectIntakes = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('hatch::livewire.project-intake.index', [
            'projectIntakes' => $projectIntakes,
            'templates' => $this->templates,
            'statuses' => $this->statuses,
            'stats' => $stats,
            'placeholderCatalog' => app(IntakePlaceholders::class)->catalog(null),
            // nur bei offenem Dialog: Auswahl der Organisationseinheit
            'entityCandidates' => $this->modalShow && !$this->entityId ? app(IntakeEntityLinks::class)->candidates(auth()->user(), $this->entitySearch, 6) : [],
            'entityName' => $this->entityId ? app(IntakeEntityLinks::class)->name($this->entityId) : null,
            'canLinkEntities' => $this->modalShow && ($this->entityId || app(IntakeEntityLinks::class)->writableEntityIds(auth()->user()) !== []),
        ])->layout('platform::layouts.app');
    }
}
