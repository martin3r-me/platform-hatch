<?php

namespace Platform\Hatch\Livewire\Placeholder;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Platform\Hatch\Models\HatchPlaceholder;
use Platform\Hatch\Support\IntakePlaceholders;

/**
 * Pflegeseite für Platzhalter: eingebaute (nur Anzeige) + eigene des Teams.
 */
class Index extends Component
{
    public bool $formOpen = false;
    public ?int $editingId = null;

    public string $label = '';
    public string $key = '';
    public string $defaultValue = '';
    public string $description = '';

    /** Kürzel folgt dem Namen, bis es von Hand geändert wurde. */
    public bool $keyTouched = false;

    public function openCreate(): void
    {
        $this->resetForm();
        $this->formOpen = true;
    }

    public function openEdit(int $id): void
    {
        $placeholder = $this->findOwned($id);
        if (!$placeholder) {
            return;
        }

        $this->resetValidation();
        $this->editingId = $placeholder->id;
        $this->label = $placeholder->label;
        $this->key = $placeholder->key;
        $this->defaultValue = (string) $placeholder->default_value;
        $this->description = (string) $placeholder->description;
        $this->keyTouched = true;
        $this->formOpen = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    public function updatedLabel(): void
    {
        if (!$this->editingId && !$this->keyTouched) {
            $this->key = Str::slug($this->label, '_');
        }
    }

    public function updatedKey(): void
    {
        $this->keyTouched = true;
        $this->key = Str::slug($this->key, '_');
    }

    public function save(): void
    {
        $teamId = auth()->user()->current_team_id;
        $this->key = Str::slug($this->key ?: $this->label, '_');

        $this->validate([
            'label' => 'required|string|max:100',
            'key' => [
                'required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('hatch_placeholders', 'key')->where('team_id', $teamId)->ignore($this->editingId),
                function ($attribute, $value, $fail) {
                    if (app(IntakePlaceholders::class)->isReservedKey($value)) {
                        $fail('Dieses Kürzel ist für einen eingebauten Platzhalter reserviert.');
                    }
                },
            ],
            'defaultValue' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:255',
        ], [
            'key.regex' => 'Nur Kleinbuchstaben, Ziffern und Unterstriche, beginnend mit einem Buchstaben.',
            'key.unique' => 'Dieses Kürzel gibt es im Team schon.',
        ], [
            'label' => 'Name',
            'key' => 'Kürzel',
            'defaultValue' => 'Standardwert',
        ]);

        $data = [
            'label' => trim($this->label),
            'default_value' => $this->defaultValue !== '' ? $this->defaultValue : null,
            'description' => trim($this->description) !== '' ? trim($this->description) : null,
        ];

        if ($this->editingId) {
            $placeholder = $this->findOwned($this->editingId);
            if (!$placeholder) {
                return;
            }
            // Kürzel bleibt nach dem Anlegen fest – sonst würden bestehende Texte mit {{alt}} brechen.
            $placeholder->update($data);
        } else {
            HatchPlaceholder::create($data + [
                'team_id' => $teamId,
                'created_by_user_id' => auth()->id(),
                'key' => $this->key,
                'sort' => (int) HatchPlaceholder::forTeam($teamId)->max('sort') + 1,
            ]);
        }

        app(IntakePlaceholders::class)->forget($teamId);
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $placeholder = $this->findOwned($id);
        if (!$placeholder) {
            return;
        }

        $placeholder->delete();
        app(IntakePlaceholders::class)->forget($placeholder->team_id);

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    private function findOwned(int $id): ?HatchPlaceholder
    {
        return HatchPlaceholder::forTeam(auth()->user()->current_team_id)->find($id);
    }

    private function resetForm(): void
    {
        $this->reset(['formOpen', 'editingId', 'label', 'key', 'defaultValue', 'description', 'keyTouched']);
        $this->resetValidation();
    }

    public function render()
    {
        $placeholders = app(IntakePlaceholders::class);
        $teamId = auth()->user()->current_team_id;
        $values = $placeholders->values(null);

        $builtins = collect($placeholders->builtins())->map(fn ($def, $key) => [
            'key' => $key,
            'token' => '{{' . $key . '}}',
            'label' => $def['label'],
            'description' => $def['description'],
            'example' => $values[$key] ?? '',
        ])->values();

        $customs = HatchPlaceholder::forTeam($teamId)->orderBy('sort')->orderBy('label')->get();

        return view('hatch::livewire.placeholder.index', [
            'builtins' => $builtins,
            'customs' => $customs,
        ])->layout('platform::layouts.app');
    }
}
