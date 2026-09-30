<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'score' => 'integer',
            'passed' => 'boolean',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'exam_attempt_id');
    }
}
