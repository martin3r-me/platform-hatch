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
    public array $selectedOptions = [];
    public ?string $respondentName = null;

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
        $this->respondentName = $this->session->respondent_name;

        if ($intake->projectTemplate) {
            $this->blocks = $intake->projectTemplate->templateBlocks
                ->sortBy('sort_order')
                ->values()
                ->map(fn($block) => [
                    'id' => $block->id,
                    'name' => $block->blockDefinition->name ?? 'Block',
                    'description' => $block->blockDefinition->description ?? '',
                    'type' => $block->blockDefinition->block_type ?? 'default',
                    'logic_config' => $block->blockDefinition->logic_config ?? [],
                    'is_required' => (bool) $block->is_required,
                ])
                ->toArray();

            $this->totalBlocks = count($this->blocks);
        }

        if ($this->session->status === 'completed') {
            $this->state = 'completed';
            $this->currentStep = 0;
            $this->loadCurrentAnswer();
            return;
        }

        $this->loadCurrentAnswer();
        $this->state = 'ready';
    }

    public function loadCurrentAnswer(): void
    {
        if (!isset($this->blocks[$this->currentStep])) {
            $this->currentAnswer = '';
            $this->selectedOptions = [];
            return;
        }

        $blockId = $this->blocks[$this->currentStep]['id'];
        $type = $this->blocks[$this->currentStep]['type'];
        $answers = $this->session->answers ?? [];
        $raw = $answers["block_{$blockId}"] ?? '';

        if ($type === 'multi_select') {
            $this->currentAnswer = '';
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                $this->selectedOptions = is_array($decoded) ? $decoded : [];
            } else {
                $this->selectedOptions = [];
            }
        } elseif ($type === 'boolean') {
            $this->selectedOptions = [];
            $this->currentAnswer = $raw === true || $raw === 'true' ? 'true' : ($raw === false || $raw === 'false' ? 'false' : '');
        } else {
            $this->selectedOptions = [];
            $this->currentAnswer = is_string($raw) ? $raw : (string) $raw;
        }
    }

    public function saveCurrentBlock(): void
    {
        if ($this->state === 'completed') {
            return;
        }

        if (!isset($this->blocks[$this->currentStep])) {
            return;
        }

        $blockId = $this->blocks[$this->currentStep]['id'];
        $type = $this->blocks[$this->currentStep]['type'];
        $answers = $this->session->answers ?? [];

        if ($type === 'multi_select') {
            $answers["block_{$blockId}"] = json_encode($this->selectedOptions);
        } else {
            $answers["block_{$blockId}"] = $this->currentAnswer;
        }

        $this->session->update([
            'answers' => $answers,
            'current_step' => $this->currentStep,
        ]);
    }

    public function submitIntake(): void
    {
        if ($this->state === 'completed') {
            return;
        }

        $this->saveCurrentBlock();

        $this->session->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->state = 'completed';
    }

    public function toggleOption(string $value): void
    {
        if ($this->state === 'completed') {
            return;
        }

        if (in_array($value, $this->selectedOptions)) {
            $this->selectedOptions = array_values(array_filter(
                $this->selectedOptions,
                fn($opt) => $opt !== $value
            ));
        } else {
            $this->selectedOptions[] = $value;
        }
    }

    public function setAnswer(string $value): void
    {
        if ($this->state === 'completed') {
            return;
        }

        $this->currentAnswer = $value;
    }

    public function goToBlock(int $index): void
    {
        if ($index < 0 || $index >= $this->totalBlocks) {
            return;
        }

        if ($this->state !== 'completed') {
            $this->saveCurrentBlock();
        }

        $this->currentStep = $index;
        $this->loadCurrentAnswer();
    }

    public function nextBlock(): void
    {
        if ($this->state !== 'completed') {
            $this->saveCurrentBlock();
        }

        if ($this->currentStep < $this->totalBlocks - 1) {
            $this->currentStep++;
            $this->loadCurrentAnswer();
        }
    }

    public function previousBlock(): void
    {
        if ($this->state !== 'completed') {
            $this->saveCurrentBlock();
        }

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
