@extends('admin.layout')

@section('title', 'Applications')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">Applications</h2>
            <p class="text-muted mb-0">
                @if(isset($selectedRole))
                    Viewing as: <strong>{{ ucfirst($selectedRole) }}</strong>
                @else
                    View and manage applications
                @endif
            </p>
        </div>
    </div>


@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Search Form -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.applications') }}" class="row g-3">
                    <div class="col-md-10">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by application ID, registration name, email, registration ID, or status..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    @if(request('search'))
                        <div class="col-12">
                            <a href="{{ route('admin.applications') }}" class="btn btn-sm btn-outline-secondary">Clear Search</a>
                            <small class="text-muted ms-2">Showing results for: <strong>{{ request('search') }}</strong></small>
                        </div>
                    @endif
                    @if(request('role'))
                        <input type="hidden" name="role" value="{{ request('role') }}">
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="mb-0" style="font-weight: 600;">Applications List</h5>
            </div>
            <div class="card-body">
                @if($applications->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Application ID</th>
                                    <th>Registration</th>
                                    <th>Status</th>
                                    <th>Submitted At</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($applications as $application)
                                <tr>
                                    <td><strong>{{ $application->application_id }}</strong></td>
                                    <td>
                                        <a href="{{ route('admin.users.show', $application->user_id) }}">
                                            {{ $application->user->fullname }}
                                        </a><br>
                                        <small class="text-muted">{{ $application->user->email }}</small>
                                    </td>
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
                                            @elseif(in_array($application->status, ['legal_forwarded_head', 'ceo_sent_back_head']))
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
                                    <td>{{ $application->submitted_at ? $application->submitted_at->format('d M Y, h:i A') : 'N/A' }}</td>
                                    <td>{{ $application->updated_at->format('d M Y, h:i A') }}</td>
                                    <td>
                                        <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-sm btn-primary">View Details</a>
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
                        <p class="text-muted">No applications available at this stage.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

