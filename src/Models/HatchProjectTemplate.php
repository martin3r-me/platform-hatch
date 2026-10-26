<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
class HatchProjectTemplate extends Model
{
    use LogsActivity;
    
    protected $table = 'hatch_project_templates';
    
    public const FLOW_MODE_BLOCK_FLOW = 'block_flow';
    public const FLOW_MODE_OVERVIEW = 'overview';

    public const FLOW_MODES = [
        self::FLOW_MODE_BLOCK_FLOW,
        self::FLOW_MODE_OVERVIEW,
    ];

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'ai_personality',
        'industry_context',
        'complexity_level',
        'flow_mode',
        'completion_message',
        'ai_instructions',
        'is_active',
        'created_by_user_id',
        'team_id'
    ];

    protected $attributes = [
        'flow_mode' => self::FLOW_MODE_BLOCK_FLOW,
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
    
    /**
     * @return HasMany<HatchTemplateBlock, $this>
     */
    /**
     * Kopiert diese Vorlage samt aller Fragen in eine neue, eigenständige Vorlage.
     * Fragen-Gruppen bekommen neue group_uuids, Sichtbarkeitsregeln werden auf die
     * neuen Block-IDs umgeschrieben (sonst zeigten sie auf die Fragen des Originals).
     */
    public function duplicateAs(string $name, int $userId, ?string $description = null): self
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($name, $userId, $description) {
            $copy = self::create([
                'name' => $name,
                'description' => $description ?? $this->description,
                'ai_personality' => $this->ai_personality,
                'industry_context' => $this->industry_context,
                'complexity_level' => $this->complexity_level,
                'flow_mode' => $this->flow_mode,
                'completion_message' => $this->completion_message,
                'ai_instructions' => $this->ai_instructions,
                'is_active' => true,
                'created_by_user_id' => $userId,
                'team_id' => $this->team_id,
            ]);

            $idMap = [];
            $groupMap = [];
            $created = [];
            foreach ($this->templateBlocks()->orderBy('sort_order')->get() as $block) {
                $new = $block->replicate(['uuid', 'project_template_id']);
                $new->project_template_id = $copy->id;
                $new->created_by_user_id = $userId;
                if ($block->group_uuid) {
                    $new->group_uuid = $groupMap[$block->group_uuid] ??= (string) \Symfony\Component\Uid\UuidV7::generate();
                }
                $new->save();
                $idMap[$block->id] = $new->id;
                $created[] = $new;
            }

            foreach ($created as $new) {
                $rules = $new->visibility_rules;
                if (!is_array($rules) || empty($rules['rules'])) {
                    continue;
                }
                $rules['rules'] = array_values(array_filter(array_map(function ($rule) use ($idMap) {
                    $source = (int) ($rule['source_block_id'] ?? 0);
                    if (!isset($idMap[$source])) {
                        return null;   // Quelle existiert nicht (mehr) – Regel nicht übernehmen
                    }
                    $rule['source_block_id'] = $idMap[$source];

                    return $rule;
                }, $rules['rules'])));
                $new->visibility_rules = $rules['rules'] ? $rules : null;
                $new->save();
            }

            return $copy;
        });
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
