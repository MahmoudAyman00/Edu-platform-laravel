<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Certificates\CertificateResource;
use App\Services\Certificates\CertificateService;
use App\Support\ApiResponse;
use App\Support\CloudStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    use ApiResponse;

    public function __construct(protected CertificateService $certificates)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            CertificateResource::collection($this->certificates->listMine($request->user())),
            'messages.certificates.list'
        );
    }

    public function download(Request $request, int $certificate): Response
    {
        $cert = $this->certificates->readyForDownload($request->user(), $certificate);
        $disk = Storage::disk(CloudStorage::disk());

        try {
            $path = $disk->path($cert->pdf_path);

            if (is_file($path)) {
                return response()->download($path, $cert->serial_number.'.pdf');
            }
        } catch (\Throwable) {
            // Non-local disks fall through to a signed URL.
        }

        return redirect()->away(CloudStorage::presignedDownloadUrl($cert->pdf_path));
    }
}
