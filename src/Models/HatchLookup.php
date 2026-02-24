<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class HatchLookup extends Model
{
    protected $table = 'hatch_lookups';

    protected $fillable = [
        'team_id',
        'created_by_user_id',
        'name',
        'label',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(HatchLookupValue::class, 'lookup_id')->orderBy('order')->orderBy('label');
    }

    public function activeValues(): HasMany
    {
        return $this->hasMany(HatchLookupValue::class, 'lookup_id')
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('label');
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    public function getOptionsArray(): array
    {
        return $this->activeValues()
            ->pluck('label', 'value')
            ->toArray();
    }

    public function getOptionsWithMeta(): array
    {
        return $this->activeValues()
            ->get()
            ->map(fn($v) => [
                'value' => $v->value,
                'label' => $v->label,
                'meta' => $v->meta,
            ])
            ->toArray();
    }
}
