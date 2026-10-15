<?php

namespace Plugins\GstBilling\Src;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class GstPdfService
{
    /**
     * Generate the PDF from the tax-invoice blade view.
     */
    public function generatePdf(array $data)
    {
        // Ensure DomPDF uses DejaVu Sans for Indian Rupee symbol support
        // We use view name 'gst-billing::tax-invoice' assuming the namespace is registered in Plugin.php
        $pdf = Pdf::loadView('gst-billing::tax-invoice', $data)
                  ->setPaper('A4', 'portrait')
                  ->setOption(['defaultFont' => 'DejaVu Sans']);

        return $pdf;
    }

    /**
     * Download the generated PDF.
     */
    public function download(array $data, string $filename = 'invoice.pdf')
    {
        $pdf = $this->generatePdf($data);
        return $pdf->download($filename);
    }

    /**
     * Stream the generated PDF in the browser (for preview).
     */
    public function stream(array $data, string $filename = 'invoice.pdf')
    {
        $pdf = $this->generatePdf($data);
        return $pdf->stream($filename);
    }
}
