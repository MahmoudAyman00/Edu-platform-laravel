<?php

namespace App\Services\Payments;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentSource;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\Payments\PaymentExpired;
use App\Events\Payments\PaymentFailed;
use App\Events\Payments\PaymentSucceeded;
use App\Exceptions\AppException;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\Enrollments\EnrollmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        protected FawryService $fawry,
        protected EnrollmentService $enrollments,
    ) {
    }

    /**
     * @return array{payment: Payment, provider: array}
     */
    public function initiate(User $user, Course $course, PaymentMethod $method, array $customer): array
    {
        if ($course->status !== CourseStatus::PUBLISHED) {
            throw AppException::fromKey('messages.enrollments.course_not_available', 'COURSE_NOT_AVAILABLE', 400);
        }

        if ($course->is_free) {
            throw AppException::fromKey('messages.payments.course_is_free', 'COURSE_IS_FREE', 400);
        }

        if ($this->enrollments->hasAccess($user, $course)) {
            throw AppException::fromKey('messages.payments.already_enrolled', 'ALREADY_ENROLLED', 400);
        }

        $existing = Payment::query()
            ->where('user_id', $user->getKey())
            ->where('course_id', $course->getKey())
            ->where('status', PaymentStatus::PENDING)
            ->latest('id')
            ->first();

        if ($existing && $existing->isLive()) {
            throw AppException::fromKey('messages.payments.pending_exists', 'PAYMENT_PENDING_EXISTS', 400);
        }

        $payment = Payment::create([
            'user_id' => $user->getKey(),
            'course_id' => $course->getKey(),
            'merchant_ref' => FawryService::merchantRef(),
            'amount' => $course->price,
            'currency' => 'EGP',
            'method' => $method,
            'status' => PaymentStatus::PENDING,
            'expires_at' => now()->addHours((int) config('fawry.reference_ttl_hours', 48)),
        ]);

        try {
            $order = [
                'merchant_ref' => $payment->merchant_ref,
                'amount' => number_format((float) $payment->amount, 2, '.', ''),
                'currency' => $payment->currency,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_mobile' => $customer['mobile'],
                'description' => 'Course enrollment: '.$course->translation()?->title,
            ];

            $provider = $method === PaymentMethod::FAWRY_CARD
                ? $this->fawry->createChargeCard($order)
                : $this->fawry->createChargeCode($order);
        } catch (AppException $e) {
            $payment->forceFill(['status' => PaymentStatus::FAILED])->save();

            throw $e;
        }

        $payment->forceFill([
            'fawry_reference' => $provider['reference_number'] ?: null,
            'meta' => array_merge($payment->meta ?? [], $provider),
        ])->save();

        return ['payment' => $payment->refresh(), 'provider' => $provider];
    }

    public function show(User $user, int $id): Payment
    {
        $payment = Payment::with(['course.translations'])->findOrFail($id);

        if (! $user->isAdmin() && $payment->user_id !== $user->getKey()) {
            throw AppException::fromKey('messages.forbidden', 'FORBIDDEN', 403);
        }

        return $this->refreshStatus($payment);
    }

    public function refreshStatus(Payment $payment): Payment
    {
        $payment = Payment::query()->findOrFail($payment->getKey());

        if ($payment->isTerminal()) {
            return $payment->load(['course.translations']);
        }

        if ($payment->expires_at && $payment->expires_at->isPast()) {
            return $this->markExpired($payment);
        }

        try {
            $result = $this->fawry->getChargeStatus($payment->merchant_ref);
        } catch (AppException $e) {
            Log::channel('fawry')->warning('Status refresh failed', ['payment_id' => $payment->getKey()]);

            return $payment->load(['course.translations']);
        }

        return match ($result['status']) {
            'PAID' => $this->markAsPaid($payment),
            'FAILED' => $this->markAsFailed($payment),
            'EXPIRED' => $this->markExpired($payment),
            default => $payment->load(['course.translations']),
        };
    }

    /**
     * @throws AppException
     */
    public function handleWebhook(array $payload): Payment
    {
        if (! $this->fawry->verifyWebhookSignature($payload)) {
            throw AppException::fromKey('messages.payments.invalid_signature', 'INVALID_SIGNATURE', 401);
        }

        Log::channel('fawry')->info('Webhook received', [
            'merchant_ref' => $payload['merchantRefNum'] ?? null,
            'status' => $payload['orderStatus'] ?? null,
        ]);

        $payment = Payment::query()
            ->where('merchant_ref', (string) ($payload['merchantRefNum'] ?? ''))
            ->first();

        if (! $payment) {
            throw AppException::fromKey('messages.payments.not_found', 'NOT_FOUND', 404);
        }

        $status = $this->fawry->normalizeStatus((string) ($payload['orderStatus'] ?? ''));

        return match ($status) {
            'PAID' => $this->markAsPaid($payment),
            'FAILED' => $this->markAsFailed($payment),
            'EXPIRED' => $this->markExpired($payment),
            default => $payment->load(['course.translations']),
        };
    }

    public function expireStale(): int
    {
        $count = 0;

        Payment::query()
            ->where('status', PaymentStatus::PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->chunkById(100, function ($payments) use (&$count) {
                foreach ($payments as $payment) {
                    $this->markExpired($payment);
                    $count++;
                }
            });

        return $count;
    }

    public function markAsPaid(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            /** @var Payment $locked */
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($locked->status === PaymentStatus::PAID) {
                return $locked->load(['course.translations']);
            }

            $locked->forceFill([
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
            ])->save();

            PaymentSucceeded::dispatch($locked);

            return $locked->load(['course.translations']);
        });
    }

    public function markAsFailed(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            /** @var Payment $locked */
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($locked->isTerminal()) {
                return $locked->load(['course.translations']);
            }

            $locked->forceFill(['status' => PaymentStatus::FAILED])->save();

            PaymentFailed::dispatch($locked);

            return $locked->load(['course.translations']);
        });
    }

    public function markExpired(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            /** @var Payment $locked */
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($locked->isTerminal()) {
                return $locked->load(['course.translations']);
            }

            $locked->forceFill(['status' => PaymentStatus::EXPIRED])->save();

            PaymentExpired::dispatch($locked);

            return $locked->load(['course.translations']);
        });
    }

    public function linkEnrollment(Payment $payment): void
    {
        $payment->loadMissing(['user', 'course.translations']);

        $enrollment = $this->enrollments->enrollAfterPayment($payment->user, $payment->course);

        $enrollment->forceFill([
            'payment_id' => $payment->getKey(),
            'source' => EnrollmentSource::PURCHASE,
        ])->save();
    }
}
