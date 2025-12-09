<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Processing Payment - NIXI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .loader {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            position: relative;
        }
        
        .loader::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #10b981;
            animation: scaleIn 0.5s ease-out;
        }
        
        .checkmark::after {
            content: '';
            width: 30px;
            height: 15px;
            border-left: 4px solid white;
            border-bottom: 4px solid white;
            transform: rotate(-45deg);
            margin-top: -5px;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        
        h1 {
            color: #1f2937;
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .message {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .warning {
            color: #f59e0b;
            font-size: 14px;
            margin-top: 20px;
            padding: 15px;
            background: #fef3c7;
            border-radius: 10px;
            border-left: 4px solid #f59e0b;
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ef4444;
            animation: scaleIn 0.5s ease-out;
        }
        
        .error-icon::after {
            content: '✕';
            color: white;
            font-size: 40px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($type === 'success')
            <div class="checkmark" id="successIcon" style="display: none;"></div>
            <div class="loader" id="loader"></div>
        @else
            <div class="error-icon"></div>
        @endif
        
        <h1 id="title">{{ $type === 'success' ? 'Processing Payment...' : 'Payment Failed' }}</h1>
        <p class="message" id="message">
            @if($type === 'success')
                Fetching payment details, please do not press any key...
            @else
                Processing payment failure details...
            @endif
        </p>
        <div class="warning" id="warning" style="display: none;">
            <strong>Please wait...</strong><br>
            Do not close this window or press any key while we process your payment.
        </div>
    </div>

    <script>
        let refreshCount = 0;
        const maxRefreshes = 1;
        const refreshDelay = 1500; // 1.5 seconds
        
        // Show warning after a short delay
        setTimeout(() => {
            document.getElementById('warning').style.display = 'block';
        }, 500);
        
        // Auto-refresh logic
        function handleRefresh() {
            refreshCount++;
            
            if (refreshCount <= maxRefreshes) {
                // Refresh the page to trigger session restoration
                setTimeout(() => {
                    window.location.reload();
                }, refreshDelay);
            } else {
                // After refresh, redirect to the actual processing endpoint
                // Preserve all existing query parameters (PayU parameters)
                setTimeout(() => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('process', '1');
                    window.location.href = url.toString();
                }, 500);
            }
        }
        
        // Check if this is the first load or a refresh
        const urlParams = new URLSearchParams(window.location.search);
        const isProcessing = urlParams.get('process') === '1';
        
        if (!isProcessing) {
            // First load - trigger refresh
            handleRefresh();
        } else {
            // After refresh - show processing and redirect
            @if($type === 'success')
                document.getElementById('loader').style.display = 'none';
                document.getElementById('successIcon').style.display = 'flex';
                document.getElementById('title').textContent = 'Payment Successful!';
                document.getElementById('message').textContent = 'Redirecting to your dashboard...';
                document.getElementById('warning').style.display = 'none';
                
                // Redirect to dashboard after showing success message
                setTimeout(() => {
                    window.location.href = '{{ route("user.applications.index") }}';
                }, 2000);
            @else
                document.getElementById('title').textContent = 'Payment Failed';
                document.getElementById('message').textContent = 'Redirecting to payment page...';
                document.getElementById('warning').style.display = 'none';
                
                // Redirect to payment page after showing failure message
                setTimeout(() => {
                    window.location.href = '{{ route("user.applications.ix.create") }}';
                }, 2000);
            @endif
        }
    </script>
</body>
</html>

