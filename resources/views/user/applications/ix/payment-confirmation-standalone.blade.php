<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment Confirmation - NIXI Application</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ time() }}">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .payment-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="payment-card">
        <div class="card-body text-center py-5 px-4">
            <div class="mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#10b981" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 4.384 6.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                </svg>
            </div>
            <h2 class="mb-3" style="color:#1f2937;">Payment Successful!</h2>
            <p class="text-muted mb-4">Your payment has been processed successfully.</p>

            <div class="card border mb-4">
                <div class="card-body">
                    <h5 class="mb-3" style="color:#1e40af;">Payment Details</h5>
                    <dl class="row mb-0 text-start">
                        <dt class="col-sm-5">Transaction ID:</dt>
                        <dd class="col-sm-7"><strong>{{ $paymentTransaction->transaction_id }}</strong></dd>

                        @if($paymentTransaction->payment_id)
                        <dt class="col-sm-5">Payment ID:</dt>
                        <dd class="col-sm-7">{{ $paymentTransaction->payment_id }}</dd>
                        @endif

                        <dt class="col-sm-5">Amount Paid:</dt>
                        <dd class="col-sm-7"><strong>₹{{ number_format($paymentTransaction->amount, 2) }}</strong></dd>

                        <dt class="col-sm-5">Payment Status:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-success">Success</span>
                        </dd>

                        @if(isset($application) && $application)
                        <dt class="col-sm-5">Application ID:</dt>
                        <dd class="col-sm-7"><strong>{{ $application->application_id }}</strong></dd>
                        @endif

                        <dt class="col-sm-5">Payment Date:</dt>
                        <dd class="col-sm-7">{{ $paymentTransaction->created_at->format('d M Y, h:i A') }}</dd>
                    </dl>
                </div>
            </div>

            @if(isset($application) && $application)
            <div class="alert alert-info">
                <p class="mb-0">
                    <strong>Application Status:</strong> Your IX application has been submitted successfully and is now under review.
                </p>
            </div>
            @endif

            @if(isset($showLoginLink) && $showLoginLink)
            <div class="alert alert-warning">
                <p class="mb-2"><strong>Note:</strong> Your session has expired. Please login to view your application details and download the application PDF.</p>
            </div>
            @endif

            <div class="d-flex gap-2 justify-content-center mt-4 flex-wrap">
                @if(isset($showLoginLink) && $showLoginLink)
                    <a href="{{ route('login.index') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login to View Applications
                    </a>
                @else
                    <a href="{{ route('user.applications.index') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-list-ul me-2"></i>View Applications
                    </a>
                    @if(isset($application) && $application)
                    <a href="{{ route('user.applications.ix.download-application-pdf', $application->id) }}" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-download me-2"></i>Download Application PDF
                    </a>
                    @endif
                @endif
                <a href="{{ route('user.dashboard') }}" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-house me-2"></i>Go to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

