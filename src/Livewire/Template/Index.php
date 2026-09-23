<?php

namespace Platform\Hatch\Livewire\Template;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchComplexityLevel;

class Index extends Component
{
    use WithPagination;

    // Search / Filter
    public $search = '';
    public $statusFilter = 'all';

    // Modal State
    public $modalShow = false;

    // Sorting
    public $sortField = 'name';
    public $sortDirection = 'asc';

    // Form Data
    public $name = '';
    public $description = '';
    public $ai_personality = '';
    public $industry_context = '';
    public $complexity_level = 'medium';
    public $ai_instructions = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'ai_personality' => 'nullable|string|max:255',
        'industry_context' => 'nullable|string|max:255',
        'complexity_level' => 'required|in:simple,medium,complex',
        'ai_instructions' => 'nullable|array',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $teamId = auth()->user()->current_team_id;

        $templates = HatchProjectTemplate::with(['createdByUser'])
            ->withCount(['templateBlocks', 'projectIntakes'])
            ->where('team_id', $teamId)
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when(!empty($this->search), function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhere('industry_context', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->sortField === 'name', function($query) {
                $query->orderBy('name', $this->sortDirection);
            })
            ->when(!in_array($this->sortField, ['name']), function($query) {
                $query->orderBy($this->sortField, $this->sortDirection);
            })
            ->paginate(10);

        $complexityLevels = HatchComplexityLevel::all();

        $stats = [
            'total' => HatchProjectTemplate::where('team_id', $teamId)->count(),
            'active' => HatchProjectTemplate::where('team_id', $teamId)->where('is_active', true)->count(),
        ];

        return view('hatch::livewire.template.index', [
            'templates' => $templates,
            'complexityLevels' => $complexityLevels,
            'stats' => $stats,
        ])->layout('platform::layouts.app');
    }

    public function createTemplate()
    {
        $this->validate();

        $template = HatchProjectTemplate::create([
            'name' => $this->name,
            'description' => $this->description,
            'complexity_level' => $this->complexity_level,
            'ai_instructions' => $this->ai_instructions ?: null,
            'team_id' => auth()->user()->current_team_id,
            'created_by_user_id' => auth()->id(),
        ]);

        $this->resetForm();
        $this->modalShow = false;

        session()->flash('message', 'Template erfolgreich erstellt!');
    }

    public function resetForm()
    {
        $this->reset([
            'name', 'description', 'ai_personality', 'industry_context',
            'complexity_level', 'ai_instructions'
        ]);
    }

    public function openCreateModal()
    {
        $this->modalShow = true;
    }

    public function closeCreateModal()
    {
        $this->modalShow = false;
        $this->resetForm();
    }

    public function sortBy($field)
    {
        if (!in_array($field, ['name', 'updated_at'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
}
