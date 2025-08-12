<?php

namespace Platform\Hatch\Livewire\BlockDefinition;

use Livewire\Component;
use Platform\Hatch\Models\HatchBlockDefinition;

class Index extends Component
{
    public function createBlockDefinition()
    {
        // Erstelle eine neue BlockDefinition
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

        // Redirect zur Show-Seite
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
        return view('hatch::livewire.block-definition.index', [
            'blockDefinitions' => HatchBlockDefinition::query()
                ->where('team_id', auth()->user()->current_team_id)
                ->orderBy('name')
                ->get()
        ])->layout('platform::layouts.app');
    }
}
