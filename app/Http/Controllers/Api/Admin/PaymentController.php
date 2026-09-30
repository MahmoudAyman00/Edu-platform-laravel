<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Payments\PaymentResource;
use App\Models\Payment;
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

    public function index(Request $request): JsonResponse
    {
        $paginator = Payment::query()
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', strtoupper($s)))
            ->when($request->integer('course_id'), fn ($q, $id) => $q->where('course_id', $id))
            ->with(['course.translations'])
            ->latest()
            ->paginate((int) $request->integer('per_page', 15));

        return $this->paginated(
            $paginator->through(fn (Payment $p) => (new PaymentResource($p))->toArray($request)),
            'messages.payments.list',
        );
    }

    public function refresh(Payment $payment): JsonResponse
    {
        return $this->success(
            new PaymentResource($this->payments->refreshStatus($payment)),
            'messages.payments.detail'
        );
    }
}
