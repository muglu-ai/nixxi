@extends('user.layout')

@section('title', 'My Applications')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary">
                <h4 class="mb-0">My Applications</h4>
            </div>
            <div class="card-body">

                @if($applications->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Application ID</th>
                                    <th>Node Name</th>
                                    <th>Status</th>
                                    <th>Current Stage</th>
                                    <th>Submitted At</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($applications as $application)
                                @php
                                    $paymentData = $application->application_type === 'IX'
                                        ? ($application->application_data['payment'] ?? null)
                                        : null;
                                    $isIxDraftAwaitingPayment = $application->application_type === 'IX'
                                        && $application->status === 'draft'
                                        && ($paymentData['status'] ?? null) === 'pending';
                                @endphp
                                <tr>
                                    <td><strong>{{ $application->application_id }}</strong></td>
                                    <td>
                                        @php
                                            $locationData = $application->application_data['location'] ?? null;
                                        @endphp
                                        @if($locationData)
                                            <div>{{ $locationData['name'] ?? 'N/A' }}</div>
                                            @if(isset($locationData['node_type']))
                                                <small class="text-muted">{{ ucfirst($locationData['node_type']) }}</small>
                                            @endif
                                            @if(isset($locationData['state']))
                                                <br><small class="text-muted">{{ $locationData['state'] }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isIxDraftAwaitingPayment)
                                            <span class="badge bg-warning">Draft - Payment Pending</span>
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
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $application->current_stage }}</span>
                                    </td>
                                    <td>{{ $application->submitted_at ? $application->submitted_at->format('d M Y, h:i A') : 'N/A' }}</td>
                                    <td>{{ $application->updated_at->format('d M Y, h:i A') }}</td>
                                    <td style="vertical-align: middle;">
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <a href="{{ route('user.applications.show', $application->id) }}" class="btn btn-sm btn-primary">
                                                View Details
                                            </a>

                                            @if($isIxDraftAwaitingPayment)
                                                <a href="{{ route('user.applications.ix.pay-now', $application->id) }}" class="btn btn-sm btn-success">
                                                    Pay Now
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $applications->links('vendor.pagination.bootstrap-5') }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16" class="text-muted mb-3">
                            <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                        </svg>
                        <p class="text-muted mb-4">No applications yet.</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="{{ route('user.applications.ix.create') }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-file-earmark-text"></i> IX Application
                            </a>
                        </div>
                    </div>
                @endif
                
                @if($applications->count() > 0 && isset($hasSubmittedIxApplication) && $hasSubmittedIxApplication)
                    <div class="mt-4 text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('user.applications.ix.create-new') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> New IX Application
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
