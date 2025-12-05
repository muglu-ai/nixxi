@extends('user.layout')

@section('title', 'Payment Confirmation')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
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
                                <dd class="col-sm-7">{{ $paymentTransaction->transaction_id }}</dd>

                                @if($paymentTransaction->payment_id)
                                <dt class="col-sm-5">Payment ID:</dt>
                                <dd class="col-sm-7">{{ $paymentTransaction->payment_id }}</dd>
                                @endif

                                <dt class="col-sm-5">Amount Paid:</dt>
                                <dd class="col-sm-7">₹{{ number_format($paymentTransaction->amount, 2) }}</dd>

                                <dt class="col-sm-5">Payment Status:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-success">Success</span>
                                </dd>

                                @if(isset($application) && $application)
                                <dt class="col-sm-5">Application ID:</dt>
                                <dd class="col-sm-7">{{ $application->application_id }}</dd>
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
                        <p class="mb-2"><strong>Note:</strong> Your session has expired. Please login to view your application details.</p>
                    </div>
                    @endif

                    <div class="d-flex gap-2 justify-content-center mt-4">
                        @if(isset($showLoginLink) && $showLoginLink)
                            <a href="{{ route('login.index') }}" class="btn btn-primary">Login to View Applications</a>
                        @else
                            <a href="{{ route('user.applications.index') }}" class="btn btn-primary">View Applications</a>
                            @if(isset($application) && $application)
                            <a href="{{ route('user.applications.ix.download-application-pdf', $application->id) }}" class="btn btn-outline-secondary">Download Application PDF</a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

