@extends('admin.layout')

@section('title', 'Ticket Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('admin.grievance.index') }}" class="btn btn-outline-secondary">← Back to Tickets</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-primary">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Ticket: {{ $ticket->ticket_id }}</h5>
                <span class="badge bg-{{ $ticket->status === 'closed' ? 'secondary' : ($ticket->status === 'resolved' ? 'success' : ($ticket->status === 'in_progress' ? 'warning' : 'primary')) }}">
                    {{ $ticket->status_display }}
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>User:</strong> {{ $ticket->user->fullname ?? 'N/A' }}</p>
                    <p><strong>Email:</strong> {{ $ticket->user->email ?? 'N/A' }}</p>
                    <p><strong>Type:</strong> <span class="badge bg-info">{{ $ticket->type_display }}</span></p>
                    <p><strong>Priority:</strong> <span class="badge bg-{{ $ticket->priority_badge_color }}">{{ ucfirst($ticket->priority) }}</span></p>
                    @if($ticket->escalation_level !== 'none')
                    <p><strong>Escalation:</strong> 
                        <span class="badge bg-danger">
                            @if($ticket->escalation_level === 'ix_head')
                                Escalated to IX Head
                            @elseif($ticket->escalation_level === 'ceo')
                                Escalated to CEO
                            @endif
                        </span>
                    </p>
                    @endif
                </div>
                <div class="col-md-6">
                    <p><strong>Created:</strong> {{ $ticket->created_at->format('d M Y, h:i A') }}</p>
                    <p><strong>Assigned:</strong> {{ $ticket->assigned_at ? $ticket->assigned_at->format('d M Y, h:i A') : 'N/A' }}</p>
                    @if($ticket->escalated_at)
                    <p><strong>Escalated:</strong> {{ $ticket->escalated_at->format('d M Y, h:i A') }}</p>
                    <p><strong>Escalated To:</strong> {{ $ticket->escalatedTo->name ?? 'N/A' }}</p>
                    @endif
                    @if($ticket->subject)
                    <p><strong>Subject:</strong> {{ $ticket->subject }}</p>
                    @endif
                    @if($ticket->closed_at)
                    <p><strong>Closed:</strong> {{ $ticket->closed_at->format('d M Y, h:i A') }}</p>
                    @endif
                </div>
            </div>
            <div class="mb-3">
                <strong>Initial Description:</strong>
                <p class="mt-2">{{ nl2br(e($ticket->description)) }}</p>
            </div>
        </div>
    </div>

    <!-- Conversation Thread -->
    <div class="card mb-3">
        <div class="card-header bg-primary">
            <h5 class="mb-0">Conversation</h5>
        </div>
        <div class="card-body">
            <div class="conversation-thread">
                @foreach($ticket->messages as $message)
                <div class="message-item mb-4 p-3 rounded {{ $message->sender_type === 'user' ? 'bg-light' : ($message->is_internal ? 'bg-warning bg-opacity-10' : 'bg-primary bg-opacity-10') }}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <strong>{{ $message->sender_name }}</strong>
                            <span class="badge bg-secondary ms-2">{{ ucfirst($message->sender_type) }}</span>
                            @if($message->is_internal)
                            <span class="badge bg-warning ms-1">Internal Note</span>
                            @endif
                        </div>
                        <small class="text-muted">{{ $message->created_at->format('d M Y, h:i A') }}</small>
                    </div>
                    <div class="message-content">
                        <p class="mb-2">{{ nl2br(e($message->message)) }}</p>
                        
                        @if($message->attachments->count() > 0)
                        <div class="attachments mt-2">
                            <strong>Attachments:</strong>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @foreach($message->attachments as $attachment)
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($attachment->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    {{ $attachment->file_name }}
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Reply Form -->
    @if($ticket->status !== 'closed')
    <div class="card shadow mb-3">
        <div class="card-header bg-light">
            <h5 class="mb-0">Reply</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.grievance.reply', $ticket->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="message" class="form-label">Your Message <span class="text-danger">*</span></label>
                    <textarea name="message" id="message" rows="4" class="form-control @error('message') is-invalid @enderror" required></textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_internal" id="is_internal" value="1">
                        <label class="form-check-label" for="is_internal">
                            Internal Note (visible only to admins)
                        </label>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="attachments" class="form-label">Attachments</label>
                    <input type="file" name="attachments[]" id="attachments" class="form-control" multiple accept="image/*,.pdf,.doc,.docx">
                    <small class="form-text text-muted">Maximum file size: 10MB per file.</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                    @if($ticket->status !== 'resolved' && $ticket->status !== 'closed')
                    <form method="POST" action="{{ route('admin.grievance.resolve', $ticket->id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Mark this ticket as resolved?')">Mark as Resolved</button>
                    </form>
                    @endif
                    @if($ticket->status !== 'closed')
                    <form method="POST" action="{{ route('admin.grievance.close', $ticket->id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to close this ticket? This action cannot be undone.')">Close Ticket</button>
                    </form>
                    @endif
                </div>
            </form>
        </div>
    </div>
    @else
    <div class="alert alert-info">
        <strong>This ticket is closed.</strong> You cannot reply to closed tickets.
    </div>
    @endif
</div>
@endsection

