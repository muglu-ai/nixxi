@extends('user.layout')

@section('title', 'Ticket Details')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-3">
            <div class="card-header bg-primary text-white">
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
                        <p><strong>Type:</strong> <span class="badge bg-info">{{ $ticket->type_display }}</span></p>
                        <p><strong>Priority:</strong> <span class="badge bg-{{ $ticket->priority_badge_color }}">{{ ucfirst($ticket->priority) }}</span></p>
                        <p><strong>Created:</strong> {{ $ticket->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Assigned To:</strong> {{ $ticket->assignedAdmin ? $ticket->assignedAdmin->name : 'Not Assigned' }}</p>
                        @if($ticket->subject)
                        <p><strong>Subject:</strong> {{ $ticket->subject }}</p>
                        @endif
                        @if($ticket->closed_at)
                        <p><strong>Closed:</strong> {{ $ticket->closed_at->format('d M Y, h:i A') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Conversation Thread -->
        <div class="card shadow mb-3">
            <div class="card-header bg-light">
                <h5 class="mb-0">Conversation</h5>
            </div>
            <div class="card-body">
                <div class="conversation-thread">
                    @foreach($ticket->messages as $message)
                    <div class="message-item mb-4 p-3 rounded {{ $message->sender_type === 'user' ? 'bg-light' : 'bg-primary bg-opacity-10' }}">
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
                                        @if($attachment->is_image)
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                                <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                                <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                                            </svg>
                                        @elseif($attachment->is_pdf)
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                                <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                                <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/>
                                            </svg>
                                        @endif
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
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">Reply</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('user.grievance.reply', $ticket->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="message" class="form-label">Your Message <span class="text-danger">*</span></label>
                        <textarea name="message" id="message" rows="4" class="form-control @error('message') is-invalid @enderror" required></textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="attachments" class="form-label">Attachments</label>
                        <input type="file" name="attachments[]" id="attachments" class="form-control" multiple accept="image/*,.pdf,.doc,.docx">
                        <small class="form-text text-muted">Maximum file size: 10MB per file.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </form>
            </div>
        </div>
        @else
        <div class="alert alert-info">
            <strong>This ticket is closed.</strong> You cannot reply to closed tickets. Please create a new ticket if you need further assistance.
        </div>
        @endif
    </div>
</div>
@endsection

