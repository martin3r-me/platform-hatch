<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class HatchIntakeSession extends Model
{
    protected $table = 'hatch_intake_sessions';

    protected $fillable = [
        'uuid',
        'session_token',
        'project_intake_id',
        'status',
        'answers',
        'respondent_name',
        'respondent_email',
        'current_step',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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

            if (empty($model->session_token)) {
                $model->session_token = bin2hex(random_bytes(32));
            }

            if (empty($model->started_at)) {
                $model->started_at = now();
            }
        });
    }

    public function projectIntake(): BelongsTo
    {
        return $this->belongsTo(HatchProjectIntake::class, 'project_intake_id');
    }
}
