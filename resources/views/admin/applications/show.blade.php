@extends('admin.layout')

@section('title', 'Application Details')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>Application Details</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications') }}">Applications</a></li>
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
                        <th>User:</th>
                        <td>
                            <a href="{{ route('admin.users.show', $application->user_id) }}">
                                {{ $application->user->fullname }}
                            </a><br>
                            <small class="text-muted">{{ $application->user->email }}</small>
                        </td>
                    </tr>
                    @if($application->membership_id)
                    <tr>
                        <th>Live Status:</th>
                        <td>
                            @if($application->is_active)
                                <span class="badge bg-success">LIVE</span>
                            @else
                                <span class="badge bg-danger">NOT LIVE</span>
                            @endif
                        </td>
                    </tr>
                    @endif
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
                @elseif($application->application_type === 'IX')
                <div class="mt-4">
                    <button type="button" class="btn btn-primary" id="viewIxDetailsBtn" data-bs-toggle="modal" data-bs-target="#ixApplicationDetailsModal" onclick="openIxApplicationModal()">
                        View Full Application Details
                    </button>
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
        <!-- Action Panel -->
        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Actions</h5>
                <small class="text-white-50">Actions are only available for applications in your stage</small>
            </div>
            <div class="card-body">
                @php
                    // Determine which role to use for actions
                    $roleToUse = $selectedRole ?? null;
                    if ($admin->roles->count() === 1) {
                        $roleToUse = $admin->roles->first()->slug;
                    }
                @endphp
                
                @if(!$roleToUse || ($application->application_type === 'IX' && !$application->isVisibleToIxProcessor() && !$application->isVisibleToIxLegal() && !$application->isVisibleToIxHead() && !$application->isVisibleToCeo() && !$application->isVisibleToNodalOfficer() && !$application->isVisibleToIxTechTeam() && !$application->isVisibleToIxAccount()))
                    <div class="alert alert-info">
                        <small>This application is not in your action stage. You can view all details but cannot perform actions.</small>
                    </div>
                @endif

                @if($roleToUse === 'processor' && $application->isVisibleToProcessor())
                    <form method="POST" action="{{ route('admin.applications.approve-to-finance', $application->id) }}" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this application and forward it to Finance?')">
                            Approve to Finance
                        </button>
                    </form>
                @endif

                @if($roleToUse === 'finance' && $application->isVisibleToFinance())
                    <form method="POST" action="{{ route('admin.applications.approve-to-technical', $application->id) }}" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this application and forward it to Technical?')">
                            Approve to Technical
                        </button>
                    </form>

                    <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#rejectToProcessorModal">
                        Send Back to Processor
                    </button>

                    <!-- Reject to Processor Modal -->
                    <div class="modal fade" id="rejectToProcessorModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.applications.send-back-to-processor', $application->id) }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Send Back to Processor</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10" placeholder="Please provide a detailed reason for sending this application back to Processor..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning">Send Back</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                @if($roleToUse === 'technical' && $application->isVisibleToTechnical())
                    <form method="POST" action="{{ route('admin.applications.approve', $application->id) }}" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this application? This is the final approval.')">
                            Approve Application
                        </button>
                    </form>

                    <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#rejectToFinanceModal">
                        Send Back to Finance
                    </button>

                    <!-- Reject to Finance Modal -->
                    <div class="modal fade" id="rejectToFinanceModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.applications.send-back-to-finance', $application->id) }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Send Back to Finance</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10" placeholder="Please provide a detailed reason for sending this application back to Finance..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning">Send Back</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- New IX Workflow Actions --}}
                @if($application->application_type === 'IX')
                    {{-- IX Processor Actions --}}
                    @if($roleToUse === 'ix_processor' && $application->isVisibleToIxProcessor())
                        <form method="POST" action="{{ route('admin.applications.ix-processor.forward-to-legal', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Forward this application to IX Legal?')">
                                Forward to Legal
                            </button>
                        </form>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#requestResubmissionModal">
                            Request Resubmission
                        </button>
                        <!-- Request Resubmission Modal -->
                        <div class="modal fade" id="requestResubmissionModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.ix-processor.request-resubmission', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Request Resubmission</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="resubmission_query" class="form-label">Query/Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="resubmission_query" name="resubmission_query" rows="4" required minlength="10" placeholder="Please provide details about what needs to be resubmitted..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Request Resubmission</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- IX Legal Actions --}}
                    @if($roleToUse === 'ix_legal' && $application->isVisibleToIxLegal())
                        <form method="POST" action="{{ route('admin.applications.ix-legal.forward-to-head', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Forward this application to IX Head?')">
                                Forward to IX Head
                            </button>
                        </form>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#legalSendBackModal">
                            Send Back to Processor
                        </button>
                        <!-- Legal Send Back Modal -->
                        <div class="modal fade" id="legalSendBackModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.ix-legal.send-back-to-processor', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Send Back to Processor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Send Back</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- IX Head Actions --}}
                    @if($roleToUse === 'ix_head' && $application->isVisibleToIxHead())
                        <form method="POST" action="{{ route('admin.applications.ix-head.forward-to-ceo', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Forward this application to CEO?')">
                                Forward to CEO
                            </button>
                        </form>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#headSendBackModal">
                            Send Back to Processor
                        </button>
                        <!-- Head Send Back Modal -->
                        <div class="modal fade" id="headSendBackModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.ix-head.send-back-to-processor', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Send Back to Processor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Send Back</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- CEO Actions --}}
                    @if($roleToUse === 'ceo' && $application->isVisibleToCeo())
                        <form method="POST" action="{{ route('admin.applications.ceo.approve', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Approve this application and forward to Nodal Officer?')">
                                Approve (Forward to Nodal Officer)
                            </button>
                        </form>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#ceoSendBackModal">
                            Send Back to IX Head
                        </button>
                        <button type="button" class="btn btn-danger w-100 mb-3" data-bs-toggle="modal" data-bs-target="#ceoRejectModal">
                            Reject
                        </button>
                        <!-- CEO Send Back Modal -->
                        <div class="modal fade" id="ceoSendBackModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.ceo.send-back-to-head', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Send Back to IX Head</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="send_back_reason" class="form-label">Reason (Optional)</label>
                                                <textarea class="form-control" id="send_back_reason" name="send_back_reason" rows="4" maxlength="1000" placeholder="Enter reason for sending back to IX Head (optional)"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Send Back to IX Head</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- CEO Reject Modal -->
                        <div class="modal fade" id="ceoRejectModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.ceo.reject', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reject Application</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Reject</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Nodal Officer Actions --}}
                    @if($roleToUse === 'nodal_officer' && $application->isVisibleToNodalOfficer())
                        <button type="button" class="btn btn-success w-100 mb-3" data-bs-toggle="modal" data-bs-target="#assignPortModal">
                            Assign Port (Forward to Tech Team)
                        </button>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#holdModal">
                            Hold
                        </button>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#notFeasibleModal">
                            Not Feasible
                        </button>
                        <form method="POST" action="{{ route('admin.applications.nodal-officer.customer-denied', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-100" onclick="return confirm('Mark as Customer Denied?')">
                                Customer Denied
                            </button>
                        </form>
                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#nodalForwardToProcessorModal">
                            Forward to Processor
                        </button>
                        <!-- Assign Port Modal -->
                        <div class="modal fade" id="assignPortModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.nodal-officer.assign-port', $application->id) }}">
                                        @csrf
                                        @php
                                            $appData = $application->application_data ?? [];
                                            $portSelection = $appData['port_selection'] ?? [];
                                            $userSelectedPortCapacity = $portSelection['capacity'] ?? '';
                                        @endphp
                                        <div class="modal-header">
                                            <h5 class="modal-title">Assign Port</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="assigned_port_capacity" class="form-label">Port Capacity <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="assigned_port_capacity" name="assigned_port_capacity" value="{{ $userSelectedPortCapacity }}" required readonly style="background-color: #e9ecef;">
                                                @if($userSelectedPortCapacity)
                                                    <small class="text-muted">Pre-filled from user's application</small>
                                                @endif
                                            </div>
                                            <div class="mb-3">
                                                <label for="assigned_port_number" class="form-label">Port Number</label>
                                                <input type="text" class="form-control" id="assigned_port_number" name="assigned_port_number" placeholder="Enter port number">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">Assign Port</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Hold Modal -->
                        <div class="modal fade" id="holdModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.nodal-officer.hold', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Hold Application</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Hold</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Not Feasible Modal -->
                        <div class="modal fade" id="notFeasibleModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.nodal-officer.not-feasible', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Mark as Not Feasible</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Mark as Not Feasible</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Forward to Processor Modal -->
                        <div class="modal fade" id="nodalForwardToProcessorModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.nodal-officer.forward-to-processor', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Forward to Processor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Forward to Processor</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- IX Tech Team Actions --}}
                    @if($roleToUse === 'ix_tech_team' && $application->isVisibleToIxTechTeam())
                        <button type="button" class="btn btn-success w-100 mb-3" data-bs-toggle="modal" data-bs-target="#assignIpModal">
                            Assign IP (Make Live)
                        </button>
                        <!-- Assign IP Modal -->
                        <div class="modal fade" id="assignIpModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.ix-tech-team.assign-ip', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Assign IP and Make Live</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="assigned_ip" class="form-label">Assigned IP <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="assigned_ip" name="assigned_ip" placeholder="Enter IP address" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="customer_id" class="form-label">Customer ID <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="customer_id" name="customer_id" value="{{ $application->user->registrationid ?? '' }}" required readonly style="background-color: #e9ecef;">
                                                <small class="text-muted">Auto-filled from user's registration ID</small>
                                            </div>
                                            <div class="mb-3">
                                                <label for="membership_id" class="form-label">Membership ID <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="membership_id" name="membership_id" value="{{ $application->application_id }}" required readonly style="background-color: #e9ecef;">
                                                <small class="text-muted">Auto-filled from application ID</small>
                                            </div>
                                            <div class="mb-3">
                                                <label for="service_activation_date" class="form-label">Service Activation Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="service_activation_date" name="service_activation_date" value="{{ old('service_activation_date', date('Y-m-d')) }}" required>
                                                <small class="text-muted">This date will be used to calculate billing cycle and payment reminders</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">Assign IP & Make Live</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- IX Account Actions --}}
                    @if($roleToUse === 'ix_account' && $application->isVisibleToIxAccount())
                        @if($application->is_active)
                            {{-- Generate Invoice - Always available for LIVE applications --}}
                            <a href="{{ route('admin.applications.ix-account.generate-invoice', $application->id) }}" class="btn btn-primary w-100 mb-3">
                                Generate Invoice
                            </a>
                            
                            {{-- Verify Payment - Only show if not already verified for current period --}}
                            @if(isset($canVerifyPayment) && $canVerifyPayment)
                                <form method="POST" action="{{ route('admin.applications.ix-account.verify-payment', $application->id) }}" class="mb-3">
                                    @csrf
                                    @if(isset($currentInvoice))
                                        <div class="alert alert-info mb-2 small">
                                            <strong>Expected Amount:</strong> ₹{{ number_format($currentInvoice->balance_amount ?? $currentInvoice->total_amount, 2) }}
                                            @if($currentInvoice->has_carry_forward && $currentInvoice->carry_forward_amount > 0)
                                                <br><small class="text-muted">(Includes carry-forward: ₹{{ number_format($currentInvoice->carry_forward_amount, 2) }})</small>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="mb-2">
                                        <label for="payment_id" class="form-label small">Payment ID <span class="text-danger">*</span>:</label>
                                        <input type="text" name="payment_id" id="payment_id" class="form-control form-control-sm" required placeholder="Enter payment ID/transaction ID">
                                    </div>
                                    <div class="mb-2">
                                        <label for="amount_captured" class="form-label small">Amount Captured (₹) <span class="text-danger">*</span>:</label>
                                        <input type="number" name="amount_captured" id="amount_captured" class="form-control form-control-sm" step="0.01" min="0" required placeholder="0.00" value="{{ isset($currentInvoice) ? ($currentInvoice->balance_amount ?? $currentInvoice->total_amount) : '' }}">
                                        @if(isset($currentInvoice))
                                            <small class="text-muted">Balance amount: ₹{{ number_format($currentInvoice->balance_amount ?? $currentInvoice->total_amount, 2) }}</small>
                                        @endif
                                    </div>
                                    <div class="mb-2">
                                        <label for="verification_notes" class="form-label small">Notes (Optional):</label>
                                        <textarea name="notes" id="verification_notes" class="form-control form-control-sm" rows="2" placeholder="Add any notes about this payment verification..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Verify payment for this application?')">
                                        Verify Payment
                                    </button>
                                </form>
                            @elseif(isset($paymentVerificationMessage))
                                <div class="alert alert-info mb-3">
                                    <small>{{ $paymentVerificationMessage }}</small>
                                </div>
                            @endif
                            
                            {{-- Payment Verification History --}}
                            @php
                                $verificationLogs = $application->paymentVerificationLogs()->with('verifiedBy')->latest()->take(5)->get();
                            @endphp
                            @if($verificationLogs->count() > 0)
                                <div class="mt-3">
                                    <h6 class="small mb-2">Recent Payment Verifications:</h6>
                                    <div class="list-group list-group-flush">
                                        @foreach($verificationLogs as $log)
                                        <div class="list-group-item px-0 py-2 small">
                                            <div class="d-flex justify-content-between">
                                                <span>
                                                    <strong>{{ $log->verification_type === 'initial' ? 'Initial' : 'Recurring' }}</strong>
                                                    @if($log->billing_period)
                                                        - {{ $log->billing_period }}
                                                    @endif
                                                    @if($log->amount_captured && $log->amount_captured < $log->amount)
                                                        <span class="badge bg-warning text-dark ms-1">Partial</span>
                                                    @endif
                                                </span>
                                                <span class="text-muted">{{ $log->verified_at->format('d M Y') }}</span>
                                            </div>
                                            <div class="text-muted">
                                                @if($log->amount_captured)
                                                    ₹{{ number_format($log->amount_captured, 2) }} 
                                                    @if($log->amount_captured < $log->amount)
                                                        of ₹{{ number_format($log->amount, 2) }}
                                                    @endif
                                                @else
                                                    ₹{{ number_format($log->amount, 2) }}
                                                @endif
                                                @if($log->payment_id)
                                                    <br><small class="text-muted">Payment ID: {{ $log->payment_id }}</small>
                                                @endif
                                                @if($log->verifiedBy)
                                                    <br><small>by {{ $log->verifiedBy->name }}</small>
                                                @else
                                                    <br><small>(Auto-verified via PayU)</small>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            {{-- Invoices History --}}
                            @php
                                $invoices = $application->invoices()->with('generatedBy')->latest()->take(5)->get();
                            @endphp
                            @if($invoices->count() > 0)
                                <div class="mt-3">
                                    <h6 class="small mb-2">Recent Invoices:</h6>
                                    <div class="list-group list-group-flush">
                                        @foreach($invoices as $invoice)
                                        <div class="list-group-item px-0 py-2 small">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span>
                                                            <strong>{{ $invoice->invoice_number }}</strong>
                                                            @if($invoice->billing_period)
                                                                - {{ $invoice->billing_period }}
                                                            @endif
                                                        </span>
                                                        <span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'warning') }}">
                                                            {{ ucfirst($invoice->status) }}
                                                        </span>
                                                    </div>
                                                    <div class="text-muted mb-2">
                                                        ₹{{ number_format($invoice->total_amount, 2) }} 
                                                        @if($invoice->payment_status === 'partial')
                                                            | Paid: ₹{{ number_format($invoice->paid_amount, 2) }} | Balance: ₹{{ number_format($invoice->balance_amount, 2) }}
                                                        @endif
                                                        | Due: {{ $invoice->due_date->format('d M Y') }}
                                                        @if($invoice->generatedBy)
                                                            | Generated by {{ $invoice->generatedBy->name }}
                                                        @endif
                                                    </div>
                                                    
                                                    {{-- Invoice Management Actions --}}
                                                    <div class="btn-group btn-group-sm mt-2" role="group">
                                                        <a href="{{ route('admin.applications.invoice.edit', $invoice->id) }}" class="btn btn-outline-primary" title="Edit Invoice">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                                <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/>
                                                            </svg>
                                                            Edit
                                                        </a>
                                                        @if($invoice->status === 'paid')
                                                        <form method="POST" action="{{ route('admin.applications.invoice.mark-unpaid', $invoice->id) }}" class="d-inline" onsubmit="return confirm('Mark this invoice as unpaid? This will reset all payment information.');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-warning" title="Mark as Unpaid">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                                                </svg>
                                                                Unpaid
                                                            </button>
                                                        </form>
                                                        @else
                                                        <form method="POST" action="{{ route('admin.applications.invoice.mark-paid', $invoice->id) }}" class="d-inline">
                                                            @csrf
                                                            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#markPaidModal{{ $invoice->id }}" title="Mark as Paid">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                                                </svg>
                                                                Paid
                                                            </button>
                                                        </form>
                                                        @endif
                                                        <form method="POST" action="{{ route('admin.applications.invoice.change-status', $invoice->id) }}" class="d-inline" id="statusForm{{ $invoice->id }}">
                                                            @csrf
                                                            <select name="payment_status" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="document.getElementById('statusForm{{ $invoice->id }}').submit();">
                                                                <option value="pending" {{ $invoice->payment_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                                <option value="partial" {{ $invoice->payment_status === 'partial' ? 'selected' : '' }}>Partial</option>
                                                                <option value="paid" {{ $invoice->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                                                                <option value="overdue" {{ $invoice->payment_status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                                                <option value="cancelled" {{ $invoice->payment_status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                            </select>
                                                            <input type="hidden" name="status" value="{{ $invoice->status }}">
                                                        </form>
                                                        <form method="POST" action="{{ route('admin.applications.invoice.delete', $invoice->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this invoice? This action cannot be undone.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger" title="Delete Invoice">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                                                </svg>
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                    
                                                    @if($invoice->status === 'paid' && $invoice->manual_payment_id)
                                                    <div class="mt-2 text-success small">
                                                        Payment ID: {{ $invoice->manual_payment_id }}
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            {{-- Mark Paid Modal --}}
                                            <div class="modal fade" id="markPaidModal{{ $invoice->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Mark Invoice as Paid</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="{{ route('admin.applications.invoice.mark-paid', $invoice->id) }}">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Payment ID <span class="text-danger">*</span></label>
                                                                    <input type="text" name="payment_id" class="form-control" required placeholder="Reference / UTR">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Notes (optional)</label>
                                                                    <textarea name="notes" class="form-control" rows="2" placeholder="Any remarks"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-success">Mark as Paid</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="alert alert-warning mb-3">
                                <small>Actions are only available for LIVE applications.</small>
                            </div>
                        @endif
                    @endif
                @endif

                {{-- Legacy Workflow Actions (for backward compatibility) --}}
                @if($application->application_type !== 'IX')
                    @if($roleToUse === 'processor' && $application->isVisibleToProcessor())
                        <form method="POST" action="{{ route('admin.applications.approve-to-finance', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this application and forward it to Finance?')">
                                Approve to Finance
                            </button>
                        </form>
                    @endif

                    @if($roleToUse === 'finance' && $application->isVisibleToFinance())
                        <form method="POST" action="{{ route('admin.applications.approve-to-technical', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this application and forward it to Technical?')">
                                Approve to Technical
                            </button>
                        </form>

                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#rejectToProcessorModal">
                            Send Back to Processor
                        </button>

                        <!-- Reject to Processor Modal -->
                        <div class="modal fade" id="rejectToProcessorModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.send-back-to-processor', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Send Back to Processor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10" placeholder="Please provide a detailed reason for sending this application back to Processor..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Send Back</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($roleToUse === 'technical' && $application->isVisibleToTechnical())
                        <form method="POST" action="{{ route('admin.applications.approve', $application->id) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this application? This is the final approval.')">
                                Approve Application
                            </button>
                        </form>

                        <button type="button" class="btn btn-warning w-100 mb-3" data-bs-toggle="modal" data-bs-target="#rejectToFinanceModal">
                            Send Back to Finance
                        </button>

                        <!-- Reject to Finance Modal -->
                        <div class="modal fade" id="rejectToFinanceModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.applications.send-back-to-finance', $application->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Send Back to Finance</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required minlength="10" placeholder="Please provide a detailed reason for sending this application back to Finance..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Send Back</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                @if($application->application_type === 'IX' && !in_array($roleToUse, ['ix_processor', 'ix_legal', 'ix_head', 'ceo', 'nodal_officer', 'ix_tech_team', 'ix_account']))
                    <div class="alert alert-info mb-3">
                        <small>Please select an IX workflow role from the dropdown to take actions on this application.</small>
                    </div>
                @elseif($application->application_type !== 'IX' && !in_array($roleToUse, ['processor', 'finance', 'technical']))
                    <div class="alert alert-info mb-3">
                        <small>Please select a role from the dropdown to take actions on this application.</small>
                    </div>
                @endif

                <a href="{{ route('admin.applications', ['role' => $selectedRole]) }}" class="btn btn-secondary w-100">Back to Applications</a>
            </div>
        </div>

        <!-- User Information -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">User Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> {{ $application->user->fullname }}</p>
                <p><strong>Email:</strong> {{ $application->user->email }}</p>
                <p><strong>Mobile:</strong> {{ $application->user->mobile }}</p>
                <p><strong>PAN:</strong> {{ $application->user->pancardno }}</p>
                <a href="{{ route('admin.users.show', $application->user_id) }}" class="btn btn-sm btn-primary w-100">View Full Profile</a>
            </div>
        </div>
    </div>
</div>

@if($application->application_type === 'IRINN')
@php
$data = $application->application_data ?? [];
$gstData = $data['gst_data'] ?? [];
$companyDetails = $gstData['company_details'] ?? [];
$files = $data['files'] ?? [];
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
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'network_plan_file']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Network Plan
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['payment_receipts_file']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['payment_receipts_file']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Payment Receipts:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'payment_receipts_file']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Payment Receipts
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['equipment_details_file']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['equipment_details_file']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Equipment Details:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'equipment_details_file']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Equipment Details
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_business_address_proof']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_business_address_proof']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Business Address Proof:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_business_address_proof']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_authorization_doc']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_authorization_doc']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Authorization Document:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_authorization_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_signature_proof']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_signature_proof']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Signature Proof:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_signature_proof']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_gst_certificate']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_gst_certificate']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>GST Certificate:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_gst_certificate']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_partnership_deed']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_partnership_deed']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Partnership Deed:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_partnership_deed']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_partnership_entity_doc']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_partnership_entity_doc']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Partnership Entity Document:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_partnership_entity_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_incorporation_cert']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_incorporation_cert']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Certificate of Incorporation:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_incorporation_cert']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_company_pan_gstin']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_company_pan_gstin']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Company PAN/GSTIN:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_company_pan_gstin']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_sole_proprietorship_doc']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_sole_proprietorship_doc']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Sole Proprietorship Document:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_sole_proprietorship_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_udyam_cert']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_udyam_cert']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>UDYAM Certificate:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_udyam_cert']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_establishment_reg']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_establishment_reg']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Establishment Registration:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_establishment_reg']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_school_pan_gstin']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_school_pan_gstin']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>School PAN/GSTIN:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_school_pan_gstin']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_rbi_license']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_rbi_license']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>RBI License:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_rbi_license']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(!empty($files['kyc_bank_pan_gstin']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($files['kyc_bank_pan_gstin']))
                                <div class="col-md-6 mb-2">
                                    <p><strong>Bank PAN/GSTIN:</strong></p>
                                    <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'kyc_bank_pan_gstin']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Document
                                    </a>
                                </div>
                                @endif
                                
                                @if(empty($files))
                                <div class="col-12">
                                    <p class="text-muted">No documents uploaded.</p>
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
    $peeringInfo = $ixData['peering'] ?? [];
    $paymentInfo = $ixData['payment'] ?? [];
