@extends('admin.layout')

@section('title', 'Profile Update Requests')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>Profile Update Requests</h1>
        <p class="text-muted">All profile update requests from users</p>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">All Profile Update Requests ({{ $requests->total() }})</h5>
            </div>
            <div class="card-body">
                @if($requests->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Requested Changes</th>
                                    <th>Status</th>
                                    <th>Requested At</th>
                                    <th>Approved/Rejected By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requests as $request)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.users.show', $request->user_id) }}" class="text-decoration-none">
                                                <strong>{{ $request->user->fullname }}</strong><br>
                                                <small class="text-muted">{{ $request->user->email }}</small><br>
                                                <small class="text-muted">{{ $request->user->mobile }}</small>
                                            </a>
                                        </td>
                                        <td>
                                            @if($request->requested_changes)
                                                {{ Str::limit(is_array($request->requested_changes) ? json_encode($request->requested_changes) : $request->requested_changes, 150) }}
                                            @else
                                                <span class="text-muted">No details provided</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $request->status === 'pending' ? 'warning' : ($request->status === 'approved' ? 'success' : 'danger') }}">
                                                {{ ucfirst($request->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $request->created_at->format('M d, Y h:i A') }}</td>
                                        <td>
                                            @if($request->approver)
                                                @if($request->approver instanceof \App\Models\Admin)
                                                    {{ $request->approver->name }}
                                                @else
                                                    {{ $request->approver->fullname ?? 'Unknown' }}
                                                @endif
                                                @if($request->approved_at)
                                                    <br><small class="text-muted">{{ $request->approved_at->format('M d, Y h:i A') }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.users.show', $request->user_id) }}" class="btn btn-sm btn-primary">View User</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $requests->links() }}
                    </div>
                @else
                    <p class="text-muted">No profile update requests found.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

