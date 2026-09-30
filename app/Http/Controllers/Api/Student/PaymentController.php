<?php

namespace App\Http\Controllers\Api\Student;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\InitiatePaymentRequest;
use App\Http\Resources\Payments\PaymentResource;
use App\Models\Course;
use App\Services\Payments\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(protected PaymentService $payments)
    {
    }

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();

        ['payment' => $payment, 'provider' => $provider] = $this->payments->initiate(
            $request->user(),
            Course::findOrFail($data['course_id']),
            PaymentMethod::from($data['method']),
            ['mobile' => $data['mobile']],
        );

        return $this->success(
            ['payment' => new PaymentResource($payment), ...$provider],
            'messages.payments.initiated',
            201
        );
    }

    public function show(Request $request, int $payment): JsonResponse
    {
        return $this->success(
            new PaymentResource($this->payments->show($request->user(), $payment)),
            'messages.payments.detail'
        );
    }
}
