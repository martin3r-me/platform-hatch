<?php

namespace Platform\Hatch\Livewire\Template;

use Illuminate\Support\Str;
use Livewire\Component;
use Platform\Hatch\Models\HatchLookup;
use Platform\Hatch\Models\HatchProjectIntake;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchTemplateBlock;
use Platform\Hatch\Support\BlockTypes;
use Symfony\Component\Uid\UuidV7;

/**
 * Template-Editor.
 *
 * Aufbau:
 *   - Seitenleiste: Vorlagen-Einstellungen, speichern automatisch.
 *   - Hauptbereich: Fragen (= Gruppen aus 1..n Blöcken mit gleicher group_uuid)
 *     als Karten. Bearbeitet wird eine Frage in einem Seitenpanel über einen
 *     Entwurf ($draft), der erst mit „Speichern“ geschrieben wird.
 *
 * Datenmodell (unverändert, MCP-kompatibel): Titel/Beschreibung der Frage
 * liegen am ersten Block der Gruppe, weitere Felder tragen ihr Label in name.
 * Typ-Einstellungen stehen in logic_config (Schlüssel siehe BlockTypes).
 */
class Show extends Component
{
    public HatchProjectTemplate $template;

    protected $rules = [
        'template.name' => 'required|string|max:255',
        'template.description' => 'nullable|string',
        'template.completion_message' => 'nullable|string|max:2000',
        'template.flow_mode' => 'required|in:block_flow,overview',
        'template.is_active' => 'boolean',
    ];

    // Typ-Auswahl: 'new' (neue Frage) | 'add' (Feld in offener Frage) | 'change:{index}'
    public ?string $typePicker = null;

    // Seitenpanel
    public ?string $editingGroup = null;
    public array $draft = [];

    public function mount(HatchProjectTemplate $template)
    {
        $this->template = HatchProjectTemplate::with(['templateBlocks'])->find($template->id);

        if ((int) $this->template->team_id !== (int) auth()->user()->current_team_id) {
            abort(403);
        }
    }

    // ---------------------------------------------------------------------
    // Vorlagen-Einstellungen (Auto-Save)
    // ---------------------------------------------------------------------

    public function updated($property)
    {
        if (!str_starts_with($property, 'template.')) {
            return;
        }

        $this->validateOnly($property);
        $this->template->save();
    }

    public function toggleActive(): void
    {
        $this->template->is_active = !$this->template->is_active;
        $this->template->save();
    }

    public function createIntakeFromTemplate()
    {
        $teamId = auth()->user()->current_team_id;

        if ((int) $this->template->team_id !== (int) $teamId) {
            abort(403);
        }

        $intake = HatchProjectIntake::create([
            'name' => $this->template->name,
            'description' => $this->template->description,
            'project_template_id' => $this->template->id,
            'status' => 'draft',
            'is_active' => false,
            'team_id' => $teamId,
            'created_by_user_id' => auth()->id(),
            'owned_by_user_id' => auth()->id(),
        ]);

        return redirect()->route('hatch.project-intakes.show', $intake);
    }

    // ---------------------------------------------------------------------
    // Fragen-Liste
    // ---------------------------------------------------------------------

