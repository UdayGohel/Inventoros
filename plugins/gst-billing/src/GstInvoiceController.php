<?php

namespace Plugins\GstBilling\Src;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GstInvoiceController extends Controller
{
    protected GstTaxEngine $taxEngine;
    protected GstPdfService $pdfService;
    protected GstNumberToWords $numberToWords;
    protected GstInvoiceSequenceService $sequenceService;

    public function __construct(
        GstTaxEngine $taxEngine,
        GstPdfService $pdfService,
        GstNumberToWords $numberToWords,
        GstInvoiceSequenceService $sequenceService
    ) {
        $this->taxEngine = $taxEngine;
        $this->pdfService = $pdfService;
        $this->numberToWords = $numberToWords;
        $this->sequenceService = $sequenceService;
    }

    /**
     * Compute and synchronize the GST invoice data for an order.
     */
    public function syncAndCalculateInvoice(Order $order): array
    {
        $settings = DB::table('gst_settings')->where('organization_id', $order->organization_id)->first();
        $sellerStateCode = $settings->state_code ?? '24'; // Default to Gujarat
        $groupingMode = $settings->invoice_item_grouping ?? 'matrix';

        // Load items with variants and customer
        $order->loadMissing(['items.variant', 'customer']);

        // Determine Customer Profile and Place of Supply (POS)
        $customerProfile = null;
        if ($order->customer_id) {
            $customerProfile = DB::table('gst_customer_profiles')
                ->where('customer_id', $order->customer_id)
                ->first();
        }

        $pos = $customerProfile->state_code ?? $sellerStateCode;
        $taxType = $this->taxEngine->determineTaxType($sellerStateCode, $pos);

        // Calculate order-level discount for pro-rating
        $orderDiscount = (float) ($order->discount_amount ?? 0);
        $orderSubtotal = (float) ($order->subtotal ?? 0);
        if ($orderSubtotal <= 0 && $order->items->count() > 0) {
            $orderSubtotal = (float) $order->items->sum('subtotal');
        }

        $rawItems = [];
        $totalTaxable = 0;
        $totalCgst = 0;
        $totalSgst = 0;
        $totalIgst = 0;
        $totalCess = 0;

        foreach ($order->items as $item) {
            $rateRecord = null;
            if ($item->product_id) {
                $rateRecord = DB::table('gst_product_rates')
                    ->where('product_id', $item->product_id)
                    ->first();
            }

            $gstRate = $rateRecord ? (float) $rateRecord->gst_rate : 18.0;
            $hsn = $rateRecord ? ($rateRecord->hsn_sac_code ?? 'NA') : 'NA';
            $cessPercent = $rateRecord ? (float) ($rateRecord->cess_percent ?? 0) : 0;
            $uom = $rateRecord ? ($rateRecord->uom ?? 'NOS') : 'NOS';

            // Discount calculation: line discount + pro-rated order discount
            $itemSubtotal = (float) $item->subtotal;
            $lineDiscount = (float) ($item->discount_amount ?? 0);
            $proratedOrderDiscount = 0;
            if ($orderDiscount > 0 && $orderSubtotal > 0) {
                $proratedOrderDiscount = round(($itemSubtotal / $orderSubtotal) * $orderDiscount, 2);
            }
            $totalItemDiscount = $lineDiscount + $proratedOrderDiscount;
            $taxableValue = max(0, round($itemSubtotal - $totalItemDiscount, 2));

            $taxResult = $this->taxEngine->calculateItemTax($taxableValue, $gstRate, $taxType, $cessPercent);

            // Determine descriptive variant title, size label, and SKU
            $variantTitle = null;
            $sizeLabel = null;
            if ($item->variant) {
                if (!empty($item->variant->option_values) && is_array($item->variant->option_values)) {
                    $variantTitle = collect($item->variant->option_values)
                        ->map(fn($val, $key) => "{$key}: {$val}")
                        ->implode(', ');
                    $sizeLabel = $item->variant->option_values['Size'] 
                        ?? $item->variant->option_values['size'] 
                        ?? $item->variant->title;
                } elseif (!empty($item->variant->title)) {
                    $variantTitle = $item->variant->title;
                    $sizeLabel = $item->variant->title;
                }
            }

            $sku = $item->sku ?: ($item->variant?->sku ?? null);

            $rawItems[] = [
                'product_id' => $item->product_id,
                'name' => $item->product_name ?? 'Item',
                'description' => $variantTitle ? "{$item->product_name} ({$variantTitle})" : ($item->product_name ?? 'Item'),
                'variant_title' => $variantTitle,
                'size_label' => $sizeLabel,
                'sku' => $sku,
                'hsn_sac_code' => $hsn,
                'quantity' => (float) $item->quantity,
                'uom' => $uom,
                'unit_rate' => (float) $item->unit_price,
                'discount' => $totalItemDiscount,
                'taxable_value' => $taxResult['taxable_value'],
                'gst_rate' => $gstRate,
                'cgst_rate' => $taxType === 'INTRA_STATE' ? $gstRate / 2 : 0,
                'cgst_amount' => $taxResult['cgst_amount'],
                'sgst_rate' => $taxType === 'INTRA_STATE' ? $gstRate / 2 : 0,
                'sgst_amount' => $taxResult['sgst_amount'],
                'igst_rate' => $taxType === 'INTER_STATE' ? $gstRate : 0,
                'igst_amount' => $taxResult['igst_amount'],
                'cess_amount' => $taxResult['cess_amount'],
                'total_amount' => $taxResult['total_amount'],
            ];

            $totalTaxable += $taxResult['taxable_value'];
            $totalCgst += $taxResult['cgst_amount'];
            $totalSgst += $taxResult['sgst_amount'];
            $totalIgst += $taxResult['igst_amount'];
            $totalCess += $taxResult['cess_amount'];
        }

        // Group into Variant Matrix if enabled
        if ($groupingMode === 'matrix') {
            $grouped = [];
            foreach ($rawItems as $item) {
                // Group by Product ID (or Name) + Unit Rate + GST Rate + HSN
                $key = ($item['product_id'] ?? $item['name']) . '_' . $item['unit_rate'] . '_' . $item['gst_rate'] . '_' . $item['hsn_sac_code'];
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'name' => $item['name'],
                        'description' => $item['name'],
                        'hsn_sac_code' => $item['hsn_sac_code'],
                        'quantity' => 0,
                        'uom' => $item['uom'],
                        'unit_rate' => $item['unit_rate'],
                        'discount' => 0,
                        'taxable_value' => 0,
                        'gst_rate' => $item['gst_rate'],
                        'cgst_rate' => $item['cgst_rate'],
                        'cgst_amount' => 0,
                        'sgst_rate' => $item['sgst_rate'],
                        'sgst_amount' => 0,
                        'igst_rate' => $item['igst_rate'],
                        'igst_amount' => 0,
                        'cess_amount' => 0,
                        'total_amount' => 0,
                        'size_matrix' => [],
                        'skus' => [],
                        'has_variants' => false,
                    ];
                }

                $grouped[$key]['quantity'] += $item['quantity'];
                $grouped[$key]['discount'] += $item['discount'];
                $grouped[$key]['taxable_value'] += $item['taxable_value'];
                $grouped[$key]['cgst_amount'] += $item['cgst_amount'];
                $grouped[$key]['sgst_amount'] += $item['sgst_amount'];
                $grouped[$key]['igst_amount'] += $item['igst_amount'];
                $grouped[$key]['cess_amount'] += $item['cess_amount'];
                $grouped[$key]['total_amount'] += $item['total_amount'];

                if (!empty($item['sku']) && !in_array($item['sku'], $grouped[$key]['skus'])) {
                    $grouped[$key]['skus'][] = $item['sku'];
                }

                $sizeLabel = $item['size_label'];
                if ($sizeLabel !== null && $sizeLabel !== '') {
                    $grouped[$key]['has_variants'] = true;
                    if (!isset($grouped[$key]['size_matrix'][$sizeLabel])) {
                        $grouped[$key]['size_matrix'][$sizeLabel] = 0;
                    }
                    $grouped[$key]['size_matrix'][$sizeLabel] += $item['quantity'];
                }
            }

