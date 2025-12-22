<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Invoice - {{ $invoiceNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            size: A4;
            margin: 6mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }
        .header-section {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .header-left {
            display: table-cell;
            width: 70%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 30%;
            vertical-align: top;
            text-align: right;
            font-size: 9px;
            font-weight: bold;
        }
        .nixi-logo {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 2px;
        }
        .nixi-subtitle {
            font-size: 8px;
            color: #666;
            text-transform: lowercase;
        }
        .tax-invoice-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 8px;
        }
        .buyer-seller-section {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            border: 1px solid #000;
        }
        .buyer-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 6px;
            border-right: 1px solid #000;
        }
        .seller-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 6px;
        }
        .section-label {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 4px;
        }
        .detail-row {
            font-size: 9px;
            margin-bottom: 3px;
            line-height: 1.4;
        }
        .detail-label {
            font-weight: bold;
            display: inline-block;
            min-width: 80px;
        }
        .invoice-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9px;
        }
        .invoice-info-table td {
            padding: 4px;
            border: 1px solid #000;
        }
        .invoice-info-table td:first-child {
            font-weight: bold;
            width: 25%;
        }
        .particulars-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9px;
        }
        .particulars-table th,
        .particulars-table td {
            padding: 5px;
            border: 1px solid #000;
            text-align: left;
        }
        .particulars-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .particulars-table td {
            text-align: center;
        }
        .particulars-table td:nth-child(5),
        .particulars-table td:nth-child(6) {
            text-align: right;
        }
        .amount-summary {
            width: 100%;
            margin-bottom: 8px;
            font-size: 9px;
        }
        .amount-row {
            display: table;
            width: 100%;
            margin-bottom: 2px;
        }
        .amount-label {
            display: table-cell;
            width: 80%;
            text-align: right;
            padding-right: 10px;
            font-weight: bold;
        }
        .amount-value {
            display: table-cell;
            width: 20%;
            text-align: right;
            font-weight: bold;
        }
        .total-row {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 4px 0;
            font-size: 10px;
        }
        .amount-in-words {
            font-size: 9px;
            font-style: italic;
            margin-bottom: 8px;
            text-align: right;
        }
        .payment-section {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .payment-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 5px;
        }
        .payment-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-left: 5px;
        }
        .payment-box {
            border: 1px solid #000;
            padding: 6px;
            font-size: 8px;
            line-height: 1.4;
        }
        .payment-title {
            font-weight: bold;
            margin-bottom: 4px;
        }
        .or-separator {
            text-align: center;
            font-weight: bold;
            margin: 4px 0;
        }
        .terms-section {
            margin-bottom: 8px;
            font-size: 8px;
            line-height: 1.4;
        }
        .terms-title {
            font-weight: bold;
            margin-bottom: 4px;
        }
        .terms-list {
            margin-left: 15px;
        }
        .terms-list li {
            margin-bottom: 2px;
        }
        .signature-section {
            margin-top: 8px;
            font-size: 8px;
            text-align: center;
        }
        .signature-row {
            margin-bottom: 3px;
        }
        .promo-banner {
            background-color: #0066cc;
            color: #fff;
            padding: 8px;
            margin: 8px 0;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-left">
            <div class="nixi-logo">nixi</div>
            <div class="nixi-subtitle">national internet exchange of india</div>
            <div class="tax-invoice-title">Tax Invoice</div>
        </div>
        <div class="header-right">
            ORIGINAL FOR RECEIPIENT
        </div>
    </div>

    <!-- Buyer and Seller Section -->
    <div class="buyer-seller-section">
        <div class="buyer-column">
            <div class="section-label">Buyer:</div>
            <div class="detail-row">
                <span class="detail-label">Buyer:</span>
                {{ $buyerDetails['company_name'] ?? $user->fullname ?? 'N/A' }}
            </div>
            <div class="detail-row">
                <span class="detail-label">Address:</span>
                {{ $buyerDetails['address'] ?? 'N/A' }}
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                {{ $buyerDetails['phone'] ?? $user->mobile ?? 'N/A' }}
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                {{ $buyerDetails['email'] ?? $user->email ?? 'N/A' }}
            </div>
            <div class="detail-row">
                <span class="detail-label">GSTIN/UIN:</span>
                {{ $buyerDetails['gstin'] ?? ($data['gstin'] ?? 'N/A') }}
            </div>
            <div class="detail-row">
                <span class="detail-label">PAN:</span>
                {{ $buyerDetails['pan'] ?? $user->pancardno ?? 'N/A' }}
            </div>
            <div class="detail-row">
                <span class="detail-label">Attn:</span>
                {{ $attnName ?? ($buyerDetails['company_name'] ?? $user->fullname ?? 'N/A') }}
            </div>
            <div class="detail-row">
                <span class="detail-label">Place of Supply:</span>
                {{ $placeOfSupply ?? 'N/A' }}
            </div>
        </div>
        <div class="seller-column">
            <div class="section-label">Seller:</div>
            <div class="detail-row">
                <span class="detail-label">Seller:</span>
                National Internet Exchange of India
            </div>
            <div class="detail-row">
                <span class="detail-label">PAN:</span>
                AABCN9308A
            </div>
            <div class="detail-row">
                <span class="detail-label">CIN:</span>
                U72900DL2003NPL120999
            </div>
            <div class="detail-row">
                <span class="detail-label">GSTIN:</span>
                09AABCN9308A1ZP
            </div>
            <div class="detail-row">
                <span class="detail-label">HSN CODE:</span>
                998319
            </div>
            <div class="detail-row">
                <span class="detail-label">Category of Service:</span>
                Other Information Technology Services N.E.C.
            </div>
        </div>
    </div>

    <!-- Invoice Information Table -->
    <table class="invoice-info-table">
        <tr>
            <td>Invoice No:</td>
            <td>{{ $invoiceNumber }}</td>
            <td>Customer Id:</td>
            <td>{{ $application->customer_id ?? $application->membership_id ?? $application->application_id ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Invoice Date (dd/mm/yyyy):</td>
            <td>{{ $invoiceDate }}</td>
            <td>Invoice Due Date (dd/mm/yyyy):</td>
            <td>{{ $dueDate }}</td>
        </tr>
    </table>

    <!-- Particulars Table -->
    @php
        // Get invoice amounts
        $amount = $invoice ? (float)$invoice->amount : 0;
        $gstAmount = $invoice ? (float)$invoice->gst_amount : 0;
        $totalAmount = $invoice ? (float)$invoice->total_amount : 0;
        
        // Get billing period dates
        $billingStartDate = null;
        $billingEndDate = null;
        $billingPeriodText = '';
        
        if ($invoice && $invoice->invoice_date && $invoice->due_date) {
            $billingStartDate = \Carbon\Carbon::parse($invoice->invoice_date);
            $billingEndDate = \Carbon\Carbon::parse($invoice->due_date);
            
            // If due date is after invoice date, use that range
            if ($billingEndDate->gt($billingStartDate)) {
                $billingPeriodText = $billingStartDate->format('d/m/Y') . ' to ' . $billingEndDate->format('d/m/Y');
            } else {
                // Calculate based on billing cycle
                $billingCycle = $application->billing_cycle ?? ($data['port_selection']['billing_plan'] ?? 'monthly');
                $startDate = $application->service_activation_date 
                    ? \Carbon\Carbon::parse($application->service_activation_date)
                    : $billingStartDate;
                
                // Get last paid invoice to determine start date
                $lastInvoice = \App\Models\Invoice::where('application_id', $application->id)
                    ->where('status', 'paid')
                    ->where('id', '<', $invoice->id)
                    ->latest('invoice_date')
                    ->first();
                
                if ($lastInvoice && $lastInvoice->due_date) {
                    $startDate = \Carbon\Carbon::parse($lastInvoice->due_date);
                }
                
                switch ($billingCycle) {
                    case 'annual':
                        $endDate = $startDate->copy()->addYear();
                        break;
                    case 'quarterly':
                        $endDate = $startDate->copy()->addMonths(3);
                        break;
                    case 'monthly':
                    default:
                        $endDate = $startDate->copy()->addMonth();
                        break;
                }
                
                $billingPeriodText = $startDate->format('d/m/Y') . ' to ' . $endDate->format('d/m/Y');
            }
        } else {
            $billingPeriodText = $invoiceDate . ' to ' . $dueDate;
        }
        
        // Get port capacity
        $portCapacity = $application->assigned_port_capacity ?? ($data['port_selection']['capacity'] ?? 'N/A');
        
        // Format port capacity for display (e.g., "1000 Mbps")
        if (strpos($portCapacity, 'Gig') !== false) {
            $portCapacity = str_replace('Gig', ' Gbps', $portCapacity);
        } elseif (strpos($portCapacity, 'Mbps') === false && is_numeric(str_replace([' ', 'Mbps', 'Gbps'], '', $portCapacity))) {
            $portCapacity = $portCapacity . ' Mbps';
        }
        
        // Determine GST type (IGST vs CGST+SGST)
        $isDelhi = strtolower($placeOfSupply ?? '') === 'delhi' || strtolower($placeOfSupply ?? '') === 'new delhi';
        if ($isDelhi) {
            $cgstAmount = round($gstAmount / 2, 2);
            $sgstAmount = round($gstAmount / 2, 2);
        } else {
            $cgstAmount = 0;
            $sgstAmount = 0;
        }
    @endphp

    <table class="particulars-table">
        <thead>
            <tr>
                <th>S.N.o.</th>
                <th>Particulars</th>
                <th>Quantity</th>
                <th>Peering Capacity</th>
                <th>Peering Charges</th>
                <th>Amount()</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Port Charges For {{ $billingPeriodText }}</td>
                <td>1</td>
                <td>{{ $portCapacity }}</td>
                <td>{{ number_format($amount, 2) }}</td>
                <td>{{ number_format($amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Amount Summary -->
    <div class="amount-summary">
        @if($isDelhi)
            <div class="amount-row">
                <div class="amount-label">CGST(9%):</div>
                <div class="amount-value">{{ number_format($cgstAmount, 2) }}</div>
            </div>
            <div class="amount-row">
                <div class="amount-label">SGST(9%):</div>
                <div class="amount-value">{{ number_format($sgstAmount, 2) }}</div>
            </div>
        @else
            <div class="amount-row">
                <div class="amount-label">IGST(18%):</div>
                <div class="amount-value">{{ number_format($gstAmount, 2) }}</div>
            </div>
        @endif
        <div class="amount-row total-row">
            <div class="amount-label">Total Amount Due:</div>
            <div class="amount-value">{{ number_format($totalAmount, 2) }}</div>
        </div>
    </div>

    <!-- Amount in Words -->
    <div class="amount-in-words">
        @php
            $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
            $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
            
            function convertToWords($num, $ones, $tens) {
                if ($num < 20) return $ones[$num];
                if ($num < 100) return $tens[floor($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
                if ($num < 1000) return $ones[floor($num / 100)] . ' Hundred' . ($num % 100 ? ' ' . convertToWords($num % 100, $ones, $tens) : '');
                if ($num < 100000) return convertToWords(floor($num / 1000), $ones, $tens) . ' Thousand' . ($num % 1000 ? ' ' . convertToWords($num % 1000, $ones, $tens) : '');
                if ($num < 10000000) return convertToWords(floor($num / 100000), $ones, $tens) . ' Lakh' . ($num % 100000 ? ' ' . convertToWords($num % 100000, $ones, $tens) : '');
                return convertToWords(floor($num / 10000000), $ones, $tens) . ' Crore' . ($num % 10000000 ? ' ' . convertToWords($num % 10000000, $ones, $tens) : '');
            }
            
            $amountInWords = '';
            if ($totalAmount > 0) {
                $amountInWords = convertToWords((int)$totalAmount, $ones, $tens);
                // Handle decimal part
                $decimalPart = round(($totalAmount - (int)$totalAmount) * 100);
                if ($decimalPart > 0) {
                    $amountInWords .= ' and ' . convertToWords($decimalPart, $ones, $tens) . ' Paise';
                }
            }
        @endphp
        <strong>Rupees: {{ ucwords($amountInWords ?: 'Zero') }} Only</strong>
    </div>

    <!-- Promotional Banner -->
    <div class="promo-banner">
        BUILD YOUR DIGITAL IDENTITY WITH .IN TRUSTED BY 3 MILLION USERS Get a global reach with .IN
    </div>

    <!-- Payment Instructions -->
    <div class="payment-section">
        <div class="payment-left">
            <div class="payment-box">
                <div class="payment-title">Please pay as per following instructions:</div>
                <div style="margin-bottom: 4px;">Online Payment/Internet Banking/Credit Card/Debit Card.</div>
                <div class="detail-row"><span class="detail-label">Bank Name:</span> AXIS Bank Ltd.</div>
                <div class="detail-row"><span class="detail-label">IFSC Code:</span> UTIB0000007</div>
                <div class="detail-row"><span class="detail-label">MICR No:</span> 110211002</div>
                <div class="detail-row"><span class="detail-label">Account Name:</span> National Internet Exchange of India.</div>
                <div class="detail-row"><span class="detail-label">Account Type:</span> Savings Bank Account</div>
                <div class="detail-row"><span class="detail-label">Account Number:</span> 922010006414634</div>
                <div class="detail-row"><span class="detail-label">Branch:</span> Statesman House, 148, Barakhamba Road, New Delhi-110001 (India)</div>
            </div>
        </div>
        <div class="payment-right">
            <div class="or-separator">OR</div>
            <div class="payment-box">
                <div style="margin-bottom: 4px;">Make Cheque/Online PG / D.D in Favour of</div>
                <div style="font-weight: bold; margin-bottom: 4px;">National Internet Exchange of India</div>
                <div style="margin-bottom: 4px;">Payable to New Delhi and deposit it in your nearest ICICI branch and acknowledge the payment detail to 'ixbilling@nixi.in'.</div>
                <div style="margin-top: 4px;">Pay through online portal via link</div>
                <div style="color: #0066cc; margin-top: 2px;">https://payonline.nixi.in/online-payment</div>
            </div>
        </div>
    </div>

    <!-- Terms & Conditions -->
    <div class="terms-section">
        <div class="terms-title">Terms & Conditions:-</div>
        <ol class="terms-list">
            <li>Please Note that the date of receipt of payment in NIXI Bank account shall be treated as the date of payment.</li>
            <li>Payment should be made as per NIXI Exchange billing procedure.</li>
            <li>Any dispute subject to jurisdiction under the 'Delhi Courts only'.</li>
        </ol>
    </div>

    <!-- Digital Signature Section -->
    <div class="signature-section">
        <div class="signature-row">
            <div style="display: inline-block; width: 48%; text-align: left;">
                <div style="margin-bottom: 20px;">[QR Code Placeholder]</div>
            </div>
            <div style="display: inline-block; width: 48%; text-align: right;">
                <div style="margin-bottom: 20px;">[eSign Logo Placeholder]</div>
            </div>
        </div>
        <div class="signature-row">
            <div><strong>Digitally Signed by NIC-IRP on:</strong> {{ $invoice->created_at ? $invoice->created_at->format('Y-m-d\TH:i') : now('Asia/Kolkata')->format('Y-m-d\TH:i') }}</div>
        </div>
        <div class="signature-row">
            <div><strong>IRN Number:</strong> {{ strtoupper(substr(md5($invoiceNumber . $invoice->id . $invoice->created_at), 0, 64)) }}</div>
        </div>
        <div class="signature-row">
            <div><strong>Acknowledge Number:</strong> {{ $invoice->id . str_pad($invoice->application_id, 10, '0', STR_PAD_LEFT) }}</div>
        </div>
        <div class="signature-row" style="margin-top: 8px;">
            <div><strong>Seller's Address:</strong> National Internet Exchange of India, H-223, Sector-63, Nodia, Gautam Buddha Nagar, Uttar Pradesh, 201301 India</div>
        </div>
    </div>
</body>
</html>
