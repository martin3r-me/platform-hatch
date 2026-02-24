<?php

namespace Platform\Hatch\Livewire\Lookup;

use Livewire\Component;
use Platform\Hatch\Models\HatchLookup;
use Platform\Hatch\Models\HatchLookupValue;

class Index extends Component
{
    public $search = '';

    // Create/Edit modal
    public $modalShow = false;
    public $editingLookupId = null;
    public $lookupName = '';
    public $lookupLabel = '';
    public $lookupDescription = '';

    // Values editor
    public $editingValuesLookupId = null;
    public $editingValues = [];
    public $newValueLabel = '';
    public $newValueValue = '';

    public function updatedSearch()
    {
        // Reactivity trigger
    }

    public function openCreateModal()
    {
        $this->editingLookupId = null;
        $this->lookupName = '';
        $this->lookupLabel = '';
        $this->lookupDescription = '';
        $this->modalShow = true;
    }

    public function openEditModal($id)
    {
        $lookup = HatchLookup::find($id);
        if (!$lookup || $lookup->team_id !== auth()->user()->current_team_id) return;

        $this->editingLookupId = $id;
        $this->lookupName = $lookup->name;
        $this->lookupLabel = $lookup->label;
        $this->lookupDescription = $lookup->description ?? '';
        $this->modalShow = true;
    }

    public function saveLookup()
    {
        $this->validate([
            'lookupName' => 'required|string|max:255',
            'lookupLabel' => 'required|string|max:255',
            'lookupDescription' => 'nullable|string',
        ]);

        $teamId = auth()->user()->current_team_id;

        if ($this->editingLookupId) {
            $lookup = HatchLookup::find($this->editingLookupId);
            if (!$lookup || $lookup->team_id !== $teamId) return;
            if ($lookup->is_system) {
                // Only allow updating label and description for system lookups
                $lookup->update([
                    'label' => $this->lookupLabel,
                    'description' => $this->lookupDescription,
                ]);
            } else {
                $lookup->update([
                    'name' => $this->lookupName,
                    'label' => $this->lookupLabel,
                    'description' => $this->lookupDescription,
                ]);
            }
            $message = 'Lookup aktualisiert.';
        } else {
            HatchLookup::create([
                'team_id' => $teamId,
                'created_by_user_id' => auth()->id(),
                'name' => $this->lookupName,
                'label' => $this->lookupLabel,
                'description' => $this->lookupDescription,
                'is_system' => false,
            ]);
            $message = 'Lookup erstellt.';
        }

        $this->modalShow = false;
        $this->dispatch('notifications:store', [
            'title' => $message,
            'notice_type' => 'success',
        ]);
    }

    public function deleteLookup($id)
    {
        $lookup = HatchLookup::find($id);
        if (!$lookup || $lookup->team_id !== auth()->user()->current_team_id) return;
        if ($lookup->is_system) return;

        $lookup->delete();
        $this->dispatch('notifications:store', [
            'title' => 'Lookup gelöscht.',
            'notice_type' => 'success',
        ]);
    }

    public function openValues($id)
    {
        $lookup = HatchLookup::find($id);
        if (!$lookup || $lookup->team_id !== auth()->user()->current_team_id) return;

        $this->editingValuesLookupId = $id;
        $this->editingValues = $lookup->values()->get()->map(fn($v) => [
            'id' => $v->id,
            'value' => $v->value,
            'label' => $v->label,
            'order' => $v->order,
            'is_active' => $v->is_active,
        ])->toArray();
        $this->newValueLabel = '';
        $this->newValueValue = '';
    }

    public function closeValues()
    {
        $this->editingValuesLookupId = null;
        $this->editingValues = [];
    }

    public function addValue()
    {
        if (empty($this->newValueLabel) || empty($this->newValueValue)) return;

        $maxOrder = collect($this->editingValues)->max('order') ?? -1;

        $value = HatchLookupValue::create([
            'lookup_id' => $this->editingValuesLookupId,
            'value' => $this->newValueValue,
            'label' => $this->newValueLabel,
            'order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        $this->editingValues[] = [
            'id' => $value->id,
            'value' => $value->value,
            'label' => $value->label,
            'order' => $value->order,
            'is_active' => true,
        ];

        $this->newValueLabel = '';
        $this->newValueValue = '';
    }

    public function toggleValueActive($valueId)
    {
        $value = HatchLookupValue::find($valueId);
        if (!$value) return;

        $value->update(['is_active' => !$value->is_active]);

        foreach ($this->editingValues as &$ev) {
            if ($ev['id'] === $valueId) {
                $ev['is_active'] = !$ev['is_active'];
                break;
            }
        }
    }

    public function deleteValue($valueId)
    {
        $value = HatchLookupValue::find($valueId);
        if (!$value) return;

        $value->delete();
        $this->editingValues = array_values(array_filter(
            $this->editingValues,
            fn($ev) => $ev['id'] !== $valueId
        ));
    }

    public function render()
    {
        $query = HatchLookup::query()
            ->withCount('values')
            ->where('team_id', auth()->user()->current_team_id);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('label', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        return view('hatch::livewire.lookup.index', [
            'lookups' => $query->orderBy('label')->get(),
        ])->layout('platform::layouts.app');
    }
}
