<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Invoice</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 4px;
            text-align: left;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .no-border { border: none; }
        .no-border-top { border-top: none; }
        .no-border-bottom { border-bottom: none; }
        .no-border-left { border-left: none; }
        .no-border-right { border-right: none; }
        .header-title { font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 5px; }
        .copy-type { text-align: right; font-size: 10px; font-style: italic; margin-bottom: 5px; }
        .section-title { background-color: #f0f0f0; font-weight: bold; }
        .amount { white-space: nowrap; }
    </style>
</head>
<body>
    <div class="copy-type">{{ $copyType ?? 'Original for Recipient' }}</div>
    <div class="header-title">TAX INVOICE</div>

    <table>
        <tr>
            <td colspan="2" width="50%" valign="top">
                <strong>{{ $settings->legal_name ?? 'Seller Name' }}</strong><br>
                {{ $settings->trade_name ?? '' }}<br>
                {!! nl2br(e($settings->registered_address ?? '')) !!}<br>
                <strong>GSTIN:</strong> {{ $settings->gstin ?? '' }}<br>
                <strong>State:</strong> {{ $sellerStateName ?? '' }} <strong>Code:</strong> {{ $settings->state_code ?? '' }}<br>
                <strong>PAN:</strong> {{ substr($settings->gstin ?? '            ', 2, 10) }}
            </td>
            <td colspan="2" width="50%" valign="top">
                <strong>Invoice No:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Invoice Date:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-M-Y') }}<br>
                <strong>Place of Supply:</strong> {{ $posStateName ?? '' }} ({{ $invoice->place_of_supply ?? '' }})<br>
                <strong>E-Way Bill No:</strong> {{ $invoice->eway_bill_no ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td colspan="2" valign="top">
                <div class="section-title">Billed To</div>
                <strong>{{ $customer->trade_name ?? $customer->name ?? 'Buyer Name' }}</strong><br>
                {!! nl2br(e($customer->billing_address ?? '')) !!}<br>
                <strong>GSTIN/UIN:</strong> {{ $customerProfile->gstin ?? 'URP' }}<br>
                <strong>State:</strong> {{ $customerStateName ?? '' }} <strong>Code:</strong> {{ $customerProfile->state_code ?? '' }}
            </td>
            <td colspan="2" valign="top">
                <div class="section-title">Shipped To</div>
                <strong>{{ $customer->trade_name ?? $customer->name ?? 'Consignee Name' }}</strong><br>
                {!! nl2br(e($customer->shipping_address ?? $customer->billing_address ?? '')) !!}<br>
                <strong>State:</strong> {{ $shippingStateName ?? $customerStateName ?? '' }} <strong>Code:</strong> {{ $shippingStateCode ?? $customerProfile->state_code ?? '' }}
            </td>
        </tr>
    </table>

    <table style="margin-top: 10px;">
        <thead>
            <tr class="section-title text-center">
                <th rowspan="2">S.No.</th>
                <th rowspan="2">Description of Goods / Services</th>
                <th rowspan="2">HSN/SAC</th>
                <th rowspan="2">Qty</th>
                <th rowspan="2">Unit</th>
                <th rowspan="2">Rate</th>
                <th rowspan="2">Discount</th>
                <th rowspan="2">Taxable Value</th>
                @if(($taxType ?? 'INTRA_STATE') === 'INTRA_STATE')
                    <th colspan="2">CGST</th>
                    <th colspan="2">SGST</th>
                @else
                    <th colspan="2">IGST</th>
                @endif
                <th rowspan="2">Total</th>
            </tr>
            <tr class="section-title text-center">
                @if(($taxType ?? 'INTRA_STATE') === 'INTRA_STATE')
                    <th>Rate</th>
                    <th>Amt</th>
                    <th>Rate</th>
                    <th>Amt</th>
                @else
                    <th>Rate</th>
                    <th>Amt</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item['name'] }}</strong>
                        @if(!empty($item['matrix_string']))
                            <div style="font-size: 8px; color: #222; margin-top: 2px;">
                                <strong>Sizes / Qty:</strong>
                                <span style="font-family: 'DejaVu Sans', monospace; background-color: #f4f4f4; padding: 1px 3px; border: 1px solid #ddd; border-radius: 2px;">
                                    [{{ $item['matrix_string'] }}]
                                </span>
                            </div>
                        @elseif(!empty($item['variant_title']))
                            <div style="font-size: 8.5px; color: #444; margin-top: 1px;">{{ $item['variant_title'] }}</div>
                        @endif
                        @if(!empty($item['sku']))
                            <div style="font-size: 7.5px; color: #666; font-family: monospace; margin-top: 1px;">SKU: {{ $item['sku'] }}</div>
                        @elseif(!empty($item['skus']) && count($item['skus']) > 0)
                            <div style="font-size: 7.5px; color: #666; font-family: monospace; margin-top: 1px;">SKUs: {{ implode(', ', array_slice($item['skus'], 0, 5)) }}{{ count($item['skus']) > 5 ? '...' : '' }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item['hsn_sac_code'] }}</td>
                    <td class="text-center">{{ $item['quantity'] }}</td>
                    <td class="text-center">{{ $item['uom'] }}</td>
                    <td class="text-right amount">{{ number_format($item['unit_rate'], 2) }}</td>
                    <td class="text-right amount">{{ number_format($item['discount'] ?? 0, 2) }}</td>
                    <td class="text-right amount">{{ number_format($item['taxable_value'], 2) }}</td>
                    @if(($taxType ?? 'INTRA_STATE') === 'INTRA_STATE')
                        <td class="text-right">{{ $item['cgst_rate'] }}%</td>
                        <td class="text-right amount">{{ number_format($item['cgst_amount'], 2) }}</td>
                        <td class="text-right">{{ $item['sgst_rate'] }}%</td>
                        <td class="text-right amount">{{ number_format($item['sgst_amount'], 2) }}</td>
                    @else
                        <td class="text-right">{{ $item['igst_rate'] }}%</td>
                        <td class="text-right amount">{{ number_format($item['igst_amount'], 2) }}</td>
                    @endif
                    <td class="text-right amount">{{ number_format($item['total_amount'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-bold">
                <td colspan="7" class="text-right">Total</td>
                <td class="text-right amount">{{ number_format($invoice->taxable_value, 2) }}</td>
                @if(($taxType ?? 'INTRA_STATE') === 'INTRA_STATE')
                    <td></td>
                    <td class="text-right amount">{{ number_format($invoice->cgst_amount, 2) }}</td>
                    <td></td>
                    <td class="text-right amount">{{ number_format($invoice->sgst_amount, 2) }}</td>
                @else
                    <td></td>
                    <td class="text-right amount">{{ number_format($invoice->igst_amount, 2) }}</td>
                @endif
                <td class="text-right amount">{{ number_format($invoice->total_amount - $invoice->round_off, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <table style="margin-top: 10px;">
        <tr>
            <td width="60%" valign="top" class="no-border-right">
                <strong>Amount in Words:</strong><br>
                <em>{{ $amountInWords ?? '' }}</em>
                <br><br>
                <table class="no-border" style="width: 100%;">
                    <tr>
                        <td class="no-border" style="vertical-align: top; width: 68%;">
                            <strong>Bank Details (NEFT/RTGS):</strong><br>
                            Bank Name: {{ $settings->bank_name ?? '' }}<br>
                            Account No: {{ $settings->bank_account_no ?? '' }}<br>
                            IFSC Code: {{ $settings->bank_ifsc ?? '' }}<br>
                            Branch: {{ $settings->bank_branch ?? '' }}
                            @if(!empty($settings->upi_id))
                                <br><strong>UPI ID:</strong> {{ $settings->upi_id }}
                            @endif
                        </td>
                        @if(!empty($qrCodeImage))
                        <td class="no-border text-center" style="vertical-align: top; width: 32%;">
                            <img src="{{ $qrCodeImage }}" alt="UPI QR" width="95" height="95" style="border: 1px solid #ccc; padding: 2px;">
                            <div style="font-size: 8px; color: #555; margin-top: 2px;">Scan & Pay via UPI</div>
                        </td>
                        @endif
                    </tr>
                </table>
            </td>
            <td width="40%" valign="top" class="no-border-left">
                <table class="no-border">
                    <tr>
                        <td class="no-border text-right font-bold">Total Taxable Amount:</td>
                        <td class="no-border text-right amount">₹ {{ number_format($invoice->taxable_value, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="no-border text-right font-bold">Add: Total Taxes:</td>
                        <td class="no-border text-right amount">₹ {{ number_format($invoice->cgst_amount + $invoice->sgst_amount + $invoice->igst_amount + $invoice->cess_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="no-border text-right font-bold">Round Off:</td>
                        <td class="no-border text-right amount">₹ {{ number_format($invoice->round_off, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="no-border text-right font-bold">Net Payable Amount:</td>
                        <td class="no-border text-right amount font-bold" style="font-size: 12px;">₹ {{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if(!empty($hsnSummary))
    <div style="margin-top: 10px;">
        <div class="section-title" style="padding: 4px; border: 1px solid #000; border-bottom: none;">HSN / SAC Summary</div>
        <table>
            <thead>
                <tr class="text-center">
                    <th>HSN / SAC</th>
                    <th>Taxable Value</th>
                    @if(($taxType ?? 'INTRA_STATE') === 'INTRA_STATE')
                        <th>CGST Amount</th>
                        <th>SGST Amount</th>
                    @else
                        <th>IGST Amount</th>
                    @endif
                    <th>Total Tax Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hsnSummary as $hsn)
                    <tr>
                        <td class="text-center">{{ $hsn['hsn_sac_code'] }}</td>
                        <td class="text-right amount">{{ number_format($hsn['taxable_value'], 2) }}</td>
                        @if(($taxType ?? 'INTRA_STATE') === 'INTRA_STATE')
                            <td class="text-right amount">{{ number_format($hsn['cgst_amount'], 2) }}</td>
                            <td class="text-right amount">{{ number_format($hsn['sgst_amount'], 2) }}</td>
                        @else
                            <td class="text-right amount">{{ number_format($hsn['igst_amount'], 2) }}</td>
                        @endif
                        <td class="text-right amount">{{ number_format($hsn['total_tax'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <table style="margin-top: 10px;">
        <tr>
            <td width="50%" valign="top">
                <strong>Terms and Conditions:</strong><br>
                <div style="font-size: 9px; white-space: pre-wrap;">{{ $settings->terms_conditions ?? '1. Goods once sold will not be taken back.' }}</div>
            </td>
            <td width="50%" valign="bottom" class="text-right">
                <div style="margin-bottom: 40px;">For <strong>{{ $settings->legal_name ?? 'Company Name' }}</strong></div>
                <div>Authorized Signatory</div>
            </td>
        </tr>
    </table>
</body>
</html>
