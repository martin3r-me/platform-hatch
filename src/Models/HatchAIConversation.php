<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class HatchAIConversation extends Model
{
    use LogsActivity;
    
    protected $table = 'hatch_ai_conversations';
    
    protected $fillable = [
        'uuid',
        'session_data',
        'user_preferences',
        'conversation_flow',
        'ai_model_version',
        'temperature',
        'max_tokens',
        'conversation_state',
        'is_active',
        'created_by_user_id',
        'team_id'
    ];
    
    protected $casts = [
        'session_data' => 'array',
        'user_preferences' => 'array',
        'conversation_flow' => 'array',
        'temperature' => 'decimal:2',
        'max_tokens' => 'integer',
        'conversation_state' => 'array',
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
    public function projectIntakes(): HasMany
    {
        return $this->hasMany(HatchProjectIntake::class, 'ai_conversation_id');
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
    
    public function scopeByModelVersion($query, $version)
    {
        return $query->where('ai_model_version', $version);
    }
}
