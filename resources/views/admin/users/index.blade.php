@extends('admin.layout')

@section('title', 'All Registration')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>All Registration</h1>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<!-- Search Form -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.users') }}" class="row g-3">
                    <div class="col-md-10">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by name, email, mobile, PAN, registration ID, or status..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    @if(request('search'))
                        <div class="col-12">
                            <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-secondary">Clear Search</a>
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
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Registration List</h5>
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
                                        @if($user->status === 'approved')
                                            <span class="badge bg-success">
                                                Registered
                                            </span>
                                        @elseif($user->status === 'pending')
                                            <span class="badge bg-warning">
                                                Pending
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                Rejected
                                            </span>
                                        @endif
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
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $users->links('vendor.pagination.bootstrap-5') }}
                    </div>
                @else
                    <p class="text-muted">No registrations found.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

