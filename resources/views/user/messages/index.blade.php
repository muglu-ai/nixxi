@extends('user.layout')

@section('title', 'Messages')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                    </svg>
                    Inbox
                </h4>
            </div>
            <div class="card-body">
                @if($messages->count() > 0)
                    <div class="list-group">
                        @foreach($messages as $message)
                            <a href="{{ route('user.messages.show', $message->id) }}" 
                               class="list-group-item list-group-item-action {{ !$message->is_read ? 'list-group-item-primary' : '' }}">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">
                                        @if(!$message->is_read)
                                            <span class="badge bg-danger me-2">New</span>
                                        @endif
                                        {{ $message->subject }}
                                    </h5>
                                    <small>{{ $message->created_at->format('M d, Y h:i A') }}</small>
                                </div>
                                <p class="mb-1">{{ Str::limit($message->message, 100) }}</p>
                                <small>From: {{ ucfirst($message->sent_by) }}</small>
                            </a>
                        @endforeach
                    </div>
                    
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $messages->links('vendor.pagination.bootstrap-5') }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16" class="text-muted mb-3">
                            <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                        </svg>
                        <p class="text-muted">No messages yet. You'll see messages from admin here.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

