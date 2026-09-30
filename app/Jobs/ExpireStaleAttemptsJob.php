<?php

namespace App\Jobs;

use App\Services\Exams\AttemptService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireStaleAttemptsJob implements ShouldQueue
{
    use Queueable;

    public function handle(AttemptService $attempts): void
    {
        $attempts->expireStale();
    }
}
