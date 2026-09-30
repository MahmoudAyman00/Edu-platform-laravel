<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FawryWebhookController extends Controller
{
    use ApiResponse;

    public function __construct(protected PaymentService $payments)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $payment = $this->payments->handleWebhook($request->all());

        return $this->success(
            ['status' => $payment->status instanceof \BackedEnum ? $payment->status->value : $payment->status],
            'messages.payments.webhook_received'
        );
    }
}