@endphp
<!-- IX Application Details Modal -->
<div class="modal fade" id="ixApplicationDetailsModal" tabindex="-1" aria-labelledby="ixApplicationDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="ixApplicationDetailsModalLabel">
                    IX Application Details - {{ $application->application_id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if(empty($ixData))
                    <div class="alert alert-warning">
                        <p>No application data available.</p>
                    </div>
                @else
                    <!-- Member Type & Location -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Member Type & Location</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Member Type:</strong> {{ $ixData['member_type'] ?? 'N/A' }}</p>
                                    <p><strong>NIXI Location:</strong> {{ $locationInfo['name'] ?? 'N/A' }}</p>
                                    <p><strong>Node Type:</strong> {{ ucfirst($locationInfo['node_type'] ?? 'N/A') }}</p>
                                    <p><strong>State:</strong> {{ $locationInfo['state'] ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Switch Details:</strong> {{ $locationInfo['switch_details'] ?? 'N/A' }}</p>
                                    <p><strong>Address:</strong> {{ $locationInfo['address'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Port Selection -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Port Selection</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Port Capacity:</strong> {{ $portInfo['capacity'] ?? 'N/A' }}</p>
                                    <p><strong>Billing Plan:</strong> {{ strtoupper($portInfo['billing_plan'] ?? 'N/A') }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Estimated Amount:</strong> ₹{{ number_format($portInfo['amount'] ?? 0, 2) }} {{ $portInfo['currency'] ?? 'INR' }}</p>
                                    @if($application->assigned_port_capacity)
                                    <p><strong>Assigned Port Capacity:</strong> <span class="badge bg-success">{{ $application->assigned_port_capacity }}</span></p>
                                    @endif
                                    @if($application->assigned_port_number)
                                    <p><strong>Assigned Port Number:</strong> <span class="badge bg-info">{{ $application->assigned_port_number }}</span></p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- IP Prefix -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">IP Prefix Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Number of Prefixes:</strong> {{ $ipInfo['count'] ?? 'N/A' }}</p>
                                    <p><strong>Source:</strong> {{ strtoupper($ipInfo['source'] ?? 'N/A') }}</p>
                                </div>
                                <div class="col-md-6">
                                    @if($application->assigned_ip)
                                    <p><strong>Assigned IP:</strong> <span class="badge bg-success">{{ $application->assigned_ip }}</span></p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Peering Details -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Peering Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>ASN Number:</strong> {{ $peeringInfo['asn_number'] ?? 'N/A' }}</p>
                                    <p><strong>Pre-NIXI Connectivity:</strong> {{ ucfirst($peeringInfo['pre_nixi_connectivity'] ?? 'N/A') }}</p>
                                </div>
                                <div class="col-md-6">
                                    @if($application->customer_id)
                                    <p><strong>Customer ID:</strong> <span class="badge bg-primary">{{ $application->customer_id }}</span></p>
                                    @endif
                                    @if($application->membership_id)
                                    <p><strong>Membership ID:</strong> <span class="badge bg-primary">{{ $application->membership_id }}</span></p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Router Details -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Router Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Height in U:</strong> {{ $routerInfo['height_u'] ?? 'N/A' }}</p>
                                    <p><strong>Make & Model:</strong> {{ $routerInfo['make_model'] ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Serial Number:</strong> {{ $routerInfo['serial_number'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    @if($paymentInfo)
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Payment Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Application Fee:</strong> ₹{{ number_format($paymentInfo['application_fee'] ?? 0, 2) }}</p>
                                    <p><strong>GST ({{ $paymentInfo['gst_percentage'] ?? 0 }}%):</strong> ₹{{ number_format(($paymentInfo['application_fee'] ?? 0) * ($paymentInfo['gst_percentage'] ?? 0) / 100, 2) }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total Amount:</strong> ₹{{ number_format($paymentInfo['total_amount'] ?? 0, 2) }}</p>
                                    <p><strong>Payment Status:</strong> 
                                        <span class="badge {{ ($paymentInfo['status'] ?? 'pending') === 'success' ? 'bg-success' : 'bg-warning' }}">
                                            {{ ucfirst($paymentInfo['status'] ?? 'pending') }}
                                        </span>
                                    </p>
                                    @if(isset($paymentInfo['paid_at']))
                                    <p><strong>Paid At:</strong> {{ \Carbon\Carbon::parse($paymentInfo['paid_at'])->format('d M Y, h:i A') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Uploaded Documents -->
                    <div class="card mb-3">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Uploaded Documents</h6>
                            <a href="{{ route('admin.applications.edit', $application->id) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil"></i> Update Documents & Details
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if(!empty($ixDocuments))
                                    @foreach($ixDocuments as $key => $path)
                                    <div class="col-md-6 mb-2">
                                        <p><strong>{{ ucwords(str_replace(['_', 'file'], [' ', ''], $key)) }}:</strong></p>
                                        @if($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path))
                                            <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => $key]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-file-earmark-text"></i> View Document
                                            </a>
                                        @else
                                            <span class="text-muted small">File not found</span>
                                        @endif
                                    </div>
                                    @endforeach
                                @else
                                    <div class="col-12">
                                        <p class="text-muted">No documents uploaded.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Application PDF -->
                    @if(isset($ixData['pdfs']['application_pdf']))
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Application PDF</h6>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => 'application_pdf']) }}" target="_blank" class="btn btn-primary">
                                <i class="bi bi-file-pdf"></i> Download Application PDF
                            </a>
                        </div>
                    </div>
                    @endif
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
function openIxApplicationModal() {
    const modalElement = document.getElementById('ixApplicationDetailsModal');
    if (modalElement) {
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modal.show();
        } else {
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'ixModalBackdrop';
            document.body.appendChild(backdrop);
        }
    } else {
        console.error('IX Application modal element not found');
        alert('Unable to load application details. Please refresh the page.');
    }
}

function openApplicationModal() {
    const modalElement = document.getElementById('applicationDetailsModal');
    if (modalElement) {
        // Try Bootstrap 5 modal
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalElement);
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

// Function to remove all modal backdrops and restore body
function cleanupModalBackdrop() {
    // Remove all modal backdrops (Bootstrap creates them without IDs sometimes)
    const allBackdrops = document.querySelectorAll('.modal-backdrop');
    allBackdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // Remove specific backdrop IDs if they exist
    const modalBackdrop = document.getElementById('modalBackdrop');
    if (modalBackdrop) {
        modalBackdrop.remove();
    }
    
    const ixModalBackdrop = document.getElementById('ixModalBackdrop');
    if (ixModalBackdrop) {
        ixModalBackdrop.remove();
    }
    
    // Remove modal-open class from body
    document.body.classList.remove('modal-open');
    
    // Remove padding-right that Bootstrap adds
    document.body.style.paddingRight = '';
    document.body.style.overflow = '';
}

// Close modal handler for fallback
function closeApplicationModal() {
    const modalElement = document.getElementById('applicationDetailsModal');
    if (modalElement) {
        modalElement.style.display = 'none';
        modalElement.classList.remove('show');
        cleanupModalBackdrop();
    }
}

// Close IX modal handler for fallback
function closeIxApplicationModal() {
    const modalElement = document.getElementById('ixApplicationDetailsModal');
    if (modalElement) {
        modalElement.style.display = 'none';
        modalElement.classList.remove('show');
        cleanupModalBackdrop();
    }
}

// Close modal handler
document.addEventListener('DOMContentLoaded', function() {
    // Handle applicationDetailsModal
    const modal = document.getElementById('applicationDetailsModal');
    if (modal) {
        // Cleanup when Bootstrap modal is hidden
        modal.addEventListener('hidden.bs.modal', function() {
            cleanupModalBackdrop();
        });
        
        // Also cleanup on hide event (before hidden)
        modal.addEventListener('hide.bs.modal', function() {
            // Ensure cleanup happens
            setTimeout(cleanupModalBackdrop, 100);
        });
    }
    
    // Handle ixApplicationDetailsModal
    const ixModal = document.getElementById('ixApplicationDetailsModal');
    if (ixModal) {
        // Cleanup when Bootstrap modal is hidden
        ixModal.addEventListener('hidden.bs.modal', function() {
            cleanupModalBackdrop();
        });
        
        // Also cleanup on hide event (before hidden)
        ixModal.addEventListener('hide.bs.modal', function() {
            // Ensure cleanup happens
            setTimeout(cleanupModalBackdrop, 100);
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
    
    // Handle close button clicks for applicationDetailsModal
    const closeButtons = document.querySelectorAll('#applicationDetailsModal [data-bs-dismiss="modal"], #applicationDetailsModal .btn-close');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (typeof bootstrap === 'undefined') {
                closeApplicationModal();
            } else {
                // Even with Bootstrap, ensure cleanup
                setTimeout(cleanupModalBackdrop, 200);
            }
        });
    });
    
    // Handle close button clicks for ixApplicationDetailsModal
    const ixCloseButtons = document.querySelectorAll('#ixApplicationDetailsModal [data-bs-dismiss="modal"], #ixApplicationDetailsModal .btn-close');
    ixCloseButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (typeof bootstrap === 'undefined') {
                closeIxApplicationModal();
            } else {
                // Even with Bootstrap, ensure cleanup
                setTimeout(cleanupModalBackdrop, 200);
            }
        });
    });
    
    // Close on backdrop click for applicationDetailsModal
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal && typeof bootstrap === 'undefined') {
                closeApplicationModal();
            }
        });
    }
    
    // Close on backdrop click for ixApplicationDetailsModal
    if (ixModal) {
        ixModal.addEventListener('click', function(e) {
            if (e.target === ixModal && typeof bootstrap === 'undefined') {
                closeIxApplicationModal();
            }
        });
    }
    
    // Global cleanup - remove any leftover backdrops on page load
    cleanupModalBackdrop();
});
</script>
@endpush
@endsection
