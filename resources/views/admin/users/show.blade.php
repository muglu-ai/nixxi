@extends('admin.layout')

@section('title', 'Registration Details')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>Registration Details</h1>
        <a href="{{ route('admin.users') }}" class="btn btn-secondary">Back to Registrations</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Registration Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%" class="bg-light">Registration ID:</th>
                        <td><strong>{{ $user->registrationid }}</strong></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Full Name:</th>
                        <td>{{ $user->fullname }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Email:</th>
                        <td>
                            {{ $user->email }}
                            @if($user->email_verified)
                                <span class="badge bg-success">Verified</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">Mobile:</th>
                        <td>
                            {{ $user->mobile }}
                            @if($user->mobile_verified)
                                <span class="badge bg-success">Verified</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">PAN Card:</th>
                        <td>
                            {{ $user->pancardno }}
                            @if($user->pan_verified)
                                <span class="badge bg-success ms-2">Verified</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">Date of Birth:</th>
                        <td>{{ $user->dateofbirth->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Registration Date:</th>
                        <td>{{ $user->registrationdate->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Status:</th>
                        <td>
                            <form method="POST" action="{{ route('admin.users.update-status', $user->id) }}" class="d-inline">
                                @csrf
                                <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                    <option value="pending" {{ $user->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ $user->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ $user->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Send Message</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.send-message', $user->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Send Message</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Messages ({{ $user->messages->count() }})</h5>
            </div>
            <div class="card-body">
                @if($user->messages->count() > 0)
                    <div class="list-group">
                        @foreach($user->messages->take(5) as $message)
                            <div class="list-group-item">
                                <h6>{{ $message->subject }}</h6>
                                <small class="text-muted">{{ $message->created_at->format('M d, Y h:i A') }}</small>
                                <p class="mb-1">{{ Str::limit($message->message, 50) }}</p>
                                @if($message->user_reply)
                                    <div class="mt-2 p-2 bg-light rounded">
                                        <small><strong>User Reply:</strong> {{ Str::limit($message->user_reply, 50) }}</small>
                                        <br><small class="text-muted">Replied: {{ $message->user_replied_at->format('M d, Y h:i A') }}</small>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">No messages sent.</p>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">Profile Update Requests ({{ $user->profileUpdateRequests->count() }})</h5>
            </div>
            <div class="card-body">
                @if($user->profileUpdateRequests->count() > 0)
                    <div class="list-group">
                        @foreach($user->profileUpdateRequests as $request)
                            <div class="list-group-item">
                                <h6>
                                    <span class="badge bg-{{ $request->status === 'pending' ? 'warning' : ($request->status === 'approved' ? 'success' : 'danger') }}">
                                        {{ $request->status }}
                                    </span>
                                    @if($request->submitted_data && !$request->update_approved)
                                        <span class="badge bg-info ms-2">Update Submitted</span>
                                    @elseif($request->update_approved)
                                        <span class="badge bg-success ms-2">Update Applied</span>
                                    @endif
                                </h6>
                                <p class="mb-2"><strong>Requested Changes:</strong> {{ Str::limit(is_array($request->requested_changes) ? json_encode($request->requested_changes) : $request->requested_changes, 100) }}</p>
                                
                                @if($request->submitted_data)
                                    <div class="mb-2 p-2 bg-light rounded">
                                        <strong>Submitted Update:</strong>
                                        <ul class="mb-0 small">
                                            @if(isset($request->submitted_data['fullname']))
                                                <li>Full Name: {{ $request->submitted_data['fullname'] }}</li>
                                            @endif
                                            @if(isset($request->submitted_data['mobile']))
                                                <li>Mobile: {{ $request->submitted_data['mobile'] }}</li>
                                            @endif
                                            @if(isset($request->submitted_data['dateofbirth']))
                                                <li>Date of Birth: {{ \Carbon\Carbon::parse($request->submitted_data['dateofbirth'])->format('d M Y') }}</li>
                                            @endif
                                        </ul>
                                        <small class="text-muted">Submitted: {{ $request->submitted_at->format('M d, Y h:i A') }}</small>
                                    </div>
                                @endif
                                
                                <small class="text-muted">Requested: {{ $request->created_at->format('M d, Y h:i A') }}</small>
                                
                                @if($request->status === 'pending')
                                    <div class="mt-2">
                                        <form method="POST" action="{{ route('admin.profile-updates.approve', $request->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Approve Request</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $request->id }}">Reject</button>
                                        
                                        <!-- Reject Modal -->
                                        <div class="modal fade" id="rejectModal{{ $request->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('admin.profile-updates.reject', $request->id) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reject Profile Update Request</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="admin_notes" class="form-label">Reason for Rejection</label>
                                                                <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Reject</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($request->status === 'approved' && $request->submitted_data && !$request->update_approved)
                                    <div class="mt-2">
                                        <form method="POST" action="{{ route('admin.profile-updates.approve-submitted', $request->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">Approve & Apply Update</button>
                                        </form>
                                    </div>
                                @endif
                                
                                @if($request->approver)
                                    <br><small class="text-muted">Approved/Rejected by: 
                                        @if($request->approver instanceof \App\Models\Admin)
                                            {{ $request->approver->name }}
                                        @else
                                            {{ $request->approver->fullname ?? 'Unknown' }}
                                        @endif
                                    </small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">No profile update requests.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Admin Activity Log</h5>
            </div>
            <div class="card-body">
                @if($adminActions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($adminActions as $action)
                                <tr>
                                    <td>{{ $action->created_at->format('M d, Y h:i A') }}</td>
                                    <td>
                                        @if($action->superAdmin)
                                            <span class="badge bg-danger">SuperAdmin</span> {{ $action->superAdmin->name }}
                                        @elseif($action->admin)
                                            {{ $action->admin->name }}
                                        @else
                                            System
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $action->action_type)) }}</span>
                                    </td>
                                    <td>{{ $action->description }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No admin actions recorded.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