    /**
     * Blöcke gruppiert nach group_uuid, stabil nach sort_order.
     * Blöcke ohne group_uuid (Altbestand) bilden eine Einzelgruppe "single:{id}".
     *
     * Bewusst eine Methode statt Computed Property: Livewire würde das Ergebnis
     * pro Request cachen, nach Änderungen würde dann die alte Liste gerendert.
     */
    private function groups(): array
    {
        $groups = [];
        foreach ($this->template->templateBlocks->sortBy('sort_order') as $block) {
            $key = $block->group_uuid ?: 'single:' . $block->id;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'header' => $block,
                    'fields' => [],
                    'sort_order' => $block->sort_order,
                ];
            }
            $groups[$key]['fields'][] = $block;
        }

        return array_values($groups);
    }

    public function openTypePicker(string $target = 'new'): void
    {
        $this->typePicker = $target;
    }

    public function closeTypePicker(): void
    {
        $this->typePicker = null;
    }

    public function pickType(string $type): void
    {
        $def = BlockTypes::get($type);
        $type = $def['type'];
        $target = $this->typePicker;
        $this->typePicker = null;

        if ($target === 'new') {
            $groupUuid = (string) UuidV7::generate();
            $this->template->templateBlocks()->create([
                'name' => 'Neue Frage',
                'description' => null,
                'sort_order' => (int) $this->template->templateBlocks->max('sort_order') + 1,
                'is_required' => false,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
                'block_type' => $type,
                'logic_config' => BlockTypes::defaults($type) ?: null,
                'group_uuid' => $groupUuid,
            ]);
            $this->refreshTemplate();
            $this->openEditor($groupUuid);
            $this->draft['is_new'] = true;

            return;
        }

        if ($target === 'add' && $this->editingGroup) {
            $this->draft['fields'][] = $this->emptyField($type);

            return;
        }

        if ($target && str_starts_with($target, 'change:')) {
            $i = (int) substr($target, 7);
            if (!isset($this->draft['fields'][$i])) {
                return;
            }
            $old = $this->draft['fields'][$i];
            $config = BlockTypes::defaults($type);
            // Antwortmöglichkeiten beim Wechsel zwischen Auswahl-Typen behalten
            if (in_array($old['type'], BlockTypes::OPTION_TYPES, true) && in_array($type, BlockTypes::OPTION_TYPES, true)) {
                $config['options'] = $old['config']['options'] ?? $config['options'] ?? [];
            }
            $this->draft['fields'][$i]['type'] = $type;
            $this->draft['fields'][$i]['config'] = $config;
            $this->draft['fields'][$i]['config_json'] = $this->toJson($config);
        }
    }

    public function moveGroup(string $key, int $direction): void
    {
        $groups = $this->groups();
        $index = collect($groups)->search(fn ($g) => $g['key'] === $key);
        $swap = $index === false ? false : $index + $direction;
        if ($index === false || $swap < 0 || $swap >= count($groups)) {
            return;
        }

        [$groups[$index], $groups[$swap]] = [$groups[$swap], $groups[$index]];
        $this->renumber($groups);
        $this->refreshTemplate();
    }

    public function duplicateGroup(string $key): void
    {
        $groups = $this->groups();
        $index = collect($groups)->search(fn ($g) => $g['key'] === $key);
        if ($index === false) {
            return;
        }

        $newUuid = (string) UuidV7::generate();
        $copies = [];
        foreach ($groups[$index]['fields'] as $i => $block) {
            $copy = $block->replicate(['uuid']);
            $copy->group_uuid = $newUuid;
            $copy->visibility_rules = null; // Regeln verweisen auf konkrete Felder – nicht blind mitkopieren
            if ($i === 0) {
                $copy->name = trim(($block->name ?: 'Frage') . ' (Kopie)');
            }
            $copy->save();
            $copies[] = $copy;
        }

        array_splice($groups, $index + 1, 0, [['key' => $newUuid, 'fields' => $copies]]);
        $this->renumber($groups);
        $this->refreshTemplate();
    }

    public function deleteGroup(string $key): void
    {
        $group = collect($this->groups())->firstWhere('key', $key);
        if (!$group) {
            return;
        }

        HatchTemplateBlock::where('project_template_id', $this->template->id)
            ->whereIn('id', collect($group['fields'])->pluck('id'))
            ->delete();

        if ($this->editingGroup === $key) {
            $this->closeEditor();
        }
        $this->refreshTemplate();
    }

    // ---------------------------------------------------------------------
    // Seitenpanel: eine Frage bearbeiten
    // ---------------------------------------------------------------------

    public function openEditor(string $key): void
    {
        $group = collect($this->groups())->firstWhere('key', $key);
        if (!$group) {
            return;
        }

        $header = $group['header'];
        $this->resetValidation();
        $this->editingGroup = $key;
        $this->draft = [
            'title' => (string) $header->name,
            'description' => (string) $header->description,
            'display_compact' => (bool) $header->display_compact,
            'fields' => collect($group['fields'])->map(fn (HatchTemplateBlock $b, $i) => [
                'id' => $b->id,
                'uid' => 'b' . $b->id,
                'label' => $i === 0 ? '' : (string) $b->name,
                'type' => $b->block_type ?: 'text',
                'is_required' => (bool) $b->is_required,
                'config' => $b->logic_config ?? [],
                'config_json' => $this->toJson($b->logic_config ?? []),
                'ai_prompt' => (string) $b->ai_prompt,
                'visibility' => $this->normalizeVisibility($b->visibility_rules),
            ])->all(),
            'removed' => [],
            'is_new' => false,
        ];
    }

    public function closeEditor(): void
    {
        $this->editingGroup = null;
        $this->draft = [];
        $this->resetValidation();
    }

    /** Frisch angelegte, nie gespeicherte Frage beim Abbrechen wieder entfernen. */
    public function cancelEditor(): void
    {
        if (($this->draft['is_new'] ?? false) && $this->editingGroup) {
            $this->deleteGroup($this->editingGroup);

            return;
        }
        $this->closeEditor();
    }

    public function removeField(int $i): void
    {
        if ($i === 0 || !isset($this->draft['fields'][$i])) {
            return;
        }
        if (!empty($this->draft['fields'][$i]['id'])) {
            $this->draft['removed'][] = $this->draft['fields'][$i]['id'];
        }
        array_splice($this->draft['fields'], $i, 1);
    }

    public function moveField(int $i, int $direction): void
    {
        $j = $i + $direction;
        // Das erste Feld trägt Titel/Beschreibung der Frage und bleibt vorne.
        if ($i < 1 || $j < 1 || !isset($this->draft['fields'][$i], $this->draft['fields'][$j])) {
            return;
        }
        [$this->draft['fields'][$i], $this->draft['fields'][$j]] = [$this->draft['fields'][$j], $this->draft['fields'][$i]];
    }

    /** Zeile in einer Listen-Einstellung (options / items) hinzufügen. */
    public function addRow(int $i, string $key): void
    {
        $rows = $this->draft['fields'][$i]['config'][$key] ?? [];
        $n = count($rows) + 1;
        $rows[] = ['label' => ($key === 'items' ? 'Aspekt ' : 'Option ') . $n, 'value' => ''];
        $this->draft['fields'][$i]['config'][$key] = $rows;
    }

    public function removeRow(int $i, string $key, int $row): void
    {
        $rows = $this->draft['fields'][$i]['config'][$key] ?? [];
        array_splice($rows, $row, 1);
        $this->draft['fields'][$i]['config'][$key] = array_values($rows);
    }

    public function moveRow(int $i, string $key, int $row, int $direction): void
    {
        $rows = $this->draft['fields'][$i]['config'][$key] ?? [];
        $to = $row + $direction;
        if (!isset($rows[$row], $rows[$to])) {
            return;
        }
        [$rows[$row], $rows[$to]] = [$rows[$to], $rows[$row]];
        $this->draft['fields'][$i]['config'][$key] = $rows;
    }

    public function addRule(int $i): void
    {
        $this->draft['fields'][$i]['visibility']['rules'][] = ['source_block_id' => '', 'operator' => 'equals', 'value' => ''];
    }

    public function removeRule(int $i, int $r): void
    {
        $rules = $this->draft['fields'][$i]['visibility']['rules'] ?? [];
        array_splice($rules, $r, 1);
        $this->draft['fields'][$i]['visibility']['rules'] = array_values($rules);
    }

    /** JSON-Feld (Erweitert) in die Einstellungen übernehmen. */
    public function applyJson(int $i): void
    {
        $raw = trim((string) ($this->draft['fields'][$i]['config_json'] ?? ''));
        $decoded = $raw === '' ? [] : json_decode($raw, true);
        if (!is_array($decoded)) {
            $this->addError("draft.fields.$i.config_json", 'Kein gültiges JSON-Objekt.');

            return;
        }
        $this->resetErrorBag("draft.fields.$i.config_json");
        $this->draft['fields'][$i]['config'] = $decoded;
    }

    public function saveEditor(): void
    {
        if (!$this->editingGroup) {
            return;
        }

        $this->validate([
            'draft.title' => 'required|string|max:255',
            'draft.description' => 'nullable|string|max:2000',
            'draft.fields.*.label' => 'nullable|string|max:255',
            'draft.fields.*.ai_prompt' => 'nullable|string|max:5000',
        ], [], [
            'draft.title' => 'Frage',
        ]);

        // Listen-Einstellungen: jede Zeile braucht einen Text
        foreach ($this->draft['fields'] as $i => $field) {
            foreach (['options', 'items'] as $listKey) {
                foreach ($field['config'][$listKey] ?? [] as $r => $row) {
                    if (trim((string) ($row['label'] ?? '')) === '') {
                        $this->addError("draft.fields.$i.config.$listKey.$r.label", 'Bitte einen Text eingeben.');
                    }
                }
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $group = collect($this->groups())->firstWhere('key', $this->editingGroup);
        if (!$group) {
            $this->closeEditor();

            return;
        }
        $groupUuid = $group['header']->group_uuid ?: (string) UuidV7::generate();
        $position = collect($this->groups())->search(fn ($g) => $g['key'] === $this->editingGroup);
        $headerSort = (int) $group['header']->sort_order;

        // Sichtbarkeitsregeln auf Zyklen prüfen (bestehende Felder)
        foreach ($this->draft['fields'] as $i => $field) {
            $rules = $this->cleanVisibility($field['visibility']);
            if ($rules && !empty($field['id']) && $this->hasVisibilityCycle($rules, (int) $field['id'])) {
                $this->addError("draft.fields.$i.visibility", 'Diese Regeln bilden einen Kreis (Felder hängen gegenseitig voneinander ab).');

                return;
            }
        }

        $saved = [];
        foreach ($this->draft['fields'] as $i => $field) {
            $type = BlockTypes::get($field['type'])['type'];
            $attributes = [
                'group_uuid' => $groupUuid,
                'block_type' => $type,
                'name' => $i === 0 ? trim($this->draft['title']) : (trim((string) $field['label']) ?: null),
                'description' => $i === 0 ? (trim((string) $this->draft['description']) ?: null) : null,
                'is_required' => (bool) $field['is_required'],
                'logic_config' => $this->normalizeConfig($type, $field['config'] ?? []) ?: null,
                'ai_prompt' => trim((string) $field['ai_prompt']) ?: null,
                'visibility_rules' => $this->cleanVisibility($field['visibility']),
            ];
            if ($i === 0) {
                $attributes['display_compact'] = (bool) ($this->draft['display_compact'] ?? false);
            }

            $block = !empty($field['id'])
                ? HatchTemplateBlock::where('project_template_id', $this->template->id)->find($field['id'])
                : null;

            if ($block) {
                $block->fill($attributes)->save();
            } else {
                $block = $this->template->templateBlocks()->create($attributes + [
                    'sort_order' => $headerSort,
                    'team_id' => auth()->user()->current_team_id,
                    'created_by_user_id' => auth()->id(),
                ]);
            }
            $saved[] = $block;
        }

        if (!empty($this->draft['removed'])) {
            HatchTemplateBlock::where('project_template_id', $this->template->id)
                ->whereIn('id', $this->draft['removed'])
                ->delete();
        }

        // Reihenfolge: gespeicherte Felder (in Panel-Reihenfolge) an die alte Stelle der Frage setzen
        $this->refreshTemplate();
        $savedIds = collect($saved)->pluck('id')->all();
        $groups = collect($this->groups())
            ->map(fn ($g) => ['key' => $g['key'], 'fields' => array_values(array_filter($g['fields'], fn ($b) => !in_array($b->id, $savedIds, true)))])
            ->filter(fn ($g) => !empty($g['fields']))
            ->values()
            ->all();
        array_splice($groups, $position === false ? count($groups) : min($position, count($groups)), 0, [['key' => $groupUuid, 'fields' => $saved]]);
        $this->renumber($groups);

        $this->refreshTemplate();
        $this->closeEditor();

        $this->dispatch('notifications:store', [
            'title' => 'Frage gespeichert',
            'message' => 'Die Änderungen sind gespeichert.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectTemplate::class,
            'noticable_id' => $this->template->getKey(),
        ]);
    }

    // ---------------------------------------------------------------------
    // Hilfen
    // ---------------------------------------------------------------------

    private function emptyField(string $type): array
    {
        $config = BlockTypes::defaults($type);

        return [
            'id' => null,
            'uid' => 'n' . Str::random(8),
            'label' => '',
            'type' => $type,
            'is_required' => false,
            'config' => $config,
            'config_json' => $this->toJson($config),
            'ai_prompt' => '',
            'visibility' => ['combinator' => 'AND', 'rules' => []],
        ];
    }

    /**
     * Einstellungen aufräumen: leere Werte entfernen, Zahlen casten,
     * Optionen/Zeilen mit stabilen value-Schlüsseln versehen.
     * Unbekannte Schlüssel (z. B. per MCP gesetzt) bleiben erhalten.
     */
    private function normalizeConfig(string $type, array $config): array
    {
        $settings = collect(BlockTypes::get($type)['settings'])->keyBy('key');

        foreach ($settings as $key => $setting) {
            $value = data_get($config, $key);

            switch ($setting['kind']) {
                case 'number':
                    if ($value === '' || $value === null) {
                        $this->forgetPath($config, $key);
                    } else {
                        data_set($config, $key, is_numeric($value) ? $value + 0 : $value);
                    }
                    break;
                case 'toggle':
                    data_set($config, $key, (bool) $value);
                    break;
                case 'lookup':
                    if ($value === '' || $value === null) {
                        $this->forgetPath($config, $key);
                    } else {
                        data_set($config, $key, (int) $value);
                    }
                    break;
                case 'options':
                case 'items':
                    $rows = [];
                    $used = [];
                    foreach ((array) $value as $row) {
                        $label = trim((string) ($row['label'] ?? ''));
                        if ($label === '') {
                            continue;
                        }
                        $v = trim((string) ($row['value'] ?? '')) ?: (Str::slug($label, '_') ?: 'wert');
                        $base = $v;
                        $n = 2;
                        while (isset($used[$v])) {
                            $v = $base . '_' . $n++;
                        }
                        $used[$v] = true;
                        $clean = array_merge($row, ['label' => $label, 'value' => $v]);
                        if ($setting['kind'] === 'items' && trim((string) ($row['group'] ?? '')) === '') {
                            unset($clean['group']);
                        }
                        $rows[] = $clean;
                    }
                    data_set($config, $key, $rows);
                    break;
                default:
                    if (is_string($value) && trim($value) === '') {
                        $this->forgetPath($config, $key);
                    }
            }
        }

        if (isset($config['scale_labels']) && is_array($config['scale_labels']) && empty(array_filter($config['scale_labels']))) {
            unset($config['scale_labels']);
        }

        return $config;
    }

    private function forgetPath(array &$array, string $path): void
    {
        $parts = explode('.', $path);
        $last = array_pop($parts);
        $ref = &$array;
        foreach ($parts as $part) {
            if (!isset($ref[$part]) || !is_array($ref[$part])) {
                return;
            }
            $ref = &$ref[$part];
        }
        unset($ref[$last]);
    }

    private function normalizeVisibility($rules): array
    {
        $rules = is_array($rules) ? $rules : [];

        return [
            'combinator' => in_array($rules['combinator'] ?? 'AND', ['AND', 'OR'], true) ? $rules['combinator'] ?? 'AND' : 'AND',
            'rules' => array_values(array_map(fn ($r) => [
                'source_block_id' => (string) ($r['source_block_id'] ?? ''),
                'operator' => $r['operator'] ?? 'equals',
                'value' => (string) ($r['value'] ?? ''),
            ], $rules['rules'] ?? [])),
        ];
    }

    private function cleanVisibility(array $visibility): ?array
    {
        $rules = array_values(array_filter(
            $visibility['rules'] ?? [],
            fn ($r) => (int) ($r['source_block_id'] ?? 0) > 0
        ));

        if (empty($rules)) {
            return null;
        }

        return [
            'combinator' => $visibility['combinator'] === 'OR' ? 'OR' : 'AND',
            'rules' => array_map(fn ($r) => [
                'source_block_id' => (int) $r['source_block_id'],
                'operator' => $r['operator'] ?: 'equals',
                'value' => $r['value'] ?? '',
            ], $rules),
        ];
    }

    /**
     * DFS über den Abhängigkeitsgraphen (A hängt von B ab, wenn A.visibility_rules
     * auf B verweist) – Zyklus, wenn der Zielblock sich selbst erreicht.
     */
    private function hasVisibilityCycle(array $pendingRules, int $targetBlockId): bool
    {
        $graph = [];
        foreach ($this->template->templateBlocks as $b) {
            $rules = $b->id === $targetBlockId ? $pendingRules : ($b->visibility_rules ?? null);
            $graph[$b->id] = collect($rules['rules'] ?? [])
                ->pluck('source_block_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->all();
        }

        $visiting = [];
        $dfs = function (int $node) use (&$dfs, &$visiting, $graph): bool {
            if (isset($visiting[$node])) {
                return true;
            }
            $visiting[$node] = true;
            foreach ($graph[$node] ?? [] as $dep) {
                if ($dfs($dep)) {
                    return true;
                }
            }
            unset($visiting[$node]);

            return false;
        };

        return $dfs($targetBlockId);
    }

    /** sort_order fortlaufend neu vergeben – Gruppen zusammenhängend in der gegebenen Reihenfolge. */
    private function renumber(array $groups): void
    {
        $n = 10;
        foreach ($groups as $group) {
            foreach ($group['fields'] as $block) {
                if ((int) $block->sort_order !== $n) {
                    $block->sort_order = $n;
                    $block->save();
                }
                $n += 10;
            }
        }
    }

    private function refreshTemplate(): void
    {
        $this->template = HatchProjectTemplate::with(['templateBlocks'])->find($this->template->id);
    }

    private function toJson(array $config): string
    {
        return $config === [] ? '' : json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Vorschau-Block im Format, das der Public-Renderer erwartet. */
    private function previewBlock(array $field, int $i): array
    {
        $type = BlockTypes::get($field['type'])['type'];

        return [
            'id' => 'preview_' . $i,
            'type' => $type,
            'name' => $i === 0 ? ($this->draft['title'] ?? '') : ($field['label'] ?? ''),
            'description' => $i === 0 ? ($this->draft['description'] ?? '') : '',
            'is_required' => (bool) $field['is_required'],
            'logic_config' => $this->normalizeConfig($type, $field['config'] ?? []),
        ];
    }

    public function render()
    {
        $teamId = auth()->user()->current_team_id;

        $previewBlocks = [];
        $sourceOptions = [];
        if ($this->editingGroup) {
            foreach ($this->draft['fields'] ?? [] as $i => $field) {
                $previewBlocks[$i] = $this->previewBlock($field, $i);
            }

            // Mögliche Quellen für Sichtbarkeitsregeln: Felder vor dieser Frage
            $group = collect($this->groups())->firstWhere('key', $this->editingGroup);
            $firstSort = $group ? $group['header']->sort_order : PHP_INT_MAX;
            $sourceOptions = $this->template->templateBlocks
                ->filter(fn ($b) => $b->sort_order < $firstSort)
                ->sortBy('sort_order')
                ->map(fn ($b) => ['id' => (string) $b->id, 'label' => $b->name ?: (BlockTypes::get($b->block_type)['label'])])
                ->values()
                ->all();
        }

        return view('hatch::livewire.template.show', [
            'template' => $this->template,
            'groups' => $this->groups(),
            'typeGroups' => BlockTypes::grouped(),
            'placeholderCatalog' => app(\Platform\Hatch\Support\IntakePlaceholders::class)->catalog(null),
            'previewBlocks' => $previewBlocks,
            'sourceOptions' => $sourceOptions,
            'lookups' => HatchLookup::where('team_id', $teamId)->orderBy('label')->get(['id', 'label']),
            'intakeCount' => $this->template->projectIntakes()->count(),
        ])->layout('platform::layouts.app');
    }
}
