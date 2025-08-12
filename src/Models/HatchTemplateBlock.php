<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class HatchTemplateBlock extends Model
{
    use LogsActivity;
    
    protected $table = 'hatch_template_blocks';
    
    protected $fillable = [
        'uuid',
        'project_template_id',
        'block_definition_id',
        'sort_order',
        'is_required',
        'is_active',
        'created_by_user_id',
        'team_id'
    ];
    
    protected $casts = [
        'sort_order' => 'integer',
        'is_required' => 'boolean',
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
    
    public function blockDefinition(): BelongsTo
    {
        return $this->belongsTo(HatchBlockDefinition::class, 'block_definition_id');
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
