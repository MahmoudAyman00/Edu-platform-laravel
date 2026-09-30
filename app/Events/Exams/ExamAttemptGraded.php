<?php

namespace App\Events\Exams;

use App\Models\ExamAttempt;
use Illuminate\Foundation\Events\Dispatchable;

class ExamAttemptGraded
{
    use Dispatchable;

    public function __construct(public readonly ExamAttempt $attempt)
    {
    }
}
