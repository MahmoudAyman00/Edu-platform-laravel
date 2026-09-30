<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory, HasTranslations;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'price' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CourseTranslation::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::PUBLISHED);
    }

    protected function isFree(): Attribute
    {
        return Attribute::get(fn () => ((float) $this->price) === 0.0);
    }
}
