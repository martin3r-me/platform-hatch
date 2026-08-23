<?php

namespace Platform\Hatch\Livewire\IntakeSession;

use Livewire\Component;
use Platform\Hatch\Enums\HatchBlockType;
use Platform\Hatch\Models\HatchIntakeSession;

class Show extends Component
{
    public HatchIntakeSession $intakeSession;

    public function mount(HatchIntakeSession $intakeSession)
    {
        $this->intakeSession = $intakeSession;
        $this->intakeSession->load([
            'projectIntake.projectTemplate.templateBlocks',
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
                    $answer = $answers["block_{$templateBlock->id}"] ?? null;

                    return [
                        'name' => $templateBlock->name ?? 'Unbekannt',
                        'type' => $templateBlock->block_type ?? 'text',
                        'type_label' => HatchBlockType::tryFrom($templateBlock->block_type)?->label() ?? 'Text',
                        'description' => $templateBlock->description,
                        'is_required' => $templateBlock->is_required ?? false,
                        'logic_config' => $templateBlock->logic_config ?? [],
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
