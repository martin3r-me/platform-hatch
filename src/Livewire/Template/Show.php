<?php

namespace Platform\Hatch\Livewire\Template;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Platform\Hatch\Models\HatchProjectTemplate;
use Platform\Hatch\Models\HatchComplexityLevel;
use Platform\Hatch\Models\HatchTemplateBlock;
use Platform\Hatch\Models\HatchBlockDefinition;

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
        $allBlockDefinitions = HatchBlockDefinition::where('is_active', true)
            ->where('team_id', auth()->user()->current_team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $usedBlockDefinitionIds = [];
        if ($this->template && $this->template->templateBlocks) {
            $usedBlockDefinitionIds = $this->template->templateBlocks
                ->where('block_definition_id', '!=', null)
                ->pluck('block_definition_id')
                ->toArray();
        }

        if ($this->editingBlock && $this->editingBlock->block_definition_id) {
            $usedBlockDefinitionIds = array_filter($usedBlockDefinitionIds, function($id) {
                return $id != $this->editingBlock->block_definition_id;
            });
        }

        $availableBlockDefinitions = $allBlockDefinitions
            ->whereNotIn('id', $usedBlockDefinitionIds);

        return collect([
            ['id' => '', 'name' => 'Jetzt auswählen...']
        ])->merge($availableBlockDefinitions);
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

            $this->editingBlock->save();
        }

        $this->cancelEditingBlock();

        $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition'])
            ->find($this->template->id);

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
        try {
            $this->template->templateBlocks()->create([
                'name' => 'Neuer Block',
                'description' => 'Beschreibung eingeben...',
                'sort_order' => $this->template->templateBlocks->count() + 1,
                'is_required' => false,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
                'block_definition_id' => null,
            ]);

            $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition'])
                ->find($this->template->id);

            $this->dispatch('notifications:store', [
                'title' => 'Block hinzugefügt',
                'message' => 'Neuer Block wurde erfolgreich hinzugefügt.',
                'notice_type' => 'success',
                'noticable_type' => HatchProjectTemplate::class,
                'noticable_id' => $this->template->id,
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notifications:store', [
                'title' => 'Fehler',
                'message' => 'Block konnte nicht hinzugefügt werden: ' . $e->getMessage(),
                'notice_type' => 'error',
            ]);
        }
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
