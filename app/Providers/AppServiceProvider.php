<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Login / register brute-force protection.
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Forgot-password / reset-password / resend-verification protection.
        RateLimiter::for('password', function (Request $request) {
            $key = $request->input('email', $request->ip());

            return Limit::perMinute(5)->by($key.'|'.$request->ip());
        });

        // Phase 1 events: every event ships with its listener (no deferred listeners).
        Event::listen(
            \App\Events\Auth\UserRegistered::class,
            \App\Listeners\Notifications\SendVerificationEmail::class,
        );
        Event::listen(
            \App\Events\Auth\VerificationEmailRequested::class,
            \App\Listeners\Notifications\SendVerificationEmail::class,
        );
        Event::listen(
            \App\Events\Auth\PasswordResetRequested::class,
            \App\Listeners\Notifications\SendPasswordResetEmail::class,
        );

        // Phase 2 events: every event ships with its listener (no deferred listeners).
        Event::listen(
            \App\Events\Courses\CoursePublished::class,
            \App\Listeners\Notifications\SendCoursePublishedNotifications::class,
        );

        // Phase 3 events: every event ships with its listener (no deferred listeners).
        Event::listen(
            \App\Events\Enrollments\StudentEnrolled::class,
            \App\Listeners\Notifications\SendEnrollmentNotification::class,
        );

        // Phase 4 events: every event ships with its listener (no deferred listeners).
        Event::listen(
            \App\Events\Exams\ExamAttemptGraded::class,
            \App\Listeners\Notifications\SendExamResultNotification::class,
        );

        // Phase 6 events: certificate issuance on final-exam pass.
        Event::listen(
            \App\Events\Exams\ExamAttemptGraded::class,
            \App\Listeners\Certificates\IssueCertificateOnPass::class,
        );

        // Phase 5 events: every event ships with its listener (no deferred listeners).
        Event::listen(
            \App\Events\Payments\PaymentSucceeded::class,
            \App\Listeners\Enrollments\EnrollStudentAfterPayment::class,
        );
        Event::listen(
            \App\Events\Payments\PaymentSucceeded::class,
            \App\Listeners\Notifications\SendPaymentSucceededNotification::class,
        );
        Event::listen(
            \App\Events\Payments\PaymentExpired::class,
            \App\Listeners\Notifications\SendPaymentExpiredNotification::class,
        );
        Event::listen(
            \App\Events\Payments\PaymentFailed::class,
            \App\Listeners\Notifications\SendPaymentFailedNotification::class,
        );
    }
}
