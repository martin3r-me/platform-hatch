<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

/**
 * @property string $name
 * @property string|null $description
 * @property string|null $group_uuid
 * @property string|null $block_type
 * @property int|null $sort_order
 * @property bool $is_required
 * @property array<string, mixed>|null $visibility_rules
 * @property bool $display_compact
 * @property bool $is_active
 * @property array<string, mixed>|null $logic_config
 * @property array<string, mixed>|null $validation_rules
 * @property array<string, mixed>|null $conditional_logic
 * @property array<string, mixed>|null $response_format
 * @property array<string, mixed>|null $fallback_questions
 * @property string|null $ai_prompt
 * @property array<string, mixed>|null $ai_behavior
 * @property array<string, mixed>|null $exit_conditions
 * @property string|null $min_confidence_threshold
 * @property int|null $max_clarification_attempts
 * @property int|null $max_messages_per_block
 */
class HatchTemplateBlock extends Model
{
    use HasFactory;
    use LogsActivity;
    
    protected $table = 'hatch_template_blocks';
    
    protected $fillable = [
        'uuid',
        'project_template_id',
        'group_uuid',
        'name',
        'description',
        'block_type',
        'logic_config',
        'validation_rules',
        'conditional_logic',
        'response_format',
        'fallback_questions',
        'ai_prompt',
        'ai_behavior',
        'exit_conditions',
        'min_confidence_threshold',
        'max_clarification_attempts',
        'max_messages_per_block',
        'sort_order',
        'is_required',
        'visibility_rules',
        'display_compact',
        'is_active',
        'created_by_user_id',
        'team_id'
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'visibility_rules' => 'array',
        'display_compact' => 'boolean',
        'logic_config' => 'array',
        'validation_rules' => 'array',
        'conditional_logic' => 'array',
        'response_format' => 'array',
        'fallback_questions' => 'array',
        'ai_behavior' => 'array',
        'exit_conditions' => 'array',
        'min_confidence_threshold' => 'decimal:2',
        'max_clarification_attempts' => 'integer',
        'max_messages_per_block' => 'integer',
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
    public function projectTemplate(): BelongsTo
    {
        return $this->belongsTo(HatchProjectTemplate::class, 'project_template_id');
    }
    
    public function intakeSteps(): HasMany
    {
        return $this->hasMany(HatchProjectIntakeStep::class, 'template_block_id');
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
    
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
