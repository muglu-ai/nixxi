@extends('user.layout')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Application Details')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>Application Details</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('user.applications.index') }}">Applications</a></li>
                <li class="breadcrumb-item active">{{ $application->application_id }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Application Information</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="200">Application ID:</th>
                        <td><strong>{{ $application->application_id }}</strong></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            @if($application->is_active && $application->service_activation_date)
                                <span class="badge bg-success">LIVE</span>
                                <span class="badge bg-success ms-2">COMPLETED</span>
                            @elseif($application->service_activation_date)
                                <span class="badge bg-info">Will be live on {{ \Carbon\Carbon::parse($application->service_activation_date)->format('d M Y') }}</span>
                            @elseif($application->application_type === 'IX')
                                {{-- New IX Workflow Statuses --}}
                                @if($application->status === 'approved' || $application->status === 'payment_verified')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($application->status === 'rejected' || $application->status === 'ceo_rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif(in_array($application->status, ['submitted', 'resubmitted', 'processor_resubmission', 'legal_sent_back', 'head_sent_back']))
                                    <span class="badge bg-warning">IX Processor Review</span>
                                @elseif($application->status === 'processor_forwarded_legal')
                                    <span class="badge bg-info">IX Legal Review</span>
                                @elseif($application->status === 'legal_forwarded_head')
                                    <span class="badge bg-primary">IX Head Review</span>
                                @elseif($application->status === 'head_forwarded_ceo')
                                    <span class="badge" style="background-color: #6f42c1; color: white;">CEO Review</span>
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
                                @if($application->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($application->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif(in_array($application->status, ['pending', 'processor_review']))
                                    <span class="badge bg-warning">Processor Review</span>
                                @elseif(in_array($application->status, ['processor_approved', 'finance_review']))
                                    <span class="badge bg-info">Finance Review</span>
                                @elseif($application->status === 'finance_approved')
                                    <span class="badge bg-primary">Technical Review</span>
                                @else
                                    <span class="badge bg-secondary">{{ $application->status_display }}</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @if($application->application_type === 'IX')
                    <tr>
                        <th>Progress:</th>
                        <td>
                            @php
                                if($application->application_type === 'IX') {
                                    // New IX Workflow Stages
                                    $stages = ['IX Processor', 'IX Legal', 'IX Head', 'CEO', 'Nodal Officer', 'IX Tech Team', 'IX Account', 'Completed'];
                                    // Application is completed if it's live with service_activation_date
                                    $isCompleted = ($application->is_active && $application->service_activation_date) || in_array($application->status, ['payment_verified', 'approved']);
                                    
                                    $processorCompleted = in_array($application->status, ['processor_forwarded_legal', 'legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                                    $legalCompleted = in_array($application->status, ['legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                                    $headCompleted = in_array($application->status, ['head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                                    $ceoCompleted = in_array($application->status, ['ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                                    $nodalCompleted = in_array($application->status, ['port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                                    $techCompleted = in_array($application->status, ['ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                                    $accountCompleted = in_array($application->status, ['payment_verified', 'approved']);
                                    $completedCompleted = $isCompleted;
                                    
                                    // If completed (live with service_activation_date), set progress to 100%
                                    if ($application->is_active && $application->service_activation_date) {
                                        $progress = 100;
                                    } else {
                                        $completedCount = ($processorCompleted ? 1 : 0) + ($legalCompleted ? 1 : 0) + ($headCompleted ? 1 : 0) + ($ceoCompleted ? 1 : 0) + ($nodalCompleted ? 1 : 0) + ($techCompleted ? 1 : 0) + ($accountCompleted ? 1 : 0) + ($completedCompleted ? 1 : 0);
                                        $progress = ($completedCount / count($stages)) * 100;
                                    }
                                } else {
                                    // Legacy Workflow Stages
                                    $stages = ['Processor', 'Finance', 'Technical', 'Approved'];
                                    $isApproved = $application->status === 'approved';
                                    
                                    $processorCompleted = in_array($application->status, ['processor_approved', 'finance_review', 'finance_approved', 'approved']);
                                    $financeCompleted = in_array($application->status, ['finance_approved', 'approved']);
                                    $technicalCompleted = $isApproved;
                                    $approvedCompleted = $isApproved;
                                    
                                    $completedCount = ($processorCompleted ? 1 : 0) + ($financeCompleted ? 1 : 0) + ($technicalCompleted ? 1 : 0) + ($approvedCompleted ? 1 : 0);
                                    $progress = ($completedCount / count($stages)) * 100;
                                }
                            @endphp
                            <div class="progress" style="height: 25px; border-radius: 12px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                     role="progressbar" 
                                     style="width: {{ $progress }}%; border-radius: 12px; font-weight: 600; font-size: 0.875rem; line-height: 25px;"
                                     aria-valuenow="{{ $progress }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    {{ round($progress) }}%
                                </div>
                            </div>
                            @if($application->is_active && $application->service_activation_date)
                                <div class="mt-2">
                                    <span class="badge bg-success">LIVE</span>
                                    <span class="badge bg-success ms-2">COMPLETED</span>
                                </div>
                            @elseif($application->service_activation_date)
                                <div class="mt-2">
                                    <span class="badge bg-info">Will be live on {{ \Carbon\Carbon::parse($application->service_activation_date)->format('d M Y') }}</span>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endif
                    @if($application->application_type === 'IX')
                        @if($application->assigned_port_capacity)
                        <tr>
                            <th>Assigned Port Capacity:</th>
                            <td>
                                <strong>{{ $application->assigned_port_capacity }}</strong>
                                @php
                                    $pendingPlanChange = \App\Models\PlanChangeRequest::where('application_id', $application->id)
                                        ->where('status', 'pending')
                                        ->first();
                                    
                                    $approvedNotEffective = \App\Models\PlanChangeRequest::where('application_id', $application->id)
                                        ->where('status', 'approved')
                                        ->whereNotNull('effective_from')
                                        ->where('effective_from', '>', now('Asia/Kolkata'))
                                        ->latest('effective_from')
                                        ->first();
                                    
                                    $canChangePlan = $application->application_type === 'IX' 
                                        && $application->assigned_port_capacity 
                                        && in_array($application->status, ['approved', 'payment_verified', 'ip_assigned', 'invoice_pending'])
                                        && !$pendingPlanChange 
                                        && !$approvedNotEffective;
                                @endphp
                                @if($canChangePlan)
                                <a href="{{ route('user.plan-change.create', $application->id) }}" class="btn btn-sm btn-primary ms-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                    </svg>
                                    Change Plan
                                </a>
                                @elseif($approvedNotEffective)
                                <span class="badge bg-info ms-2" title="Plan change is scheduled">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                    </svg>
                                    Change Scheduled
                                </span>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @if($application->assigned_port_number)
                        <tr>
                            <th>Assigned Port Number:</th>
                            <td><strong>{{ $application->assigned_port_number }}</strong></td>
                        </tr>
                        @endif
                        @php
                            if (!isset($pendingPlanChange)) {
                                $pendingPlanChange = \App\Models\PlanChangeRequest::where('application_id', $application->id)
                                    ->where('status', 'pending')
                                    ->first();
                            }
                            
                            if (!isset($approvedNotEffective)) {
                                $approvedNotEffective = \App\Models\PlanChangeRequest::where('application_id', $application->id)
                                    ->where('status', 'approved')
                                    ->whereNotNull('effective_from')
                                    ->where('effective_from', '>', now('Asia/Kolkata'))
                                    ->latest('effective_from')
                                    ->first();
                            }
                        @endphp
                        @if($pendingPlanChange)
                        <tr>
                            <th>Plan Change Request:</th>
                            <td>
                                <span class="badge bg-warning">Pending Approval</span>
                                <small class="text-muted ms-2">Requested: {{ $pendingPlanChange->new_port_capacity }} ({{ strtoupper($pendingPlanChange->new_billing_plan) }})</small>
                            </td>
                        </tr>
                        @endif
                        @if($approvedNotEffective)
                        <tr>
                            <th>Upcoming Plan Change:</th>
                            <td>
                                <span class="badge bg-success">Approved</span>
                                <small class="text-muted ms-2">
                                    Will change from <strong>{{ $approvedNotEffective->current_port_capacity }}</strong> to <strong>{{ $approvedNotEffective->new_port_capacity }}</strong> 
                                    on <strong>{{ \Carbon\Carbon::parse($approvedNotEffective->effective_from)->format('d/m/Y') }}</strong>
                                </small>
                                <br>
                                <small class="text-info mt-1 d-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                                    </svg>
                                    Your plan will be automatically updated on this date. You cannot request another change until then.
                                </small>
                            </td>
                        </tr>
                        @endif
                        @if($application->customer_id)
                        <tr>
                            <th>Customer ID:</th>
                            <td><strong>{{ $application->customer_id }}</strong></td>
                        </tr>
                        @endif
                        @if($application->membership_id)
                        <tr>
                            <th>Membership ID:</th>
                            <td><strong>{{ $application->membership_id }}</strong></td>
                        </tr>
                        <tr>
                            <th>Live Status:</th>
                            <td>
                                @if($application->is_active && $application->service_activation_date)
                                    <span class="badge bg-success">LIVE</span>
                                    <span class="badge bg-success ms-2">COMPLETED</span>
                                    <br><small class="text-muted mt-1 d-block">Service activated on {{ \Carbon\Carbon::parse($application->service_activation_date)->format('d M Y') }}</small>
                                @elseif($application->is_active)
                                    <span class="badge bg-success">LIVE</span>
                                @else
                                    <span class="badge bg-danger">NOT LIVE</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @if($application->assigned_ip)
                        <tr>
                            <th>Assigned IP:</th>
                            <td>
                                <strong>{{ $application->assigned_ip }}</strong>
                                @if($application->is_active && $application->service_activation_date)
                                    <br><small class="text-success mt-1 d-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 4.384 6.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                        </svg>
                                        IP is live and active
                                    </small>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @if($application->resubmission_query)
                        <tr>
                            <th>Resubmission Query:</th>
                            <td class="text-warning">{{ $application->resubmission_query }}</td>
                        </tr>
                        @endif
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

                @if($application->application_type === 'IRINN')
                <div class="mt-4">
                    <button type="button" class="btn btn-primary" id="viewDetailsBtn" data-bs-toggle="modal" data-bs-target="#applicationDetailsModal" onclick="openApplicationModal()">
                        View Application Details
                    </button>
                </div>
                
                <div class="mt-3">
                    <h6>Download Documents:</h6>
                    <div class="d-flex gap-2">
                        {{-- <a href="{{ route('user.applications.download-application-pdf', $application->id) }}" class="btn btn-primary" target="_blank">
                            <i class="fas fa-download"></i> Download Application PDF
                        </a> --}}
                        <a href="{{ route('user.applications.download-invoice-pdf', $application->id) }}" class="btn btn-success" target="_blank">
                            <i class="fas fa-file-invoice"></i> Download Invoice PDF
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if($application->application_type === 'IX')
        <!-- Registration Details -->
        @if($application->registration_details)
        <div class="card shadow mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Registration Details</h5>
            </div>
            <div class="card-body">
                @php
                    $regDetails = $application->registration_details;
                @endphp
                <table class="table table-sm">
                    <tr>
                        <th width="200">Registration ID:</th>
                        <td>{{ $regDetails['registration_id'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Registration Type:</th>
                        <td>{{ ucfirst($regDetails['registration_type'] ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <th>Full Name:</th>
                        <td>{{ $regDetails['fullname'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>PAN Card:</th>
                        <td>{{ $regDetails['pancardno'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>{{ $regDetails['email'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Mobile:</th>
                        <td>{{ $regDetails['mobile'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Date of Birth:</th>
                        <td>{{ $regDetails['dateofbirth'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Registration Date:</th>
                        <td>{{ $regDetails['registrationdate'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>PAN Verified:</th>
                        <td>
                            @if($regDetails['pan_verified'] ?? false)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Email Verified:</th>
                        <td>
                            @if($regDetails['email_verified'] ?? false)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Mobile Verified:</th>
                        <td>
                            @if($regDetails['mobile_verified'] ?? false)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        @endif

        <!-- KYC Details -->
        @if($application->kyc_details)
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">KYC Details</h5>
            </div>
            <div class="card-body">
                @php
                    $kycDetails = $application->kyc_details;
                @endphp
                <table class="table table-sm">
                    <tr>
                        <th width="200">GSTIN:</th>
                        <td>{{ $kycDetails['gstin'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>GST Verified:</th>
                        <td>
                            @if($kycDetails['gst_verified'] ?? false)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Is MSME:</th>
                        <td>
                            @if($kycDetails['is_msme'] ?? false)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                    </tr>
                    @if($kycDetails['udyam_number'] ?? null)
                    <tr>
                        <th>UDYAM Number:</th>
                        <td>{{ $kycDetails['udyam_number'] }}</td>
                    </tr>
                    <tr>
                        <th>UDYAM Verified:</th>
                        <td>
                            @if($kycDetails['udyam_verified'] ?? false)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                    @if($kycDetails['cin'] ?? null)
                    <tr>
                        <th>CIN:</th>
                        <td>{{ $kycDetails['cin'] }}</td>
                    </tr>
                    <tr>
                        <th>MCA Verified:</th>
                        <td>
                            @if($kycDetails['mca_verified'] ?? false)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <th>Contact Name:</th>
                        <td>{{ $kycDetails['contact_name'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Contact PAN:</th>
                        <td>{{ $kycDetails['contact_pan'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Contact DOB:</th>
                        <td>{{ $kycDetails['contact_dob'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Contact Email:</th>
                        <td>{{ $kycDetails['contact_email'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Contact Mobile:</th>
                        <td>{{ $kycDetails['contact_mobile'] ?? 'N/A' }}</td>
                    </tr>
                    @if($kycDetails['billing_address'] ?? null)
                    <tr>
                        <th>Billing Address:</th>
                        <td>{{ is_array($kycDetails['billing_address']) ? json_encode($kycDetails['billing_address'], JSON_PRETTY_PRINT) : $kycDetails['billing_address'] }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
        @endif

        <!-- Authorized Representative Details -->
        @if($application->authorized_representative_details)
        <div class="card shadow mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Authorized Representative Details</h5>
            </div>
            <div class="card-body">
                @php
                    $repDetails = $application->authorized_representative_details;
                @endphp
                <table class="table table-sm">
                    <tr>
                        <th width="200">Name:</th>
                        <td>{{ $repDetails['name'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>PAN Card:</th>
                        <td>{{ $repDetails['pan'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Date of Birth:</th>
                        <td>{{ $repDetails['dob'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>{{ $repDetails['email'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Mobile:</th>
                        <td>{{ $repDetails['mobile'] ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @endif
        @endif

    </div>

    <div class="col-md-4">
        <!-- Status Tracking -->
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Application Status</h5>
            </div>
            <div class="card-body">
                <div class="progress mb-3" style="height: 30px;">
                    @php
                        if($application->application_type === 'IX') {
                            $stages = ['IX Processor', 'IX Legal', 'IX Head', 'CEO', 'Nodal Officer', 'IX Tech Team', 'IX Account', 'Completed'];
                            // Application is completed if it's live with service_activation_date
                            $isCompleted = ($application->is_active && $application->service_activation_date) || in_array($application->status, ['payment_verified', 'approved']);
                            
                            $processorCompleted = in_array($application->status, ['processor_forwarded_legal', 'legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $legalCompleted = in_array($application->status, ['legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $headCompleted = in_array($application->status, ['head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $ceoCompleted = in_array($application->status, ['ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $nodalCompleted = in_array($application->status, ['port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $techCompleted = in_array($application->status, ['ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $accountCompleted = in_array($application->status, ['payment_verified', 'approved']);
                            $completedCompleted = $isCompleted;
                            
                            // If completed (live with service_activation_date), set progress to 100%
                            if ($application->is_active && $application->service_activation_date) {
                                $progress = 100;
                            } else {
                                $completedCount = ($processorCompleted ? 1 : 0) + ($legalCompleted ? 1 : 0) + ($headCompleted ? 1 : 0) + ($ceoCompleted ? 1 : 0) + ($nodalCompleted ? 1 : 0) + ($techCompleted ? 1 : 0) + ($accountCompleted ? 1 : 0) + ($completedCompleted ? 1 : 0);
                                $progress = ($completedCount / count($stages)) * 100;
                            }
                        } else {
                            $stages = ['Processor', 'Finance', 'Technical', 'Approved'];
                            $currentStage = $application->current_stage;
                            $stageIndex = array_search($currentStage, $stages);
                            $progress = $stageIndex !== false ? (($stageIndex + 1) / count($stages)) * 100 : 0;
                        }
                    @endphp
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $progress }}%">
                        {{ round($progress) }}%
                    </div>
                </div>
                <ul class="list-unstyled">
                    @if($application->application_type === 'IX')
                        {{-- New IX Workflow Stages --}}
                        <li class="mb-2">
                            @if($processorCompleted)
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            @else
                                <i class="bi bi-circle text-muted me-2"></i>
                            @endif
                            <strong>IX Processor</strong>
                        </li>
                        <li class="mb-2">
                            @if($legalCompleted)
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            @else
                                <i class="bi bi-circle text-muted me-2"></i>
                            @endif
                            <strong>IX Legal</strong>
                        </li>
                        <li class="mb-2">
                            @if($headCompleted)
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            @else
                                <i class="bi bi-circle text-muted me-2"></i>
                            @endif
                            <strong>IX Head</strong>
                        </li>
                        <li class="mb-2">
                            @if($ceoCompleted)
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            @else
                                <i class="bi bi-circle text-muted me-2"></i>
                            @endif
                            <strong>CEO</strong>
                        </li>
                        <li class="mb-2">
                            @if($nodalCompleted)
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            @else
                                <i class="bi bi-circle text-muted me-2"></i>
                            @endif
                            <strong>Nodal Officer</strong>
                        </li>
                        <li class="mb-2">
                            @if($techCompleted)
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            @else
                                <i class="bi bi-circle text-muted me-2"></i>
                            @endif
                            <strong>IX Tech Team</strong>
                        </li>
                        <li class="mb-2">
                            @if($accountCompleted)
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            @else
                                <i class="bi bi-circle text-muted me-2"></i>
                            @endif
                            <strong>IX Account</strong>
                        </li>
                        <li class="mb-2">
                            @if($completedCompleted)
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            @else
                                <i class="bi bi-circle text-muted me-2"></i>
                            @endif
                            <strong>Completed</strong>
                        </li>
                    @else
                        {{-- Legacy Workflow Stages --}}
                        @php
                            $isApproved = $application->status === 'approved';
                            $processorCompleted = $isApproved || in_array($application->status, ['pending', 'processor_review', 'processor_approved', 'finance_review', 'finance_approved']);
                            $financeCompleted = $isApproved || in_array($application->status, ['processor_approved', 'finance_review', 'finance_approved']);
                            $technicalCompleted = $isApproved || $application->status === 'finance_approved';
                        @endphp
                        <li class="mb-2">
                            @if($processorCompleted)
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            @else
                                <i class="bi bi-circle text-muted me-2"></i>
                            @endif
                            <strong>Processor</strong>
                        </li>
                        <li class="mb-2">
                            @if($financeCompleted)
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            @else
                                <i class="bi bi-circle text-muted me-2"></i>
                            @endif
                            <strong>Finance</strong>
                        </li>
                        <li class="mb-2">
                            @if($technicalCompleted)
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            @else
                                <i class="bi bi-circle text-muted me-2"></i>
                            @endif
                            <strong>Technical</strong>
                        </li>
                        <li class="mb-2">
                            @if($isApproved)
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            @else
                                <i class="bi bi-circle text-muted me-2"></i>
                            @endif
                            <strong>Approved</strong>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <a href="{{ route('user.applications.index') }}" class="btn btn-secondary w-100">Back to Applications</a>
    </div>
</div>

@if($application->application_type === 'IRINN')
@php
$data = $application->application_data ?? [];
$files = $data['files'] ?? [];

// Get company details from GST verification if available
$gstVerification = $application->gstVerification;
$companyDetails = [];
if ($gstVerification) {
    $companyDetails = [
        'legal_name' => $gstVerification->legal_name,
        'trade_name' => $gstVerification->trade_name,
        'pan' => $gstVerification->pan,
        'state' => $gstVerification->state,
        'registration_date' => $gstVerification->registration_date?->format('d/m/Y'),
        'gst_type' => $gstVerification->gst_type,
        'company_status' => $gstVerification->company_status,
        'primary_address' => $gstVerification->primary_address,
    ];
    
    // Parse primary address if it's a JSON string
    if ($gstVerification->verification_data) {
        $verificationData = is_string($gstVerification->verification_data) 
            ? json_decode($gstVerification->verification_data, true) 
            : $gstVerification->verification_data;
        
        if (isset($verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'])) {
            $address = $verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'];
            $companyDetails['pradr'] = [
                'addr' => trim(($address['door_number'] ?? '') . ' ' . ($address['building_name'] ?? '') . ' ' . ($address['street'] ?? '') . ' ' . ($address['location'] ?? '') . ' ' . ($address['dst'] ?? '') . ' ' . ($address['city'] ?? '') . ' ' . ($address['state_name'] ?? '') . ' ' . ($address['pincode'] ?? ''))
            ];
        }
    }
}
@endphp
<!-- Application Details Modal -->
<div class="modal fade" id="applicationDetailsModal" tabindex="-1" aria-labelledby="applicationDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="applicationDetailsModalLabel">
                    IRINN Application Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if(empty($data))
                    <div class="alert alert-warning">
                        <p>No application data available.</p>
                    </div>
                @else

                <!-- Company Information -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Company Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>GSTIN:</strong> {{ $data['gstin'] ?? 'N/A' }}</p>
                                <p><strong>Legal Name:</strong> {{ $companyDetails['legal_name'] ?? 'N/A' }}</p>
                                <p><strong>Trade Name:</strong> {{ $companyDetails['trade_name'] ?? 'N/A' }}</p>
                                <p><strong>PAN:</strong> {{ $companyDetails['pan'] ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>State:</strong> {{ $companyDetails['state'] ?? 'N/A' }}</p>
                                <p><strong>Registration Date:</strong> {{ $companyDetails['registration_date'] ?? 'N/A' }}</p>
                                <p><strong>GST Type:</strong> {{ $companyDetails['gst_type'] ?? 'N/A' }}</p>
                                <p><strong>Company Status:</strong> {{ $companyDetails['company_status'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                        @if(!empty($companyDetails['pradr']))
                        <div class="mt-3">
                            <strong>Principal Address:</strong>
                            <p class="mb-0">{{ $companyDetails['pradr']['addr'] ?? 'N/A' }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Applicant Details -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Applicant Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> {{ $data['mr_name'] ?? 'N/A' }}</p>
                                <p><strong>Email:</strong> {{ $data['mr_email'] ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Mobile:</strong> {{ $data['mr_mobile'] ?? 'N/A' }}</p>
                                <p><strong>Designation:</strong> {{ $data['mr_designation'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- IRINN Specific Details -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">IRINN Specific Details</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Account Name:</strong> {{ $data['account_name'] ?? 'N/A' }}</p>
                        <p><strong>Dot in Domain Required:</strong> {{ isset($data['dot_in_domain_required']) && $data['dot_in_domain_required'] ? 'Yes' : 'No' }}</p>
                    </div>
                </div>

                <!-- IP Address Requirements -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">IP Address Requirements</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                @if(isset($data['ipv4_selected']) && $data['ipv4_selected'])
                                    <p><strong>IPv4:</strong> Selected</p>
                                    <p><strong>IPv4 Size:</strong> {{ $data['ipv4_size'] ?? 'N/A' }}</p>
                                    <p><strong>IPv4 Fee:</strong> ₹ {{ number_format($data['ipv4_fee'] ?? 0, 2) }}</p>
                                @else
                                    <p><strong>IPv4:</strong> Not Selected</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                @if(isset($data['ipv6_selected']) && $data['ipv6_selected'])
                                    <p><strong>IPv6:</strong> Selected</p>
                                    <p><strong>IPv6 Size:</strong> {{ $data['ipv6_size'] ?? 'N/A' }}</p>
                                    <p><strong>IPv6 Fee:</strong> ₹ {{ number_format($data['ipv6_fee'] ?? 0, 2) }}</p>
                                @else
                                    <p><strong>IPv6:</strong> Not Selected</p>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="mb-0"><strong>Total Fee:</strong> ₹ {{ number_format($data['total_fee'] ?? 0, 2) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Business & Network Details -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Business & Network Details</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Nature of Business:</strong> {{ $data['nature_of_business'] ?? 'N/A' }}</p>
                        <p><strong>Industry Type:</strong> {{ $data['industry_type'] ?? 'N/A' }}</p>
                        @if(!empty($data['udyam_number']))
                        <p><strong>UDYAM Number:</strong> {{ $data['udyam_number'] }}</p>
                        @endif
                        @if(!empty($data['mca_tan']))
                        <p><strong>MCA TAN:</strong> {{ $data['mca_tan'] }}</p>
                        @endif
                        
                        @if(isset($data['as_number_required']) && $data['as_number_required'])
                            <div class="mt-3">
                                <p><strong>ASN Required:</strong> Yes</p>
                                @if(!empty($data['upstream_name']))
                                <p><strong>Upstream Provider Name:</strong> {{ $data['upstream_name'] }}</p>
                                <p><strong>Upstream Provider Mobile:</strong> {{ $data['upstream_mobile'] ?? 'N/A' }}</p>
                                <p><strong>Upstream Provider Email:</strong> {{ $data['upstream_email'] ?? 'N/A' }}</p>
                                <p><strong>Upstream ASN:</strong> {{ $data['upstream_asn'] ?? 'N/A' }}</p>
                                @endif
                            </div>
                        @else
                            <div class="mt-3">
                                <p><strong>ASN Required:</strong> No</p>
                                @if(!empty($data['company_asn']))
                                <p><strong>Company ASN:</strong> {{ $data['company_asn'] }}</p>
                                @endif
                                @if(!empty($data['isp_company_name']))
                                <p><strong>ISP Company Name:</strong> {{ $data['isp_company_name'] }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Billing Details -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Billing Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Affiliate Name:</strong> {{ $data['billing_affiliate_name'] ?? 'N/A' }}</p>
                                <p><strong>Email:</strong> {{ $data['billing_email'] ?? 'N/A' }}</p>
                                <p><strong>Mobile:</strong> {{ $data['billing_mobile'] ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Address:</strong> {{ $data['billing_address'] ?? 'N/A' }}</p>
                                <p><strong>City:</strong> {{ $data['billing_city'] ?? 'N/A' }}</p>
                                <p><strong>State:</strong> {{ $data['billing_state'] ?? 'N/A' }}</p>
                                <p><strong>Postal Code:</strong> {{ $data['billing_postal_code'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Uploaded Documents -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Uploaded Documents</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @if(!empty($files['network_plan_file']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['network_plan_file']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Network Plan:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'network_plan_file']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Network Plan
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['payment_receipts_file']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['payment_receipts_file']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Payment Receipts:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'payment_receipts_file']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Payment Receipts
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['equipment_details_file']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['equipment_details_file']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Equipment Details:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'equipment_details_file']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Equipment Details
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_business_address_proof']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_business_address_proof']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Business Address Proof:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_business_address_proof']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_authorization_doc']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_authorization_doc']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Authorization Document:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_authorization_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_signature_proof']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_signature_proof']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Signature Proof:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_signature_proof']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_gst_certificate']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_gst_certificate']))
                            <div class="col-md-6 mb-2">
                                <p><strong>GST Certificate:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_gst_certificate']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_partnership_deed']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_partnership_deed']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Partnership Deed:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_partnership_deed']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_partnership_entity_doc']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_partnership_entity_doc']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Partnership Entity Document:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_partnership_entity_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_incorporation_cert']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_incorporation_cert']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Certificate of Incorporation:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_incorporation_cert']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_company_pan_gstin']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_company_pan_gstin']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Company PAN/GSTIN:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_company_pan_gstin']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_sole_proprietorship_doc']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_sole_proprietorship_doc']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Sole Proprietorship Document:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_sole_proprietorship_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_udyam_cert']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_udyam_cert']))
                            <div class="col-md-6 mb-2">
                                <p><strong>UDYAM Certificate:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_udyam_cert']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_establishment_reg']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_establishment_reg']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Establishment Registration:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_establishment_reg']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_school_pan_gstin']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_school_pan_gstin']))
                            <div class="col-md-6 mb-2">
                                <p><strong>School PAN/GSTIN:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_school_pan_gstin']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_rbi_license']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_rbi_license']))
                            <div class="col-md-6 mb-2">
                                <p><strong>RBI License:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_rbi_license']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_bank_pan_gstin']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_bank_pan_gstin']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Bank PAN/GSTIN:</strong></p>
                                <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => 'kyc_bank_pan_gstin']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@elseif($application->application_type === 'IX')
@php
    $ixData = $application->application_data ?? [];
    $ixDocuments = $ixData['documents'] ?? [];
    $locationInfo = $ixData['location'] ?? [];
    $portInfo = $ixData['port_selection'] ?? [];
    $ipInfo = $ixData['ip_prefix'] ?? [];
    $routerInfo = $ixData['router_details'] ?? [];
@endphp
<div class="row mt-4">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">IX Application Summary</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Member Type</dt>
                    <dd class="col-sm-8">{{ $ixData['member_type'] ?? 'N/A' }}</dd>
                    <dt class="col-sm-4">NIXI Location</dt>
                    <dd class="col-sm-8">
                        {{ $locationInfo['name'] ?? 'N/A' }}
                        ({{ ucfirst($locationInfo['node_type'] ?? '-') }}, {{ $locationInfo['state'] ?? '-' }})
                    </dd>
                    <dt class="col-sm-4">Switch Details</dt>
                    <dd class="col-sm-8">{{ $locationInfo['switch_details'] ?? 'N/A' }}</dd>
                    <dt class="col-sm-4">Port Capacity</dt>
                    <dd class="col-sm-8">{{ $portInfo['capacity'] ?? 'N/A' }}</dd>
                    <dt class="col-sm-4">Billing Plan</dt>
                    <dd class="col-sm-8">{{ strtoupper($portInfo['billing_plan'] ?? '-') }}</dd>
                    <dt class="col-sm-4">Estimated Amount</dt>
                    <dd class="col-sm-8">₹{{ number_format($portInfo['amount'] ?? 0, 2) }} {{ $portInfo['currency'] ?? 'INR' }}</dd>
                    <dt class="col-sm-4">IP Prefixes</dt>
                    <dd class="col-sm-8">{{ $ipInfo['count'] ?? 'N/A' }}</dd>
                    <dt class="col-sm-4">ASN Number</dt>
                    <dd class="col-sm-8">{{ $ixData['peering']['asn_number'] ?? 'N/A' }}</dd>
                    <dt class="col-sm-4">Pre-NIXI Connectivity</dt>
                    <dd class="col-sm-8">{{ ucfirst($ixData['peering']['pre_nixi_connectivity'] ?? '-') }}</dd>
                </dl>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Router Details</h5>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>Height in U:</strong> {{ $routerInfo['height_u'] ?? 'N/A' }}</p>
                <p class="mb-1"><strong>Make &amp; Model:</strong> {{ $routerInfo['make_model'] ?? 'N/A' }}</p>
                <p class="mb-0"><strong>Serial Number:</strong> {{ $routerInfo['serial_number'] ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light">
                <h5 class="mb-0">Uploaded Documents</h5>
            </div>
            <div class="card-body">
                @if(!empty($ixDocuments))
                    <ul class="list-unstyled mb-0">
                        @php
                            // Document display order and labels
                            $documentLabels = [
                                'agreement_file' => 'Signed Agreement',
                                'license_isp_file' => 'ISP License',
                                'license_vno_file' => 'VNO License',
                                'cdn_declaration_file' => 'CDN Declaration',
                                'general_declaration_file' => 'General Declaration',
                                'board_resolution_file' => 'Board Resolution',
                                'whois_details_file' => 'Whois Details',
                                'pan_document_file' => 'PAN Document',
                                'gstin_document_file' => 'GSTIN Document',
                                'new_gst_document' => 'New GST Document',
                                'msme_document_file' => 'MSME Document',
                                'incorporation_document_file' => 'Certificate of Incorporation',
                                'authorized_rep_document_file' => 'Authorized Representative Document',
                            ];
                            
                            // If new_gst_document exists, use that and hide gstin_document_file (they're the same file)
                            // Otherwise, show gstin_document_file from previous application
                            $displayDocs = [];
                            $hasNewGstDoc = isset($ixDocuments['new_gst_document']);
                            
                            foreach ($ixDocuments as $key => $path) {
                                // Skip gstin_document_file if new_gst_document exists (they point to same file)
                                if ($key === 'gstin_document_file' && $hasNewGstDoc) {
                                    continue;
                                }
                                $displayDocs[$key] = $path;
                            }
                        @endphp
                        @foreach($displayDocs as $key => $path)
                            <li class="mb-2">
                                <i class="bi bi-file-earmark-text me-1 text-primary"></i>
                                {{ $documentLabels[$key] ?? ucwords(str_replace(['_', 'file'], [' ', ''], $key)) }}
                                @if($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path))
                                    <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => $key]) }}" target="_blank" class="ms-2 small">View</a>
                                @else
                                    <span class="ms-2 small text-muted">File not found</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">No documents available.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
// Comprehensive cleanup function
function forceModalCleanup() {
    const modal = document.getElementById('applicationDetailsModal');
    
    // Remove all backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Remove body classes and styles
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    document.body.style.overflowX = '';
    document.body.style.paddingLeft = '';
    
    // Hide modal if it exists
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');
        modal.removeAttribute('role');
    }
    
    // Remove any inline styles that might block interaction
    const bodyStyle = window.getComputedStyle(document.body);
    if (bodyStyle.overflow === 'hidden') {
        document.body.style.overflow = '';
    }
}

function openApplicationModal() {
    const modalElement = document.getElementById('applicationDetailsModal');
    if (modalElement) {
        // Try Bootstrap 5 modal
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modal.show();
        } else {
            // Fallback: manually show modal
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'modalBackdrop';
            document.body.appendChild(backdrop);
        }
    } else {
        console.error('Modal element not found');
        alert('Unable to load application details. Please refresh the page.');
    }
}

// Close modal handler for fallback
function closeApplicationModal() {
    forceModalCleanup();
}

// Also handle click event
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('applicationDetailsModal');
    
    // Handle Bootstrap modal events
    if (modal && typeof bootstrap !== 'undefined') {
        // Cleanup when modal is fully hidden
        modal.addEventListener('hidden.bs.modal', function() {
            forceModalCleanup();
        });
        
        // Cleanup when modal starts hiding
        modal.addEventListener('hide.bs.modal', function() {
            // Start cleanup early
            setTimeout(forceModalCleanup, 50);
        });
    }
    
    const btn = document.getElementById('viewDetailsBtn');
    if (btn) {
        btn.addEventListener('click', function(e) {
            // Let Bootstrap handle it if available, otherwise use our function
            if (typeof bootstrap === 'undefined') {
                e.preventDefault();
                openApplicationModal();
            }
        });
    }
    
    // Handle close button clicks - immediate cleanup
    const closeButtons = document.querySelectorAll('#applicationDetailsModal [data-bs-dismiss="modal"], #applicationDetailsModal .btn-close');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (typeof bootstrap === 'undefined') {
                closeApplicationModal();
            } else {
                // Immediate cleanup
                forceModalCleanup();
                // Also cleanup after animation completes
                setTimeout(forceModalCleanup, 300);
            }
        });
    });
    
    // Close on backdrop click
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                if (typeof bootstrap === 'undefined') {
                    closeApplicationModal();
                } else {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                    }
                    // Immediate cleanup
                    forceModalCleanup();
                    // Also cleanup after animation completes
                    setTimeout(forceModalCleanup, 300);
                }
            }
        });
    }
    
    // Periodic cleanup check (in case events don't fire)
    setInterval(function() {
        const modal = document.getElementById('applicationDetailsModal');
        const backdrops = document.querySelectorAll('.modal-backdrop');
        
        // If modal is not showing but backdrops exist, clean up
        if (modal && !modal.classList.contains('show') && backdrops.length > 0) {
            forceModalCleanup();
        }
        
        // If body has modal-open but no modal is showing, clean up
        if (document.body.classList.contains('modal-open') && modal && !modal.classList.contains('show')) {
            forceModalCleanup();
        }
    }, 500);
    
    // Cleanup on page visibility change
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            const modal = document.getElementById('applicationDetailsModal');
            const backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 0 && modal && !modal.classList.contains('show')) {
                forceModalCleanup();
            }
        }
    });
    
    // Cleanup on window focus (in case modal was closed while tab was inactive)
    window.addEventListener('focus', function() {
        const modal = document.getElementById('applicationDetailsModal');
        if (modal && !modal.classList.contains('show')) {
            forceModalCleanup();
        }
    });
    });
    
    // Handle status history collapse icon rotation
    const statusHistory = document.getElementById('statusHistory');
    const statusHistoryIcon = document.querySelector('.status-history-icon');
    
    if (statusHistory && statusHistoryIcon) {
        statusHistory.addEventListener('show.bs.collapse', function() {
            statusHistoryIcon.style.transform = 'rotate(0deg)';
        });
        
        statusHistory.addEventListener('hide.bs.collapse', function() {
            statusHistoryIcon.style.transform = 'rotate(180deg)';
        });
    }
</script>
@endpush

<!-- Status History (Collapsible) -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center" 
                 style="border-radius: 16px 16px 0 0; cursor: pointer;" 
                 data-bs-toggle="collapse" 
                 data-bs-target="#statusHistory" 
                 aria-expanded="false" 
                 aria-controls="statusHistory">
                <h5 class="mb-0" style="font-weight: 600;">Status History</h5>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="status-history-icon" viewBox="0 0 16 16" style="transition: transform 0.3s; transform: rotate(180deg);">
                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                </svg>
            </div>
            <div id="statusHistory" class="collapse">
                <div class="card-body p-4">
                    @if($application->statusHistory && $application->statusHistory->count() > 0)
                        <div class="timeline">
                            @foreach($application->statusHistory->sortBy('created_at') as $history)
                            <div class="mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ $history->status_from ? ucfirst(str_replace('_', ' ', $history->status_from)) : 'New' }}</strong>
                                        <i class="bi bi-arrow-right mx-2"></i>
                                        <strong>{{ ucfirst(str_replace('_', ' ', $history->status_to)) }}</strong>
                                    </div>
                                    <small class="text-muted">{{ $history->created_at->format('d M Y, h:i A') }}</small>
                                </div>
                                @if($history->notes)
                                <p class="mb-0 mt-2"><small class="text-muted">{{ $history->notes }}</small></p>
                                @endif
                                @php
                                    $changedBy = $history->changedBy();
                                @endphp
                                @if($changedBy)
                                <p class="mb-0 mt-1">
                                    <small class="text-muted">
                                        Changed by: 
                                        @if($history->changed_by_type === 'admin')
                                            {{ $changedBy->name ?? 'Admin' }}
                                        @elseif($history->changed_by_type === 'superadmin')
                                            {{ $changedBy->name ?? 'SuperAdmin' }}
                                        @elseif($history->changed_by_type === 'user')
                                            User
                                        @endif
                                    </small>
                                </p>
                                @elseif($history->changed_by_type === 'user')
                                <p class="mb-0 mt-1">
                                    <small class="text-muted">Changed by: User</small>
                                </p>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No status history available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

