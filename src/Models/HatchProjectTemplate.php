<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\AiAssistant\Traits\HasAiAssistant;

class HatchProjectTemplate extends Model
{
    use LogsActivity;
    use HasAiAssistant;
    
    protected $table = 'hatch_project_templates';
    
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'ai_personality',
        'industry_context',
        'complexity_level',
        'ai_instructions',
        'is_active',
        'created_by_user_id',
        'team_id'
    ];
    
    protected $casts = [
        'ai_instructions' => 'array',
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
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }
    
    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }
    
    public function templateBlocks(): HasMany
    {
        return $this->hasMany(HatchTemplateBlock::class, 'project_template_id');
    }
    
    public function projectIntakes(): HasMany
    {
        return $this->hasMany(HatchProjectIntake::class, 'project_template_id');
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
    
    public function scopeByComplexity($query, $level)
    {
        return $query->where('complexity_level', $level);
    }
    
    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry_context', $industry);
    }
}
