<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionTranslation extends Model
{
    protected $guarded = [];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
