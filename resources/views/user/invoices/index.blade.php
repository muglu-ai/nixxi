@extends('user.layout')

@section('title', 'My Invoices')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">My Invoices</h2>
            <p class="text-muted mb-0">View and download all your invoices.</p>
        </div>
        <div>
            <a href="{{ route('user.dashboard') }}" class="btn btn-outline-secondary">
                ← Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-0">
            @if($invoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="color: #2c3e50; font-weight: 600;">Invoice Number</th>
                                <th style="color: #2c3e50; font-weight: 600;">Application ID</th>
                                <th style="color: #2c3e50; font-weight: 600;">Invoice Date</th>
                                <th style="color: #2c3e50; font-weight: 600;">Due Date</th>
                                <th style="color: #2c3e50; font-weight: 600;">Billing Period</th>
                                <th style="color: #2c3e50; font-weight: 600;">Amount</th>
                                <th style="color: #2c3e50; font-weight: 600;">Status</th>
                                <th style="color: #2c3e50; font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                            <tr>
                                <td><strong>{{ $invoice->invoice_number }}</strong></td>
                                <td>
                                    <a href="{{ route('user.applications.show', $invoice->application_id) }}" style="color: #0d6efd; text-decoration: none;">
                                        {{ $invoice->application->application_id }}
                                    </a>
                                </td>
                                <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                                <td>
                                    {{ $invoice->due_date->format('d M Y') }}
                                    @if($invoice->due_date->isPast() && $invoice->status === 'pending')
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    @endif
                                </td>
                                <td>
                                    @if($invoice->billing_period)
                                        {{ $invoice->billing_period }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>₹{{ number_format($invoice->total_amount, 2) }}</strong>
                                    @if($invoice->gst_amount > 0)
                                        <br><small class="text-muted">(Base: ₹{{ number_format($invoice->amount, 2) }} + GST: ₹{{ number_format($invoice->gst_amount, 2) }})</small>
                                    @endif
                                </td>
                                <td>
                                    @if($invoice->status === 'paid')
                                        <span class="badge bg-success">Paid</span>
                                        @if($invoice->paid_at)
                                            <br><small class="text-muted">{{ $invoice->paid_at->format('d M Y') }}</small>
                                        @endif
                                    @elseif($invoice->status === 'overdue')
                                        <span class="badge bg-danger">Overdue</span>
                                    @elseif($invoice->status === 'cancelled')
                                        <span class="badge bg-secondary">Cancelled</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        @if($invoice->status === 'pending')
                                            <form action="{{ route('user.payments.pay-now', $invoice->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                        <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm.5-1.037a4.5 4.5 0 0 1-1.013-8.986A4.5 4.5 0 0 1 8.5 10.963z"/>
                                                        <path d="M5.232 4.616a.5.5 0 0 1 .106.7L1.907 8l3.43 2.684a.5.5 0 1 1-.768.64L1.907 9l-3.43-2.684a.5.5 0 0 1 .768-.64zm10.536 0a.5.5 0 0 0-.106.7L14.093 8l-3.43 2.684a.5.5 0 1 0 .768.64L14.093 9l3.43-2.684a.5.5 0 0 0-.768-.64z"/>
                                                    </svg>
                                                    Pay Now
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('user.invoices.download', $invoice->id) }}" class="btn btn-sm btn-primary" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                            </svg>
                                            Download
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        {{ $invoices->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#6c757d" viewBox="0 0 16 16" class="mb-3">
                        <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                    </svg>
                    <p class="text-muted mb-0">No invoices found.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
