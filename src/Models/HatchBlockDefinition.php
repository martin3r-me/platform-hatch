<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class HatchBlockDefinition extends Model
{
    use LogsActivity;
    
    protected $table = 'hatch_block_definitions';
    
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'block_type',
        'ai_prompt',
        'conditional_logic',
        'response_format',
        'fallback_questions',
        'validation_rules',
        'logic_config',
        'ai_behavior',
        'is_active',
        'created_by_user_id',
        'team_id'
    ];
    
    protected $casts = [
        'ai_prompt' => 'string',
        'conditional_logic' => 'array',
        'response_format' => 'array',
        'fallback_questions' => 'array',
        'validation_rules' => 'array',
        'logic_config' => 'array',
        'ai_behavior' => 'array',
        'is_active' => 'boolean',
    ];
    
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());
                
                $model->uuid = $uuid;
            }
        });
    }
    
    /**
     * Beziehungen
     */
    public function createdByUser()
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }
    
    public function templateBlocks(): HasMany
    {
        return $this->hasMany(HatchTemplateBlock::class, 'block_definition_id');
    }
    
    public function intakeSteps(): HasMany
    {
        return $this->hasMany(HatchProjectIntakeStep::class, 'block_definition_id');
    }
    
    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('block_type', $type);
    }
    
    /**
     * Hilfsmethoden für Block-Typen
     */
    public static function getBlockTypes(): array
    {
        return [
            'text' => 'Text-Eingabe',
            'long_text' => 'Langer Text / Freitext',
            'email' => 'E-Mail Adresse',
            'phone' => 'Telefonnummer',
            'url' => 'URL / Webadresse',
            'select' => 'Auswahl (Single)',
            'multi_select' => 'Auswahl (Multiple)',
            'number' => 'Zahl',
            'scale' => 'Skala (1-10, 1-5 etc.)',
            'date' => 'Datum',
            'boolean' => 'Ja/Nein',
            'file' => 'Datei-Upload',
            'rating' => 'Bewertung',
            'location' => 'Standort',
            'custom' => 'Benutzerdefiniert'
        ];
    }
    
    public function getBlockTypeLabel(): string
    {
        return self::getBlockTypes()[$this->block_type] ?? $this->block_type;
    }
}
