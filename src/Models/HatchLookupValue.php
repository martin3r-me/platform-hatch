<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class HatchLookupValue extends Model
{
    protected $table = 'hatch_lookup_values';

    protected $fillable = [
        'lookup_id',
        'value',
        'label',
        'order',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function lookup(): BelongsTo
    {
        return $this->belongsTo(HatchLookup::class, 'lookup_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('label');
    }
}
