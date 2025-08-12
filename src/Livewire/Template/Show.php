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
    public HatchProjectTemplate $template; // Direkt als Property!
    public $complexityLevels;
    public $blockDefinitionOptions;
    public $assistant_id;
    public $availableAssistants;
    
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
        // Template mit Relationships laden
        $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition', 'assignedAiAssistant'])
            ->find($template->id);
            
        $this->complexityLevels = HatchComplexityLevel::all();
        
        // AI Assistant Optionen laden
        $this->availableAssistants = $this->template->availableAiAssistants();
        $this->assistant_id = $this->template->assignedAiAssistant->first()?->id;
        
        // BlockDefinition-Optionen mit "Jetzt auswählen" als erste Option
        $this->blockDefinitionOptions = collect([
            ['id' => '', 'name' => 'Jetzt auswählen...']
        ])->merge(
            HatchBlockDefinition::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function($item) {
                    return ['id' => $item->id, 'name' => $item->name];
                })
        );
    }
    
    // Aktualisiert die verfügbaren BlockDefinition-Optionen
    public function getAvailableBlockDefinitionOptions()
    {
        // Hole alle aktiven BlockDefinitions
        $allBlockDefinitions = HatchBlockDefinition::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        // Hole alle bereits verknüpften BlockDefinition-IDs für dieses Template
        $usedBlockDefinitionIds = [];
        if ($this->template && $this->template->templateBlocks) {
            $usedBlockDefinitionIds = $this->template->templateBlocks
                ->where('block_definition_id', '!=', null)
                ->pluck('block_definition_id')
                ->toArray();
        }
            
        // Wenn wir gerade einen Block bearbeiten, entferne seine aktuelle BlockDefinition NICHT aus der Liste
        if ($this->editingBlock && $this->editingBlock->block_definition_id) {
            $usedBlockDefinitionIds = array_filter($usedBlockDefinitionIds, function($id) {
                return $id != $this->editingBlock->block_definition_id;
            });
        }
            
        // Filtere die bereits verwendeten heraus
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
        // Debug: Schauen was im Template steht
        \Log::info('Template before save:', [
            'name' => $this->template->name,
            'description' => $this->template->description,
            'complexity_level' => $this->template->complexity_level,
            'ai_personality' => $this->template->ai_personality,
            'industry_context' => $this->template->industry_context,
        ]);
        
        // Temporär Validierung deaktiviert
        // $this->validate();
        $this->template->save();
        
        // AI Assistant zuweisen/aktualisieren
        if ($this->assistant_id) {
            $this->template->setAssignedAiAssistant($this->assistant_id);
        }
        
        // Template neu laden mit Relationships
        $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition', 'assignedAiAssistant'])
            ->find($this->template->id);
        
        $this->dispatch('notifications:store', [
            'title' => 'Template gespeichert',
            'message' => 'Template wurde erfolgreich gespeichert.',
            'notice_type' => 'success',
            'noticable_type' => HatchProjectTemplate::class,
            'noticable_id' => $this->template->getKey(),
        ]);
    }

    // Block management
    public function startEditingBlock($blockId)
    {
        $this->editingBlockId = $blockId;
        $this->editingBlock = $this->template->templateBlocks->find($blockId);
        
        // Aktualisiere die verfügbaren BlockDefinition-Optionen
        $this->blockDefinitionOptions = $this->getAvailableBlockDefinitionOptions();
        
        // Wenn keine BlockDefinition verknüpft ist, setze leeren String für Select
        if ($this->editingBlock && !$this->editingBlock->block_definition_id) {
            $this->editingBlock->block_definition_id = '';
        }
    }

    public function saveBlock()
    {
        if ($this->editingBlock) {
            // Leeren String wieder auf null setzen für die Datenbank
            if ($this->editingBlock->block_definition_id === '') {
                $this->editingBlock->block_definition_id = null;
            }
            
            $this->editingBlock->save();
        }
        
        $this->cancelEditingBlock();
        
        // Template neu laden mit Relationships
        $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition', 'assignedAiAssistant'])
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
            
            // Template neu laden mit Relationships
            $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition', 'assignedAiAssistant'])
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
            // Neuen Block erstellen
            $newBlock = $this->template->templateBlocks()->create([
                'name' => 'Neuer Block',
                'description' => 'Beschreibung eingeben...',
                'sort_order' => $this->template->templateBlocks->count() + 1,
                'is_required' => false,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
                'block_definition_id' => null,
            ]);
            
            // Template neu laden mit Relationships
            $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition', 'assignedAiAssistant'])
                ->find($this->template->id);
            
            $this->dispatch('notifications:store', [
                'title' => 'Block hinzugefügt',
                'message' => 'Neuer Block wurde erfolgreich hinzugefügt.',
                'notice_type' => 'success',
                'noticable_type' => HatchProjectTemplate::class,
                'noticable_id' => $this->template->id,
            ]);
            
        } catch (\Exception $e) {
            logger()->error('Error adding block', [
                'error' => $e->getMessage(),
                'template_id' => $this->template->id,
            ]);
            
            $this->dispatch('notifications:store', [
                'title' => 'Fehler',
                'message' => 'Block konnte nicht hinzugefügt werden: ' . $e->getMessage(),
                'notice_type' => 'error',
            ]);
        }
    }

    public function updateBlockOrder($items)
    {
        \Log::info('updateBlockOrder called with:', ['items' => $items, 'type' => gettype($items)]);
        
        // Sicherstellen, dass $items ein Array ist
        if (!is_array($items)) {
            \Log::error('items is not an array:', ['items' => $items, 'type' => gettype($items)]);
            return;
        }
        
        foreach ($items as $item) {
            $blockId = $item['value'];
            $newOrder = $item['order'];
            
            \Log::info("Processing block {$blockId} with new order {$newOrder}");
            
            $block = HatchTemplateBlock::where('id', $blockId)
                ->where('project_template_id', $this->template->id)
                ->first();
                
            if ($block) {
                $oldSortOrder = $block->sort_order;
                $block->sort_order = $newOrder;
                $block->save();
                
                \Log::info("Block {$block->id} sort_order updated: {$oldSortOrder} -> {$block->sort_order}");
            } else {
                \Log::warning("Block {$blockId} not found or doesn't belong to template {$this->template->id}");
            }
        }
        
        // Template neu laden mit Relationships
        $this->template = HatchProjectTemplate::with(['templateBlocks.blockDefinition', 'assignedAiAssistant'])
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
        // Aktualisiere die verfügbaren BlockDefinition-Optionen
        $this->blockDefinitionOptions = $this->getAvailableBlockDefinitionOptions();
        
        return view('hatch::livewire.template.show', [
            'template' => $this->template,
            'complexityLevels' => $this->complexityLevels,
            'blockDefinitionOptions' => $this->blockDefinitionOptions,
            'availableAssistants' => $this->availableAssistants,
        ])->layout('platform::layouts.app');
    }
}
