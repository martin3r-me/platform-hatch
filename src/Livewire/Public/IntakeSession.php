<?php

namespace Platform\Hatch\Livewire\Public;

use Livewire\Component;
use Platform\Hatch\Models\HatchIntakeSession;

class IntakeSession extends Component
{
    public ?HatchIntakeSession $session = null;
    public string $state = 'loading';
    public ?string $intakeName = null;
    public array $blocks = [];
    public int $totalBlocks = 0;
    public int $currentStep = 0;

    public function mount(string $sessionToken)
    {
        $this->session = HatchIntakeSession::where('session_token', $sessionToken)
            ->with(['projectIntake.projectTemplate.templateBlocks.blockDefinition'])
            ->first();

        if (!$this->session) {
            $this->state = 'notFound';
            return;
        }

        $intake = $this->session->projectIntake;
        $this->intakeName = $intake->name;
        $this->currentStep = $this->session->current_step;

        if ($intake->projectTemplate) {
            $this->blocks = $intake->projectTemplate->templateBlocks
                ->sortBy('sort_order')
                ->values()
                ->map(fn($block) => [
                    'id' => $block->id,
                    'name' => $block->blockDefinition->name ?? 'Block',
                    'description' => $block->blockDefinition->description ?? '',
                    'type' => $block->blockDefinition->block_type ?? 'default',
                ])
                ->toArray();

            $this->totalBlocks = count($this->blocks);
        }

        $this->state = 'ready';
    }

    public function render()
    {
        return view('hatch::livewire.public.intake-session')
            ->layout('platform::layouts.guest');
    }
}
