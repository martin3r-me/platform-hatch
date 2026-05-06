<?php

namespace Platform\Hatch\Livewire\Public;

use Livewire\Component;
use Platform\Hatch\Models\HatchIntakeSession;
use Platform\Hatch\Models\HatchLookup;
use Platform\Hatch\Models\HatchProjectTemplate;

/**
 * Overview-Modus: rendert alle Blöcke einer Session auf einer einzigen Seite
 * (z. B. Wochenfeedback). Pflichtfelder werden erst beim finalen Submit
 * validiert. Verwendet wird diese Komponente automatisch, wenn das zugrunde
 * liegende Template flow_mode = 'overview' hat — sonst greift der bestehende
 * IntakeSession-Block-Flow.
 */
class IntakeSessionOverview extends Component
{
    public ?HatchIntakeSession $session = null;
    public string $state = 'loading';
    public ?string $intakeName = null;
    public ?string $sessionToken = null;
    public array $blocks = [];
    public int $totalBlocks = 0;
    public ?string $respondentName = null;
    public array $missingRequiredBlocks = [];
    public ?string $validationError = null;

    /** Indexed State pro Block-Id (string-Keys, da Livewire-Wires Strings erwarten). */
    public array $answersByBlock = [];          // skalar: text, number, boolean, scale, rating, ...
    public array $selectedOptionsByBlock = [];  // multi_select / lookup(multiple)
    public array $matrixAnswersByBlock = [];    // matrix: [blockId => [item => value]]
    public array $addressFieldsByBlock = [];
    public array $rankingOrderByBlock = [];
    public array $dateRangeStartByBlock = [];
    public array $dateRangeEndByBlock = [];
    public array $repeaterEntriesByBlock = [];

    /** Lookup-Optionen, gecached pro Block-Id. */
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

        // Wenn das Template inzwischen wieder auf 'block_flow' steht, zurück
        // auf die klassische Route — Sicherheitsnetz, falls jemand die
        // /overview-URL bookmark.
        $flowMode = $intake?->projectTemplate?->flow_mode
            ?? HatchProjectTemplate::FLOW_MODE_BLOCK_FLOW;

        if ($flowMode !== HatchProjectTemplate::FLOW_MODE_OVERVIEW) {
            return redirect()->route('hatch.public.intake-session', [
                'sessionToken' => $sessionToken,
            ]);
        }

        $this->intakeName = $intake->name;
        $this->sessionToken = $this->session->session_token;
        $this->respondentName = $this->session->respondent_name;

        if ($intake->projectTemplate) {
            $this->blocks = $intake->projectTemplate->templateBlocks
                ->sortBy('sort_order')
                ->values()
                ->map(fn($block) => [
                    'id' => (string) $block->id,
                    'name' => $block->name ?: ($block->blockDefinition->name ?? 'Block'),
                    // block_definition_name separat behalten — wird im
                    // Compact-Table-Renderer benötigt, um spaltenweite
                    // Header-Heuristiken über mehrere Gruppen zu bilden.
                    'block_definition_name' => $block->blockDefinition->name ?? null,
                    'description' => $block->description ?: ($block->blockDefinition->description ?? ''),
                    'type' => $block->blockDefinition->block_type ?? 'default',
                    'logic_config' => $block->blockDefinition->logic_config ?? [],
                    'is_required' => (bool) $block->is_required,
                    'group_uuid' => $block->group_uuid,
                    'visibility_rules' => $block->visibility_rules,
                    'display_compact' => (bool) $block->display_compact,
                ])
                ->toArray();

            $this->totalBlocks = count($this->blocks);
        }

