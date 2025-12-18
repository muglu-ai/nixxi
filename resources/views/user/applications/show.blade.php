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
                            @if($application->application_type === 'IX')
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
                        @if($application->membership_id)
                        <tr>
                            <th>Membership ID:</th>
                            <td><strong>{{ $application->membership_id }}</strong></td>
                        </tr>
                        @endif
                        @if($application->assigned_ip)
                        <tr>
                            <th>Assigned IP:</th>
                            <td><strong>{{ $application->assigned_ip }}</strong></td>
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

        <!-- Status History -->
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Status History</h5>
            </div>
            <div class="card-body">
                @if($application->statusHistory && $application->statusHistory->count() > 0)
                    <div class="timeline">
                        @foreach($application->statusHistory->sortBy('created_at') as $history)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $history->status_from ? ucfirst(str_replace('_', ' ', $history->status_from)) : 'New' }}</strong>
                                    <i class="bi bi-arrow-right"></i>
                                    <strong>{{ ucfirst(str_replace('_', ' ', $history->status_to)) }}</strong>
                                </div>
                                <small class="text-muted">{{ $history->created_at->format('d M Y, h:i A') }}</small>
                            </div>
                            @if($history->notes)
                            <p class="mb-0 mt-2"><small>{{ $history->notes }}</small></p>
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
                    <p class="text-muted">No status history available.</p>
                @endif
            </div>
        </div>
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
                            $isCompleted = in_array($application->status, ['payment_verified', 'approved']);
                            
                            $processorCompleted = in_array($application->status, ['processor_forwarded_legal', 'legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $legalCompleted = in_array($application->status, ['legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $headCompleted = in_array($application->status, ['head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $ceoCompleted = in_array($application->status, ['ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $nodalCompleted = in_array($application->status, ['port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $techCompleted = in_array($application->status, ['ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
                            $accountCompleted = in_array($application->status, ['payment_verified', 'approved']);
                            $completedCompleted = $isCompleted;
                            
                            $completedCount = ($processorCompleted ? 1 : 0) + ($legalCompleted ? 1 : 0) + ($headCompleted ? 1 : 0) + ($ceoCompleted ? 1 : 0) + ($nodalCompleted ? 1 : 0) + ($techCompleted ? 1 : 0) + ($accountCompleted ? 1 : 0) + ($completedCompleted ? 1 : 0);
                            $progress = ($completedCount / count($stages)) * 100;
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
                            @if(!empty($files['network_plan_file']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Network Plan:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['network_plan_file']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Network Plan
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['payment_receipts_file']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Payment Receipts:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['payment_receipts_file']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Payment Receipts
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['equipment_details_file']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Equipment Details:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['equipment_details_file']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Equipment Details
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_business_address_proof']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Business Address Proof:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_business_address_proof']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_authorization_doc']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Authorization Document:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_authorization_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_signature_proof']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Signature Proof:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_signature_proof']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_gst_certificate']))
                            <div class="col-md-6 mb-2">
                                <p><strong>GST Certificate:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_gst_certificate']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_partnership_deed']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Partnership Deed:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_partnership_deed']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_partnership_entity_doc']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Partnership Entity Document:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_partnership_entity_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_incorporation_cert']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Certificate of Incorporation:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_incorporation_cert']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_company_pan_gstin']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Company PAN/GSTIN:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_company_pan_gstin']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_sole_proprietorship_doc']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Sole Proprietorship Document:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_sole_proprietorship_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_udyam_cert']))
                            <div class="col-md-6 mb-2">
                                <p><strong>UDYAM Certificate:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_udyam_cert']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_establishment_reg']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Establishment Registration:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_establishment_reg']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_school_pan_gstin']))
                            <div class="col-md-6 mb-2">
                                <p><strong>School PAN/GSTIN:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_school_pan_gstin']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_rbi_license']))
                            <div class="col-md-6 mb-2">
                                <p><strong>RBI License:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_rbi_license']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Document
                                </a>
                            </div>
                            @endif
                            
                            @if(!empty($files['kyc_bank_pan_gstin']))
                            <div class="col-md-6 mb-2">
                                <p><strong>Bank PAN/GSTIN:</strong></p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($files['kyc_bank_pan_gstin']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
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
                                <a href="{{ Storage::url($path) }}" target="_blank" class="ms-2 small">View</a>
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
</script>
@endpush
@endsection

