<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
class HatchProjectIntake extends Model
{
    use LogsActivity;
    
    protected $table = 'hatch_project_intakes';
    
    protected $fillable = [
        'uuid',
        'public_token',
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

    public function sessions(): HasMany
    {
        return $this->hasMany(HatchIntakeSession::class, 'project_intake_id');
    }

    public function generatePublicToken(): string
    {
        $this->public_token = bin2hex(random_bytes(16));
        $this->save();

        return $this->public_token;
    }

    public function getPublicUrl(): ?string
    {
        if (!$this->public_token) {
            return null;
        }

        return url('/hatch/p/' . $this->public_token);
    }

    /**
     * Vereinfachtes Status-Modell: draft → published → closed
     *
     * - draft: Erhebung wird vorbereitet, nicht öffentlich zugänglich
     * - published: Erhebung ist live und nimmt Antworten entgegen
     * - closed: Erhebung ist beendet/archiviert
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Entwurf',
        self::STATUS_PUBLISHED => 'Veröffentlicht',
        self::STATUS_CLOSED => 'Geschlossen',
    ];

    /**
     * Veröffentlicht die Erhebung (ein Klick = live).
     * Setzt status, is_active und started_at automatisch.
     */
    public function publish(): self
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->is_active = true;

        if (empty($this->started_at)) {
            $this->started_at = now();
        }

        $this->save();

        return $this;
    }

    /**
     * Schließt die Erhebung.
     * Setzt status, is_active und completed_at automatisch.
     */
    public function close(): self
    {
        $this->status = self::STATUS_CLOSED;
        $this->is_active = false;

        if (empty($this->completed_at)) {
            $this->completed_at = now();
        }

        $this->save();

        return $this;
    }

    /**
     * Setzt die Erhebung zurück auf Entwurf.
     */
    public function unpublish(): self
    {
        $this->status = self::STATUS_DRAFT;
        $this->is_active = false;
        $this->save();

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
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
