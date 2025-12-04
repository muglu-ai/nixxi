<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NIXI IX Agreement Template</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #1f2937; margin: 24px; }
        h1 { font-size: 20px; text-align: center; margin-bottom: 6px; }
        h2 { font-size: 16px; margin-top: 24px; margin-bottom: 12px; }
        p { line-height: 1.5; margin-bottom: 8px; }
        ol { padding-left: 20px; }
        .signature-block { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-box { width: 48%; border-top: 1px solid #9ca3af; padding-top: 8px; text-align: center; font-size: 12px; }
        .meta { font-size: 11px; color: #6b7280; margin-bottom: 24px; }
    </style>
</head>
<body>
    <h1>Peering Agreement Template</h1>
    <p class="meta">Generated on {{ $generatedAt ?? now()->format('d M Y, h:i A') }}</p>

    <p>This template should be executed on the applicant’s letterhead and signed by the authorised representative. Submit the signed copy as part of your IX application.</p>

    <h2>1. Parties</h2>
    <p>This agreement is entered into between:</p>
    <ol>
        <li><strong>National Internet Exchange of India (NIXI)</strong>, having its registered office at 8B, 6th Floor, Hansalya Building, 15 Barakhamba Road, Connaught Place, New Delhi – 110001, India; and</li>
        <li><strong>{{ $companyName ?? '[Applicant Organisation Name]' }}</strong>@if(isset($companyAddress) && $companyAddress), having its registered office at {{ $companyAddress }}.@else, having its registered office at [Registered Address].@endif</li>
    </ol>

    <h2>2. Purpose</h2>
    <p>The applicant requests membership at the {{ config('app.name', 'NIXI') }} Internet Exchange for the purpose of exchanging Internet traffic via Border Gateway Protocol (BGP) within the exchange fabric.</p>

    <h2>3. Applicant Undertakings</h2>
    <ol>
        <li>Operate at least one publicly routable Autonomous System Number (ASN) for peering activities.</li>
        <li>Advertise only authorised and properly registered IP prefixes.</li>
        <li>Ensure 24x7 contact availability for technical coordination and incident response.</li>
        <li>Abide by NIXI’s Memorandum & Articles of Association, policies, circulars, and technical guidelines.</li>
        <li>Clear all applicable membership and port charges when invoiced by NIXI.</li>
    </ol>

    <h2>4. Fees</h2>
    <p>The applicant agrees to pay the membership fee of INR 1,000 plus applicable taxes, and the selected port charges (ARC/MRC/Quarterly) as per the published tariff at the time of onboarding.</p>

    <h2>5. Term & Termination</h2>
    <p>This agreement remains in force until terminated by either party with 30 days’ written notice. NIXI reserves the right to suspend services for policy violations or non-payment.</p>

    <h2>6. Acceptance</h2>
    <p>Authorised representatives confirm that the information furnished in the IX application is accurate and that they have the authority to bind their respective organisations.</p>

    <div class="signature-block">
        <div class="signature-box">
            Authorised Signatory<br>
            National Internet Exchange of India
        </div>
        <div class="signature-box">
            Authorised Signatory<br>
            Applicant Organisation
        </div>
    </div>
</body>
</html>

