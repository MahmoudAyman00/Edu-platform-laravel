<?php

namespace App\Models;

use App\Enums\ExamStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasTranslations, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ExamStatus::class,
            'is_final' => 'boolean',
            'pass_score' => 'integer',
            'max_attempts' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ExamTranslation::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
