<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class HatchProjectIntakeStep extends Model
{
    use LogsActivity;
    
    protected $table = 'hatch_project_intake_steps';
    
    protected $fillable = [
        'uuid',
        'project_intake_id',
        'template_block_id',
        'block_definition_id',
        'answers',
        'ai_interpretation',
        'user_clarification_needed',
        'ai_suggestions',
        'validation_errors',
        'ai_confidence',
        'conversation_context',
        'is_completed',
        'completed_at',
        'created_by_user_id',
        'team_id'
    ];
    
    protected $casts = [
        'answers' => 'array',
        'ai_interpretation' => 'array',
        'user_clarification_needed' => 'boolean',
        'ai_suggestions' => 'array',
        'validation_errors' => 'array',
        'ai_confidence' => 'decimal:2',
        'conversation_context' => 'array',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
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
    public function projectIntake(): BelongsTo
    {
        return $this->belongsTo(HatchProjectIntake::class, 'project_intake_id');
    }
    
    public function templateBlock(): BelongsTo
    {
        return $this->belongsTo(HatchTemplateBlock::class, 'template_block_id');
    }
    
    public function blockDefinition(): BelongsTo
    {
        return $this->belongsTo(HatchBlockDefinition::class, 'block_definition_id');
    }
    
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }
    
    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class, 'team_id');
    }
    
    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }
    
    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }
    
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }
    
    public function scopeNeedsClarification($query)
    {
        return $query->where('user_clarification_needed', true);
    }
    
    public function scopeByConfidence($query, $minScore)
    {
        return $query->where('ai_confidence', '>=', $minScore);
    }
}
