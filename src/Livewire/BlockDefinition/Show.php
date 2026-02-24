<?php

namespace Platform\Hatch\Livewire\BlockDefinition;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Platform\Hatch\Models\HatchBlockDefinition;
use Platform\Hatch\Models\HatchLookup;

class Show extends Component
{
    public HatchBlockDefinition $blockDefinition;
    public $blockTypeOptions;

    // Hilfsarrays für die Eingabe - nicht direkt an das Model gebunden
    public $responseFormatInput = [];
    public $conditionalLogicInput = [];
    public $validationRulesInput = [];

    // Typ-spezifische Konfiguration (in logic_config gespeichert)
    public $typeConfig = [];

    // Verfügbare Lookups für das aktuelle Team
    public $availableLookups = [];

    protected $rules = [
        'blockDefinition.name' => 'required|string|max:255',
        'blockDefinition.description' => 'nullable|string',
        'blockDefinition.block_type' => 'required|string',
        'blockDefinition.ai_prompt' => 'nullable|string',
        'blockDefinition.conditional_logic' => 'nullable|array',
        'blockDefinition.response_format' => 'nullable|array',
        'blockDefinition.fallback_questions' => 'nullable|array',
        'blockDefinition.validation_rules' => 'nullable|array',
        'blockDefinition.logic_config' => 'nullable|array',
        'blockDefinition.ai_behavior' => 'nullable|array',
    ];

    public function mount(HatchBlockDefinition $blockDefinition)
    {
        $this->blockDefinition = $blockDefinition;

        // Initialize JSON fields as empty arrays if they're null
        if (is_null($this->blockDefinition->conditional_logic)) {
            $this->blockDefinition->conditional_logic = [];
        }
        if (is_null($this->blockDefinition->response_format)) {
            $this->blockDefinition->response_format = [];
        }
        if (is_null($this->blockDefinition->validation_rules)) {
            $this->blockDefinition->validation_rules = [];
        }

        // Load data into helper arrays for input
        $this->responseFormatInput = $this->blockDefinition->response_format;
        $this->conditionalLogicInput = $this->blockDefinition->conditional_logic;
        $this->validationRulesInput = $this->blockDefinition->validation_rules;
        $this->typeConfig = $this->blockDefinition->logic_config ?? $this->getDefaultTypeConfig($this->blockDefinition->block_type);

        $this->blockTypeOptions = collect(HatchBlockDefinition::getBlockTypes())->map(function($label, $value) {
            return ['value' => $value, 'label' => $label];
        });

        // Load available lookups for the current team
        $this->loadAvailableLookups();
    }

    private function loadAvailableLookups(): void
    {
        $teamId = $this->blockDefinition->team_id ?? auth()->user()->current_team_id;
        $this->availableLookups = HatchLookup::forTeam($teamId)
            ->orderBy('label')
            ->get(['id', 'name', 'label'])
            ->map(fn($l) => ['value' => $l->id, 'label' => $l->label])
            ->toArray();
    }

    #[Computed]
    public function getIsDirtyProperty()
    {
        return $this->blockDefinition->isDirty();
    }

    public function saveBlockDefinition()
    {
        // Model is already up-to-date via updated events
        $this->validate();
        $this->blockDefinition->save();

        $this->dispatch('notifications:store', [
            'title' => 'BlockDefinition gespeichert',
            'message' => 'BlockDefinition wurde erfolgreich gespeichert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->blockDefinition),
            'noticable_id' => $this->blockDefinition->getKey(),
        ]);
    }



    public function toggleActive()
    {
        $this->blockDefinition->is_active = !$this->blockDefinition->is_active;
        $this->blockDefinition->save();

        $this->dispatch('notifications:store', [
            'title' => 'Status geändert',
            'message' => 'BlockDefinition wurde ' . ($this->blockDefinition->is_active ? 'aktiviert' : 'deaktiviert') . '.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->blockDefinition),
            'noticable_id' => $this->blockDefinition->getKey(),
        ]);
    }

    // Methoden für erweiterte Konfiguration
    public function addFallbackQuestion()
    {
        $fallbackQuestions = $this->blockDefinition->fallback_questions ?? [];
        $fallbackQuestions[] = [
            'question' => '',
            'condition' => 'always',
            'order' => count($fallbackQuestions) + 1
        ];
        $this->blockDefinition->fallback_questions = $fallbackQuestions;
    }

    public function removeFallbackQuestion($index)
    {
        $fallbackQuestions = $this->blockDefinition->fallback_questions ?? [];
        unset($fallbackQuestions[$index]);
        $this->blockDefinition->fallback_questions = array_values($fallbackQuestions);
    }



