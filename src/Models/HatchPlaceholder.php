<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eigener Platzhalter eines Teams mit festem Standardwert, pro Erhebung
 * überschreibbar (intake_settings.placeholder_values[key]).
 * Die eingebauten, berechneten Platzhalter (KW, Jahr) stehen in
 * \Platform\Hatch\Support\IntakePlaceholders und nicht in dieser Tabelle.
 */
class HatchPlaceholder extends Model
{
    protected $table = 'hatch_placeholders';

    protected $fillable = [
        'team_id',
        'created_by_user_id',
        'key',
        'label',
        'description',
        'default_value',
        'sort',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    /** Technische Schreibweise, z. B. "{{standort}}" (für MCP / Anzeige). */
    public function token(): string
    {
        return '{{' . $this->key . '}}';
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }
}
