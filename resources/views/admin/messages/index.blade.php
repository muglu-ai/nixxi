@extends('admin.layout')

@section('title', 'Messages')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>Messages</h1>
        <p class="text-muted">All messages sent to registrations</p>
    </div>
</div>

<!-- Search Form -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.messages') }}" class="row g-3">
                    <div class="col-md-10">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by subject, message, registration name, email, or registration ID..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    @if(request('search'))
                        <div class="col-12">
                            <a href="{{ route('admin.messages') }}" class="btn btn-sm btn-outline-secondary">Clear Search</a>
                            <small class="text-muted ms-2">Showing results for: <strong>{{ request('search') }}</strong></small>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">All Messages ({{ $messages->total() }})</h5>
            </div>
            <div class="card-body">
                @if($messages->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Registration</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Sent At</th>
                                    <th>Read At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($messages as $message)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.users.show', $message->user_id) }}" class="text-decoration-none">
                                                <strong>{{ $message->user->fullname }}</strong><br>
                                                <small class="text-muted">{{ $message->user->email }}</small>
                                            </a>
                                        </td>
                                        <td>{{ $message->subject }}</td>
                                        <td>{{ Str::limit($message->message, 100) }}</td>
                                        <td>
                                            @if($message->is_read)
                                                <span class="badge bg-success">Read</span>
                                            @else
                                                <span class="badge bg-warning">Unread</span>
                                            @endif
                                        </td>
                                        <td>{{ $message->created_at->format('M d, Y h:i A') }}</td>
                                        <td>
                                            @if($message->read_at)
                                                {{ $message->read_at->format('M d, Y h:i A') }}
                                            @else
                                                <span class="text-muted">Not read</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.users.show', $message->user_id) }}" class="btn btn-sm btn-primary">View Registration</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $messages->links('vendor.pagination.bootstrap-5') }}
                    </div>
                @else
                    <p class="text-muted">No messages sent yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

