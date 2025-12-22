@extends('superadmin.layout')

@section('title', 'IX Point Details')

@push('styles')
<style>
    .bg-purple {
        background-color: #6f42c1 !important;
        color: white !important;
    }
    .accordion-button:not(.collapsed) {
        background-color: #f8f9fa;
    }
    .accordion-button:focus {
        box-shadow: none;
        border-color: #e0e0e0;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $location->name }}</h2>
                    <p class="mb-0">IX Point Details</p>
                    <div class="accent-line"></div>
                </div>
                <div>
                    <a href="{{ route('superadmin.ix-points') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Grid View
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Location Details -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Location Details</h5>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="40%" class="text-muted">Name:</th>
                            <td><strong>{{ $location->name }}</strong></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Node Type:</th>
                            <td>
                                @if($location->node_type === 'metro')
                                    <span class="badge bg-success">{{ ucfirst($location->node_type) }}</span>
                                @else
                                    <span class="badge bg-info">{{ ucfirst($location->node_type) }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">State:</th>
                            <td>{{ $location->state }}</td>
                        </tr>
                        @if($location->city)
                        <tr>
                            <th class="text-muted">City:</th>
                            <td>{{ $location->city }}</td>
                        </tr>
                        @endif
                        @if($location->ports)
                        <tr>
                            <th class="text-muted">Ports:</th>
                            <td>{{ $location->ports }}</td>
                        </tr>
                        @endif
                        @if($location->switch_details)
                        <tr>
                            <th class="text-muted">Switch Details:</th>
                            <td>{{ $location->switch_details }}</td>
                        </tr>
                        @endif
                        @if($location->nodal_officer)
                        <tr>
                            <th class="text-muted">Nodal Officer:</th>
                            <td>{{ $location->nodal_officer }}</td>
                        </tr>
                        @endif
                        @if($location->zone)
                        <tr>
                            <th class="text-muted">Zone:</th>
                            <td>{{ $location->zone }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th class="text-muted">Status:</th>
                            <td>
                                @if($location->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Admin-Only Details -->
            <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Admin-Only Details</h5>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless mb-0">
                        @if($location->p2p_capacity)
                        <tr>
                            <th width="40%" class="text-muted">P2P Capacity:</th>
                            <td><strong>{{ $location->p2p_capacity }}</strong></td>
                        </tr>
                        @endif
                        @if($location->p2p_provider)
                        <tr>
                            <th class="text-muted">P2P Provider:</th>
                            <td>{{ $location->p2p_provider }}</td>
                        </tr>
                        @endif
                        @if($location->connected_main_node)
                        <tr>
                            <th class="text-muted">Connected Main Node:</th>
                            <td>{{ $location->connected_main_node }}</td>
                        </tr>
                        @endif
                        @if($location->p2p_arc)
                        <tr>
                            <th class="text-muted">P2P ARC:</th>
                            <td><strong>₹{{ number_format($location->p2p_arc, 2) }}</strong></td>
                        </tr>
                        @endif
                        @if($location->colocation_provider)
                        <tr>
                            <th class="text-muted">Colocation Provider:</th>
                            <td>{{ $location->colocation_provider }}</td>
                        </tr>
                        @endif
                        @if($location->colocation_arc)
                        <tr>
                            <th class="text-muted">Colocation ARC:</th>
                            <td><strong>₹{{ number_format($location->colocation_arc, 2) }}</strong></td>
                        </tr>
                        @endif
                        @if(!$location->p2p_capacity && !$location->p2p_provider && !$location->connected_main_node && !$location->p2p_arc && !$location->colocation_provider && !$location->colocation_arc)
                        <tr>
                            <td colspan="2" class="text-muted text-center">No admin-only details available.</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- Applications -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Applications ({{ $applications->count() }})</h5>
                </div>
                <div class="card-body p-4">
                    <!-- Application Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                                <h3 class="mb-0">{{ $applications->count() }}</h3>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                <h3 class="mb-0">{{ $applicationsByStatus['approved']->count() }}</h3>
                                <small class="text-muted">Approved</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                                <h3 class="mb-0">{{ $applicationsByStatus['pending']->count() }}</h3>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-danger bg-opacity-10 rounded">
                                <h3 class="mb-0">{{ $applicationsByStatus['rejected']->count() }}</h3>
                                <small class="text-muted">Rejected</small>
                            </div>
                        </div>
                    </div>

                    <!-- Applications List -->
                    @if($applications->count() > 0)
                        <div class="accordion" id="applicationsAccordion">
                            @foreach($applications as $index => $application)
                            @php
                                $appData = $application->application_data ?? [];
                                $locationData = $appData['location'] ?? null;
                                $portSelection = $appData['port_selection'] ?? null;
                                $representative = $appData['representative'] ?? null;
                                $gstin = $appData['gstin'] ?? null;
                                
                                // Determine current phase
                                $currentPhase = 'Unknown';
                                $phaseBadgeClass = 'bg-secondary';
                                if($application->status === 'approved' || $application->status === 'payment_verified') {
                                    $currentPhase = 'Completed';
                                    $phaseBadgeClass = 'bg-success';
                                } elseif($application->status === 'rejected' || $application->status === 'ceo_rejected') {
                                    $currentPhase = 'Rejected';
                                    $phaseBadgeClass = 'bg-danger';
                                } elseif(in_array($application->status, ['submitted', 'resubmitted', 'processor_resubmission', 'legal_sent_back', 'head_sent_back'])) {
                                    $currentPhase = 'IX Processor Review';
                                    $phaseBadgeClass = 'bg-warning';
                                } elseif($application->status === 'processor_forwarded_legal') {
                                    $currentPhase = 'IX Legal Review';
                                    $phaseBadgeClass = 'bg-info';
                                } elseif($application->status === 'legal_forwarded_head') {
                                    $currentPhase = 'IX Head Review';
                                    $phaseBadgeClass = 'bg-primary';
                                } elseif($application->status === 'head_forwarded_ceo') {
                                    $currentPhase = 'CEO Review';
                                    $phaseBadgeClass = 'bg-purple';
                                } elseif($application->status === 'ceo_approved') {
                                    $currentPhase = 'Nodal Officer Review';
                                    $phaseBadgeClass = 'bg-info';
                                } elseif($application->status === 'port_assigned') {
                                    $currentPhase = 'IX Tech Team Review';
                                    $phaseBadgeClass = 'bg-primary';
                                } elseif(in_array($application->status, ['ip_assigned', 'invoice_pending'])) {
                                    $currentPhase = 'IX Account Review';
                                    $phaseBadgeClass = 'bg-warning';
                                } else {
                                    $currentPhase = $application->current_stage ?? 'Draft';
                                }
                            @endphp
                            <div class="accordion-item mb-3" style="border-radius: 12px; overflow: hidden; border: 1px solid #e0e0e0;">
                                <h2 class="accordion-header" id="heading{{ $index }}">
                                    <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $index }}">
                                        <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div>
                                                    <strong style="color: #2c3e50;">{{ $application->application_id }}</strong>
                                                    <div class="small text-muted">{{ $application->user->fullname ?? 'N/A' }}</div>
                                                </div>
                                                <div>
                                                    <span class="badge {{ $phaseBadgeClass }}">{{ $currentPhase }}</span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    @if($application->submitted_at)
                                                        {{ $application->submitted_at->format('d M Y') }}
                                                    @else
                                                        Not submitted
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="heading{{ $index }}" data-bs-parent="#applicationsAccordion">
                                    <div class="accordion-body">
                                        <div class="row g-3">
                                            <!-- User Information -->
                                            <div class="col-md-6">
                                                <div class="card bg-light">
                                                    <div class="card-body p-3">
                                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">User Information</h6>
                                                        <table class="table table-sm table-borderless mb-0">
                                                            <tr>
                                                                <td class="text-muted" width="40%">Name:</td>
                                                                <td><strong>{{ $application->user->fullname ?? 'N/A' }}</strong></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">Email:</td>
                                                                <td>{{ $application->user->email ?? 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">Registration ID:</td>
                                                                <td>{{ $application->user->registrationid ?? 'N/A' }}</td>
                                                            </tr>
                                                            @if($representative)
                                                            <tr>
                                                                <td class="text-muted">Representative:</td>
                                                                <td>{{ $representative['name'] ?? 'N/A' }}</td>
                                                            </tr>
                                                            @endif
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Application Details -->
                                            <div class="col-md-6">
                                                <div class="card bg-light">
                                                    <div class="card-body p-3">
                                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">Application Details</h6>
                                                        <table class="table table-sm table-borderless mb-0">
                                                            <tr>
                                                                <td class="text-muted" width="40%">Application ID:</td>
                                                                <td><strong>{{ $application->application_id }}</strong></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">Status:</td>
                                                                <td>
                                                                    @if($application->status === 'approved' || $application->status === 'payment_verified')
                                                                        <span class="badge bg-success">Approved</span>
                                                                    @elseif($application->status === 'rejected' || $application->status === 'ceo_rejected')
                                                                        <span class="badge bg-danger">Rejected</span>
                                                                    @else
                                                                        <span class="badge bg-warning">Pending</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">Current Phase:</td>
                                                                <td><span class="badge {{ $phaseBadgeClass }}">{{ $currentPhase }}</span></td>
                                                            </tr>
                                                            @if($portSelection)
                                                            <tr>
                                                                <td class="text-muted">Port Capacity:</td>
                                                                <td>{{ $portSelection['capacity'] ?? 'N/A' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">Billing Plan:</td>
                                                                <td>{{ ucfirst($portSelection['billing_plan'] ?? 'N/A') }}</td>
                                                            </tr>
                                                            @endif
                                                            @if($application->assigned_port_capacity)
                                                            <tr>
                                                                <td class="text-muted">Assigned Port:</td>
                                                                <td><strong>{{ $application->assigned_port_capacity }}</strong></td>
                                                            </tr>
                                                            @endif
                                                            @if($application->assigned_port_number)
                                                            <tr>
                                                                <td class="text-muted">Port Number:</td>
                                                                <td><strong>{{ $application->assigned_port_number }}</strong></td>
                                                            </tr>
                                                            @endif
                                                            @if($application->assigned_ip)
                                                            <tr>
                                                                <td class="text-muted">Assigned IP:</td>
                                                                <td><strong>{{ $application->assigned_ip }}</strong></td>
                                                            </tr>
                                                            @endif
                                                            @if($application->customer_id)
                                                            <tr>
                                                                <td class="text-muted">Customer ID:</td>
                                                                <td><strong>{{ $application->customer_id }}</strong></td>
                                                            </tr>
                                                            @endif
                                                            @if($application->membership_id)
                                                            <tr>
                                                                <td class="text-muted">Membership ID:</td>
                                                                <td><strong>{{ $application->membership_id }}</strong></td>
                                                            </tr>
                                                            @endif
                                                            @if($gstin)
                                                            <tr>
                                                                <td class="text-muted">GSTIN:</td>
                                                                <td>{{ $gstin }}</td>
                                                            </tr>
                                                            @endif
                                                            <tr>
                                                                <td class="text-muted">Submitted:</td>
                                                                <td>
                                                                    @if($application->submitted_at)
                                                                        {{ $application->submitted_at->format('d M Y, h:i A') }}
                                                                    @else
                                                                        <span class="text-muted">Not submitted</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            @if($application->approved_at)
                                                            <tr>
                                                                <td class="text-muted">Approved:</td>
                                                                <td>{{ $application->approved_at->format('d M Y, h:i A') }}</td>
                                                            </tr>
                                                            @endif
                                                            @if($application->rejection_reason)
                                                            <tr>
                                                                <td class="text-muted">Rejection Reason:</td>
                                                                <td class="text-danger">{{ $application->rejection_reason }}</td>
                                                            </tr>
                                                            @endif
                                                            @if($application->resubmission_query)
                                                            <tr>
                                                                <td class="text-muted">Resubmission Query:</td>
                                                                <td class="text-warning">{{ $application->resubmission_query }}</td>
                                                            </tr>
                                                            @endif
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">No applications found for this IX point.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
