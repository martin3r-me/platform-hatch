<?php

namespace Platform\Hatch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Traits\Encryptable;
use Platform\Crm\Traits\HasContactLinksTrait;
use Platform\Hatch\Support\AnswerValue;
use Platform\Hatch\Support\IsoWeekResolver;
use Symfony\Component\Uid\UuidV7;

/**
 * @property array<string, mixed>|null $answers
 * @property array<string, mixed>|null $metadata
 * @property string|null $respondent_name
 * @property string|null $respondent_email
 */
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
        'iso_week',
        'iso_year',
        'answers',
        'respondent_name',
        'respondent_email',
        'current_step',
        'answered_count',
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
        'iso_week' => 'integer',
        'iso_year' => 'integer',
        'answered_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Mitzählen, wie viele Felder wirklich beantwortet sind (siehe scopeWithAnswers)
        static::saving(function (self $model) {
            if (!$model->exists || $model->isDirty('answers')) {
                $model->answered_count = $model->computeAnsweredCount();
            }
        });

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

            // ISO-KW automatisch stempeln. Genutzt für Auswertungen pro Woche
            // (z. B. Wochenfeedback) ohne separaten Intake pro KW.
            if ($model->iso_week === null || $model->iso_year === null) {
                $resolver = app(IsoWeekResolver::class);
                $resolved = $resolver->resolve($model->projectIntake, $model->started_at);
                $model->iso_year = $resolved['iso_year'];
                $model->iso_week = $resolved['iso_week'];
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

    /**
     * Nur Teilnahmen mit mindestens einer Antwort (oder abgeschlossen).
     * Wer die Umfrage nur öffnet und wieder schließt, verfälscht sonst die Zahlen.
     */
    public function scopeWithAnswers($query)
    {
        return $query->where(fn ($q) => $q->where('answered_count', '>', 0)->orWhere('status', 'completed'));
    }

    public function computeAnsweredCount(): int
    {
        $answers = is_array($this->answers) ? $this->answers : [];
        if ($answers === []) {
            return 0;
        }

        $template = $this->projectIntake?->projectTemplate;
        $types = $template ? $template->templateBlocks->pluck('block_type', 'id')->all() : [];

        return min(AnswerValue::countAnswered($answers, $types), 65535);
    }

    public function projectIntake(): BelongsTo
    {
        return $this->belongsTo(HatchProjectIntake::class, 'project_intake_id');
    }
}
