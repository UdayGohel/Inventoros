<?php

namespace Plugins\GstBilling\Src;

class GstTaxEngine
{
    /**
     * Determine if the transaction is intra-state (CGST + SGST) or inter-state (IGST).
     */
    public function determineTaxType(string $sellerStateCode, string $buyerStateCode): string
    {
        return $sellerStateCode === $buyerStateCode ? 'INTRA_STATE' : 'INTER_STATE';
    }

    /**
     * Calculate tax breakdown for a single item.
     */
    public function calculateItemTax(float $taxableValue, float $gstRate, string $taxType, float $cessPercent = 0): array
    {
        $cgst = 0;
        $sgst = 0;
        $igst = 0;
        
        $totalGst = ($taxableValue * $gstRate) / 100;
        $cess = ($taxableValue * $cessPercent) / 100;

        if ($taxType === 'INTRA_STATE') {
            $cgst = $totalGst / 2;
            $sgst = $totalGst / 2;
        } else {
            $igst = $totalGst;
        }

        return [
            'taxable_value' => round($taxableValue, 2),
            'cgst_amount' => round($cgst, 2),
            'sgst_amount' => round($sgst, 2),
            'igst_amount' => round($igst, 2),
            'cess_amount' => round($cess, 2),
            'total_tax' => round($cgst + $sgst + $igst + $cess, 2),
            'total_amount' => round($taxableValue + $cgst + $sgst + $igst + $cess, 2),
        ];
    }
    
    /**
     * Group items by HSN/SAC code and calculate totals.
     */
    public function generateHsnSummary(array $items, string $taxType): array
    {
        $summary = [];

        foreach ($items as $item) {
            $hsn = $item['hsn_sac_code'] ?? 'NA';
            $gstRate = (float) ($item['gst_rate'] ?? 0);
            $key = $hsn . '_' . $gstRate;

            if (!isset($summary[$key])) {
                $summary[$key] = [
                    'hsn_sac_code' => $hsn,
                    'gst_rate' => $gstRate,
                    'taxable_value' => 0,
                    'cgst_amount' => 0,
                    'sgst_amount' => 0,
                    'igst_amount' => 0,
                    'cess_amount' => 0,
                    'total_tax' => 0,
                ];
            }

            $taxCalc = $this->calculateItemTax(
                (float)($item['taxable_value'] ?? 0), 
                $gstRate, 
                $taxType, 
                (float)($item['cess_percent'] ?? 0)
            );
            
            $summary[$key]['taxable_value'] += $taxCalc['taxable_value'];
            $summary[$key]['cgst_amount'] += $taxCalc['cgst_amount'];
            $summary[$key]['sgst_amount'] += $taxCalc['sgst_amount'];
            $summary[$key]['igst_amount'] += $taxCalc['igst_amount'];
            $summary[$key]['cess_amount'] += $taxCalc['cess_amount'];
            $summary[$key]['total_tax'] += $taxCalc['total_tax'];
        }
        
        // Round off totals in summary
        foreach ($summary as &$row) {
            $row['taxable_value'] = round($row['taxable_value'], 2);
            $row['cgst_amount'] = round($row['cgst_amount'], 2);
            $row['sgst_amount'] = round($row['sgst_amount'], 2);
            $row['igst_amount'] = round($row['igst_amount'], 2);
            $row['cess_amount'] = round($row['cess_amount'], 2);
            $row['total_tax'] = round($row['total_tax'], 2);
        }

        return array_values($summary);
    }
    
    /**
     * Calculate order totals with round-off.
     */
    public function calculateOrderTotals(array $items, string $taxType): array
    {
        $totalTaxable = 0;
        $totalCgst = 0;
        $totalSgst = 0;
        $totalIgst = 0;
        $totalCess = 0;
        
        foreach ($items as $item) {
            $taxCalc = $this->calculateItemTax(
                (float)($item['taxable_value'] ?? 0), 
                (float)($item['gst_rate'] ?? 0), 
                $taxType, 
                (float)($item['cess_percent'] ?? 0)
            );
            $totalTaxable += $taxCalc['taxable_value'];
            $totalCgst += $taxCalc['cgst_amount'];
            $totalSgst += $taxCalc['sgst_amount'];
            $totalIgst += $taxCalc['igst_amount'];
            $totalCess += $taxCalc['cess_amount'];
        }
        
        $grossTotal = $totalTaxable + $totalCgst + $totalSgst + $totalIgst + $totalCess;
        $roundedTotal = round($grossTotal);
        $roundOff = round($roundedTotal - $grossTotal, 2);
        
        return [
            'taxable_value' => round($totalTaxable, 2),
            'cgst_amount' => round($totalCgst, 2),
            'sgst_amount' => round($totalSgst, 2),
            'igst_amount' => round($totalIgst, 2),
            'cess_amount' => round($totalCess, 2),
            'gross_amount' => round($grossTotal, 2),
            'round_off' => $roundOff,
            'total_amount' => $roundedTotal,
        ];
    }
}
