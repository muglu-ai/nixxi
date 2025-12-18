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
                    <div class="mb-4 pb-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="mb-1" style="color: #2c3e50; font-weight: 600;">{{ $application->application_id }}</h6>
                                <p class="text-muted mb-0 small">{{ $application->application_type }}</p>
                            </div>
                            <div>
                                @if($application->status === 'approved' || $application->status === 'payment_verified')
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
                        
                        @php
                            if($application->application_type === 'IX') {
                                // New IX Workflow Stages
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
                        
                        <div class="progress mb-3" style="height: 25px; border-radius: 12px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                 role="progressbar" 
                                 style="width: {{ $progress }}%; border-radius: 12px; font-weight: 600; font-size: 0.875rem;"
                                 aria-valuenow="{{ $progress }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                {{ round($progress) }}%
                            </div>
                        </div>
                        
                        <div class="d-flex flex-wrap gap-3">
                            @if($application->application_type === 'IX')
                                {{-- New IX Workflow Stages --}}
                                <div class="d-flex align-items-center">
                                    @if($processorCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 18px;"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2" style="font-size: 18px;"></i>
                                    @endif
                                    <span style="color: #2c3e50; font-weight: 500; font-size: 0.875rem;">IX Processor</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    @if($legalCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 18px;"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2" style="font-size: 18px;"></i>
                                    @endif
                                    <span style="color: #2c3e50; font-weight: 500; font-size: 0.875rem;">IX Legal</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    @if($headCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 18px;"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2" style="font-size: 18px;"></i>
                                    @endif
                                    <span style="color: #2c3e50; font-weight: 500; font-size: 0.875rem;">IX Head</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    @if($ceoCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 18px;"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2" style="font-size: 18px;"></i>
                                    @endif
                                    <span style="color: #2c3e50; font-weight: 500; font-size: 0.875rem;">CEO</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    @if($nodalCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 18px;"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2" style="font-size: 18px;"></i>
                                    @endif
                                    <span style="color: #2c3e50; font-weight: 500; font-size: 0.875rem;">Nodal Officer</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    @if($techCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 18px;"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2" style="font-size: 18px;"></i>
                                    @endif
                                    <span style="color: #2c3e50; font-weight: 500; font-size: 0.875rem;">IX Tech Team</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    @if($accountCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 18px;"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2" style="font-size: 18px;"></i>
                                    @endif
                                    <span style="color: #2c3e50; font-weight: 500; font-size: 0.875rem;">IX Account</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    @if($completedCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 18px;"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2" style="font-size: 18px;"></i>
                                    @endif
                                    <span style="color: #2c3e50; font-weight: 500; font-size: 0.875rem;">Completed</span>
                                </div>
                            @else
                                {{-- Legacy Workflow Stages --}}
                                <div class="d-flex align-items-center">
                                    @if($processorCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 20px;"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2" style="font-size: 20px;"></i>
                                    @endif
                                    <span style="color: #2c3e50; font-weight: 500;">Processor</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    @if($financeCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 20px;"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2" style="font-size: 20px;"></i>
                                    @endif
                                    <span style="color: #2c3e50; font-weight: 500;">Finance</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    @if($technicalCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 20px;"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2" style="font-size: 20px;"></i>
                                    @endif
                                    <span style="color: #2c3e50; font-weight: 500;">Technical</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    @if($approvedCompleted)
                                        <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 20px;"></i>
                                    @else
                                        <i class="bi bi-circle text-muted me-2" style="font-size: 20px;"></i>
                                    @endif
                                    <span style="color: #2c3e50; font-weight: 500;">Approved</span>
                                </div>
                            @endif
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
                                New IX Application
                            </a>
                        @else
                            <a href="{{ route('user.applications.ix.create-new') }}" class="btn btn-primary px-4 py-2" style="border-radius: 10px; font-weight: 500;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                </svg>
                                New IX Application
                            </a>
                        @endif
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
@endsection
