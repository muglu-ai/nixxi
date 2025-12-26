@extends('user.layout')

@section('title', 'Applicant Dashboard')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-1">Applicant Dashboard</h2>
        <p class="mb-0">Welcome back, <strong>{{ $user->fullname }}</strong>!</p>
        <div class="accent-line"></div>
    </div>

    <!-- User Details Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Profile</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="#2c3e50" viewBox="0 0 16 16">
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="mb-1" style="color: #2c3e50; font-weight: 600;">{{ $user->fullname }}</h4>
                                    <p class="text-muted mb-0 small">{{ $user->email }}</p>
                                </div>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted small me-2">Registration ID:</span>
                                        <strong style="color: #2c3e50;">{{ $user->registrationid }}</strong>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted small me-2">Status:</span>
                                        <span class="badge 
                                            @if($user->status === 'approved' || $user->status === 'active') bg-success
                                            @elseif($user->status === 'pending') bg-warning
                                            @else bg-secondary @endif"
                                            @if($user->status === 'pending')
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                title="Once approved you will be able to fill application"
                                            @endif>
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted small me-2">Mobile:</span>
                                        <strong style="color: #2c3e50;">{{ $user->mobile }}</strong>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted small me-2">PAN:</span>
                                        <strong style="color: #2c3e50;">{{ $user->pancardno }}</strong>
                                        @if($user->pan_verified)
                                            <span class="badge bg-success ms-2">Verified</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="{{ route('user.profile') }}" class="btn btn-primary">
                                View Full Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Outstanding Amount Summary -->
    @if($user->status === 'approved' || $user->status === 'active')
    @if(isset($outstandingAmount) && $outstandingAmount > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; border-left: 4px solid #dc3545;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mb-3 mb-md-0">
                            <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Outstanding Amount</h6>
                            <a href="{{ route('user.payments.pending') }}" style="text-decoration: none; color: inherit;">
                                <h2 class="mb-0" style="color: #dc3545; font-weight: 700; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">₹{{ number_format($outstandingAmount, 2) }}</h2>
                            </a>
                            <p class="text-muted small mb-0 mt-1">{{ $pendingInvoices ?? 0 }} {{ ($pendingInvoices ?? 0) == 1 ? 'invoice' : 'invoices' }} pending payment</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('user.payments.pending') }}" class="btn btn-outline-danger">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                </svg>
                                View Pending Payments
                            </a>
                            @if(isset($pendingInvoicesList) && $pendingInvoicesList->count() > 0)
                            <form action="{{ route('user.payments.pay-all') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                        <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm.5-1.037a4.5 4.5 0 0 1-1.013-8.986A4.5 4.5 0 0 1 8.5 10.963z"/>
                                        <path d="M5.232 4.616a.5.5 0 0 1 .106.7L1.907 8l3.43 2.684a.5.5 0 1 1-.768.64L1.907 9l-3.43-2.684a.5.5 0 0 1 .768-.64zm10.536 0a.5.5 0 0 0-.106.7L14.093 8l-3.43 2.684a.5.5 0 1 0 .768.64L14.093 9l3.43-2.684a.5.5 0 0 0-.768-.64z"/>
                                    </svg>
                                    Pay All Now
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    <!-- Invoice Summary -->
    @if($user->status === 'approved' || $user->status === 'active')
    @if(isset($invoiceCount) && $invoiceCount > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="font-weight: 600;">Invoice Summary</h5>
                        <a href="{{ route('user.invoices.index') }}" class="text-white text-decoration-none">
                            View All <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="ms-1">
                                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#0dcaf0" viewBox="0 0 16 16">
                                        <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">Total Invoices</h6>
                                    <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $invoiceCount }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-3 me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffc107" viewBox="0 0 16 16">
                                        <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022l-.074.997zm2.004.45a7.003 7.003 0 0 0-.985-.299l.219-.976c.383.086.76.2 1.126.342l-.36.933zm1.37.71a7.01 7.01 0 0 0-.439-.27l.493-.87a8.025 8.025 0 0 1 .979.654l-.615.789a6.996 6.996 0 0 0-.418-.302zm1.834 1.79a6.99 6.99 0 0 0-.653-.796l.724-.69c.27.285.52.59.747.91l-.818.576zm.744 1.352a7.08 7.08 0 0 0-.214-.468l.893-.45a7.976 7.976 0 0 1 .45 1.088l-.95.313a7.023 7.023 0 0 0-.179-.483zm.53 2.507a6.991 6.991 0 0 0-.1-1.025l.985-.17c.067.386.106.778.116 1.175l-.99-.13zm-.131 1.538c.033-.17.06-.339.081-.51l.993.123a7.957 7.957 0 0 1-.23 1.155l-.964-.267c.046-.165.086-.332.12-.501zm-.952 2.379c.184-.29.346-.594.486-.908l.914.405c-.236.36-.504.696-.796 1.007l-.844-.497zm-.964 1.205c.122-.122.239-.248.35-.378l.758.653a8.073 8.073 0 0 1-.401.432l-.707-.707z"/>
                                        <path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0v1z"/>
                                        <path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">Pending</h6>
                                    <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $pendingInvoices ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#198754" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 4.384 6.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">Paid</h6>
                                    <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $paidInvoices ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    <!-- Applications Progress -->
    @if($user->status === 'approved' || $user->status === 'active')
    @if(isset($applications) && $applications->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">My Applications</h5>
                </div>
                <div class="card-body p-4">
                    @foreach($applications as $application)
                    @php
                        // Get invoices for this application
                        $appInvoices = \App\Models\Invoice::where('application_id', $application->id)
                            ->where('status', '!=', 'cancelled')
                            ->get();
                        
                        $appPendingInvoices = $appInvoices->whereIn('payment_status', ['pending', 'partial']);
                        $appPaidInvoices = $appInvoices->where('payment_status', 'paid');
                        
                        $appOutstandingAmount = $appPendingInvoices->sum(function ($invoice) {
                            return $invoice->balance_amount ?? $invoice->total_amount;
                        });
                        
                        $appPaidAmount = $appPaidInvoices->sum('paid_amount');
                        
                        $appData = $application->application_data ?? [];
                        $portSelection = $appData['port_selection'] ?? [];
                    @endphp
                    <div class="mb-4 pb-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h6 class="mb-1" style="color: #2c3e50; font-weight: 600;">{{ $application->application_id }}</h6>
                                <p class="text-muted mb-0 small">{{ $application->application_type }}</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary toggle-summary" 
                                        data-target="summary-{{ $application->id }}"
                                        style="border-radius: 8px; min-width: 40px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="toggle-icon">
                                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                    </svg>
                                </button>
                                <div>
                                @if($application->is_active && $application->service_activation_date)
                                    <span class="badge bg-success">COMPLETED</span>
                                @elseif($application->status === 'approved' || $application->status === 'payment_verified')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($application->status === 'rejected' || $application->status === 'ceo_rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif($application->application_type === 'IX')
                                    {{-- New IX Workflow Statuses --}}
                                    @if(in_array($application->status, ['submitted', 'resubmitted', 'processor_resubmission', 'legal_sent_back', 'head_sent_back']))
                                        <span class="badge bg-warning">IX Processor Review</span>
                                    @elseif($application->status === 'processor_forwarded_legal')
                                        <span class="badge bg-info">IX Legal Review</span>
                                    @elseif($application->status === 'legal_forwarded_head')
                                        <span class="badge bg-primary">IX Head Review</span>
                                    @elseif($application->status === 'head_forwarded_ceo')
                                        <span class="badge bg-purple">CEO Review</span>
                                    @elseif($application->status === 'ceo_approved')
                                        <span class="badge bg-info">Nodal Officer Review</span>
                                    @elseif($application->status === 'port_assigned')
                                        <span class="badge bg-primary">IX Tech Team Review</span>
                                    @elseif(in_array($application->status, ['ip_assigned', 'invoice_pending']))
                                        <span class="badge bg-warning">IX Account Review</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $application->status_display }}</span>
                                    @endif
                                @else
                                    {{-- Legacy Statuses --}}
                                    @if(in_array($application->status, ['pending', 'processor_review']))
                                        <span class="badge bg-warning">Processor Review</span>
                                    @elseif(in_array($application->status, ['processor_approved', 'finance_review']))
                                        <span class="badge bg-info">Finance Review</span>
                                    @elseif($application->status === 'finance_approved')
                                        <span class="badge bg-primary">Technical Review</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $application->status_display }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        
                        <!-- Application Summary (Collapsible) -->
                        <div id="summary-{{ $application->id }}" class="application-summary mt-3" style="display: none;">
                            <div class="card border-0 shadow-sm bg-light" style="border-radius: 12px;">
                                <div class="card-body p-3">
                                    <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">Application Summary</h6>
                                    <div class="row g-3">
                                        @if($application->application_type === 'IX')
                                            <!-- Port Details -->
                                            <div class="col-md-6">
                                                <div class="p-2 bg-white rounded">
                                                    <small class="text-muted d-block mb-1">Port Capacity</small>
                                                    <strong style="color: #2c3e50;">{{ $application->assigned_port_capacity ?? ($portSelection['capacity'] ?? 'N/A') }}</strong>
                                                </div>
                                            </div>
                                            @if($application->assigned_port_number)
                                            <div class="col-md-6">
                                                <div class="p-2 bg-white rounded">
                                                    <small class="text-muted d-block mb-1">Port Number</small>
                                                    <strong style="color: #2c3e50;">{{ $application->assigned_port_number }}</strong>
                                                </div>
                                            </div>
                                            @endif
                                            <!-- IP Details -->
                                            @if($application->assigned_ip)
                                            <div class="col-md-6">
                                                <div class="p-2 bg-white rounded">
                                                    <small class="text-muted d-block mb-1">Assigned IP</small>
                                                    <strong style="color: #2c3e50;">{{ $application->assigned_ip }}</strong>
                                                </div>
                                            </div>
                                            @endif
                                            <!-- Live Status -->
                                            <div class="col-md-6">
                                                <div class="p-2 bg-white rounded">
                                                    <small class="text-muted d-block mb-1">Live Status</small>
                                                    @if($application->is_active)
                                                        <span class="badge bg-success">LIVE</span>
                                                        @if($application->service_activation_date)
                                                            <br><small class="text-muted">Since {{ \Carbon\Carbon::parse($application->service_activation_date)->format('d/m/Y') }}</small>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-danger">NOT LIVE</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($application->membership_id)
                                            <div class="col-md-6">
                                                <div class="p-2 bg-white rounded">
                                                    <small class="text-muted d-block mb-1">Membership ID</small>
                                                    <strong style="color: #2c3e50;">{{ $application->membership_id }}</strong>
                                                </div>
                                            </div>
                                            @endif
                                            @if($application->customer_id)
                                            <div class="col-md-6">
                                                <div class="p-2 bg-white rounded">
                                                    <small class="text-muted d-block mb-1">Customer ID</small>
                                                    <strong style="color: #2c3e50;">{{ $application->customer_id }}</strong>
                                                </div>
                                            </div>
                                            @endif
                                        @endif
                                        
                                        <!-- Payment Summary -->
                                        <div class="col-md-6">
                                            <div class="p-2 bg-white rounded">
                                                <small class="text-muted d-block mb-1">Amount to Pay</small>
                                                @if($appOutstandingAmount > 0)
                                                    <strong class="text-warning">₹{{ number_format($appOutstandingAmount, 2) }}</strong>
                                                    <br><small class="text-muted">{{ $appPendingInvoices->count() }} pending invoice(s)</small>
                                                @else
                                                    <strong class="text-success">₹0.00</strong>
                                                    <br><small class="text-muted">No pending payments</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-2 bg-white rounded">
                                                <small class="text-muted d-block mb-1">Total Paid</small>
                                                <strong class="text-success">₹{{ number_format($appPaidAmount, 2) }}</strong>
                                                <br><small class="text-muted">{{ $appPaidInvoices->count() }} paid invoice(s)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="{{ route('user.applications.show', $application->id) }}" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">
                                View Details
                            </a>
                        </div>
                    </div>
                    @endforeach
                    
                    @if($applications->count() >= 5)
                    <div class="text-center mt-3">
                        <a href="{{ route('user.applications.index') }}" class="btn btn-primary" style="border-radius: 10px; font-weight: 500;">
                            View All Applications
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">Quick Actions</h5>
                    <div class="d-flex flex-wrap gap-3">
                        @if(!isset($hasIxApplication) || !$hasIxApplication)
                            <a href="{{ route('user.applications.ix.create') }}" class="btn btn-primary px-4 py-2" style="border-radius: 10px; font-weight: 500;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                </svg>
                                IX Application
                            </a>
                        @else
                            <a href="{{ route('user.applications.ix.create-new') }}" class="btn btn-primary px-4 py-2" style="border-radius: 10px; font-weight: 500;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                </svg>
                                New IX Application
                            </a>
                        @endif
                        <a href="{{ route('user.invoices.index') }}" class="btn btn-info px-4 py-2" style="border-radius: 10px; font-weight: 500;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                            </svg>
                            My Invoices
                            @if(isset($invoiceCount) && $invoiceCount > 0)
                                <span class="badge bg-light text-dark ms-2">{{ $invoiceCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('user.applications.index') }}" class="btn btn-outline-primary px-4 py-2" style="border-radius: 10px; font-weight: 500;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                            </svg>
                            View Applications
                        </a>
                        <a href="{{ route('user.messages.index') }}" class="btn btn-outline-info px-4 py-2" style="border-radius: 10px; font-weight: 500;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                            </svg>
                            Messages
                            @if($unreadCount > 0)
                                <span class="badge bg-danger ms-2">{{ $unreadCount }}</span>
                            @endif
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    // Ensure alert close button works
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    alert.classList.remove('show');
                    setTimeout(function() {
                        alert.remove();
                    }, 150);
                });
            }
        });

        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle application summary
    document.querySelectorAll('.toggle-summary').forEach(function(button) {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const summary = document.getElementById(targetId);
            const icon = this.querySelector('.toggle-icon');
            
            if (summary.style.display === 'none') {
                summary.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                summary.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        });
    });
});
</script>
@endpush
@endsection
