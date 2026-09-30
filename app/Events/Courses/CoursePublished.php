<?php

namespace App\Events\Courses;

use App\Models\Course;
use Illuminate\Foundation\Events\Dispatchable;

class CoursePublished
{
    use Dispatchable;

    public function __construct(public readonly Course $course)
    {
    }
}
