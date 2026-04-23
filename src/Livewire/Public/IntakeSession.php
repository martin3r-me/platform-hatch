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
                    // Template-Block überschreibt Label/Description, falls gesetzt — sonst BlockDefinition.
                    'name' => $block->name ?: ($block->blockDefinition->name ?? 'Block'),
                    'description' => $block->description ?: ($block->blockDefinition->description ?? ''),
                    'type' => $block->blockDefinition->block_type ?? 'default',
                    'logic_config' => $block->blockDefinition->logic_config ?? [],
                    'is_required' => (bool) $block->is_required,
                    'group_uuid' => $block->group_uuid,
                    'visibility_rules' => $block->visibility_rules,
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

        // Falls der gespeicherte currentStep wegen Conditional Logic nicht sichtbar ist:
        // auf den nächsten sichtbaren springen.
        if (isset($this->blocks[$this->currentStep]) && !$this->isBlockVisible($this->blocks[$this->currentStep])) {
            $next = $this->findNextVisibleStep($this->currentStep, +1)
                ?? $this->findNextVisibleStep($this->currentStep, -1)
                ?? 0;
            $this->currentStep = $next;
        }

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

    /**
     * Wertet die visibility_rules eines Blocks gegen die bisherigen Antworten aus.
     * Blocks ohne Regeln sind immer sichtbar.
     */
    public function isBlockVisible(array $block, ?array $answers = null): bool
    {
        $rules = $block['visibility_rules'] ?? null;
        if (!is_array($rules) || empty($rules['rules'])) {
            return true;
        }

        $answers = $answers ?? ($this->session?->answers ?? []);
        $combinator = strtoupper($rules['combinator'] ?? 'AND');

        $results = [];
        foreach ($rules['rules'] as $rule) {
            $sourceId = (int) ($rule['source_block_id'] ?? 0);
            if ($sourceId <= 0) continue;

            $operator = $rule['operator'] ?? 'equals';
            $expected = $rule['value'] ?? '';
            $key = "block_{$sourceId}";
            $raw = $answers[$key] ?? '';

            // JSON-encoded Antworten (multi_select, matrix, ...) für selected/contains vernünftig prüfen.
            $decoded = null;
            if (is_string($raw) && $raw !== '' && ($raw[0] === '[' || $raw[0] === '{')) {
                $tmp = json_decode($raw, true);
                if (is_array($tmp)) $decoded = $tmp;
            }

            $results[] = match ($operator) {
                'equals' => (string) $raw === (string) $expected,
                'not_equals' => (string) $raw !== (string) $expected,
                'contains' => is_string($raw) ? str_contains($raw, (string) $expected) : false,
                'empty' => $raw === '' || $raw === null || $raw === '[]' || $raw === '{}',
                'not_empty' => !($raw === '' || $raw === null || $raw === '[]' || $raw === '{}'),
                'selected' => is_array($decoded)
                    ? in_array((string) $expected, array_map('strval', $decoded), true)
                    : (string) $raw === (string) $expected,
                'not_selected' => is_array($decoded)
                    ? !in_array((string) $expected, array_map('strval', $decoded), true)
                    : (string) $raw !== (string) $expected,
                default => true,
            };
        }

        if (empty($results)) return true;

        return $combinator === 'OR'
            ? in_array(true, $results, true)
            : !in_array(false, $results, true);
    }

    /**
     * Liefert den nächsten sichtbaren Block-Index in der Richtung (+1 vorwärts, -1 rückwärts).
     * Liefert null, wenn keiner gefunden wird.
     */
    private function findNextVisibleStep(int $from, int $direction): ?int
    {
        $idx = $from + $direction;
        while ($idx >= 0 && $idx < $this->totalBlocks) {
            if ($this->isBlockVisible($this->blocks[$idx])) {
                return $idx;
            }
            $idx += $direction;
        }
        return null;
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

            // Ausgeblendete Blocks werden nicht validiert.
            if (!$this->isBlockVisible($block, $answers)) {
                continue;
            }

            $config = $block['logic_config'] ?? [];

            // Sonderfall: Matrix mit per_row-Pflicht. Greift auch, wenn der Block
            // selbst nicht als "is_required" markiert ist, solange einzelne Zeilen Pflicht sind.
            if ($block['type'] === 'matrix' && ($config['required_mode'] ?? 'matrix') === 'per_row') {
                $key = "block_{$block['id']}";
                $raw = $answers[$key] ?? '';
                $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                $decoded = is_array($decoded) ? $decoded : [];

                foreach (($config['items'] ?? []) as $item) {
                    if (!is_array($item) || empty($item['is_required'])) {
                        continue;
                    }
                    $itemValue = $item['value'] ?? $item['label'] ?? '';
                    if ($itemValue === '' || !isset($decoded[$itemValue]) || $decoded[$itemValue] === '') {
                        $missing[] = $index;
                        continue 2;
                    }
                }
                // per_row abgedeckt — falls der Block zusätzlich is_required ist, greift der reguläre Check unten.
                if (!$block['is_required']) {
                    continue;
                }
            }

            if (!$block['is_required']) {
                continue;
            }

            $key = "block_{$block['id']}";
            $raw = $answers[$key] ?? '';

            $isEmpty = match ($block['type']) {
                'multi_select' => (function () use ($raw, $config) {
                    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                    if (!is_array($decoded) || empty($decoded) || $raw === '' || $raw === '[]') {
                        return true;
                    }
                    // Min-Selections erzwingen — wenn gesetzt, zählt der Block auch dann als unvollständig,
                    // wenn zwar eine Auswahl existiert aber unter dem Minimum liegt.
                    $min = $config['min_selections'] ?? null;
                    if ($min !== null && $min !== '' && (int) $min > 0) {
                        return count($decoded) < (int) $min;
                    }
                    return false;
                })(),
                'matrix' => (function () use ($raw, $config) {
                    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                    if (!is_array($decoded) || empty($decoded)) return true;
                    // Bei "matrix"-Mode: jede Zeile muss beantwortet sein
                    if (($config['required_mode'] ?? 'matrix') === 'matrix') {
                        foreach (($config['items'] ?? []) as $item) {
                            $itemValue = is_array($item) ? ($item['value'] ?? $item['label'] ?? '') : $item;
                            if ($itemValue === '') continue;
                            if (!isset($decoded[$itemValue]) || $decoded[$itemValue] === '') {
                                return true;
                            }
                        }
                    }
                    return false;
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

        // Max-Limit auch beim finalen Absenden checken.
        if (!$this->enforceMaxSelectionsOnCurrentBlock()) {
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

        // Antworten ausgeblendeter Blocks entfernen — sie dürfen nicht im Export landen.
        $answers = $this->session->answers ?? [];
        foreach ($this->blocks as $block) {
            if (!$this->isBlockVisible($block, $answers)) {
                unset($answers["block_{$block['id']}"]);
            }
        }

        $this->session->update([
            'answers' => $answers,
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
            $this->validationError = null;
            return;
        }

        // Max-Selections für multi_select hart durchsetzen.
        $block = $this->blocks[$this->currentStep] ?? null;
        $max = null;
        if ($block && $block['type'] === 'multi_select') {
            $max = $block['logic_config']['max_selections'] ?? null;
            $max = ($max === null || $max === '') ? null : (int) $max;
        }

        if ($max !== null && $max > 0 && count($this->selectedOptions) >= $max) {
            $this->validationError = 'Du kannst maximal ' . $max . ' Option(en) auswählen. Bitte zuerst eine andere Auswahl abwählen.';
            return;
        }

        $this->selectedOptions[] = $value;
        $this->validationError = null;
    }

    /**
     * Erzwingt Max-Selections für multi_select beim Speichern/Navigieren:
     * falls selectedOptions bereits über dem Limit liegt (z. B. aus einer
     * alten Session, die vor dem Setzen des Limits Antworten hatte), wird
     * auf die ersten N getrimmt und eine Meldung gesetzt.
     */
    private function enforceMaxSelectionsOnCurrentBlock(): bool
    {
        $block = $this->blocks[$this->currentStep] ?? null;
        if (!$block || $block['type'] !== 'multi_select') {
            return true;
        }

        $max = $block['logic_config']['max_selections'] ?? null;
        $max = ($max === null || $max === '') ? null : (int) $max;
        if ($max === null || $max <= 0) {
            return true;
        }

        if (count($this->selectedOptions) > $max) {
            // Nicht automatisch trimmen — User muss selbst abwählen, damit
            // die Entscheidung bewusst bleibt. Wir blockieren die Navigation.
            $this->validationError = 'Du hast ' . count($this->selectedOptions)
                . ' Optionen ausgewählt, erlaubt sind maximal ' . $max
                . '. Bitte wähle einige Optionen ab, bevor du weitergehst.';
            return false;
        }

        return true;
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
            // Max-Limit durchsetzen bevor wir weiterspringen. Wenn das fehlschlägt,
            // bleibt der User auf dem aktuellen Block und sieht die Fehlermeldung.
            if (!$this->enforceMaxSelectionsOnCurrentBlock()) {
                return;
            }
            $this->saveCurrentBlock();
            $this->session->refresh();

            // Pflichtfeld-Check auf aktuellen Block: wenn required und leer,
            // nicht weiter navigieren.
            $missing = $this->getUnansweredRequiredBlocks();
            if (in_array($this->currentStep, $missing, true)) {
                $this->missingRequiredBlocks = $missing;
                $this->validationError = 'Dieses Feld ist ein Pflichtfeld. Bitte fülle es aus, bevor du weitergehst.';
                return;
            }
        }

        $this->validationError = null;

        $next = $this->findNextVisibleStep($this->currentStep, +1);
        if ($next !== null) {
            $this->currentStep = $next;
            $this->loadCurrentAnswer();
        }
    }

    public function previousBlock(): void
    {
        if ($this->state !== 'completed') {
            $this->saveCurrentBlock();
        }

        $this->validationError = null;

        $prev = $this->findNextVisibleStep($this->currentStep, -1);
        if ($prev !== null) {
            $this->currentStep = $prev;
            $this->loadCurrentAnswer();
        }
    }

    public function render()
    {
        return view('hatch::livewire.public.intake-session')
            ->layout('platform::layouts.guest');
    }
}