        $this->loadLookupOptions();

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
            $this->hydrateAllAnswers();
            $this->state = 'completed';
            return;
        }

        $this->autoSaveHiddenFields();
        $this->hydrateAllAnswers();
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

    /**
     * Liest aus session.answers den State pro Block in die indexed
     * Livewire-Properties — analog zu IntakeSession::loadCurrentAnswer(),
     * aber für alle Blöcke gleichzeitig.
     */
    private function hydrateAllAnswers(): void
    {
        $answers = $this->session->answers ?? [];

        foreach ($this->blocks as $block) {
            $blockId = $block['id'];
            $type = $block['type'];
            $raw = $answers["block_{$blockId}"] ?? '';

            switch ($type) {
                case 'multi_select':
                    if (is_string($raw) && $raw !== '') {
                        $decoded = json_decode($raw, true);
                        $this->selectedOptionsByBlock[$blockId] = is_array($decoded) ? $decoded : [];
                    } else {
                        $this->selectedOptionsByBlock[$blockId] = [];
                    }
                    break;

                case 'boolean':
                case 'consent':
                    $this->answersByBlock[$blockId] = $raw === true || $raw === 'true'
                        ? 'true'
                        : ($raw === false || $raw === 'false' ? 'false' : '');
                    break;

                case 'matrix':
                    if (is_string($raw) && $raw !== '') {
                        $decoded = json_decode($raw, true);
                        $this->matrixAnswersByBlock[$blockId] = is_array($decoded) ? $decoded : [];
                    } else {
                        $this->matrixAnswersByBlock[$blockId] = [];
                    }
                    break;

                case 'ranking':
                    if (is_string($raw) && $raw !== '') {
                        $decoded = json_decode($raw, true);
                        $this->rankingOrderByBlock[$blockId] = is_array($decoded) ? $decoded : [];
                    } else {
                        $config = $block['logic_config'] ?? [];
                        $options = $config['options'] ?? [];
                        $this->rankingOrderByBlock[$blockId] = array_map(
                            fn($o) => is_array($o) ? ($o['value'] ?? '') : $o,
                            $options
                        );
                    }
                    break;

                case 'address':
                    if (is_string($raw) && $raw !== '') {
                        $decoded = json_decode($raw, true);
                        $this->addressFieldsByBlock[$blockId] = is_array($decoded) ? $decoded : [];
                    } else {
                        $this->addressFieldsByBlock[$blockId] = [];
                    }
                    break;

                case 'date_range':
                    if (is_string($raw) && $raw !== '') {
                        $decoded = json_decode($raw, true);
                        $this->dateRangeStartByBlock[$blockId] = $decoded['start'] ?? '';
                        $this->dateRangeEndByBlock[$blockId] = $decoded['end'] ?? '';
                    } else {
                        $this->dateRangeStartByBlock[$blockId] = '';
                        $this->dateRangeEndByBlock[$blockId] = '';
                    }
                    break;

                case 'repeater':
                    $entries = [];
                    if (is_string($raw) && $raw !== '') {
                        $decoded = json_decode($raw, true);
                        $entries = is_array($decoded) ? $decoded : [];
                    }
                    if (empty($entries)) {
                        $config = $block['logic_config'] ?? [];
                        $minEntries = (int) ($config['min_entries'] ?? 0);
                        if ($minEntries > 0) {
                            $fields = $config['fields'] ?? [];
                            $emptyEntry = [];
                            foreach ($fields as $f) {
                                $emptyEntry[$f['key'] ?? ''] = '';
                            }
                            for ($i = 0; $i < $minEntries; $i++) {
                                $entries[] = $emptyEntry;
                            }
                        }
                    }
                    $this->repeaterEntriesByBlock[$blockId] = $entries;
                    break;

                case 'lookup':
                    $config = $block['logic_config'] ?? [];
                    $multiple = $config['multiple'] ?? false;
                    if ($multiple) {
                        if (is_string($raw) && $raw !== '') {
                            $decoded = json_decode($raw, true);
                            $this->selectedOptionsByBlock[$blockId] = is_array($decoded) ? $decoded : [];
                        } else {
                            $this->selectedOptionsByBlock[$blockId] = [];
                        }
                    } else {
                        $this->answersByBlock[$blockId] = is_string($raw) ? $raw : (string) $raw;
                    }
                    break;

                default:
                    $this->answersByBlock[$blockId] = is_string($raw) ? $raw : (string) $raw;
                    break;
            }
        }
    }

    private function isIntakeAccessible(): bool
    {
        $intake = $this->session?->projectIntake;
        return $intake && $intake->status === 'published';
    }

    /**
     * Wertet visibility_rules eines Blocks gegen den aktuellen Live-State
     * (nicht die DB-Antworten) aus, damit Conditional Logic auch im
     * Overview-Modus reagiert, ohne dass der User vorher submitten muss.
     */
    public function isBlockVisible(array $block): bool
    {
        $rules = $block['visibility_rules'] ?? null;
        if (!is_array($rules) || empty($rules['rules'])) {
            return true;
        }

        $combinator = strtoupper($rules['combinator'] ?? 'AND');
        $results = [];

        foreach ($rules['rules'] as $rule) {
            $sourceId = (int) ($rule['source_block_id'] ?? 0);
            if ($sourceId <= 0) continue;

            $operator = $rule['operator'] ?? 'equals';
            $expected = $rule['value'] ?? '';
            $sourceKey = (string) $sourceId;

            // Source-Block aus dem Live-State auflösen.
            $sourceBlock = collect($this->blocks)->firstWhere('id', $sourceKey);
            $raw = '';
            $decoded = null;

            if ($sourceBlock) {
                $sType = $sourceBlock['type'];
                if ($sType === 'multi_select' || ($sType === 'lookup' && ($sourceBlock['logic_config']['multiple'] ?? false))) {
                    $decoded = $this->selectedOptionsByBlock[$sourceKey] ?? [];
                    $raw = empty($decoded) ? '' : json_encode($decoded);
                } elseif ($sType === 'matrix') {
                    $decoded = $this->matrixAnswersByBlock[$sourceKey] ?? [];
                    $raw = empty($decoded) ? '' : json_encode($decoded);
                } else {
                    $raw = $this->answersByBlock[$sourceKey] ?? '';
                }
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
     * Schreibt den kompletten Live-State in session.answers — wird vor dem
     * Validieren und beim manuellen Zwischenspeichern aufgerufen.
     */
    private function persistAllAnswers(): void
    {
        $answers = $this->session->answers ?? [];

        foreach ($this->blocks as $block) {
            $blockId = $block['id'];
            $type = $block['type'];
            $key = "block_{$blockId}";

            if (in_array($type, ['info', 'section', 'calculated'])) {
                continue;
            }

            switch ($type) {
                case 'multi_select':
                    $answers[$key] = json_encode($this->selectedOptionsByBlock[$blockId] ?? []);
                    break;

                case 'matrix':
                    $answers[$key] = json_encode($this->matrixAnswersByBlock[$blockId] ?? []);
                    break;

                case 'ranking':
                    $answers[$key] = json_encode($this->rankingOrderByBlock[$blockId] ?? []);
                    break;

                case 'address':
                    $answers[$key] = json_encode($this->addressFieldsByBlock[$blockId] ?? []);
                    break;

                case 'date_range':
                    $answers[$key] = json_encode([
                        'start' => $this->dateRangeStartByBlock[$blockId] ?? '',
                        'end' => $this->dateRangeEndByBlock[$blockId] ?? '',
                    ]);
                    break;

                case 'repeater':
                    $answers[$key] = json_encode($this->repeaterEntriesByBlock[$blockId] ?? []);
                    break;

                case 'lookup':
                    $config = $block['logic_config'] ?? [];
                    $multiple = $config['multiple'] ?? false;
                    if ($multiple) {
                        $answers[$key] = json_encode($this->selectedOptionsByBlock[$blockId] ?? []);
                    } else {
                        $answers[$key] = $this->answersByBlock[$blockId] ?? '';
                    }
                    break;

                default:
                    $answers[$key] = $this->answersByBlock[$blockId] ?? '';
                    break;
            }
        }

        $this->session->update(['answers' => $answers]);
    }

    /**
     * Liefert die Indexe der Pflichtblöcke, die im Live-State nicht oder
     * nicht ausreichend beantwortet sind. Berücksichtigt Visibility-Regeln —
     * ausgeblendete Blöcke werden ignoriert.
     */
    private function getUnansweredRequiredBlocks(): array
    {
        $missing = [];

        foreach ($this->blocks as $index => $block) {
            $type = $block['type'];
            if (in_array($type, ['info', 'section', 'calculated', 'hidden'])) {
                continue;
            }

            if (!$this->isBlockVisible($block)) {
                continue;
            }

            $config = $block['logic_config'] ?? [];
            $blockId = $block['id'];

            // Sonderfall: Matrix mit per_row-Pflicht.
            if ($type === 'matrix' && ($config['required_mode'] ?? 'matrix') === 'per_row') {
                $matrixState = $this->matrixAnswersByBlock[$blockId] ?? [];
                foreach (($config['items'] ?? []) as $item) {
                    if (!is_array($item) || empty($item['is_required'])) {
                        continue;
                    }
                    $itemValue = $item['value'] ?? $item['label'] ?? '';
                    if ($itemValue === '' || empty($matrixState[$itemValue])) {
                        $missing[] = $index;
                        continue 2;
                    }
                }
                if (!$block['is_required']) {
                    continue;
                }
            }

            if (!$block['is_required']) {
                continue;
            }

            $isEmpty = match ($type) {
                'multi_select' => (function () use ($blockId, $config) {
                    $sel = $this->selectedOptionsByBlock[$blockId] ?? [];
                    if (empty($sel)) return true;
                    $min = $config['min_selections'] ?? null;
                    if ($min !== null && $min !== '' && (int) $min > 0) {
                        return count($sel) < (int) $min;
                    }
                    return false;
                })(),
                'matrix' => (function () use ($blockId, $config) {
                    $state = $this->matrixAnswersByBlock[$blockId] ?? [];
                    if (empty($state)) return true;
                    if (($config['required_mode'] ?? 'matrix') === 'matrix') {
                        foreach (($config['items'] ?? []) as $item) {
                            $itemValue = is_array($item) ? ($item['value'] ?? $item['label'] ?? '') : $item;
                            if ($itemValue === '') continue;
                            if (!isset($state[$itemValue]) || $state[$itemValue] === '') {
                                return true;
                            }
                        }
                    }
                    return false;
                })(),
                'ranking' => empty($this->rankingOrderByBlock[$blockId] ?? []),
                'address' => empty(array_filter($this->addressFieldsByBlock[$blockId] ?? [])),
                'date_range' => ($this->dateRangeStartByBlock[$blockId] ?? '') === ''
                    || ($this->dateRangeEndByBlock[$blockId] ?? '') === '',
                'repeater' => (function () use ($blockId, $config) {
                    $entries = $this->repeaterEntriesByBlock[$blockId] ?? [];
                    $min = (int) ($config['min_entries'] ?? 0);
                    if (empty($entries)) return true;
                    return $min > 0 && count($entries) < $min;
                })(),
                'lookup' => (function () use ($blockId, $config) {
                    $multiple = $config['multiple'] ?? false;
                    if ($multiple) {
                        return empty($this->selectedOptionsByBlock[$blockId] ?? []);
                    }
                    $val = $this->answersByBlock[$blockId] ?? '';
                    return $val === '' || $val === null;
                })(),
                'consent' => (function () use ($blockId, $config) {
                    $mustAccept = $config['must_accept'] ?? true;
                    return $mustAccept && ($this->answersByBlock[$blockId] ?? '') !== 'true';
                })(),
                default => (($this->answersByBlock[$blockId] ?? '') === ''),
            };

            if ($isEmpty) {
                $missing[] = $index;
            }
        }

        return $missing;
    }

    /**
     * Erzwingt max_selections für alle multi_select-Blöcke vor dem Submit.
     * Bei Verletzung: validationError setzen und false zurückgeben.
     */
    private function enforceAllMaxSelections(): bool
    {
        foreach ($this->blocks as $block) {
            if ($block['type'] !== 'multi_select') continue;

            $max = $block['logic_config']['max_selections'] ?? null;
            $max = ($max === null || $max === '') ? null : (int) $max;
            if ($max === null || $max <= 0) continue;

            $blockId = $block['id'];
            $sel = $this->selectedOptionsByBlock[$blockId] ?? [];
            if (count($sel) > $max) {
                $this->validationError = 'Im Feld „' . $block['name'] . '" sind '
                    . count($sel) . ' Optionen ausgewählt, erlaubt sind maximal ' . $max
                    . '. Bitte wähle einige Optionen ab.';
                return false;
            }
        }
        return true;
    }

    public function submit(): void
    {
        if ($this->state === 'completed') return;

        if (!$this->isIntakeAccessible()) {
            $intake = $this->session?->projectIntake;
            $this->state = ($intake && $intake->status === 'draft') ? 'notStarted' : 'notActive';
            return;
        }

        if (!$this->enforceAllMaxSelections()) {
            return;
        }

        $missing = $this->getUnansweredRequiredBlocks();
        if (!empty($missing)) {
            $this->missingRequiredBlocks = $missing;
            $this->validationError = 'Bitte beantworten Sie alle Pflichtfragen, bevor Sie abschliessen.';
            return;
        }

        $this->missingRequiredBlocks = [];
        $this->validationError = null;

        $this->persistAllAnswers();
        $this->session->refresh();

        // Ausgeblendete Blocks entfernen (analog Block-Flow).
        $answers = $this->session->answers ?? [];
        foreach ($this->blocks as $block) {
            if (!$this->isBlockVisible($block)) {
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

    public function saveDraft(): void
    {
        if ($this->state === 'completed') return;
        if (!$this->isIntakeAccessible()) return;

        $this->persistAllAnswers();
        $this->validationError = null;
    }

    /* ──────────────────────────────────────────────────────────────────
     * Indexed Action-Helper (alle akzeptieren block_id als ersten Param,
     * damit Buttons im Overview-Mode den jeweiligen Block adressieren
     * können).
     * ────────────────────────────────────────────────────────────────── */

    public function setAnswerFor(string $blockId, string $value): void
    {
        if ($this->state === 'completed') return;
        $this->answersByBlock[$blockId] = $value;
    }

    public function toggleOptionFor(string $blockId, string $value): void
    {
        if ($this->state === 'completed') return;

        $current = $this->selectedOptionsByBlock[$blockId] ?? [];

        if (in_array($value, $current, true)) {
            $this->selectedOptionsByBlock[$blockId] = array_values(array_filter(
                $current,
                fn($opt) => $opt !== $value
            ));
            $this->validationError = null;
            return;
        }

        // Max-Selections hart durchsetzen.
        $block = collect($this->blocks)->firstWhere('id', $blockId);
        $max = null;
        if ($block && $block['type'] === 'multi_select') {
            $m = $block['logic_config']['max_selections'] ?? null;
            $max = ($m === null || $m === '') ? null : (int) $m;
        }

        if ($max !== null && $max > 0 && count($current) >= $max) {
            $this->validationError = 'Du kannst im Feld „' . ($block['name'] ?? '')
                . '" maximal ' . $max . ' Option(en) auswählen.';
            return;
        }

        $current[] = $value;
        $this->selectedOptionsByBlock[$blockId] = $current;
        $this->validationError = null;
    }

    public function setMatrixAnswerFor(string $blockId, string $item, string $value): void
    {
        if ($this->state === 'completed') return;
        $current = $this->matrixAnswersByBlock[$blockId] ?? [];
        $current[$item] = $value;
        $this->matrixAnswersByBlock[$blockId] = $current;
    }

    public function moveRankingItemFor(string $blockId, int $from, int $to): void
    {
        if ($this->state === 'completed') return;
        $order = $this->rankingOrderByBlock[$blockId] ?? [];
        if ($from < 0 || $from >= count($order)) return;
        if ($to < 0 || $to >= count($order)) return;

        $item = array_splice($order, $from, 1);
        array_splice($order, $to, 0, $item);
        $this->rankingOrderByBlock[$blockId] = $order;
    }

    public function updateAddressFieldFor(string $blockId, string $field, string $value): void
    {
        if ($this->state === 'completed') return;
        $current = $this->addressFieldsByBlock[$blockId] ?? [];
        $current[$field] = $value;
        $this->addressFieldsByBlock[$blockId] = $current;
    }

    public function setDateRangeStartFor(string $blockId, string $value): void
    {
        if ($this->state === 'completed') return;
        $this->dateRangeStartByBlock[$blockId] = $value;
    }

    public function setDateRangeEndFor(string $blockId, string $value): void
    {
        if ($this->state === 'completed') return;
        $this->dateRangeEndByBlock[$blockId] = $value;
    }

    public function addRepeaterEntryFor(string $blockId): void
    {
        if ($this->state === 'completed') return;
        $block = collect($this->blocks)->firstWhere('id', $blockId);
        if (!$block) return;

        $config = $block['logic_config'] ?? [];
        $maxEntries = (int) ($config['max_entries'] ?? 10);
        $entries = $this->repeaterEntriesByBlock[$blockId] ?? [];
        if (count($entries) >= $maxEntries) return;

        $fields = $config['fields'] ?? [];
        $entry = [];
        foreach ($fields as $f) {
            $entry[$f['key'] ?? ''] = '';
        }
        $entries[] = $entry;
        $this->repeaterEntriesByBlock[$blockId] = $entries;
    }

    public function removeRepeaterEntryFor(string $blockId, int $index): void
    {
        if ($this->state === 'completed') return;
        $block = collect($this->blocks)->firstWhere('id', $blockId);
        if (!$block) return;

        $config = $block['logic_config'] ?? [];
        $minEntries = (int) ($config['min_entries'] ?? 0);
        $entries = $this->repeaterEntriesByBlock[$blockId] ?? [];
        if (count($entries) <= $minEntries) return;

        unset($entries[$index]);
        $this->repeaterEntriesByBlock[$blockId] = array_values($entries);
    }

    public function updateRepeaterFieldFor(string $blockId, int $entryIndex, string $fieldKey, string $value): void
    {
        if ($this->state === 'completed') return;
        $entries = $this->repeaterEntriesByBlock[$blockId] ?? [];
        if (isset($entries[$entryIndex])) {
            $entries[$entryIndex][$fieldKey] = $value;
            $this->repeaterEntriesByBlock[$blockId] = $entries;
        }
    }

    /**
     * Liefert die Blöcke gruppiert nach group_uuid und detektiert
     * aufeinanderfolgende display_compact-Gruppen mit identischer
     * Block-Definition-Sequenz als "compact_table". Genutzt vom Overview-
     * Renderer, um Mo–Fr-artige Strukturen als Tabellenzeilen zu rendern.
     *
     * Rückgabe: array of segments
     *   - ['kind' => 'compact_table', 'groups' => [groupArray, …], 'columns' => [block, …]]
     *   - ['kind' => 'standard',      'groups' => [groupArray]]
     *
     * Ein groupArray sieht aus wie:
     *   ['group_uuid' => string|null, 'fields' => [block, …], 'compact' => bool]
     */
    public function getRenderSegments(): array
    {
        // 1. Blöcke nach group_uuid gruppieren (Reihenfolge erhalten).
        $groups = [];
        $orderedKeys = [];
        foreach ($this->blocks as $block) {
            $key = $block['group_uuid'] ?: ('single:' . $block['id']);
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'group_uuid' => $block['group_uuid'],
                    'fields' => [],
                    'compact' => false,
                ];
                $orderedKeys[] = $key;
            }
            $groups[$key]['fields'][] = $block;
            if (!empty($block['display_compact'])) {
                $groups[$key]['compact'] = true;
            }
        }

        // 2. Aufeinanderfolgende compact-Gruppen mit identischer Struktur
        //    zu einer Tabelle zusammenfassen.
        $segments = [];
        $i = 0;
        $orderedGroups = array_map(fn($k) => $groups[$k], $orderedKeys);
        $count = count($orderedGroups);

        while ($i < $count) {
            $group = $orderedGroups[$i];
            if (!$group['compact']) {
                $segments[] = ['kind' => 'standard', 'groups' => [$group]];
                $i++;
                continue;
            }

            $signature = $this->groupStructureSignature($group['fields']);
            $bucket = [$group];
            $j = $i + 1;
            while ($j < $count
                && $orderedGroups[$j]['compact']
                && $this->groupStructureSignature($orderedGroups[$j]['fields']) === $signature
            ) {
                $bucket[] = $orderedGroups[$j];
                $j++;
            }

            if (count($bucket) > 1) {
                // Mehrere strukturgleiche compact-Gruppen → eine Tabelle.
                // Spalten-Header per gemeinsamem Wort-Tail aller block_definition.name
                // über alle Gruppen ableiten (z. B. „… Montag Bewertung" /
                // „… Dienstag Bewertung" → „Bewertung"). Damit werden die
                // Header automatisch entkoppelt vom konkreten Zeilen-Label.
                $columnCount = count($bucket[0]['fields']);
                $columnHeaders = [];
                for ($c = 0; $c < $columnCount; $c++) {
                    $names = [];
                    foreach ($bucket as $g) {
                        $field = $g['fields'][$c] ?? null;
                        if ($field) {
                            $names[] = $field['block_definition_name'] ?? $field['name'] ?? '';
                        }
                    }
                    $columnHeaders[] = $this->commonWordTail($names);
                }

                $segments[] = [
                    'kind' => 'compact_table',
                    'groups' => $bucket,
                    'columns' => $bucket[0]['fields'],
                    'column_headers' => $columnHeaders,
                ];
            } else {
                // Einzelne compact-Gruppe — als Standard rendern (nichts zum Tabellieren).
                $segments[] = ['kind' => 'standard', 'groups' => [$group]];
            }
            $i = $j;
        }

        return $segments;
    }

    /**
     * Signatur einer Gruppenstruktur: Sequenz aus block_definition_id +
     * block_type. Zwei Gruppen sind tabellarisch zusammenführbar, wenn ihre
     * Signaturen identisch sind (z. B. Mo, Di, Mi mit jeweils rating + long_text).
     */
    private function groupStructureSignature(array $fields): string
    {
        return collect($fields)
            ->map(fn($f) => $f['type'] . ':' . ($f['logic_config']['lookup_id'] ?? ''))
            ->implode('|');
    }

    /**
     * Liefert den längsten gemeinsamen Wort-Tail (vom Ende her) aller
     * übergebenen Strings. Beispiel:
     *   ["Wochenfeedback - Montag Bewertung",
     *    "Wochenfeedback - Dienstag Bewertung", …]
     *   → "Bewertung"
     * Leerer Rückgabewert, wenn kein gemeinsames Endwort gefunden wird oder
     * die Eingabe leer ist.
     */
    private function commonWordTail(array $strings): string
    {
        $strings = array_values(array_filter($strings, fn($s) => is_string($s) && trim($s) !== ''));
        if (empty($strings)) return '';

        $tokenized = array_map(fn($s) => preg_split('/\s+/', trim($s)) ?: [], $strings);
        if (count($tokenized) === 1) return implode(' ', $tokenized[0]);

        $shortest = min(array_map('count', $tokenized));
        $tail = [];
        for ($i = 1; $i <= $shortest; $i++) {
            $first = $tokenized[0][count($tokenized[0]) - $i] ?? null;
            $allSame = $first !== null;
            foreach ($tokenized as $tokens) {
                if (($tokens[count($tokens) - $i] ?? null) !== $first) {
                    $allSame = false;
                    break;
                }
            }
            if (!$allSame) break;
            array_unshift($tail, $first);
        }

        return implode(' ', $tail);
    }

    public function render()
    {
        return view('hatch::livewire.public.intake-session-overview')
            ->layout('platform::layouts.guest');
    }
}
