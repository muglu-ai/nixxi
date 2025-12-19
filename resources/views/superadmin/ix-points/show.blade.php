@extends('superadmin.layout')

@section('title', 'IX Point Details')

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
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="color: #2c3e50; font-weight: 600;">Application ID</th>
                                        <th style="color: #2c3e50; font-weight: 600;">User</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Status</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Submitted</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($applications as $application)
                                    <tr>
                                        <td><strong>{{ $application->application_id }}</strong></td>
                                        <td>
                                            <div>{{ $application->user->fullname ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $application->user->email ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            @if($application->status === 'approved' || $application->status === 'payment_verified')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($application->status === 'rejected' || $application->status === 'ceo_rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @else
                                                <span class="badge bg-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($application->submitted_at)
                                                {{ $application->submitted_at->format('d M Y, h:i A') }}
                                            @else
                                                <span class="text-muted">Not submitted</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-sm btn-primary" target="_blank">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