    // Response Format Methoden
    public function addResponseFormat()
    {
        $this->responseFormatInput[] = [
            'type' => 'string',
            'description' => '',
            'constraints' => '',
            'validations' => []
        ];
    }

    public function removeResponseFormat($index)
    {
        unset($this->responseFormatInput[$index]);
        $this->responseFormatInput = array_values($this->responseFormatInput);
    }

    public function resetResponseFormat()
    {
        $this->responseFormatInput = [];
        $this->updateModelFromInputs();
    }

    // Inline Validation methods for Response Format fields
    public function addValidationForField($fieldIndex)
    {
        if (!isset($this->responseFormatInput[$fieldIndex]['validations'])) {
            $this->responseFormatInput[$fieldIndex]['validations'] = [];
        }

        $this->responseFormatInput[$fieldIndex]['validations'][] = [
            'type' => 'required',
            'message' => '',
            'params' => ''
        ];

        $this->updateModelFromInputs();
    }

    public function removeValidationFromField($fieldIndex, $validationIndex)
    {
        if (isset($this->responseFormatInput[$fieldIndex]['validations'][$validationIndex])) {
            unset($this->responseFormatInput[$fieldIndex]['validations'][$validationIndex]);
            $this->responseFormatInput[$fieldIndex]['validations'] = array_values($this->responseFormatInput[$fieldIndex]['validations']);
            $this->updateModelFromInputs();
        }
    }

    // Updated events for automatic JSON conversion
    public function updatedResponseFormatInput()
    {
        $this->updateModelFromInputs();
    }

    public function updatedConditionalLogicInput()
    {
        $this->updateModelFromInputs();
    }

    public function updatedValidationRulesInput()
    {
        $this->updateModelFromInputs();
    }

    // Helper method to update model from input arrays
    private function updateModelFromInputs()
    {
        $this->blockDefinition->response_format = $this->responseFormatInput;
        $this->blockDefinition->conditional_logic = $this->conditionalLogicInput;
        $this->blockDefinition->validation_rules = $this->validationRulesInput;
    }

