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
    public array $missingRequiredBlocks = [];
    public ?string $validationError = null;

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

        if ($intake->status !== 'in_progress' || !$intake->is_active) {
            if ($this->session->status === 'completed') {
                // Completed sessions remain viewable in read-only mode
            } else {
                $this->state = 'paused';
                return;
            }
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

    private function isIntakeAccessible(): bool
    {
        $intake = $this->session?->projectIntake;

        return $intake && $intake->is_active && $intake->status === 'in_progress';
    }

    private function getUnansweredRequiredBlocks(): array
    {
        $answers = $this->session->answers ?? [];
        $missing = [];

        foreach ($this->blocks as $index => $block) {
            if (!$block['is_required']) {
                continue;
            }

            $key = "block_{$block['id']}";
            $raw = $answers[$key] ?? '';

            if ($block['type'] === 'multi_select') {
                $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                $isEmpty = !is_array($decoded) || empty($decoded) || $raw === '' || $raw === '[]';
            } else {
                $isEmpty = ($raw === '' || $raw === null);
            }

            if ($isEmpty) {
                $missing[] = $index;
            }
        }

        return $missing;
    }

    public function saveCurrentBlock(): void
    {
        if ($this->state === 'completed') {
            return;
        }

        if (!$this->isIntakeAccessible()) {
            $this->state = 'paused';
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

        $this->missingRequiredBlocks = array_values(array_diff($this->missingRequiredBlocks, [$this->currentStep]));
    }

    public function submitIntake(): void
    {
        if ($this->state === 'completed') {
            return;
        }

        $this->saveCurrentBlock();

        if ($this->state === 'paused') {
            return;
        }

        $this->session->refresh();

        $missing = $this->getUnansweredRequiredBlocks();
        if (!empty($missing)) {
            $this->missingRequiredBlocks = $missing;
            $this->validationError = 'Bitte beantworten Sie alle Pflichtfragen bevor Sie abschliessen.';
            $this->currentStep = $missing[0];
            $this->loadCurrentAnswer();
            return;
        }

        $this->missingRequiredBlocks = [];
        $this->validationError = null;

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

        $this->validationError = null;
        $this->currentStep = $index;
        $this->loadCurrentAnswer();
    }

    public function nextBlock(): void
    {
        if ($this->state !== 'completed') {
            $this->saveCurrentBlock();
        }

        $this->validationError = null;

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

        $this->validationError = null;

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
