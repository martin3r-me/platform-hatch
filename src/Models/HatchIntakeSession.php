<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Traits\Encryptable;
use Platform\Crm\Traits\HasContactLinksTrait;
use Symfony\Component\Uid\UuidV7;

class HatchIntakeSession extends Model
{
    use Encryptable;
    use HasContactLinksTrait;
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

    protected array $encryptable = [
        'answers' => 'json',
        'metadata' => 'json',
        'respondent_name' => 'string',
        'respondent_email' => 'string',
    ];

    protected $casts = [
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
                $model->session_token = self::generateShortToken();
            }

            if (empty($model->started_at)) {
                $model->started_at = now();
            }
        });
    }

    public static function generateShortToken(): string
    {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $part1 = '';
            $part2 = '';
            for ($i = 0; $i < 4; $i++) {
                $part1 .= $chars[random_int(0, 29)];
                $part2 .= $chars[random_int(0, 29)];
            }
            $token = $part1 . '-' . $part2;
        } while (self::where('session_token', $token)->exists());

        return $token;
    }

    public function projectIntake(): BelongsTo
    {
        return $this->belongsTo(HatchProjectIntake::class, 'project_intake_id');
    }
}
