@extends('superadmin.layout')

@section('title', 'All Applications')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-1">All Applications</h2>
        <p class="mb-0">View and manage all IX applications</p>
        <div class="accent-line"></div>
    </div>

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body">
                    <form method="GET" action="{{ route('superadmin.applications.index') }}" class="row g-3">
                        <div class="col-md-10">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by application ID, membership ID, applicant name, email, or status..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Applications Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-0">
                    @if($applications->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="padding: 1rem;">Application ID</th>
                                        <th style="padding: 1rem;">Member</th>
                                        <th style="padding: 1rem;">Membership ID</th>
                                        <th style="padding: 1rem;">Status</th>
                                        <th style="padding: 1rem;">Live Status</th>
                                        <th style="padding: 1rem;">Created</th>
                                        <th style="padding: 1rem;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($applications as $application)
                                    <tr>
                                        <td style="padding: 1rem;">
                                            <strong>{{ $application->application_id }}</strong>
                                        </td>
                                        <td style="padding: 1rem;">
                                            {{ $application->user->fullname ?? 'N/A' }}
                                            <br><small class="text-muted">{{ $application->user->email ?? 'N/A' }}</small>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <strong>{{ $application->membership_id ?? 'N/A' }}</strong>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <span class="badge rounded-pill px-3 py-1
                                                @if($application->status === 'approved' || $application->status === 'payment_verified') bg-success
                                                @elseif(in_array($application->status, ['ip_assigned', 'invoice_pending'])) bg-info
                                                @elseif($application->status === 'rejected' || $application->status === 'ceo_rejected') bg-danger
                                                @else bg-secondary @endif">
                                                {{ $application->status_display }}
                                            </span>
                                        </td>
                                        <td style="padding: 1rem;">
                                            @if($application->is_active)
                                                <span class="badge bg-success">Live</span>
                                            @else
                                                <span class="badge bg-secondary">Not Live</span>
                                            @endif
                                        </td>
                                        <td style="padding: 1rem;">
                                            {{ $application->created_at->format('d M Y') }}
                                        </td>
                                        <td style="padding: 1rem;">
                                            <a href="{{ route('superadmin.applications.show', $application->id) }}" class="btn btn-sm btn-primary">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    Showing {{ $applications->firstItem() }} to {{ $applications->lastItem() }} of {{ $applications->total() }} applications
                                </div>
                                <div>
                                    {{ $applications->links() }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">No applications found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

