<?php

namespace Platform\Hatch\Livewire\Public;

use Livewire\Component;
use Platform\Hatch\Models\HatchIntakeSession;

class IntakeSession extends Component
{
    public ?HatchIntakeSession $session = null;
    public string $state = 'loading';
    public ?string $intakeName = null;
    public ?string $sessionToken = null;
    public array $blocks = [];
    public int $totalBlocks = 0;
    public int $currentStep = 0;
    public string $currentAnswer = '';

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
        $this->sessionToken = $this->session->session_token;
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

        $this->loadCurrentAnswer();
        $this->state = 'ready';
    }

    public function loadCurrentAnswer(): void
    {
        if (!isset($this->blocks[$this->currentStep])) {
            $this->currentAnswer = '';
            return;
        }

        $blockId = $this->blocks[$this->currentStep]['id'];
        $answers = $this->session->answers ?? [];
        $this->currentAnswer = $answers["block_{$blockId}"] ?? '';
    }

    public function saveCurrentBlock(): void
    {
        if (!isset($this->blocks[$this->currentStep])) {
            return;
        }

        $blockId = $this->blocks[$this->currentStep]['id'];
        $answers = $this->session->answers ?? [];
        $answers["block_{$blockId}"] = $this->currentAnswer;

        $this->session->update([
            'answers' => $answers,
            'current_step' => $this->currentStep,
        ]);
    }

    public function nextBlock(): void
    {
        $this->saveCurrentBlock();

        if ($this->currentStep < $this->totalBlocks - 1) {
            $this->currentStep++;
            $this->loadCurrentAnswer();
        }
    }

    public function previousBlock(): void
    {
        $this->saveCurrentBlock();

        if ($this->currentStep > 0) {
            $this->currentStep--;
            $this->loadCurrentAnswer();
        }
    }

    public function render()
    {
        return view('hatch::livewire.public.intake-session')
            ->layout('platform::layouts.guest');
    }
}
