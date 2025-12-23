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

<!-- Filters and Search Form -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.applications') }}" class="row g-3">
                    @if(request('role'))
                        <input type="hidden" name="role" value="{{ request('role') }}">
                    @endif
                    
                    <!-- Search -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Search</label>
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by application ID, applicant name, email, registration ID, membership ID, customer ID, mobile, or status..."
                               value="{{ request('search') }}">
                    </div>
                    
                    <!-- Filters Row -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="ip_assigned" {{ request('status') === 'ip_assigned' ? 'selected' : '' }}>IP Assigned</option>
                            <option value="invoice_pending" {{ request('status') === 'invoice_pending' ? 'selected' : '' }}>Invoice Pending</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Assigned Role</label>
                        <select name="role_filter" class="form-select">
                            <option value="">All Roles</option>
                            <option value="ix_processor" {{ request('role_filter') === 'ix_processor' ? 'selected' : '' }}>IX Processor</option>
                            <option value="ix_legal" {{ request('role_filter') === 'ix_legal' ? 'selected' : '' }}>IX Legal</option>
                            <option value="ix_head" {{ request('role_filter') === 'ix_head' ? 'selected' : '' }}>IX Head</option>
                            <option value="ceo" {{ request('role_filter') === 'ceo' ? 'selected' : '' }}>CEO</option>
                            <option value="nodal_officer" {{ request('role_filter') === 'nodal_officer' ? 'selected' : '' }}>Nodal Officer</option>
                            <option value="ix_tech_team" {{ request('role_filter') === 'ix_tech_team' ? 'selected' : '' }}>IX Tech Team</option>
                            <option value="ix_account" {{ request('role_filter') === 'ix_account' ? 'selected' : '' }}>IX Account</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Registration Date</label>
                        <select name="registration_filter" class="form-select">
                            <option value="">All Time</option>
                            <option value="today" {{ request('registration_filter') === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="this_week" {{ request('registration_filter') === 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="this_month" {{ request('registration_filter') === 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="this_year" {{ request('registration_filter') === 'this_year' ? 'selected' : '' }}>This Year</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Live Status</label>
                        <select name="is_active" class="form-select">
                            <option value="">All</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Live</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Not Live</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ request('payment_status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        </select>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="{{ route('admin.applications', ['role' => request('role')]) }}" class="btn btn-outline-secondary">Clear Filters</a>
                    </div>
                    
                    @if(request('search') || request('status') || request('role_filter') || request('registration_filter') || request('is_active') || request('payment_status'))
                        <div class="col-12">
                            <small class="text-muted">
                                Active filters: 
                                @if(request('search'))<span class="badge bg-info">{{ request('search') }}</span>@endif
                                @if(request('status'))<span class="badge bg-info">Status: {{ request('status') }}</span>@endif
                                @if(request('role_filter'))<span class="badge bg-info">Role: {{ request('role_filter') }}</span>@endif
                                @if(request('registration_filter'))<span class="badge bg-info">Date: {{ request('registration_filter') }}</span>@endif
                                @if(request('is_active'))<span class="badge bg-info">Live: {{ request('is_active') === '1' ? 'Yes' : 'No' }}</span>@endif
                                @if(request('payment_status'))<span class="badge bg-info">Payment: {{ request('payment_status') }}</span>@endif
                            </small>
                        </div>
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
                                    <th>Applicant Name</th>
                                    <th>Node Name</th>
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

