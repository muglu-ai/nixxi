@extends('admin.layout')

@section('title', 'Requests & Messages')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>Requests & Messages</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">Profile Update Requests ({{ $profileUpdateRequests->total() }})</h5>
            </div>
            <div class="card-body">
                @if($profileUpdateRequests->count() > 0)
                    <div class="list-group">
                        @foreach($profileUpdateRequests as $request)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="{{ route('admin.users.show', $request->user_id) }}" class="text-decoration-none">
                                                {{ $request->user->fullname }}
                                            </a>
                                            <span class="badge bg-{{ $request->status === 'pending' ? 'warning' : ($request->status === 'approved' ? 'success' : 'danger') }} ms-2">
                                                {{ $request->status }}
                                            </span>
                                        </h6>
                                        <p class="mb-1 text-muted small">
                                            <strong>Email:</strong> {{ $request->user->email }} | 
                                            <strong>Mobile:</strong> {{ $request->user->mobile }}
                                        </p>
                                        @if($request->requested_changes)
                                            <p class="mb-1">{{ Str::limit(is_array($request->requested_changes) ? json_encode($request->requested_changes) : $request->requested_changes, 150) }}</p>
                                        @endif
                                        <small class="text-muted">{{ $request->created_at->format('M d, Y h:i A') }}</small>
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $request->user_id) }}" class="btn btn-sm btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        {{ $profileUpdateRequests->links() }}
                    </div>
                @else
                    <p class="text-muted">No pending profile update requests.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Recent Messages ({{ $messages->total() }})</h5>
            </div>
            <div class="card-body">
                @if($messages->count() > 0)
                    <div class="list-group">
                        @foreach($messages as $message)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="{{ route('admin.users.show', $message->user_id) }}" class="text-decoration-none">
                                                {{ $message->user->fullname }}
                                            </a>
                                        </h6>
                                        <p class="mb-1"><strong>{{ $message->subject }}</strong></p>
                                        <p class="mb-1 text-muted small">{{ Str::limit($message->message, 100) }}</p>
                                        <small class="text-muted">{{ $message->created_at->format('M d, Y h:i A') }}</small>
                                        @if($message->is_read)
                                            <span class="badge bg-success ms-2">Read</span>
                                        @else
                                            <span class="badge bg-warning ms-2">Unread</span>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $message->user_id) }}" class="btn btn-sm btn-primary">View User</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        {{ $messages->links() }}
                    </div>
                @else
                    <p class="text-muted">No messages sent yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

