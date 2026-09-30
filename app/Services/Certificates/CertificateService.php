<?php

namespace App\Services\Certificates;

use App\Exceptions\AppException;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Jobs\GenerateCertificateJob;
use Illuminate\Database\Eloquent\Collection;

class CertificateService
{
    public function issue(User $user, Course $course): Certificate
    {
        $certificate = Certificate::firstOrCreate(
            ['user_id' => $user->getKey(), 'course_id' => $course->getKey()],
            ['serial_number' => self::serial()],
        );

        if ($certificate->wasRecentlyCreated) {
            GenerateCertificateJob::dispatch($certificate->getKey());
        }

        return $certificate->load(['user', 'course.translations']);
    }

    /**
     * @return Collection<int, Certificate>
     */
    public function listMine(User $user): Collection
    {
        return Certificate::query()
            ->where('user_id', $user->getKey())
            ->with(['course.translations'])
            ->latest()
            ->get();
    }

    /**
     * @throws AppException
     */
    public function readyForDownload(User $user, int $id): Certificate
    {
        /** @var Certificate $certificate */
        $certificate = Certificate::with(['course.translations'])->findOrFail($id);

        if (! $user->isAdmin() && $certificate->user_id !== $user->getKey()) {
            throw AppException::fromKey('messages.forbidden', 'FORBIDDEN', 403);
        }

        if (! $certificate->isReady()) {
            throw AppException::fromKey('messages.certificates.not_ready', 'CERTIFICATE_NOT_READY', 409);
        }

        return $certificate;
    }

    public function verifyBySerial(string $serial): Certificate
    {
        return Certificate::with(['user', 'course.translations'])
            ->where('serial_number', $serial)
            ->firstOrFail();
    }

    public static function serial(): string
    {
        do {
            $serial = 'CERT-'.strtoupper(\Illuminate\Support\Str::random(10));
        } while (Certificate::where('serial_number', $serial)->exists());

        return $serial;
    }
}
