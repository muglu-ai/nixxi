@extends('admin.layout')

@section('title', 'All Users')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>All Users</h1>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Users List</h5>
            </div>
            <div class="card-body">
                @if($users->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Registration ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                <tr>
                                    <td><strong>{{ $user->registrationid }}</strong></td>
                                    <td>{{ $user->fullname }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->mobile }}</td>
                                    <td>
                                        <span class="badge bg-{{ $user->status === 'pending' ? 'warning' : ($user->status === 'approved' ? 'success' : 'secondary') }}">
                                            {{ $user->status }}
                                        </span>
                                    </td>
                                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-primary">View Details</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $users->links() }}
                    </div>
                @else
                    <p class="text-muted">No users found.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

