<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasTranslations, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(LessonTranslation::class);
    }
}
