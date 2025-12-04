@extends('superadmin.layout')

@section('title', 'Message Details')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">Message Details</h2>
        <p class="text-muted mb-0">
            <a href="{{ route('superadmin.messages') }}" class="text-decoration-none">← Back to Messages</a>
        </p>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Message Card -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Message</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <label class="text-muted small mb-1">Subject</label>
                        <p class="mb-0" style="color: #2c3e50; font-weight: 500; font-size: 1.1rem;">{{ $message->subject }}</p>
                    </div>
                    <div class="mb-4">
                        <label class="text-muted small mb-1">Message</label>
                        <div class="p-3 bg-light rounded" style="color: #2c3e50; white-space: pre-wrap;">{{ $message->message }}</div>
                    </div>
                    @if($message->user_reply)
                        <div class="mb-4">
                            <label class="text-muted small mb-1">User Reply</label>
                            <div class="p-3 bg-info bg-opacity-10 rounded" style="color: #2c3e50; white-space: pre-wrap;">{{ $message->user_reply }}</div>
                            <small class="text-muted">Replied on: {{ $message->user_replied_at->format('M d, Y h:i A') }} ({{ $message->user_replied_at->diffForHumans() }})</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- User Information -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">User Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Name</label>
                        <p class="mb-0" style="color: #2c3e50; font-weight: 500;">
                            <a href="{{ route('superadmin.users.show', $message->user_id) }}" class="text-decoration-none" style="color: #2c3e50;">
                                {{ $message->user->fullname }}
                            </a>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Email</label>
                        <p class="mb-0" style="color: #2c3e50; font-weight: 500;">{{ $message->user->email }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Mobile</label>
                        <p class="mb-0" style="color: #2c3e50; font-weight: 500;">{{ $message->user->mobile }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Status</label>
                        <p class="mb-0">
                            <span class="badge rounded-pill px-3 py-1 
                                @if($message->user->status === 'approved' || $message->user->status === 'active') bg-success
                                @elseif($message->user->status === 'pending') bg-warning text-dark
                                @else bg-secondary @endif">
                                {{ ucfirst($message->user->status) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Message Metadata -->
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Message Details</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Sent By</label>
                        <p class="mb-0">
                            @if($message->sent_by === 'admin')
                                @if($adminAction && $adminAction->admin)
                                    <span class="badge rounded-pill px-3 py-1 bg-primary">
                                        {{ $adminAction->admin->name }}
                                    </span>
                                    <br>
                                    <small class="text-muted">{{ $adminAction->admin->email }}</small>
                                @else
                                    <span class="badge rounded-pill px-3 py-1 bg-primary">Admin</span>
                                @endif
                            @else
                                <span class="badge rounded-pill px-3 py-1 bg-info">{{ ucfirst($message->sent_by) }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Sent On</label>
                        <p class="mb-0" style="color: #2c3e50; font-weight: 500;">
                            {{ $message->created_at->format('M d, Y h:i A') }}
                            <br>
                            <small class="text-muted">{{ $message->created_at->diffForHumans() }}</small>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Read Status</label>
                        <p class="mb-0">
                            @if($message->is_read)
                                <span class="badge rounded-pill px-3 py-1 bg-success">Read</span>
                                @if($message->read_at)
                                    <br>
                                    <small class="text-muted">Read on: {{ $message->read_at->format('M d, Y h:i A') }}</small>
                                @endif
                            @else
                                <span class="badge rounded-pill px-3 py-1 bg-warning text-dark">Unread</span>
                            @endif
                        </p>
                    </div>
                    @if($message->user_reply)
                        <div class="mb-3">
                            <label class="text-muted small mb-1">User Replied</label>
                            <p class="mb-0">
                                <span class="badge rounded-pill px-3 py-1 bg-success">Yes</span>
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

