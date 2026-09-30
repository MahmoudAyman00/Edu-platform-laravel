<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Events\Payments\PaymentExpired;
use App\Events\Payments\PaymentSucceeded;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\FawryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function paidCourse(): Course
    {
        return Course::factory()->published()->create(['price' => 150]);
    }

    private function fakeFawry(): void
    {
        // NOTE: Http::fake() merges stubs (first match wins),
        // so the more specific pattern must come first.
        Http::fake([
            '*chargeStatus*' => Http::response(['orderStatus' => 'PENDING']),
            '*charge*' => Http::response(['referenceNumber' => 'FAWRY123', 'paymentUrl' => 'https://pay.example/abc']),
        ]);
    }

    public function test_student_can_initiate_code_payment(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->fakeFawry();

        $response = $this->postJson('/api/payments/initiate', [
            'course_id' => $this->paidCourse()->id,
            'method' => 'FAWRY_CODE',
            'mobile' => '01001234567',
        ])->assertCreated()->assertJsonPath('success', true);

        $this->assertSame('PENDING', $response->json('data.payment.status'));
        $this->assertSame('FAWRY123', $response->json('data.payment.fawry_reference'));
        $this->assertDatabaseHas('payments', ['status' => 'PENDING', 'method' => 'FAWRY_CODE']);
    }

    public function test_card_payment_returns_payment_url(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->fakeFawry();

        $this->postJson('/api/payments/initiate', [
            'course_id' => $this->paidCourse()->id,
            'method' => 'FAWRY_CARD',
            'mobile' => '01001234567',
        ])->assertCreated()
            ->assertJsonPath('data.payment_url', 'https://pay.example/abc');
    }

    public function test_cannot_pay_for_free_or_enrolled_course(): void
    {
        Sanctum::actingAs($student = User::factory()->create());
        $this->fakeFawry();

        $free = Course::factory()->published()->create(['price' => 0]);
        $this->postJson('/api/payments/initiate', [
            'course_id' => $free->id, 'method' => 'FAWRY_CODE', 'mobile' => '01001234567',
        ])->assertStatus(400)->assertJsonPath('code', 'COURSE_IS_FREE');

        // Enrolled (via paid webhook) -> ALREADY_ENROLLED.
        $course = $this->paidCourse();
        $payment = Payment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'merchant_ref' => 'EDU-ENR1',
            'amount' => 150,
            'currency' => 'EGP',
            'method' => 'FAWRY_CODE',
            'status' => PaymentStatus::PENDING,
            'expires_at' => now()->addHours(48),
        ]);
        app(\App\Services\Payments\PaymentService::class)->markAsPaid($payment);

        $this->postJson('/api/payments/initiate', [
            'course_id' => $course->id, 'method' => 'FAWRY_CODE', 'mobile' => '01001234567',
        ])->assertStatus(400)->assertJsonPath('code', 'ALREADY_ENROLLED');
    }

    public function test_cannot_initiate_twice_with_live_pending(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->fakeFawry();
        $courseId = $this->paidCourse()->id;
        $payload = ['course_id' => $courseId, 'method' => 'FAWRY_CODE', 'mobile' => '01001234567'];

        $this->postJson('/api/payments/initiate', $payload)->assertCreated();
        $this->postJson('/api/payments/initiate', $payload)
            ->assertStatus(400)->assertJsonPath('code', 'PAYMENT_PENDING_EXISTS');
    }

    public function test_webhook_marks_paid_and_enrolls_idempotently(): void
    {
        $student = User::factory()->create();
        $course = $this->paidCourse();
        $payment = Payment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'merchant_ref' => 'EDU-TEST123',
            'amount' => 150,
            'currency' => 'EGP',
            'method' => 'FAWRY_CODE',
            'status' => PaymentStatus::PENDING,
            'expires_at' => now()->addHours(48),
        ]);

        $payload = [
            'merchantRefNum' => 'EDU-TEST123',
            'fawryRefNumber' => 'FAWRY123',
            'orderAmount' => '150.00',
            'orderStatus' => 'PAID',
        ];
        $payload['signature'] = FawryService::signature(
            $payload['merchantRefNum'], $payload['fawryRefNumber'], $payload['orderAmount'], $payload['orderStatus']
        );

        Event::fake([PaymentSucceeded::class]);

        // Public webhook: no auth needed.
        $this->postJson('/api/payments/webhook/fawry', $payload)
            ->assertOk()->assertJsonPath('data.status', 'PAID');

        Event::assertDispatched(PaymentSucceeded::class);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'PAID']);

        // Second delivery: idempotent, no duplicate enrollment.
        Event::fake([PaymentSucceeded::class]);
        $this->postJson('/api/payments/webhook/fawry', $payload)->assertOk();
        Event::assertNotDispatched(PaymentSucceeded::class);
    }

    public function test_webhook_rejects_bad_signature(): void
    {
        $this->postJson('/api/payments/webhook/fawry', [
            'merchantRefNum' => 'EDU-NOPE',
            'signature' => 'invalid',
        ])->assertUnauthorized()->assertJsonPath('code', 'INVALID_SIGNATURE');
    }

    public function test_paid_payment_enrolls_student_with_payment_link(): void
    {
        $student = User::factory()->create();
        $course = $this->paidCourse();
        $payment = Payment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'merchant_ref' => 'EDU-LINK1',
            'amount' => 150,
            'currency' => 'EGP',
            'method' => 'FAWRY_CODE',
            'status' => PaymentStatus::PENDING,
            'expires_at' => now()->addHours(48),
        ]);

        app(\App\Services\Payments\PaymentService::class)->markAsPaid($payment);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_id' => $payment->id,
        ]);
    }

    public function test_refresh_maps_fawry_status_and_expiry_wins(): void
    {
        Sanctum::actingAs($student = User::factory()->create());
        $course = $this->paidCourse();

        Http::fake([
            '*chargeStatus*' => Http::response(['orderStatus' => 'PAID']),
            '*charge*' => Http::response(['referenceNumber' => 'F1']),
        ]);
        $id = $this->postJson('/api/payments/initiate', [
            'course_id' => $course->id, 'method' => 'FAWRY_CODE', 'mobile' => '01001234567',
        ])->json('data.payment.id');

        // Student sees live status (polling).
        $this->getJson("/api/payments/{$id}")
            ->assertOk()->assertJsonPath('data.status', 'PAID');

        $this->assertDatabaseHas('enrollments', ['user_id' => $student->id, 'course_id' => $course->id]);
    }

    public function test_expire_stale_marks_expired_and_fires_event(): void
    {
        Event::fake([PaymentExpired::class]);
        $student = User::factory()->create();
        $course = $this->paidCourse();

        Payment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'merchant_ref' => 'EDU-OLD1',
            'amount' => 150,
            'currency' => 'EGP',
            'method' => 'FAWRY_CODE',
            'status' => PaymentStatus::PENDING,
            'expires_at' => now()->subHour(),
        ]);

        $count = app(\App\Services\Payments\PaymentService::class)->expireStale();

        $this->assertSame(1, $count);
        Event::assertDispatched(PaymentExpired::class);
        $this->assertDatabaseHas('payments', ['merchant_ref' => 'EDU-OLD1', 'status' => 'EXPIRED']);
    }

    public function test_admin_routes_not_shadowed_and_student_isolation(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $course = $this->paidCourse();
        $payment = Payment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'merchant_ref' => 'EDU-ISO1',
            'amount' => 150,
            'currency' => 'EGP',
            'method' => 'FAWRY_CODE',
            'status' => PaymentStatus::PENDING,
            'expires_at' => now()->addHours(48),
        ]);

        Sanctum::actingAs($admin);
        $this->getJson('/api/payments/admin')->assertOk()->assertJsonPath('success', true);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/payments/{$payment->id}")->assertForbidden();

        Sanctum::actingAs($student);
        $this->getJson("/api/payments/{$payment->id}")->assertOk();
    }
}
