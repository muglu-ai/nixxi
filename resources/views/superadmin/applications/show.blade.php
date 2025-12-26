@extends('superadmin.layout')

@section('title', 'Application Details - ' . $application->application_id)

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">Application Details</h2>
                <p class="mb-0">Application ID: <strong>{{ $application->application_id }}</strong></p>
                <div class="accent-line"></div>
            </div>
            <div>
                <a href="{{ route('superadmin.applications.index') }}" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 1 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    Back to List
                </a>
            </div>
        </div>
    </div>

    <!-- Super Admin Actions -->
    @if(!in_array($application->status, ['ip_assigned', 'invoice_pending', 'payment_verified', 'approved']))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm border-warning" style="border-width: 2px !important; border-radius: 16px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 text-warning">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                </svg>
                                Super Admin Action
                            </h5>
                            <p class="mb-0 text-muted">Approve this application to invoice stage. This will set the status to "IP Assigned" and allow IX Account to generate invoices. <strong>No emails will be sent.</strong></p>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('superadmin.applications.approve-to-invoice', $application->id) }}" onsubmit="return confirm('Are you sure you want to approve this application to invoice stage? This action will not send any emails.');">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                                        <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                    </svg>
                                    Approve to Invoice Stage
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <strong>Status:</strong> This application is already at or beyond the invoice stage. IX Account can generate invoices for this application.
            </div>
        </div>
    </div>
    @endif

    <div class="row g-4">
        <!-- Application Information -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Application Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Application ID:</th>
                            <td><strong>{{ $application->application_id }}</strong></td>
                        </tr>
                        <tr>
                            <th>User:</th>
                            <td>
                                <a href="{{ route('superadmin.users.show', $application->user_id) }}">
                                    {{ $application->user->fullname ?? 'N/A' }}
                                </a><br>
                                <small class="text-muted">{{ $application->user->email ?? 'N/A' }}</small>
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge rounded-pill px-3 py-1
                                    @if($application->status === 'approved' || $application->status === 'payment_verified') bg-success
                                    @elseif(in_array($application->status, ['ip_assigned', 'invoice_pending'])) bg-info
                                    @elseif($application->status === 'rejected' || $application->status === 'ceo_rejected') bg-danger
                                    @else bg-secondary @endif">
                                    {{ $application->status_display }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Live Status:</th>
                            <td>
                                @if($application->is_active)
                                    <span class="badge bg-success">LIVE</span>
                                @else
                                    <span class="badge bg-secondary">NOT LIVE</span>
                                @endif
                            </td>
                        </tr>
                        @if($application->membership_id)
                        <tr>
                            <th>Membership ID:</th>
                            <td><strong>{{ $application->membership_id }}</strong></td>
                        </tr>
                        @endif
                        @if($application->assigned_port_capacity)
                        <tr>
                            <th>Assigned Port Capacity:</th>
                            <td><strong>{{ $application->assigned_port_capacity }}</strong></td>
                        </tr>
                        @endif
                        @if($application->assigned_port_number)
                        <tr>
                            <th>Assigned Port Number:</th>
                            <td><strong>{{ $application->assigned_port_number }}</strong></td>
                        </tr>
                        @endif
                        @if($application->customer_id)
                        <tr>
                            <th>Customer ID:</th>
                            <td><strong>{{ $application->customer_id }}</strong></td>
                        </tr>
                        @endif
                        @if($application->assigned_ip)
                        <tr>
                            <th>Assigned IP:</th>
                            <td><strong>{{ $application->assigned_ip }}</strong></td>
                        </tr>
                        @endif
                        @if($application->service_activation_date)
                        <tr>
                            <th>Service Activation Date:</th>
                            <td><strong>{{ \Carbon\Carbon::parse($application->service_activation_date)->format('d M Y') }}</strong></td>
                        </tr>
                        @endif
                        @if($application->billing_cycle)
                        <tr>
                            <th>Billing Cycle:</th>
                            <td><strong>{{ ucfirst($application->billing_cycle) }}</strong></td>
                        </tr>
                        @endif
                        <tr>
                            <th>Current Stage:</th>
                            <td><span class="badge bg-light text-dark">{{ $application->current_stage }}</span></td>
                        </tr>
                        <tr>
                            <th>Submitted At:</th>
                            <td>{{ $application->submitted_at ? $application->submitted_at->format('d M Y, h:i A') : 'N/A' }}</td>
                        </tr>
                        @if($application->approved_at)
                        <tr>
                            <th>Approved At:</th>
                            <td>{{ $application->approved_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        @endif
                        @if($application->rejection_reason)
                        <tr>
                            <th>Rejection Reason:</th>
                            <td class="text-danger">{{ $application->rejection_reason }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Application Data (if available) -->
            @if($application->application_data)
            <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
                <div class="card-header bg-secondary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Application Data</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">{{ json_encode($application->application_data, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Payment Verification Logs -->
            @if($application->paymentVerificationLogs->count() > 0)
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Payment Verifications</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($application->paymentVerificationLogs->take(5) as $log)
                        <div class="list-group-item px-0 py-2">
                            <div class="d-flex justify-content-between">
                                <span>
                                    <strong>{{ $log->verification_type === 'initial' ? 'Initial' : 'Recurring' }}</strong>
                                    @if($log->billing_period)
                                        - {{ $log->billing_period }}
                                    @endif
                                </span>
                                <span class="text-muted small">{{ $log->verified_at->format('d M Y') }}</span>
                            </div>
                            <div class="text-muted small">
                                ₹{{ number_format($log->amount_captured ?? $log->amount, 2) }}
                                @if($log->verifiedBy)
                                    <br>by {{ $log->verifiedBy->name }}
                                @else
                                    <br>(Auto-verified)
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Invoices -->
            @if($application->invoices->count() > 0)
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Recent Invoices</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($application->invoices->take(5) as $invoice)
                        <div class="list-group-item px-0 py-2">
                            <div class="d-flex justify-content-between">
                                <span>
                                    <strong>{{ $invoice->invoice_number }}</strong>
                                </span>
                                <span class="badge bg-{{ $invoice->payment_status === 'paid' ? 'success' : ($invoice->payment_status === 'partial' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($invoice->payment_status) }}
                                </span>
                            </div>
                            <div class="text-muted small">
                                ₹{{ number_format($invoice->total_amount, 2) }}
                                <br>{{ $invoice->invoice_date->format('d M Y') }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Status History -->
            @if($application->statusHistory->count() > 0)
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-dark text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Status History</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($application->statusHistory->take(10) as $history)
                        <div class="list-group-item px-0 py-2 small">
                            <div class="d-flex justify-content-between">
                                <span><strong>{{ $history->new_status_display ?? $history->new_status }}</strong></span>
                                <span class="text-muted">{{ $history->created_at->format('d M Y') }}</span>
                            </div>
                            @if($history->notes)
                            <div class="text-muted mt-1">{{ $history->notes }}</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

