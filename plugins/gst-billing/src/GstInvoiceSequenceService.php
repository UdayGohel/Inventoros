<?php

namespace Plugins\GstBilling\Src;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GstInvoiceSequenceService
{
    /**
     * Generate the next consecutive invoice number for the given prefix and date.
     * Format: {PREFIX}/{YY}-{YY+1}/{0001}
     */
    public function generateNextInvoiceNumber(int $organizationId, string $prefix = 'INV', ?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        
        // Determine Financial Year
        // If month is Jan, Feb, Mar, the financial year started last year
        $year = $date->year;
        if ($date->month <= 3) {
            $startYear = $year - 1;
            $endYear = $year;
        } else {
            $startYear = $year;
            $endYear = $year + 1;
        }
        
        $yyStart = substr((string)$startYear, -2);
        $yyEnd = substr((string)$endYear, -2);
        $fyString = $yyStart . '-' . $yyEnd;

        $pattern = $prefix . '/' . $fyString . '/';

        // The caller holds the organization row lock until it has inserted the
        // invoice (GstInvoiceController), so two requests never read the same last number.
        $lastInvoice = DB::table('gst_invoices')
            ->where('organization_id', $organizationId)
            ->where('invoice_number', 'like', $pattern . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;

        if ($lastInvoice) {
            // Extract sequence number
            $lastNumber = str_replace($pattern, '', $lastInvoice->invoice_number);
            if (is_numeric($lastNumber)) {
                $sequence = (int) $lastNumber + 1;
            }
        }

        return $pattern . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
    }
}
