@extends('admin.layout')

@section('title', 'Plan Change Requests')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">Plan Change Requests</h2>
            <p class="text-muted mb-0">Manage user requests for plan upgrades and downgrades.</p>
        </div>
        <div>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.plan-change.index') }}" class="d-flex gap-2">
                <select name="status" class="form-select" style="max-width: 200px;">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <select name="change_type" class="form-select" style="max-width: 200px;">
                    <option value="">All Types</option>
                    <option value="upgrade" {{ request('change_type') === 'upgrade' ? 'selected' : '' }}>Upgrade</option>
                    <option value="downgrade" {{ request('change_type') === 'downgrade' ? 'selected' : '' }}>Downgrade</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.plan-change.index') }}" class="btn btn-outline-secondary">Clear</a>
            </form>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="color: #2c3e50; font-weight: 600;">Application ID</th>
                            <th style="color: #2c3e50; font-weight: 600;">User</th>
                            <th style="color: #2c3e50; font-weight: 600;">Current Plan</th>
                            <th style="color: #2c3e50; font-weight: 600;">Requested Plan</th>
                            <th style="color: #2c3e50; font-weight: 600;">Change Type</th>
                            <th style="color: #2c3e50; font-weight: 600;">Adjustment</th>
                            <th style="color: #2c3e50; font-weight: 600;">Status</th>
                            <th style="color: #2c3e50; font-weight: 600;">Requested</th>
                            <th style="color: #2c3e50; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                        <tr>
                            <td>
                                <a href="{{ route('admin.applications.show', $request->application_id) }}" style="color: #0d6efd; text-decoration: none;">
                                    {{ $request->application->application_id }}
                                </a>
                            </td>
                            <td>{{ $request->user->fullname ?? 'N/A' }}</td>
                            <td>
                                <small>
                                    <strong>{{ $request->current_port_capacity ?? 'N/A' }}</strong><br>
                                    {{ strtoupper($request->current_billing_plan ?? 'N/A') }}<br>
                                    ₹{{ number_format($request->current_amount ?? 0, 2) }}
                                </small>
                            </td>
                            <td>
                                <small>
                                    <strong>{{ $request->new_port_capacity }}</strong><br>
                                    {{ strtoupper($request->new_billing_plan) }}<br>
                                    ₹{{ number_format($request->new_amount, 2) }}
                                </small>
                            </td>
                            <td>
                                @if($request->change_type === 'upgrade')
                                    <span class="badge bg-success">Upgrade</span>
                                @else
                                    <span class="badge bg-info">Downgrade</span>
                                @endif
                            </td>
                            <td>
                                @if($request->adjustment_amount > 0)
                                    <span class="text-danger">+₹{{ number_format($request->adjustment_amount, 2) }}</span>
                                @elseif($request->adjustment_amount < 0)
                                    <span class="text-success">₹{{ number_format(abs($request->adjustment_amount), 2) }}</span>
                                @else
                                    <span class="text-muted">₹0.00</span>
                                @endif
                            </td>
                            <td>
                                @if($request->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($request->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($request->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($request->status) }}</span>
                                @endif
                            </td>
                            <td>{{ $request->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('admin.plan-change.show', $request->id) }}" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                No plan change requests found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-center">
                {{ $requests->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
