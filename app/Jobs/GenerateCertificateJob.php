<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Support\CloudStorage;
use App\Support\PdfRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateCertificateJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $certificateId)
    {
    }

    public function handle(): void
    {
        $certificate = Certificate::with(['user', 'course.translations'])->findOrFail($this->certificateId);

        if ($certificate->isReady()) {
            return;
        }

        try {
            $translation = $certificate->course->translation();
            $locale = $translation?->locale ?? 'ar';

            $pdf = PdfRenderer::render('certificates.certificate', [
                'title' => $locale === 'ar' ? 'شهادة إتمام' : 'Certificate of Completion',
                'subtitle' => $locale === 'ar' ? 'تشهد المنصة بأن' : 'This certifies that',
                'completed_label' => $locale === 'ar' ? 'قد أتم بنجاح كورس' : 'has successfully completed the course',
                'serial_label' => $locale === 'ar' ? 'الرقم المسلسل' : 'Serial number',
                'date_label' => $locale === 'ar' ? 'تاريخ الإصدار' : 'Issued at',
                'student_name' => $certificate->user->name,
                'course_title' => $translation?->title ?? '#'.$certificate->course_id,
                'serial_number' => $certificate->serial_number,
                'issued_at' => $certificate->created_at->format('Y-m-d'),
            ]);

            $path = "certificates/{$certificate->serial_number}.pdf";

            CloudStorage::store($path, $pdf);

            $certificate->forceFill(['pdf_path' => $path])->save();
        } catch (\Throwable $e) {
            Log::warning('Certificate PDF generation failed', [
                'certificate_id' => $certificate->getKey(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
