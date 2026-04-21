<?php

namespace Platform\Hatch\Livewire\Template;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchComplexityLevel;
use Platform\Hatch\Models\HatchTemplateBlock;
use Platform\Hatch\Models\HatchBlockDefinition;
use Symfony\Component\Uid\UuidV7;

class Show extends Component
{
    public HatchProjectTemplate $template;
    public $complexityLevels;
    public $blockDefinitionOptions;

    // Block editing
    public $editingBlockId = null;
    public $editingBlock = null;

    protected $rules = [
        'template.name' => 'required|string|max:255',
        'template.description' => 'nullable|string',
        'template.ai_personality' => 'nullable|string|max:255',
        'template.industry_context' => 'nullable|string|max:255',
        'template.complexity_level' => 'required|in:simple,medium,complex',
        'template.ai_instructions' => 'nullable|array',
        'editingBlock.name' => 'required|string|max:255',
        'editingBlock.description' => 'nullable|string|max:1000',
        'editingBlock.block_definition_id' => 'nullable|exists:hatch_block_definitions,id',
    ];

    public function mount(HatchProjectTemplate $template)
    {
        $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition'])
            ->find($template->id);

        if ($this->template->team_id !== auth()->user()->current_team_id) {
            abort(403);
        }

        $this->complexityLevels = HatchComplexityLevel::all();

        $teamId = auth()->user()->current_team_id;

        $this->blockDefinitionOptions = collect([
            ['id' => '', 'name' => 'Jetzt auswählen...']
        ])->merge(
            HatchBlockDefinition::where('is_active', true)
                ->where('team_id', $teamId)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function($item) {
                    return ['id' => $item->id, 'name' => $item->name];
                })
        );
    }

    public function getAvailableBlockDefinitionOptions()
    {
        // BlockDefinitions dürfen mehrfach im selben Template verwendet werden
        // (z. B. zwei Freitextfelder in unterschiedlichen Abfragen).
        $all = HatchBlockDefinition::where('is_active', true)
            ->where('team_id', auth()->user()->current_team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return collect([
            ['id' => '', 'name' => 'Jetzt auswählen...']
        ])->merge($all);
    }

    #[Computed]
    public function isDirty()
    {
        return $this->template->isDirty();
    }

    public function saveTemplate()
    {
        $this->template->save();

        $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition'])
            ->find($this->template->id);

        $this->dispatch('notifications:store', [
            'title' => 'Template gespeichert',
            'message' => 'Template wurde erfolgreich gespeichert.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectTemplate::class,
            'noticable_id' => $this->template->getKey(),
        ]);
    }

    public function startEditingBlock($blockId)
    {
        $this->editingBlockId = $blockId;
        $this->editingBlock = $this->template->templateBlocks->find($blockId);

        $this->blockDefinitionOptions = $this->getAvailableBlockDefinitionOptions();

        if ($this->editingBlock && !$this->editingBlock->block_definition_id) {
            $this->editingBlock->block_definition_id = '';
        }
    }

    public function saveBlock()
    {
        if ($this->editingBlock) {
            if ($this->editingBlock->block_definition_id === '') {
                $this->editingBlock->block_definition_id = null;
            }

            // Cycle-Detection für Visibility-Rules
            $rules = $this->editingBlock->visibility_rules;
            if (is_array($rules) && !empty($rules['rules'])) {
                if ($this->hasVisibilityCycle($rules, $this->editingBlock->id)) {
                    $this->dispatch('notifications:store', [
                        'title' => 'Zyklische Regel',
                        'message' => 'Die Sichtbarkeitsregeln erzeugen einen Zyklus und können so nicht gespeichert werden.',
                        'notice_type' => 'error',
                    ]);
                    return;
                }

                // Leere Regeln (ohne Quelle) rausfiltern, damit Frontend nicht stolpert.
                $rules['rules'] = array_values(array_filter(
                    $rules['rules'],
                    fn ($r) => is_array($r) && (int) ($r['source_block_id'] ?? 0) > 0
                ));
                $this->editingBlock->visibility_rules = empty($rules['rules']) ? null : $rules;
            }

            $this->editingBlock->save();
        }

        $this->cancelEditingBlock();

        $this->refreshTemplate();

        $this->dispatch('notifications:store', [
            'title' => 'Block gespeichert',
            'message' => 'Block wurde erfolgreich gespeichert.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectTemplate::class,
            'noticable_id' => $this->template->getKey(),
        ]);
    }

    public function cancelEditingBlock()
    {
        $this->editingBlockId = null;
        $this->editingBlock = null;
    }

    public function deleteBlock($blockId)
    {
        $block = $this->template->templateBlocks->find($blockId);
        if ($block) {
            $block->delete();

            $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition'])
                ->find($this->template->id);

            $this->dispatch('notifications:store', [
                'title' => 'Block gelöscht',
                'message' => 'Block wurde erfolgreich gelöscht.',
                'notice_type' => 'success',
                'noticable_type' => HatchProjectTemplate::class,
                'noticable_id' => $this->template->getKey(),
            ]);
        }
    }

    public function addBlock()
    {
        // Neue Abfrage = neue Gruppe mit genau einem Feld.
        $this->addQuestionGroup();
    }

    /**
     * Legt eine neue Abfrage-Gruppe mit einem initialen Feld an.
     */
    public function addQuestionGroup(): void
    {
        try {
            $groupUuid = (string) UuidV7::generate();
            $nextSort = $this->nextGroupSortOrder();

            $this->template->templateBlocks()->create([
                'name' => 'Neue Abfrage',
                'description' => 'Beschreibung eingeben...',
                'sort_order' => $nextSort,
                'is_required' => false,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
                'block_definition_id' => null,
                'group_uuid' => $groupUuid,
            ]);

            $this->refreshTemplate();

            $this->dispatch('notifications:store', [
                'title' => 'Abfrage hinzugefügt',
                'message' => 'Neue Abfrage wurde erstellt.',
                'notice_type' => 'success',
                'noticable_type' => HatchProjectTemplate::class,
                'noticable_id' => $this->template->id,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notifications:store', [
                'title' => 'Fehler',
                'message' => 'Abfrage konnte nicht angelegt werden: ' . $e->getMessage(),
                'notice_type' => 'error',
            ]);
        }
    }

    /**
     * Fügt einer bestehenden Abfrage-Gruppe ein weiteres Feld hinzu.
     */
    public function addFieldToGroup(string $groupUuid): void
    {
        try {
            // Sort-Order: direkt nach dem letzten Block dieser Gruppe einfügen.
            $lastInGroup = $this->template->templateBlocks
                ->where('group_uuid', $groupUuid)
                ->sortByDesc('sort_order')
                ->first();

            $insertAfter = $lastInGroup?->sort_order ?? 0;

            // Nachfolgende Blocks um 1 nach hinten schieben.
            foreach ($this->template->templateBlocks->where('sort_order', '>', $insertAfter) as $b) {
                $b->sort_order = $b->sort_order + 1;
                $b->save();
            }

            $this->template->templateBlocks()->create([
                'name' => null,
                'description' => null,
                'sort_order' => $insertAfter + 1,
                'is_required' => false,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
                'block_definition_id' => null,
                'group_uuid' => $groupUuid,
            ]);

            $this->refreshTemplate();

            $this->dispatch('notifications:store', [
                'title' => 'Feld hinzugefügt',
                'message' => 'Neues Feld wurde zur Abfrage hinzugefügt.',
                'notice_type' => 'success',
                'noticable_type' => HatchProjectTemplate::class,
                'noticable_id' => $this->template->id,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notifications:store', [
                'title' => 'Fehler',
                'message' => 'Feld konnte nicht hinzugefügt werden: ' . $e->getMessage(),
                'notice_type' => 'error',
            ]);
        }
    }

    /**
     * Fügt dem aktuell editierten Block eine leere Sichtbarkeitsregel hinzu.
     */
    public function addVisibilityRule(): void
    {
        if (!$this->editingBlock) return;

        $rules = $this->editingBlock->visibility_rules ?? ['combinator' => 'AND', 'rules' => []];
        if (!isset($rules['rules']) || !is_array($rules['rules'])) {
            $rules['rules'] = [];
        }
        $rules['rules'][] = [
            'source_block_id' => '',
            'operator' => 'equals',
            'value' => '',
        ];
        $this->editingBlock->visibility_rules = $rules;
    }

    public function removeVisibilityRule(int $index): void
    {
        if (!$this->editingBlock) return;

        $rules = $this->editingBlock->visibility_rules ?? ['combinator' => 'AND', 'rules' => []];
        if (isset($rules['rules'][$index])) {
            array_splice($rules['rules'], $index, 1);
        }
        $this->editingBlock->visibility_rules = $rules;
    }

    public function setVisibilityCombinator(string $combinator): void
    {
        if (!$this->editingBlock) return;

        $combinator = in_array($combinator, ['AND', 'OR']) ? $combinator : 'AND';
        $rules = $this->editingBlock->visibility_rules ?? ['combinator' => 'AND', 'rules' => []];
        $rules['combinator'] = $combinator;
        $this->editingBlock->visibility_rules = $rules;
    }

    /**
     * Einfache Cycle-Detection: baut aus allen TemplateBlocks einen
     * Abhängigkeitsgraph (A hängt von B ab, wenn A.visibility_rules
     * auf B.id verweist) und prüft auf Zyklen via DFS.
     */
    private function hasVisibilityCycle(array $pendingRules, int $targetBlockId): bool
    {
        $graph = [];
        foreach ($this->template->templateBlocks as $b) {
            $rules = $b->visibility_rules ?? null;
            if ($b->id === $targetBlockId) {
                $rules = $pendingRules;
            }
            $sources = collect($rules['rules'] ?? [])
                ->pluck('source_block_id')
                ->filter(fn ($id) => (int) $id > 0)
                ->map(fn ($id) => (int) $id)
                ->all();
            $graph[$b->id] = $sources;
        }

        // DFS ab targetBlockId — Zyklus, wenn wir targetBlockId wieder erreichen.
        $visiting = [];
        $dfs = function (int $node) use (&$dfs, &$visiting, $graph): bool {
            if (isset($visiting[$node])) return true;
            $visiting[$node] = true;
            foreach ($graph[$node] ?? [] as $dep) {
                if ($dfs($dep)) return true;
            }
            unset($visiting[$node]);
            return false;
        };

        return $dfs($targetBlockId);
    }

    public function deleteGroup(string $groupUuid): void
    {
        HatchTemplateBlock::where('project_template_id', $this->template->id)
            ->where('group_uuid', $groupUuid)
            ->delete();

        $this->refreshTemplate();

        $this->dispatch('notifications:store', [
            'title' => 'Abfrage gelöscht',
            'message' => 'Abfrage und alle zugehörigen Felder wurden gelöscht.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectTemplate::class,
            'noticable_id' => $this->template->id,
        ]);
    }

    private function refreshTemplate(): void
    {
        $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition'])
            ->find($this->template->id);
    }

    private function nextGroupSortOrder(): int
    {
        $max = (int) ($this->template->templateBlocks->max('sort_order') ?? 0);
        return $max + 1;
    }

    /**
     * Blocks gruppiert nach group_uuid — stabil nach sort_order.
     * Ungroup-Blocks (aus Altbestand) bekommen automatisch eine virtuelle
     * Einzelgruppe über ihre ID ("single:{id}").
     */
    public function getGroupedBlocksProperty(): array
    {
        $groups = [];
        foreach ($this->template->templateBlocks->sortBy('sort_order') as $block) {
            $key = $block->group_uuid ?: 'single:' . $block->id;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'group_uuid' => $block->group_uuid,
                    'is_virtual' => $block->group_uuid === null,
                    'sort_order' => $block->sort_order,
                    'header_block' => $block,
                    'fields' => [],
                ];
            }
            $groups[$key]['fields'][] = $block;
        }

        usort($groups, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);
        return $groups;
    }

    public function updateBlockOrder($items)
    {
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            $block = HatchTemplateBlock::where('id', $item['value'])
                ->where('project_template_id', $this->template->id)
                ->first();

            if ($block) {
                $block->sort_order = $item['order'];
                $block->save();
            }
        }

        $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition'])
            ->find($this->template->id);

        $this->dispatch('notifications:store', [
            'title' => 'Sortierung aktualisiert',
            'message' => 'Block-Reihenfolge wurde erfolgreich gespeichert.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectTemplate::class,
            'noticable_id' => $this->template->getKey(),
        ]);
    }

    public function render()
    {
        $this->blockDefinitionOptions = $this->getAvailableBlockDefinitionOptions();

        return view('hatch::livewire.template.show', [
            'template' => $this->template,
            'complexityLevels' => $this->complexityLevels,
            'blockDefinitionOptions' => $this->blockDefinitionOptions,
        ])->layout('platform::layouts.app');
    }
}
