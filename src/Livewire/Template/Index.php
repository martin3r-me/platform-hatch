<?php

namespace Platform\Hatch\Livewire\Template;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchComplexityLevel;
use Platform\AiAssistant\Models\AiAssistantAssistant;

class Index extends Component
{
    use WithPagination;

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
    public $assistant_id = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'ai_personality' => 'nullable|string|max:255',
        'industry_context' => 'nullable|string|max:255',
        'complexity_level' => 'required|in:simple,medium,complex',
        'ai_instructions' => 'nullable|array',
        'assistant_id' => 'nullable|exists:ai_assistant_assistants,id',
    ];

    public function render()
    {
        $templates = HatchProjectTemplate::with(['createdByUser'])
            ->when($this->sortField === 'name', function($query) {
                $query->orderBy('name', $this->sortDirection);
            })
            ->when(!in_array($this->sortField, ['name']), function($query) {
                $query->orderBy($this->sortField, $this->sortDirection);
            })
            ->paginate(10);

        $complexityLevels = HatchComplexityLevel::all();

        return view('hatch::livewire.template.index', [
            'templates' => $templates,
            'complexityLevels' => $complexityLevels,
            'assistants' => $this->availableAssistants(),
        ])->layout('platform::layouts.app');
    }

    public function createTemplate()
    {
        $this->validate();
        
        $template = HatchProjectTemplate::create([
            'name' => $this->name,
            'description' => $this->description,
            'ai_personality' => $this->ai_personality,
            'industry_context' => $this->industry_context,
            'complexity_level' => $this->complexity_level,
            'ai_instructions' => $this->ai_instructions ?: null,
            'team_id' => auth()->user()->current_team_id,
            'created_by_user_id' => auth()->id(),
        ]);

        // Optional: Assistant zuweisen
        if (!empty($this->assistant_id)) {
            $template->setAssignedAiAssistant((int) $this->assistant_id);
        }

        $this->resetForm();
        $this->modalShow = false;
        
        session()->flash('message', 'Template erfolgreich erstellt!');
    }

    public function resetForm()
    {
        $this->reset([
            'name', 'description', 'ai_personality', 'industry_context',
            'complexity_level', 'ai_instructions', 'assistant_id'
        ]);
    }

    private function availableAssistants()
    {
        $user = auth()->user();
        return AiAssistantAssistant::query()
            ->where(function ($q) use ($user) {
                $q->where('ownership_type', 'team')
                  ->where('team_id', $user->current_team_id)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('ownership_type', 'user')
                         ->where('user_id', $user->id);
                  });
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
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
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
}
