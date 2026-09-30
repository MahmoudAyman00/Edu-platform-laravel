<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, HasTranslations;

    protected $guarded = [];

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
