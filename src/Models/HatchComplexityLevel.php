<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class HatchComplexityLevel extends Model
{
    protected $table = 'hatch_complexity_levels';
    
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'sort_order',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    // Relationships
    public function projectTemplates(): HasMany
    {
        return $this->hasMany(HatchProjectTemplate::class, 'complexity_level', 'name');
    }
    
    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
    
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('display_name');
    }
}
