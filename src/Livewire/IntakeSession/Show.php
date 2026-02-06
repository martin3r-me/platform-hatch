<?php

namespace Platform\Hatch\Livewire\IntakeSession;

use Livewire\Component;
use Platform\Hatch\Models\HatchIntakeSession;

class Show extends Component
{
    public HatchIntakeSession $intakeSession;

    public function mount(HatchIntakeSession $intakeSession)
    {
        $this->intakeSession = $intakeSession;
        $this->intakeSession->load([
            'projectIntake.projectTemplate.templateBlocks.blockDefinition',
        ]);
    }

    public function render()
    {
        $blocks = collect();
        $answers = $this->intakeSession->answers ?? [];

        if ($this->intakeSession->projectIntake?->projectTemplate) {
            $blocks = $this->intakeSession->projectIntake->projectTemplate
                ->templateBlocks
                ->sortBy('sort_order')
                ->values()
                ->map(function ($templateBlock) use ($answers) {
                    $blockDef = $templateBlock->blockDefinition;
                    $blockId = $blockDef?->id;
                    $answer = $answers[$blockId] ?? $answers[$templateBlock->id] ?? null;

                    return [
                        'name' => $blockDef?->name ?? 'Unbekannt',
                        'type' => $blockDef?->block_type ?? 'text',
                        'type_label' => $blockDef?->getBlockTypeLabel() ?? 'Text',
                        'description' => $blockDef?->description,
                        'is_required' => $templateBlock->is_required ?? false,
                        'logic_config' => $blockDef?->logic_config ?? [],
                        'answer' => $answer,
                        'sort_order' => $templateBlock->sort_order,
                    ];
                });
        }

        $totalBlocks = $blocks->count();

        return view('hatch::livewire.intake-session.show', [
            'intakeSession' => $this->intakeSession,
            'blocks' => $blocks,
            'totalBlocks' => $totalBlocks,
        ])->layout('platform::layouts.app');
    }
}
