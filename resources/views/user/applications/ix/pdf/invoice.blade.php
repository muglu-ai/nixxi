<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Invoice - {{ $invoiceNumber }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
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
            font-family: 'Nunito', 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #2c3e50;
            padding: 6px;
            background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
        }
        .invoice-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 12px;
            border-radius: 8px 8px 0 0;
        }
        .invoice-header h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #2c3e50;
        }
        .invoice-header h2 {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 4px;
            color: #7f8c8d;
        }
        .nixi-logo {
            text-align: right;
            margin-bottom: 8px;
            font-size: 16px;
            font-weight: 700;
            color: #27ae60;
        }
        .two-column {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }
        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 10px;
            font-size: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 0 5px;
        }
        .column:first-child {
            margin-right: 5px;
        }
        .column:last-child {
            margin-left: 5px;
        }
        .section-title {
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 8px;
            color: #2c3e50;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
        }
        .row {
            margin-bottom: 4px;
            font-size: 9px;
        }
        .label {
            font-weight: 700;
            display: inline-block;
            width: 100px;
            color: #34495e;
        }
        .invoice-details {
            margin-bottom: 12px;
        }
        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .invoice-details td {
            padding: 6px;
            border: 1px solid #dee2e6;
            font-size: 9px;
            background: #ffffff;
            font-weight: 600;
        }
        .invoice-details td:first-child {
            font-weight: 700;
            width: 35%;
            background: #e9ecef;
        }
        .description-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .description-table th,
        .description-table td {
            border: 1px solid #dee2e6;
            padding: 7px;
            text-align: left;
            font-size: 9px;
            font-weight: 600;
        }
        .description-table th {
            background: linear-gradient(to bottom, #3498db, #2980b9);
            color: #ffffff;
            font-weight: 700;
        }
        .description-table td {
            background: #ffffff;
            font-weight: 600;
        }
        .text-right {
            text-align: right;
        }
        .amount-section {
            margin-top: 10px;
            font-size: 10px;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 8px;
        }
        .amount-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        .amount-label {
            display: table-cell;
            width: 70%;
            text-align: right;
            padding-right: 8px;
            font-weight: 700;
            color: #2c3e50;
        }
        .amount-value {
            display: table-cell;
            width: 30%;
            text-align: right;
            color: #27ae60;
            font-weight: 700;
        }
        .total-row {
            border-top: 3px solid #3498db;
            border-bottom: 3px solid #3498db;
            padding: 8px 0;
            font-weight: 700;
            font-size: 12px;
            background: #e8f4f8;
        }
        .bank-details {
            margin-top: 10px;
            display: table;
            width: 100%;
        }
        .bank-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 6px;
        }
        .bank-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-left: 6px;
        }
        .bank-box {
            border: 2px solid #27ae60;
            padding: 8px;
            font-size: 8px;
            border-radius: 8px;
            background: #f0f8f4;
        }
        .bank-box .row {
            font-weight: 600;
        }
        .terms {
            margin-top: 10px;
            font-size: 8px;
            line-height: 1.4;
            background: #fff9e6;
            padding: 8px;
            border-radius: 8px;
            border-left: 4px solid #f39c12;
        }
        .terms ol {
            margin-left: 20px;
        }
        .terms li {
            margin-bottom: 4px;
        }
        .purchase-details {
            background: #f0f8ff;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 12px;
            border-left: 4px solid #3498db;
        }
        .purchase-details .row {
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="nixi-logo">
            <div>nixi</div>
            <div style="font-size: 9px; color: #7f8c8d;">Empowering Netizens</div>
        </div>

        <div class="invoice-header">
            <h1>Tax Invoice</h1>
            <h2>Invoice for Internet Exchange (IX) Services (ORIGINAL FOR RECIPIENT)</h2>
        </div>

        <div class="two-column">
            <div class="column">
                <div class="section-title">BUYER</div>
                <div class="row"><span class="label">Name:</span> {{ $companyDetails['legal_name'] ?? $companyDetails['trade_name'] ?? $user->fullname ?? 'N/A' }}</div>
                <div class="row"><span class="label">Address:</span> {{ $companyDetails['pradr']['addr'] ?? $companyDetails['primary_address'] ?? 'N/A' }}</div>
                <div class="row"><span class="label">Attn:</span> {{ $companyDetails['legal_name'] ?? $companyDetails['trade_name'] ?? $user->fullname ?? 'N/A' }}</div>
                <div class="row"><span class="label">GSTIN:</span> {{ $data['gstin'] ?? 'N/A' }}</div>
                <div class="row"><span class="label">PAN:</span> {{ $companyDetails['pan'] ?? $user->pancardno ?? 'N/A' }}</div>
            </div>
            <div class="column">
                <div class="section-title">SELLER</div>
                <div class="row"><span class="label">Name:</span> NATIONAL INTERNET EXCHANGE OF INDIA</div>
                <div class="row"><span class="label">Address:</span> B-901, 9TH FLOOR TOWER B, World Trade Centre, NAUROJI NAGAR, New Delhi, Delhi, 110029</div>
                <div class="row"><span class="label">PAN:</span> AABCN9308A</div>
                <div class="row"><span class="label">CIN:</span> U72900DL2003NPL120999</div>
                <div class="row"><span class="label">GSTIN:</span> 07AABCN9308A1ZT</div>
                <div class="row"><span class="label">HSN:</span> 998319</div>
                <div class="row"><span class="label">Category:</span> Other IT Services N.E.C</div>
            </div>
        </div>

        <div class="invoice-details">
            <table>
                <tr>
                    <td>Invoice No.</td>
                    <td>{{ $invoiceNumber }}</td>
                    <td>Application ID</td>
                    <td>{{ $application->application_id ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Invoice Date</td>
                    <td>{{ $invoiceDate }}</td>
                    <td>Due Date</td>
                    <td>{{ $dueDate }}</td>
                </tr>
                <tr>
                    <td>Place of Supply</td>
                    <td>{{ $companyDetails['state_info']['name'] ?? $companyDetails['state'] ?? 'N/A' }}</td>
                    <td>Reverse Charge Applicable</td>
                    <td>NO</td>
                </tr>
            </table>
        </div>

        <div class="purchase-details">
            <div class="section-title">Purchase Details</div>
            @if(!empty($data['port_selection']))
                <div class="row"><span class="label">Port Capacity:</span> {{ $data['port_selection']['capacity'] ?? 'N/A' }}</div>
                @if(!empty($data['port_selection']['billing_plan']))
                    @php
                        $billingPlan = $data['port_selection']['billing_plan'];
                        $planName = match($billingPlan) {
                            'arc' => 'Annual (ARC)',
                            'mrc' => 'Monthly (MRC)',
                            'quarterly' => 'Quarterly',
                            default => ucfirst($billingPlan)
                        };
                    @endphp
                    <div class="row"><span class="label">Billing Plan:</span> {{ $planName }}</div>
                @endif
            @endif
            @if(!empty($application->assigned_port_capacity))
                <div class="row"><span class="label">Assigned Port:</span> {{ $application->assigned_port_capacity }}@if($application->assigned_port_number) (Port #{{ $application->assigned_port_number }})@endif</div>
            @endif
            @if(!empty($application->assigned_ip))
                <div class="row"><span class="label">Assigned IP:</span> {{ $application->assigned_ip }}</div>
            @endif
            @if(!empty($data['ip_prefix']['count']))
                <div class="row"><span class="label">No. of IP Prefixes:</span> {{ $data['ip_prefix']['count'] }}</div>
            @endif
            @if(!empty($data['location']['name']))
                <div class="row"><span class="label">NIXI Location:</span> {{ $data['location']['name'] }}@if(!empty($data['location']['state'])), {{ $data['location']['state'] }}@endif</div>
            @endif
        </div>

        @php
            // Calculate amounts
            $portAmount = (float) ($data['port_selection']['amount'] ?? 0);
            $applicationFee = (float) ($data['payment']['application_fee'] ?? $applicationPricing->application_fee ?? 1000.00);
            $gstPercentage = (float) ($data['payment']['gst_percentage'] ?? $applicationPricing->gst_percentage ?? 18.00);
            
            // Calculate GST on application fee only (port fee may already include GST or be separate)
            $gstAmount = ($applicationFee * $gstPercentage) / 100;
            $subTotal = $portAmount + $applicationFee;
            $totalWithGst = $subTotal + $gstAmount;
            
            // Round total
            $totalAmount = round($totalWithGst, 2);
        @endphp

        <table class="description-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                @if($portAmount > 0)
                <tr>
                    <td>
                        Port Connectivity Service - {{ $data['port_selection']['capacity'] ?? 'N/A' }}
                        @if(!empty($data['port_selection']['billing_plan']))
                            ({{ match($data['port_selection']['billing_plan']) {
                                'arc' => 'Annual',
                                'mrc' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                default => ucfirst($data['port_selection']['billing_plan'])
                            } }})
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($portAmount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td>IX Application Fee</td>
                    <td class="text-right">{{ number_format($applicationFee, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Subtotal</strong></td>
                    <td class="text-right"><strong>{{ number_format($subTotal, 2) }}</strong></td>
                </tr>
                <tr>
                    <td>IGST ({{ number_format($gstPercentage, 2) }}%)</td>
                    <td class="text-right">{{ number_format($gstAmount, 2) }}</td>
                </tr>
                <tr>
                    <td>Round(+-)</td>
                    <td class="text-right">{{ number_format($totalAmount - $totalWithGst, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td><strong>Total Amount</strong></td>
                    <td class="text-right"><strong>{{ number_format($totalAmount, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="amount-section">
            <div class="amount-row">
                <div class="amount-label">Total Amount in Words:</div>
                <div class="amount-value"></div>
            </div>
            <div style="text-align: right; font-style: italic; margin-top: 4px; font-size: 9px; font-weight: 600;">
                @php
                    $amountInWords = '';
                    if ($totalAmount > 0) {
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
                        $amountInWords = convertToWords($totalAmount, $ones, $tens);
                    }
                @endphp
                (Rupees {{ ucwords($amountInWords ?: 'Zero') }} Only)
            </div>
        </div>

        <div class="bank-details">
            <div class="bank-left">
                <div class="bank-box">
                    <div class="section-title">Bank Details</div>
                    <div class="row"><span class="label">Account Name:</span> National Internet Exchange Of India</div>
                    <div class="row"><span class="label">Bank Name:</span> HDFC Bank</div>
                    <div class="row"><span class="label">Account Type:</span> Current</div>
                    <div class="row"><span class="label">Account Number:</span> 02712320001421</div>
                    <div class="row"><span class="label">IFSC Code:</span> HDFC0000271</div>
                    <div class="row"><span class="label">Branch:</span> Kalkaji, New Delhi - 110019 (India)</div>
                </div>
            </div>
            <div class="bank-right">
                <div class="bank-box">
                    <div class="section-title">Payment Instructions</div>
                    <div style="font-size: 8px; line-height: 1.5;">
                        Please make payment via Cheque / D.D in favour of <strong>National Internet Exchange Of India</strong>, payable at New Delhi.<br><br>
                        Deposit at nearest HDFC branch and acknowledge payment detail to <strong>"billing@nixi.in"</strong>.<br><br>
                        <strong>Note:</strong> Payment must be completed within the due date to avoid service interruption.
                    </div>
                </div>
            </div>
        </div>

        <div class="terms">
            <div class="section-title">Terms & Conditions</div>
            <ol>
                <li>Please Note that the date of receipt of payment in NIXI Bank account shall be treated as the date of payment.</li>
                <li>Payment should be made as per NIXI billing procedure available at www.nixi.in</li>
                <li>Any dispute subject to jurisdiction under the "Delhi Courts only".</li>
                <li>Service will be activated only after successful payment verification.</li>
            </ol>
        </div>
    </div>
</body>
</html>

