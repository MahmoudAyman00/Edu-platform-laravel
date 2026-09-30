<?php

use App\Jobs\CheckPendingFawryPaymentsJob;
use App\Jobs\ExpireStaleAttemptsJob;
use Illuminate\Support\Facades\Schedule;

// Phase 4: close abandoned IN_PROGRESS attempts (past duration + grace).
Schedule::job(new ExpireStaleAttemptsJob)->everyFiveMinutes();
// Phase 5: reconcile pending Fawry payments (expire + poll status).
Schedule::job(new CheckPendingFawryPaymentsJob)->everyTenMinutes();
