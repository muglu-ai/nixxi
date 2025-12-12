@extends('admin.layout')

@section('title', 'Grievance Tickets')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Assigned Grievance Tickets</h2>
            <div class="accent-line"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary">
            <h5 class="mb-0">Tickets List</h5>
        </div>
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
                                <th>Created</th>
                                <th>Last Reply</th>
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
                                <td>{{ $ticket->created_at->format('d M Y') }}</td>
                                <td>
                                    @if($ticket->messages->count() > 0)
                                        {{ $ticket->messages->last()->created_at->format('d M Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.grievance.show', $ticket->id) }}" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3 d-flex justify-content-center">
                    {{ $tickets->links('vendor.pagination.bootstrap-5') }}
                </div>
            @else
                <div class="text-center py-5">
                    <p class="text-muted">No tickets assigned to you yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

