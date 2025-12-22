<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Generated - {{ $invoiceNumber }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: #ffffff; margin: 0; font-size: 24px;">NIXI</h1>
        <p style="color: #f0f0f0; margin: 5px 0 0 0; font-size: 14px;">Empowering Netizens</p>
    </div>
    
    <div style="background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px;">
        <h2 style="color: #2c3e50; margin-top: 0;">Invoice Generated</h2>
        
        <p>Dear {{ $userName }},</p>
        
        <p>Your invoice has been generated for your IX application <strong>{{ $applicationId }}</strong>.</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Invoice Number:</strong> {{ $invoiceNumber }}</p>
            <p style="margin: 5px 0;"><strong>Application ID:</strong> {{ $applicationId }}</p>
            <p style="margin: 5px 0;"><strong>Total Amount:</strong> ₹{{ number_format($totalAmount, 2) }}</p>
            <p style="margin: 5px 0;"><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $status)) }}</p>
        </div>
        
        <p>The invoice PDF is attached to this email. Please review the invoice and complete the payment as per the instructions provided.</p>
        
        @if($payuPaymentUrl && $payuPaymentData)
        <div style="background: #e8f5e9; padding: 20px; border-radius: 5px; margin: 20px 0; text-align: center;">
            <h3 style="color: #2e7d32; margin-top: 0;">Pay Now</h3>
            <p style="margin: 10px 0;">Click the button below to make payment securely via PayU:</p>
            <form action="{{ $payuPaymentUrl }}" method="POST" style="margin: 15px 0;">
                @foreach($payuPaymentData as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <button type="submit" style="background: #4caf50; color: #ffffff; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block;">
                    Pay ₹{{ number_format($totalAmount, 2) }} Now
                </button>
            </form>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">Secure payment powered by PayU</p>
        </div>
        @endif
        
        <p style="margin-top: 30px;">
            <strong>Important:</strong> Please ensure payment is completed within the due date mentioned in the invoice to avoid any service interruption.
        </p>
        
        <p>If you have any questions or concerns, please contact our billing team at <a href="mailto:billing@nixi.in" style="color: #667eea;">billing@nixi.in</a>.</p>
        
        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>NIXI Team</strong>
        </p>
    </div>
    
    <div style="text-align: center; margin-top: 20px; color: #666; font-size: 12px;">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; {{ date('Y') }} National Internet Exchange of India. All rights reserved.</p>
    </div>
</body>
</html>

