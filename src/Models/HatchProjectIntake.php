<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\AiAssistant\Traits\HasAiAssistant;

class HatchProjectIntake extends Model
{
    use LogsActivity;
    use HasAiAssistant;
    
    protected $table = 'hatch_project_intakes';
    
    protected $fillable = [
        'uuid',
        'project_template_id',
        'name',
        'description',
        'status',
        'ai_conversation_id',
        'thread_id',
        'workflow_instance_id',
        'next_step_trigger',
        'workflow_status',
        'current_step',
        'conversation_history',
        'ai_confidence_score',
        'user_preferences',
        'started_at',
        'completed_at',
        'created_by_user_id',
        'owned_by_user_id',
        'team_id',
        'is_active'
    ];
    
    protected $casts = [
        'conversation_history' => 'array',
        'ai_confidence_score' => 'decimal:2',
        'user_preferences' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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
    public function projectTemplate(): BelongsTo
    {
        return $this->belongsTo(HatchProjectTemplate::class, 'project_template_id');
    }
    
    public function intakeSteps(): HasMany
    {
        return $this->hasMany(HatchProjectIntakeStep::class, 'project_intake_id');
    }
    
    public function aiConversation(): BelongsTo
    {
        return $this->belongsTo(HatchAIConversation::class, 'ai_conversation_id');
    }
    
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }
    
    public function ownedByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'owned_by_user_id');
    }
    
    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
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
    
    public function scopeForUser($query, $userId)
    {
        return $query->where('owned_by_user_id', $userId);
    }
    
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    public function scopeByConfidence($query, $minScore)
    {
        return $query->where('ai_confidence_score', '>=', $minScore);
    }
}
