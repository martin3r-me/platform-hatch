<?php

namespace Platform\Hatch\Livewire\Public;

use Livewire\Component;
use Platform\Hatch\Models\HatchIntakeSession;
use Platform\Hatch\Models\HatchLookup;

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

    // Compound type properties
    public array $matrixAnswers = [];
    public array $addressFields = [];
    public array $rankingOrder = [];
    public string $dateRangeStart = '';
    public string $dateRangeEnd = '';
    public array $repeaterEntries = [];

    // Lookup options cache (keyed by block id)
    public array $lookupOptions = [];

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

        // Load lookup options for all lookup-type blocks
        $this->loadLookupOptions();

        // Vereinfachte Zugriffsprüfung basierend auf dem neuen Status-Modell
        if ($intake->status === 'draft') {
            if ($this->session->status !== 'completed') {
                $this->state = 'notStarted';
                return;
            }
        } elseif ($intake->status === 'closed') {
            if ($this->session->status !== 'completed') {
                $this->state = 'notActive';
                return;
            }
        }

        if ($this->session->status === 'completed') {
            $this->state = 'completed';
            $this->currentStep = 0;
            $this->loadCurrentAnswer();
            return;
        }

        // Auto-save hidden fields
        $this->autoSaveHiddenFields();

        $this->loadCurrentAnswer();
        $this->state = 'ready';
    }

    private function loadLookupOptions(): void
    {
        foreach ($this->blocks as $block) {
            if ($block['type'] === 'lookup') {
                $lookupId = $block['logic_config']['lookup_id'] ?? null;
                if ($lookupId) {
                    $lookup = HatchLookup::find($lookupId);
                    if ($lookup) {
                        $this->lookupOptions[$block['id']] = $lookup->getOptionsWithMeta();
                    }
                }
            }
        }
    }

    private function autoSaveHiddenFields(): void
    {
        $answers = $this->session->answers ?? [];
        $changed = false;

        foreach ($this->blocks as $block) {
            if ($block['type'] !== 'hidden') continue;
            $key = "block_{$block['id']}";
            if (isset($answers[$key]) && $answers[$key] !== '') continue;

            $config = $block['logic_config'] ?? [];
            $source = $config['source'] ?? 'static';
            $value = $config['default_value'] ?? '';

            if ($source === 'url_param') {
                $value = request()->query($value, '');
            } elseif ($source === 'referrer') {
                $value = request()->header('referer', '');
            }

            $answers[$key] = $value;
            $changed = true;
        }

        if ($changed) {
            $this->session->update(['answers' => $answers]);
        }
    }

    public function loadCurrentAnswer(): void
    {
        if (!isset($this->blocks[$this->currentStep])) {
            $this->currentAnswer = '';
            $this->selectedOptions = [];
            $this->matrixAnswers = [];
            $this->addressFields = [];
            $this->rankingOrder = [];
            $this->dateRangeStart = '';
            $this->dateRangeEnd = '';
            return;
        }

        $blockId = $this->blocks[$this->currentStep]['id'];
        $type = $this->blocks[$this->currentStep]['type'];
        $answers = $this->session->answers ?? [];
        $raw = $answers["block_{$blockId}"] ?? '';

        // Reset all compound properties
        $this->matrixAnswers = [];
        $this->addressFields = [];
        $this->rankingOrder = [];
        $this->dateRangeStart = '';
        $this->dateRangeEnd = '';
        $this->repeaterEntries = [];
        $this->selectedOptions = [];

        switch ($type) {
            case 'multi_select':
                $this->currentAnswer = '';
                if (is_string($raw) && $raw !== '') {
                    $decoded = json_decode($raw, true);
                    $this->selectedOptions = is_array($decoded) ? $decoded : [];
                }
                break;

            case 'boolean':
            case 'consent':
                $this->currentAnswer = $raw === true || $raw === 'true' ? 'true' : ($raw === false || $raw === 'false' ? 'false' : '');
                break;

            case 'matrix':
                $this->currentAnswer = '';
                if (is_string($raw) && $raw !== '') {
                    $decoded = json_decode($raw, true);
                    $this->matrixAnswers = is_array($decoded) ? $decoded : [];
                }
                break;

            case 'ranking':
                $this->currentAnswer = '';
                if (is_string($raw) && $raw !== '') {
                    $decoded = json_decode($raw, true);
                    $this->rankingOrder = is_array($decoded) ? $decoded : [];
                } else {
                    // Initialize with default order from config
                    $config = $this->blocks[$this->currentStep]['logic_config'] ?? [];
                    $options = $config['options'] ?? [];
                    $this->rankingOrder = array_map(fn($o) => is_array($o) ? ($o['value'] ?? '') : $o, $options);
                }
                break;

            case 'address':
                $this->currentAnswer = '';
                if (is_string($raw) && $raw !== '') {
                    $decoded = json_decode($raw, true);
                    $this->addressFields = is_array($decoded) ? $decoded : [];
                }
                break;

            case 'date_range':
                $this->currentAnswer = '';
                if (is_string($raw) && $raw !== '') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $this->dateRangeStart = $decoded['start'] ?? '';
                        $this->dateRangeEnd = $decoded['end'] ?? '';
                    }
                }
                break;

            case 'repeater':
                $this->currentAnswer = '';
                if (is_string($raw) && $raw !== '') {
                    $decoded = json_decode($raw, true);
                    $this->repeaterEntries = is_array($decoded) ? $decoded : [];
                }
                if (empty($this->repeaterEntries)) {
                    $config = $this->blocks[$this->currentStep]['logic_config'] ?? [];
                    $minEntries = (int) ($config['min_entries'] ?? 0);
                    if ($minEntries > 0) {
                        $fields = $config['fields'] ?? [];
                        $emptyEntry = [];
                        foreach ($fields as $f) {
                            $emptyEntry[$f['key'] ?? ''] = '';
                        }
                        for ($i = 0; $i < $minEntries; $i++) {
                            $this->repeaterEntries[] = $emptyEntry;
                        }
                    }
                }
                break;

            case 'lookup':
                $config = $this->blocks[$this->currentStep]['logic_config'] ?? [];
                $multiple = $config['multiple'] ?? false;
                if ($multiple) {
                    $this->currentAnswer = '';
                    if (is_string($raw) && $raw !== '') {
                        $decoded = json_decode($raw, true);
                        $this->selectedOptions = is_array($decoded) ? $decoded : [];
                    }
                } else {
                    $this->currentAnswer = is_string($raw) ? $raw : (string) $raw;
                }
                break;

            default:
                $this->currentAnswer = is_string($raw) ? $raw : (string) $raw;
                break;
        }
    }

    private function isIntakeAccessible(): bool
    {
        $intake = $this->session?->projectIntake;

        return $intake && $intake->status === 'published';
    }

    private function getUnansweredRequiredBlocks(): array
    {
        $answers = $this->session->answers ?? [];
        $missing = [];

        foreach ($this->blocks as $index => $block) {
            // Display-only types don't need answers
            if (in_array($block['type'], ['info', 'section', 'calculated', 'hidden'])) {
                continue;
            }

            if (!$block['is_required']) {
                continue;
            }

            $key = "block_{$block['id']}";
            $raw = $answers[$key] ?? '';

            $isEmpty = match ($block['type']) {
                'multi_select' => (function () use ($raw) {
                    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                    return !is_array($decoded) || empty($decoded) || $raw === '' || $raw === '[]';
                })(),
                'matrix' => (function () use ($raw) {
                    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                    return !is_array($decoded) || empty($decoded);
                })(),
                'ranking' => (function () use ($raw) {
                    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                    return !is_array($decoded) || empty($decoded);
                })(),
                'address' => (function () use ($raw) {
                    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                    if (!is_array($decoded) || empty($decoded)) return true;
                    return empty(array_filter($decoded));
                })(),
                'date_range' => (function () use ($raw) {
                    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                    return !is_array($decoded) || empty($decoded['start'] ?? '') || empty($decoded['end'] ?? '');
                })(),
                'repeater' => (function () use ($raw, $block) {
                    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                    if (!is_array($decoded) || empty($decoded)) return true;
                    $config = $block['logic_config'] ?? [];
                    $minEntries = (int) ($config['min_entries'] ?? 0);
                    return $minEntries > 0 && count($decoded) < $minEntries;
                })(),
                'lookup' => (function () use ($raw, $block) {
                    $config = $block['logic_config'] ?? [];
                    $multiple = $config['multiple'] ?? false;
                    if ($multiple) {
                        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                        return !is_array($decoded) || empty($decoded);
                    }
                    return $raw === '' || $raw === null;
                })(),
                'consent' => (function () use ($raw, $block) {
                    $config = $block['logic_config'] ?? [];
                    $mustAccept = $config['must_accept'] ?? true;
                    return $mustAccept && $raw !== 'true';
                })(),
                default => ($raw === '' || $raw === null),
            };

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
            $intake = $this->session?->projectIntake;
            if ($intake && $intake->status === 'draft') {
                $this->state = 'notStarted';
            } else {
                $this->state = 'notActive';
            }
            return;
        }

        if (!isset($this->blocks[$this->currentStep])) {
            return;
        }

        $blockId = $this->blocks[$this->currentStep]['id'];
        $type = $this->blocks[$this->currentStep]['type'];

        // Display-only types don't save
        if (in_array($type, ['info', 'section', 'calculated'])) {
            return;
        }

        $answers = $this->session->answers ?? [];

        switch ($type) {
            case 'multi_select':
                $answers["block_{$blockId}"] = json_encode($this->selectedOptions);
                break;

            case 'matrix':
                $answers["block_{$blockId}"] = json_encode($this->matrixAnswers);
                break;

            case 'ranking':
                $answers["block_{$blockId}"] = json_encode($this->rankingOrder);
                break;

            case 'address':
                $answers["block_{$blockId}"] = json_encode($this->addressFields);
                break;

            case 'date_range':
                $answers["block_{$blockId}"] = json_encode([
                    'start' => $this->dateRangeStart,
                    'end' => $this->dateRangeEnd,
                ]);
                break;

            case 'repeater':
                $answers["block_{$blockId}"] = json_encode($this->repeaterEntries);
                break;

            case 'lookup':
                $config = $this->blocks[$this->currentStep]['logic_config'] ?? [];
                $multiple = $config['multiple'] ?? false;
                if ($multiple) {
                    $answers["block_{$blockId}"] = json_encode($this->selectedOptions);
                } else {
                    $answers["block_{$blockId}"] = $this->currentAnswer;
                }
                break;

            default:
                $answers["block_{$blockId}"] = $this->currentAnswer;
                break;
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

        if ($this->state === 'notActive') {
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

    // Matrix: set a single cell answer
    public function setMatrixAnswer(string $item, string $value): void
    {
        if ($this->state === 'completed') return;
        $this->matrixAnswers[$item] = $value;
    }

    // Ranking: reorder items
    public function moveRankingItem(int $from, int $to): void
    {
        if ($this->state === 'completed') return;
        if ($from < 0 || $from >= count($this->rankingOrder)) return;
        if ($to < 0 || $to >= count($this->rankingOrder)) return;

        $item = array_splice($this->rankingOrder, $from, 1);
        array_splice($this->rankingOrder, $to, 0, $item);
    }

    // Address: update a single field
    public function updateAddressField(string $field, string $value): void
    {
        if ($this->state === 'completed') return;
        $this->addressFields[$field] = $value;
    }

    // Date Range: set start/end
    public function setDateRangeStart(string $value): void
    {
        if ($this->state === 'completed') return;
        $this->dateRangeStart = $value;
    }

    public function setDateRangeEnd(string $value): void
    {
        if ($this->state === 'completed') return;
        $this->dateRangeEnd = $value;
    }

    // Repeater: add entry
    public function addRepeaterEntry(): void
    {
        if ($this->state === 'completed') return;
        if (!isset($this->blocks[$this->currentStep])) return;

        $config = $this->blocks[$this->currentStep]['logic_config'] ?? [];
        $maxEntries = (int) ($config['max_entries'] ?? 10);
        if (count($this->repeaterEntries) >= $maxEntries) return;

        $fields = $config['fields'] ?? [];
        $entry = [];
        foreach ($fields as $f) {
            $entry[$f['key'] ?? ''] = '';
        }
        $this->repeaterEntries[] = $entry;
    }

    // Repeater: remove entry
    public function removeRepeaterEntry(int $index): void
    {
        if ($this->state === 'completed') return;
        $config = $this->blocks[$this->currentStep]['logic_config'] ?? [];
        $minEntries = (int) ($config['min_entries'] ?? 0);
        if (count($this->repeaterEntries) <= $minEntries) return;

        unset($this->repeaterEntries[$index]);
        $this->repeaterEntries = array_values($this->repeaterEntries);
    }

    // Repeater: update a single field in an entry
    public function updateRepeaterField(int $entryIndex, string $fieldKey, string $value): void
    {
        if ($this->state === 'completed') return;
        if (isset($this->repeaterEntries[$entryIndex])) {
            $this->repeaterEntries[$entryIndex][$fieldKey] = $value;
        }
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
