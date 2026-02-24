<?php

namespace Platform\Hatch\Livewire\ProjectIntake;

use Livewire\Component;
use Livewire\Attributes\On;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectTemplate;

class Index extends Component
{
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

        $projectIntake = HatchProjectIntake::create([
            'name' => $this->name,
            'description' => $this->description,
            'project_template_id' => $this->project_template_id,
            'status' => 'draft',
            'is_active' => false,
            'team_id' => auth()->user()->current_team_id,
            'created_by_user_id' => auth()->id(),
        ]);

        $this->closeCreateModal();

        $this->dispatch('notifications:store', [
            'title' => 'Projektierung erstellt',
            'message' => 'Die Projektierung wurde erfolgreich angelegt.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectIntake::class,
            'noticable_id' => $projectIntake->id,
        ]);
    }

    public function deleteProjectIntake(string $id)
    {
        $intake = HatchProjectIntake::find($id);

        if (!$intake || $intake->team_id !== auth()->user()->current_team_id) {
            return;
        }

        $intake->delete();

        $this->dispatch('notifications:store', [
            'title' => 'Erhebung gelöscht',
            'message' => 'Die Erhebung und alle zugehörigen Sessions wurden gelöscht.',
            'notice_type' => 'success',
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
            ->withCount('sessions')
            ->where('team_id', $teamId);

        // Suchfilter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
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
        ])->layout('platform::layouts.app');
    }
}
