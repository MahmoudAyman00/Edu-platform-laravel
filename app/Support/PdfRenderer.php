<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Thin wrapper around dompdf, used by the Certificates feature (Phase 6).
 */
class PdfRenderer
{
    public static function render(string $view, array $data = []): string
    {
        return Pdf::loadView($view, $data)->output();
    }
}
