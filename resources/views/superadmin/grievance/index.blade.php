@extends('superadmin.layout')

@section('title', 'Grievance Tickets')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <h4>All Grievance Tickets</h4>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('superadmin.grievance.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="technical" {{ request('type') === 'technical' ? 'selected' : '' }}>Technical</option>
                        <option value="billing" {{ request('type') === 'billing' ? 'selected' : '' }}>Billing</option>
                        <option value="general_complaint" {{ request('type') === 'general_complaint' ? 'selected' : '' }}>General Complaint</option>
                        <option value="feedback" {{ request('type') === 'feedback' ? 'selected' : '' }}>Feedback</option>
                        <option value="suggestion" {{ request('type') === 'suggestion' ? 'selected' : '' }}>Suggestion</option>
                        <option value="request" {{ request('type') === 'request' ? 'selected' : '' }}>Request</option>
                        <option value="enquiry" {{ request('type') === 'enquiry' ? 'selected' : '' }}>Enquiry</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="assigned" class="form-label">Assignment</label>
                    <select name="assigned" id="assigned" class="form-select">
                        <option value="">All</option>
                        <option value="assigned" {{ request('assigned') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="unassigned" {{ request('assigned') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('superadmin.grievance.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            @if($tickets->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Assigned To</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tickets as $ticket)
                            <tr>
                                <td><strong>{{ $ticket->ticket_id }}</strong></td>
                                <td>{{ $ticket->user->fullname ?? 'N/A' }}</td>
                                <td><span class="badge bg-info">{{ $ticket->type_display }}</span></td>
                                <td>{{ $ticket->subject ?? \Illuminate\Support\Str::limit($ticket->description, 40) }}</td>
                                <td>
                                    <span class="badge bg-{{ $ticket->status === 'closed' ? 'secondary' : ($ticket->status === 'resolved' ? 'success' : ($ticket->status === 'in_progress' ? 'warning' : 'primary')) }}">
                                        {{ $ticket->status_display }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $ticket->priority_badge_color }}">
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $ticket->assignedAdmin ? $ticket->assignedAdmin->name : 'Not Assigned' }}
                                </td>
                                <td>{{ $ticket->created_at->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('superadmin.grievance.show', $ticket->id) }}" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $tickets->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <p class="text-muted">No tickets found.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

