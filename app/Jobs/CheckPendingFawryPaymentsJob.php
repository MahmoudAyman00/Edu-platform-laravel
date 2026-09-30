<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckPendingFawryPaymentsJob implements ShouldQueue
{
    use Queueable;

    public function handle(PaymentService $payments): void
    {
        $expired = $payments->expireStale();

        if ($expired > 0) {
            Log::channel('fawry')->info('Expired stale payments', ['count' => $expired]);
        }

        // Poll Fawry for live pending payments (bounded, oldest first).
        Payment::query()
            ->where('status', \App\Enums\PaymentStatus::PENDING)
            ->where('updated_at', '<', now()->subMinutes(10))
            ->orderBy('id')
            ->limit(50)
            ->chunkById(50, function ($batch) use ($payments) {
                foreach ($batch as $payment) {
                    try {
                        $payments->refreshStatus($payment);
                    } catch (\Throwable $e) {
                        Log::channel('fawry')->warning('Polling failed', ['payment_id' => $payment->getKey()]);
                    }
                }
            });
    }
}
