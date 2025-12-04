<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IX Application - {{ $application->application_id }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
                font-family: DejaVu Sans, sans-serif;
                font-size: 9px;
                color: #6b7280;
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: DejaVu Sans, 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #2c3e50;
            background: linear-gradient(to bottom, #f0f4f8 0%, #ffffff 100%);
            padding: 10px;
        }
        
        .application-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 2px solid #e5e7eb;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 4px solid #2563eb;
            border-radius: 8px 8px 0 0;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            padding: 20px;
            margin: -20px -20px 25px -20px;
        }
        
        .header h1 {
            font-size: 26px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .header .meta {
            font-size: 11px;
            color: #64748b;
            margin-top: 8px;
        }
        
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px;
            border-left: 5px solid #3b82f6;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        
        .section-title {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 12px 18px;
            font-weight: 700;
            margin: -15px -15px 15px -15px;
            font-size: 14px;
            border-radius: 10px 10px 0 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        table td {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
        }
        
        table td.label {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            font-weight: 700;
            color: #475569;
            width: 35%;
            border-right: 2px solid #cbd5e1;
        }
        
        table td.value {
            color: #1e293b;
            font-weight: 500;
        }
        
        .document-section {
            margin-top: 25px;
            margin-bottom: 25px;
            page-break-inside: avoid;
            background: white;
            border-radius: 10px;
            padding: 15px;
            border: 2px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .document-title {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 15px;
            color: #1e40af;
            padding: 10px 15px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .document-image {
            max-width: 100%;
            height: auto;
            margin-bottom: 15px;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: block;
            page-break-inside: avoid;
        }
        
        .document-image:last-child {
            margin-bottom: 0;
        }
        
        .no-image-message {
            padding: 30px;
            text-align: center;
            border: 2px dashed #cbd5e1;
            background: #f8fafc;
            border-radius: 8px;
            color: #64748b;
            font-style: italic;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 3px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #64748b;
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin: 30px -20px -20px -20px;
        }
    </style>
</head>
<body>
    <div class="application-container">
        <div class="header">
            <h1>IX Application</h1>
            <div class="meta">
                <strong>Application ID:</strong> {{ $application->application_id }} | 
                <strong>Generated:</strong> {{ now('Asia/Kolkata')->format('d M Y, h:i A') }}
            </div>
        </div>

        <!-- Personal Information -->
        <div class="section">
            <div class="section-title">Personal Information</div>
            <table>
                <tr>
                    <td class="label">Full Name</td>
                    <td class="value">{{ $user->fullname }}</td>
                </tr>
                <tr>
                    <td class="label">Email</td>
                    <td class="value">{{ $user->email }}</td>
                </tr>
                <tr>
                    <td class="label">Mobile</td>
                    <td class="value">{{ $user->mobile }}</td>
                </tr>
                <tr>
                    <td class="label">PAN Card Number</td>
                    <td class="value">{{ $user->pancardno }}</td>
                </tr>
                <tr>
                    <td class="label">Date of Birth</td>
                    <td class="value">{{ $user->dateofbirth?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Registration ID</td>
                    <td class="value">{{ $user->registrationid }}</td>
                </tr>
            </table>
        </div>

        <!-- Company/Business Information -->
        @if($gstVerification || $kyc)
        <div class="section">
            <div class="section-title">Company/Business Information</div>
            <table>
                @if($gstVerification)
                <tr>
                    <td class="label">Legal Name</td>
                    <td class="value">{{ $gstVerification->legal_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Trade Name</td>
                    <td class="value">{{ $gstVerification->trade_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">GSTIN</td>
                    <td class="value">{{ $gstVerification->gstin ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">PAN</td>
                    <td class="value">{{ $gstVerification->pan ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">State</td>
                    <td class="value">{{ $gstVerification->state ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Primary Address</td>
                    <td class="value">{{ $gstVerification->primary_address ?? '—' }}</td>
                </tr>
                @endif
                @if($kyc)
                <tr>
                    <td class="label">UDYAM Number</td>
                    <td class="value">{{ $kyc->udyam_number ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">CIN</td>
                    <td class="value">{{ $kyc->cin ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Contact Person</td>
                    <td class="value">{{ $kyc->contact_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Contact Email</td>
                    <td class="value">{{ $kyc->contact_email ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Contact Mobile</td>
                    <td class="value">{{ $kyc->contact_mobile ?? '—' }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif

        <!-- Application Details -->
        <div class="section">
            <div class="section-title">Application Details</div>
            <table>
                @if(isset($data['member_type']))
                <tr>
                    <td class="label">Member Type</td>
                    <td class="value">{{ $data['member_type'] }}</td>
                </tr>
                @endif
                @if(isset($data['location']))
                <tr>
                    <td class="label">NIXI Location</td>
                    <td class="value">{{ $data['location']['name'] ?? '—' }} ({{ ucfirst($data['location']['node_type'] ?? '') }} - {{ $data['location']['state'] ?? '' }})</td>
                </tr>
                <tr>
                    <td class="label">Node Type</td>
                    <td class="value">{{ ucfirst($data['location']['node_type'] ?? '—') }}</td>
                </tr>
                <tr>
                    <td class="label">State</td>
                    <td class="value">{{ $data['location']['state'] ?? '—' }}</td>
                </tr>
                @endif
                @if(isset($data['port_selection']))
                <tr>
                    <td class="label">Port Capacity</td>
                    <td class="value">{{ $data['port_selection']['capacity'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Billing Plan</td>
                    <td class="value">{{ strtoupper($data['port_selection']['billing_plan'] ?? '—') }}</td>
                </tr>
                <tr>
                    <td class="label">Amount</td>
                    <td class="value">₹{{ number_format($data['port_selection']['amount'] ?? 0, 2) }}</td>
                </tr>
                @endif
                @if(isset($data['ip_prefix']))
                <tr>
                    <td class="label">Number of IP Prefixes</td>
                    <td class="value">{{ $data['ip_prefix']['count'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">IP Prefix Source</td>
                    <td class="value">{{ strtoupper($data['ip_prefix']['source'] ?? '—') }}</td>
                </tr>
                @if(isset($data['ip_prefix']['provider']))
                <tr>
                    <td class="label">IP Prefix Provider</td>
                    <td class="value">{{ $data['ip_prefix']['provider'] }}</td>
                </tr>
                @endif
                @endif
                @if(isset($data['peering']))
                <tr>
                    <td class="label">Pre-NIXI Peering Connectivity</td>
                    <td class="value">{{ ucfirst($data['peering']['pre_nixi_connectivity'] ?? '—') }}</td>
                </tr>
                <tr>
                    <td class="label">ASN Number</td>
                    <td class="value">{{ $data['peering']['asn_number'] ?? '—' }}</td>
                </tr>
                @endif
                @if(isset($data['router_details']))
                @if($data['router_details']['height_u'] ?? null)
                <tr>
                    <td class="label">Router Height (U)</td>
                    <td class="value">{{ $data['router_details']['height_u'] }}</td>
                </tr>
                @endif
                @if($data['router_details']['make_model'] ?? null)
                <tr>
                    <td class="label">Router Make & Model</td>
                    <td class="value">{{ $data['router_details']['make_model'] }}</td>
                </tr>
                @endif
                @if($data['router_details']['serial_number'] ?? null)
                <tr>
                    <td class="label">Router Serial Number</td>
                    <td class="value">{{ $data['router_details']['serial_number'] }}</td>
                </tr>
                @endif
                @endif
            </table>
        </div>

        <!-- Payment Information -->
        @if(isset($data['payment']))
        <div class="section">
            <div class="section-title">Payment Information</div>
            <table>
                <tr>
                    <td class="label">Billing Plan</td>
                    <td class="value">{{ strtoupper($data['payment']['plan'] ?? '—') }}</td>
                </tr>
                <tr>
                    <td class="label">Amount</td>
                    <td class="value">₹{{ number_format($data['payment']['amount'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Currency</td>
                    <td class="value">{{ $data['payment']['currency'] ?? 'INR' }}</td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td class="value">{{ ucfirst($data['payment']['status'] ?? '—') }}</td>
                </tr>
            </table>
        </div>
        @endif

        <!-- Uploaded Documents -->
        @if(isset($pdfImages) && !empty($pdfImages))
        <div class="section">
            <div class="section-title">Uploaded Documents</div>
            @foreach($pdfImages as $field => $docData)
            <div class="document-section">
                <div class="document-title">{{ $docData['name'] }}</div>
                @if(isset($docData['images']) && !empty($docData['images']))
                    @foreach($docData['images'] as $index => $image)
                    <img src="data:image/png;base64,{{ $image }}" class="document-image" alt="{{ $docData['name'] }} - Page {{ $index + 1 }}">
                    @if($index < count($docData['images']) - 1)
                    <div style="text-align: center; margin: 10px 0; color: #64748b; font-size: 10px; font-style: italic;">Page {{ $index + 1 }}</div>
                    @endif
                    @endforeach
                @else
                <div style="padding: 40px; text-align: center; border: 3px dashed #cbd5e1; background: #f8fafc; border-radius: 10px; color: #64748b;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📄</div>
                    <div style="font-weight: 700; font-size: 14px; margin-bottom: 8px; color: #475569;">{{ $docData['name'] }}</div>
                    <div style="font-size: 11px;">PDF Document Attached</div>
                    <div style="font-size: 10px; margin-top: 10px; color: #94a3b8;">File: {{ basename($docData['path'] ?? 'N/A') }}</div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        <div class="footer">
            <p><strong>National Internet Exchange of India (NIXI)</strong></p>
            <p>This document is computer-generated and does not require a signature.</p>
        </div>
    </div>
</body>
</html>
