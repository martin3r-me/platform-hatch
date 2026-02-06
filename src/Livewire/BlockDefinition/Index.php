<?php

namespace Platform\Hatch\Livewire\BlockDefinition;

use Livewire\Component;
use Platform\Hatch\Models\HatchBlockDefinition;

class Index extends Component
{
    public $search = '';

    public function updatedSearch()
    {
        // Reactivity trigger - Livewire re-renders automatically
    }

    public function createBlockDefinition()
    {
        $blockDefinition = HatchBlockDefinition::create([
            'name' => 'Neue BlockDefinition',
            'description' => 'Beschreibung eingeben...',
            'block_type' => 'text',
            'ai_prompt' => 'Frage nach: ',
            'is_active' => true,
            'team_id' => auth()->user()->current_team_id,
            'created_by_user_id' => auth()->id(),
        ]);

        $this->dispatch('notifications:store', [
            'title' => 'BlockDefinition erstellt',
            'message' => 'Neue BlockDefinition wurde erfolgreich angelegt.',
            'notice_type' => 'success',
            'noticable_type' => get_class($blockDefinition),
            'noticable_id' => $blockDefinition->getKey(),
        ]);

        return redirect()->route('hatch.block-definitions.show', $blockDefinition);
    }

    public function editBlockDefinition($id)
    {
        return redirect()->route('hatch.block-definitions.show', $id);
    }

    public function deleteBlockDefinition($id)
    {
        $blockDefinition = HatchBlockDefinition::find($id);

        if ($blockDefinition && $blockDefinition->team_id === auth()->user()->current_team_id) {
            $blockDefinition->delete();

            $this->dispatch('notifications:store', [
                'title' => 'BlockDefinition gelöscht',
                'message' => 'Die BlockDefinition wurde erfolgreich gelöscht.',
                'notice_type' => 'success',
            ]);
        }
    }

    public function render()
    {
        $query = HatchBlockDefinition::query()
            ->where('team_id', auth()->user()->current_team_id);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('block_type', 'like', '%' . $this->search . '%');
            });
        }

        return view('hatch::livewire.block-definition.index', [
            'blockDefinitions' => $query->orderBy('name')->get()
        ])->layout('platform::layouts.app');
    }
}
