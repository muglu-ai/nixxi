<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Verifying Payment - NIXI</title>
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            overflow: hidden;
        }
        .verification-container {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto 2rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h3 {
            color: #333;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        p {
            color: #666;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="spinner"></div>
        <h3>Verifying Payment...</h3>
        <p>Please wait while we verify your payment details.</p>
        <p class="text-muted small mt-2">This page will refresh automatically.</p>
    </div>

    <script>
        // Prevent back button
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };

        // Refresh the page once after 2 seconds
        setTimeout(function() {
            // Preserve all query parameters and POST data by redirecting to the same URL with verified parameter
            const url = new URL('{{ $callback_url }}', window.location.origin);
            url.searchParams.set('verified', '1');
            
            // If there are POST parameters, we need to preserve them
            // Since we're doing a GET redirect, POST params will be lost, but PayU usually sends GET params on redirect
            window.location.href = url.toString();
        }, 2000);
    </script>
</body>
</html>

