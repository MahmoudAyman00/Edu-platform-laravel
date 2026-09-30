<?php

namespace App\Enums;

enum CourseStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case ARCHIVED = 'ARCHIVED';
}
