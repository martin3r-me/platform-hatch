<?php

namespace Platform\Hatch\Livewire\BlockDefinition;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Platform\Hatch\Models\HatchBlockDefinition;

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
            default => [],
        };
    }

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



    public function render()
    {
        return view('hatch::livewire.block-definition.show', [
            'blockDefinition' => $this->blockDefinition->load('templateBlocks.projectTemplate'),
            'blockTypeOptions' => $this->blockTypeOptions,
        ])->layout('platform::layouts.app');
    }
}