            foreach ($grouped as &$g) {
                $g['discount'] = round($g['discount'], 2);
                $g['taxable_value'] = round($g['taxable_value'], 2);
                $g['cgst_amount'] = round($g['cgst_amount'], 2);
                $g['sgst_amount'] = round($g['sgst_amount'], 2);
                $g['igst_amount'] = round($g['igst_amount'], 2);
                $g['cess_amount'] = round($g['cess_amount'], 2);
                $g['total_amount'] = round($g['total_amount'], 2);

                if (!empty($g['size_matrix'])) {
                    $g['matrix_string'] = collect($g['size_matrix'])
                        ->map(fn($qty, $size) => "{$size}: {$qty}")
                        ->implode(', ');
                } else {
                    $g['matrix_string'] = null;
                }
            }

            $items = array_values($grouped);
        } else {
            $items = $rawItems;
        }

        $hsnSummary = $this->taxEngine->generateHsnSummary($rawItems, $taxType);

        $totalBeforeRound = $totalTaxable + $totalCgst + $totalSgst + $totalIgst + $totalCess;
        $roundedTotal = round($totalBeforeRound);
        $roundOff = round($roundedTotal - $totalBeforeRound, 2);

        $invoiceDate = Carbon::parse($order->order_date ?? now());

        // Number and insert in one transaction under the organization's row lock,
        // so concurrent requests cannot allocate the same invoice number.
        $invoice = DB::transaction(function () use ($order, $settings, $invoiceDate, $pos, $totalTaxable, $totalCgst, $totalSgst, $totalIgst, $totalCess, $roundOff, $roundedTotal, $hsnSummary) {
            DB::table('organizations')->where('id', $order->organization_id)->lockForUpdate()->first();

            // Find existing invoice or generate sequential number
            $existingInvoice = DB::table('gst_invoices')->where('order_id', $order->id)->first();
            if ($existingInvoice && !empty($existingInvoice->invoice_number)) {
                $invoiceNumber = $existingInvoice->invoice_number;
            } else {
                $prefix = $settings->invoice_prefix ?? 'INV';
                $invoiceNumber = $this->sequenceService->generateNextInvoiceNumber($order->organization_id, $prefix, $invoiceDate);
            }

            // Synchronize database record with exact computed numbers
            DB::table('gst_invoices')->updateOrInsert(
                ['order_id' => $order->id],
                [
                    'organization_id' => $order->organization_id,
                    'invoice_number' => $invoiceNumber,
                    'invoice_date' => $invoiceDate->toDateString(),
                    'place_of_supply' => $pos,
                    'taxable_value' => $totalTaxable,
                    'cgst_amount' => $totalCgst,
                    'sgst_amount' => $totalSgst,
                    'igst_amount' => $totalIgst,
                    'cess_amount' => $totalCess,
                    'round_off' => $roundOff,
                    'total_amount' => $roundedTotal,
                    'hsn_summary' => json_encode($hsnSummary),
                    'updated_at' => now(),
                ]
            );

            return DB::table('gst_invoices')->where('order_id', $order->id)->first();
        });

        $amountInWords = $this->numberToWords->convert((float) $invoice->total_amount);

        // Generate dynamic UPI QR Code if UPI ID is configured
        $qrCodeImage = null;
        if (!empty($settings->upi_id)) {
            $payeeName = urlencode($settings->trade_name ?: ($settings->legal_name ?: 'Merchant'));
            $amount = number_format((float) $invoice->total_amount, 2, '.', '');
            $note = urlencode('Invoice ' . $invoice->invoice_number);
            $upiPayload = "upi://pay?pa={$settings->upi_id}&pn={$payeeName}&am={$amount}&cu=INR&tn={$note}";

            try {
                $qrService = app(\App\Services\QrCodeService::class);
                $qrCodeImage = $qrService->dataUri($upiPayload, 110);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('UPI QR generation failed: ' . $e->getMessage());
            }
        }

        return [
            'order' => $order,
            'invoice' => $invoice,
            'settings' => $settings,
            'customer' => $order->customer,
            'customerProfile' => $customerProfile,
            'taxType' => $taxType,
            'items' => $items,
            'hsnSummary' => $hsnSummary,
            'amountInWords' => $amountInWords,
            'sellerStateName' => GstStateCodes::getName($sellerStateCode),
            'posStateName' => GstStateCodes::getName($pos),
            'customerStateName' => isset($customerProfile->state_code) ? GstStateCodes::getName($customerProfile->state_code) : null,
            'qrCodeImage' => $qrCodeImage,
            'groupingMode' => $groupingMode,
            'copyType' => 'Original for Recipient',
        ];
    }

    /**
     * Stream the GST invoice PDF directly in browser.
     */
    public function downloadInvoice(Order $order)
    {
        $data = $this->syncAndCalculateInvoice($order);
        $filename = 'Tax_Invoice_' . str_replace('/', '_', $data['invoice']->invoice_number) . '.pdf';

        return $this->pdfService->stream($data, $filename);
    }

    /**
     * Preview endpoint.
     */
    public function previewInvoice(Order $order)
    {
        return $this->downloadInvoice($order);
    }

    /**
     * API endpoint to get invoice details or tax breakdown JSON for an order.
     */
    public function getBreakdown(Order $order)
    {
        $data = $this->syncAndCalculateInvoice($order);
        $invoice = $data['invoice'];

        return response()->json([
            'invoice_number' => $invoice->invoice_number,
            'place_of_supply' => $invoice->place_of_supply,
            'pos_state_name' => $data['posStateName'],
            'tax_type' => $data['taxType'],
            'taxable_value' => (float) $invoice->taxable_value,
            'cgst_amount' => (float) $invoice->cgst_amount,
            'sgst_amount' => (float) $invoice->sgst_amount,
            'igst_amount' => (float) $invoice->igst_amount,
            'total_tax' => (float) ($invoice->cgst_amount + $invoice->sgst_amount + $invoice->igst_amount + $invoice->cess_amount),
            'total_amount' => (float) $invoice->total_amount,
        ]);
    }

    public function generateInvoice(Request $request, Order $order)
    {
        $data = $this->syncAndCalculateInvoice($order);
        return response()->json([
            'message' => 'Invoice generated successfully',
            'invoice' => $data['invoice']
        ]);
    }
}
