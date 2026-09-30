<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Certificates\CertificatePublicResource;
use App\Services\Certificates\CertificateService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CertificateVerificationController extends Controller
{
    use ApiResponse;

    public function __construct(protected CertificateService $certificates)
    {
    }

    public function verify(string $serial): JsonResponse
    {
        return $this->success(
            new CertificatePublicResource($this->certificates->verifyBySerial($serial)),
            'messages.certificates.verified'
        );
    }
}
