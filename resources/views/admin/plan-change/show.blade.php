@extends('admin.layout')

@section('title', 'Plan Change Request Details')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">Plan Change Request Details</h2>
            <p class="text-muted mb-0">Application: {{ $request->application->application_id }}</p>
        </div>
        <div>
            <a href="{{ route('admin.plan-change.index') }}" class="btn btn-outline-secondary">
                ← Back to Requests
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Request Details -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Request Information</h5>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="40%" class="text-muted">Application ID:</th>
                            <td>
                                <a href="{{ route('admin.applications.show', $request->application_id) }}" style="color: #0d6efd; text-decoration: none;">
                                    {{ $request->application->application_id }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">User:</th>
                            <td>{{ $request->user->fullname ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Status:</th>
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
                        </tr>
                        <tr>
                            <th class="text-muted">Change Type:</th>
                            <td>
                                @if($request->change_type === 'upgrade')
                                    <span class="badge bg-success">Upgrade</span>
                                @else
                                    <span class="badge bg-info">Downgrade</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Requested:</th>
                            <td>{{ $request->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        @if($request->reviewed_at)
                        <tr>
                            <th class="text-muted">Reviewed:</th>
                            <td>{{ $request->reviewed_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Reviewed By:</th>
                            <td>{{ $request->reviewedBy->name ?? 'N/A' }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Current Plan -->
            <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Current Plan</h5>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="40%" class="text-muted">Port Capacity:</th>
                            <td><strong>{{ $request->current_port_capacity ?? 'N/A' }}</strong></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Billing Plan:</th>
                            <td>{{ strtoupper($request->current_billing_plan ?? 'N/A') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Amount:</th>
                            <td>₹{{ number_format($request->current_amount ?? 0, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Requested Plan -->
            <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
                <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Requested Plan</h5>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="40%" class="text-muted">Port Capacity:</th>
                            <td><strong>{{ $request->new_port_capacity }}</strong></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Billing Plan:</th>
                            <td>{{ strtoupper($request->new_billing_plan) }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Amount:</th>
                            <td>₹{{ number_format($request->new_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Adjustment:</th>
                            <td>
                                @if($request->adjustment_amount > 0)
                                    <span class="text-danger"><strong>+₹{{ number_format($request->adjustment_amount, 2) }}</strong> (Additional payment required)</span>
                                @elseif($request->adjustment_amount < 0)
                                    <span class="text-success"><strong>₹{{ number_format(abs($request->adjustment_amount), 2) }}</strong> (Credit will be applied)</span>
                                @else
                                    <span class="text-muted">₹0.00 (No change)</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Reason & Actions -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-warning text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">User's Reason</h5>
                </div>
                <div class="card-body p-4">
                    <p>{{ $request->reason ?? 'No reason provided.' }}</p>
                </div>
            </div>

            @if($request->status === 'pending')
            <!-- Approval Form -->
            <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
                <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Approve Request</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.plan-change.approve', $request->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="effective_from" class="form-label">Effective From</label>
                            <input type="date" name="effective_from" id="effective_from" class="form-control" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}">
                            <small class="form-text text-muted">When should this plan change take effect?</small>
                        </div>
                        <div class="mb-3">
                            <label for="admin_notes_approve" class="form-label">Admin Notes (Optional)</label>
                            <textarea name="admin_notes" id="admin_notes_approve" rows="3" class="form-control" placeholder="Add any notes for the user..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this plan change request?')">
                            Approve Request
                        </button>
                    </form>
                </div>
            </div>

            <!-- Rejection Form -->
            <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
                <div class="card-header bg-danger text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Reject Request</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.plan-change.reject', $request->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="admin_notes_reject" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea name="admin_notes" id="admin_notes_reject" rows="3" class="form-control @error('admin_notes') is-invalid @enderror" required placeholder="Please provide a reason for rejection (minimum 10 characters)..."></textarea>
                            @error('admin_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Minimum 10 characters required.</small>
                        </div>
                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to reject this plan change request?')">
                            Reject Request
                        </button>
                    </form>
                </div>
            </div>
            @else
            <!-- Admin Notes (if reviewed) -->
            @if($request->admin_notes)
            <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
                <div class="card-header bg-secondary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Admin Notes</h5>
                </div>
                <div class="card-body p-4">
                    <p>{{ $request->admin_notes }}</p>
                </div>
            </div>
            @endif
            @endif

            <!-- History -->
            @if($request->history->count() > 0)
            <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Change History</h5>
                </div>
                <div class="card-body p-4">
                    <div class="timeline">
                        @foreach($request->history as $history)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ ucfirst($history->action) }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $history->performed_by ?? 'System' }}</small>
                                    @if($history->notes)
                                    <br>
                                    <small class="text-muted">{{ $history->notes }}</small>
                                    @endif
                                </div>
                                <small class="text-muted">{{ $history->created_at->format('d M Y, h:i A') }}</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