    public function updatedTypeConfig()
    {
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    public function updatedBlockDefinitionBlockType($value)
    {
        if (empty($this->typeConfig) || $this->blockDefinition->logic_config === null) {
            $this->typeConfig = $this->getDefaultTypeConfig($value);
            $this->blockDefinition->logic_config = $this->typeConfig;
        }
    }

    public function getDefaultTypeConfig(string $type): array
    {
        return match($type) {
            'text' => [
                'placeholder' => '',
                'min_length' => null,
                'max_length' => 255,
            ],
            'long_text' => [
                'placeholder' => '',
                'min_length' => null,
                'max_length' => 5000,
                'rows' => 6,
            ],
            'email' => [
                'placeholder' => 'name@beispiel.de',
            ],
            'phone' => [
                'placeholder' => '+49 123 456789',
                'format' => 'international',
            ],
            'url' => [
                'placeholder' => 'https://beispiel.de',
            ],
            'select', 'multi_select' => [
                'options' => [],
            ],
            'number' => [
                'placeholder' => '',
                'min' => null,
                'max' => null,
                'step' => 1,
                'unit' => '',
            ],
            'scale' => [
                'min' => 1,
                'max' => 10,
                'step' => 1,
                'labels' => ['min_label' => '', 'max_label' => ''],
            ],
            'rating' => [
                'min' => 1,
                'max' => 5,
                'step' => 1,
            ],
            'date' => [
                'format' => 'Y-m-d',
                'min_date' => '',
                'max_date' => '',
            ],
            'boolean' => [
                'true_label' => 'Ja',
                'false_label' => 'Nein',
                'style' => 'toggle',
            ],
            'file' => [
                'allowed_types' => 'pdf,jpg,png,doc,docx',
                'max_size_mb' => 10,
            ],
            'location' => [
                'placeholder' => 'Adresse eingeben...',
                'format' => 'address',
            ],
            'info' => [
                'content' => '',
            ],
            'matrix' => [
                'items' => [],
                'scale_min' => 1,
                'scale_max' => 5,
                'scale_labels' => ['min_label' => '', 'max_label' => ''],
            ],
            'ranking' => [
                'options' => [],
            ],
            'nps' => [],
            'dropdown' => [
                'options' => [],
                'placeholder' => 'Bitte wählen...',
                'searchable' => false,
            ],
            'datetime' => [
                'min_datetime' => '',
                'max_datetime' => '',
            ],
            'time' => [
                'min_time' => '',
                'max_time' => '',
                'step_minutes' => 15,
            ],
            'slider' => [
                'min' => 0,
                'max' => 100,
                'step' => 1,
                'unit' => '',
                'show_value' => true,
            ],
            'image_choice' => [
                'options' => [],
                'columns' => 3,
            ],
            'consent' => [
                'text' => '',
                'link_url' => '',
                'link_label' => 'Datenschutzerklärung',
                'must_accept' => true,
            ],
            'section' => [
                'title' => '',
                'subtitle' => '',
                'content' => '',
            ],
            'hidden' => [
                'default_value' => '',
                'source' => 'static',
            ],
            'address' => [
                'fields' => ['street', 'house_number', 'zip', 'city', 'country'],
                'country_lookup_id' => null,
            ],
            'color' => [
                'format' => 'hex',
                'presets' => [],
            ],
            'lookup' => [
                'lookup_id' => null,
                'multiple' => false,
                'searchable' => true,
                'placeholder' => 'Bitte wählen...',
            ],
            'signature' => [
                'width' => 400,
                'height' => 200,
                'pen_color' => '#000000',
            ],
            'date_range' => [
                'min_date' => '',
                'max_date' => '',
                'format' => 'Y-m-d',
            ],
            'calculated' => [
                'formula' => '',
                'source_blocks' => [],
                'display_format' => '',
                'operation' => 'custom',
            ],
            'repeater' => [
                'fields' => [],
                'min_entries' => 0,
                'max_entries' => 10,
                'add_label' => 'Eintrag hinzufügen',
            ],
            default => [],
        };
    }

    // Select/Multi-Select/Dropdown Options
    public function addSelectOption()
    {
        $options = $this->typeConfig['options'] ?? [];
        $options[] = ['label' => '', 'value' => ''];
        $this->typeConfig['options'] = $options;
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    public function removeSelectOption($index)
    {
        $options = $this->typeConfig['options'] ?? [];
        unset($options[$index]);
        $this->typeConfig['options'] = array_values($options);
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    // Matrix Items
    public function addMatrixItem()
    {
        $items = $this->typeConfig['items'] ?? [];
        $items[] = ['label' => '', 'value' => ''];
        $this->typeConfig['items'] = $items;
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    public function removeMatrixItem($index)
    {
        $items = $this->typeConfig['items'] ?? [];
        unset($items[$index]);
        $this->typeConfig['items'] = array_values($items);
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    // Image Choice Options
    public function addImageOption()
    {
        $options = $this->typeConfig['options'] ?? [];
        $options[] = ['label' => '', 'value' => '', 'file_id' => null];
        $this->typeConfig['options'] = $options;
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    public function removeImageOption($index)
    {
        $options = $this->typeConfig['options'] ?? [];
        unset($options[$index]);
        $this->typeConfig['options'] = array_values($options);
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    // Color Presets
    public function addColorPreset()
    {
        $presets = $this->typeConfig['presets'] ?? [];
        $presets[] = '#000000';
        $this->typeConfig['presets'] = $presets;
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    public function removeColorPreset($index)
    {
        $presets = $this->typeConfig['presets'] ?? [];
        unset($presets[$index]);
        $this->typeConfig['presets'] = array_values($presets);
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    // Ranking Options
    public function addRankingOption()
    {
        $options = $this->typeConfig['options'] ?? [];
        $options[] = ['label' => '', 'value' => ''];
        $this->typeConfig['options'] = $options;
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    public function removeRankingOption($index)
    {
        $options = $this->typeConfig['options'] ?? [];
        unset($options[$index]);
        $this->typeConfig['options'] = array_values($options);
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    // Repeater Fields
    public function addRepeaterField()
    {
        $fields = $this->typeConfig['fields'] ?? [];
        $fields[] = ['key' => 'field_' . (count($fields) + 1), 'label' => '', 'type' => 'text'];
        $this->typeConfig['fields'] = $fields;
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    public function removeRepeaterField($index)
    {
        $fields = $this->typeConfig['fields'] ?? [];
        unset($fields[$index]);
        $this->typeConfig['fields'] = array_values($fields);
        $this->blockDefinition->logic_config = $this->typeConfig;
    }

    public function render()
    {
        return view('hatch::livewire.block-definition.show', [
            'blockDefinition' => $this->blockDefinition->load('templateBlocks.projectTemplate'),
            'blockTypeOptions' => $this->blockTypeOptions,
        ])->layout('platform::layouts.app');
    }
}
