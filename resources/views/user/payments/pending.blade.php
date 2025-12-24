@extends('user.layout')

@section('title', 'Pending Payments')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-1">Pending Payments</h2>
        <p class="mb-0">Pay your outstanding invoices</p>
        <div class="accent-line"></div>
    </div>

    <!-- Outstanding Amount Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; border-left: 4px solid #dc3545;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Total Outstanding Amount</h6>
                            <h2 class="mb-0" style="color: #dc3545; font-weight: 700;">₹{{ number_format($outstandingAmount, 2) }}</h2>
                            <p class="text-muted small mb-0 mt-1">{{ $pendingInvoices->count() }} {{ $pendingInvoices->count() == 1 ? 'invoice' : 'invoices' }} pending payment</p>
                        </div>
                        @if($pendingInvoices->count() > 0)
                        <div>
                            <form action="{{ route('user.payments.pay-all') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                        <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm.5-1.037a4.5 4.5 0 0 1-1.013-8.986A4.5 4.5 0 0 1 8.5 10.963z"/>
                                        <path d="M5.232 4.616a.5.5 0 0 1 .106.7L1.907 8l3.43 2.684a.5.5 0 1 1-.768.64L1.907 9l-3.43-2.684a.5.5 0 0 1 .768-.64zm10.536 0a.5.5 0 0 0-.106.7L14.093 8l-3.43 2.684a.5.5 0 1 0 .768.64L14.093 9l3.43-2.684a.5.5 0 0 0-.768-.64z"/>
                                    </svg>
                                    Pay All Now (₹{{ number_format($outstandingAmount, 2) }})
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Invoices List -->
    @if($pendingInvoices->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Pending Invoices</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="padding: 1rem;">Invoice Number</th>
                                    <th style="padding: 1rem;">Application ID</th>
                                    <th style="padding: 1rem;">Invoice Date</th>
                                    <th style="padding: 1rem;">Due Date</th>
                                    <th style="padding: 1rem;">Billing Period</th>
                                    <th style="padding: 1rem;">Amount</th>
                                    <th style="padding: 1rem;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingInvoices as $invoice)
                                <tr>
                                    <td style="padding: 1rem;">
                                        <strong>{{ $invoice->invoice_number }}</strong>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <a href="{{ route('user.applications.show', $invoice->application_id) }}" style="color: #0d6efd; text-decoration: none;">
                                            {{ $invoice->application->application_id }}
                                        </a>
                                    </td>
                                    <td style="padding: 1rem;">{{ $invoice->invoice_date->format('d M Y') }}</td>
                                    <td style="padding: 1rem;">
                                        {{ $invoice->due_date->format('d M Y') }}
                                        @if($invoice->due_date->isPast())
                                            <span class="badge bg-danger ms-1">Overdue</span>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem;">
                                        @if($invoice->billing_period)
                                            {{ $invoice->billing_period }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem;">
                                        @if($invoice->payment_status === 'partial')
                                            <strong>₹{{ number_format($invoice->balance_amount ?? $invoice->total_amount, 2) }}</strong>
                                            <br><small class="text-warning">(Partial: ₹{{ number_format($invoice->paid_amount, 2) }} paid of ₹{{ number_format($invoice->total_amount, 2) }})</small>
                                        @else
                                            <strong>₹{{ number_format($invoice->total_amount, 2) }}</strong>
                                        @endif
                                        @if($invoice->gst_amount > 0)
                                            <br><small class="text-muted">(Base: ₹{{ number_format($invoice->amount, 2) }} + GST: ₹{{ number_format($invoice->gst_amount, 2) }})</small>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem;" class="text-center">
                                        <form action="{{ route('user.payments.pay-now', $invoice->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                    <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm.5-1.037a4.5 4.5 0 0 1-1.013-8.986A4.5 4.5 0 0 1 8.5 10.963z"/>
                                                    <path d="M5.232 4.616a.5.5 0 0 1 .106.7L1.907 8l3.43 2.684a.5.5 0 1 1-.768.64L1.907 9l-3.43-2.684a.5.5 0 0 1 .768-.64zm10.536 0a.5.5 0 0 0-.106.7L14.093 8l-3.43 2.684a.5.5 0 1 0 .768.64L14.093 9l3.43-2.684a.5.5 0 0 0-.768-.64z"/>
                                                </svg>
                                                Pay Now
                                            </button>
                                        </form>
                                        <a href="{{ route('user.invoices.download', $invoice->id) }}" class="btn btn-outline-primary btn-sm ms-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                <path d="M.5 9.9a.5.5 0 0 1 .5.5h13a.5.5 0 0 1 0-1H1a.5.5 0 0 1-.5.5zM.5 11.9a.5.5 0 0 1 .5.5h13a.5.5 0 0 1 0-1H1a.5.5 0 0 1-.5.5z"/>
                                                <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0zM1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8z"/>
                                                <path d="M7.5 5.5a.5.5 0 0 1 1 0v5.793l2.146-2.147a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 1 1 .708-.708L7.5 11.293V5.5z"/>
                                            </svg>
                                            Download
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body text-center py-5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#198754" class="mb-3" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 4.384 6.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                    <h5 class="mb-2">No Pending Payments</h5>
                    <p class="text-muted mb-0">All your invoices have been paid.</p>
                    <a href="{{ route('user.dashboard') }}" class="btn btn-primary mt-3">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
