<?php

namespace App\Listeners\Certificates;

use App\Events\Exams\ExamAttemptGraded;
use App\Services\Certificates\CertificateService;
use Illuminate\Contracts\Queue\ShouldQueue;

class IssueCertificateOnPass implements ShouldQueue
{
    public function __construct(protected CertificateService $certificates)
    {
    }

    public function handle(ExamAttemptGraded $event): void
    {
        $attempt = $event->attempt->loadMissing(['exam.course', 'user']);

        if (! $attempt->passed || ! $attempt->exam || ! $attempt->exam->is_final) {
            return;
        }

        $this->certificates->issue($attempt->user, $attempt->exam->course);
    }
}
