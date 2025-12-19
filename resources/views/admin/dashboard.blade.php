@extends('admin.layout')

@section('title', 'Admin Dashboard')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">Admin Dashboard</h2>
            <p class="mb-0">Welcome back, <strong>{{ $admin->name ?? 'Admin' }}</strong>!</p>
            <div class="accent-line"></div>
        </div>
    </div>


    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <a href="{{ route('admin.users') }}" class="text-decoration-none">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h6 class="mb-0">Total Registrations</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h2 class="mb-0">{{ $totalUsers }}</h2>
                            </div>
                            <div class="bg-yellow p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#000000" viewBox="0 0 16 16">
                                    <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.applications', ['role' => $selectedRole]) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm" style="border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Total Applications</h6>
                                <h2 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $totalApplications }}</h2>
                            </div>
                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#0dcaf0" viewBox="0 0 16 16">
                                    <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.applications', ['role' => $selectedRole, 'status' => 'approved']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm" style="border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Approved Applications</h6>
                                <h2 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $approvedApplications }}</h2>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#198754" viewBox="0 0 16 16">
                                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.applications', ['role' => $selectedRole, 'status' => 'pending']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm" style="border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Pending Applications</h6>
                                <h2 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $pendingApplications }}</h2>
                            </div>
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffc107" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Quick Actions</h5>
                </div>
                <div class="card-body p-4">
                    <a href="{{ route('admin.backend-data-entry') }}" class="btn btn-info btn-lg w-100 mb-2" style="border-radius: 10px; font-weight: 500;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        Backend Data Entry
                    </a>
                    <a href="{{ route('admin.users') }}" class="btn btn-primary btn-lg w-100 mb-2" style="border-radius: 10px; font-weight: 500;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                        </svg>
                        View All Registrations
                    </a>
                    @if($admin->hasRole('processor') || $admin->hasRole('finance') || $admin->hasRole('technical'))
                    <a href="{{ route('admin.applications', ['role' => $selectedRole]) }}" class="btn btn-success btn-lg w-100 mb-2" style="border-radius: 10px; font-weight: 500;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                        </svg>
                        View Applications
                    </a>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Recent Registrations</h5>
                </div>
                <div class="card-body p-4">
                    @if($recentUsers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="color: #2c3e50; font-weight: 600;">Name</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Email</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Status</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentUsers as $user)
                                    <tr>
                                        <td><a href="{{ route('admin.users.show', $user->id) }}" style="color: #0d6efd; text-decoration: none;">{{ $user->fullname }}</a></td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            @if($user->status === 'approved')
                                                <span class="badge rounded-pill px-3 py-1 bg-success">
                                                    Registered
                                                </span>
                                            @elseif($user->status === 'pending')
                                                <span class="badge rounded-pill px-3 py-1 bg-warning">
                                                    Pending
                                                </span>
                                            @else
                                                <span class="badge rounded-pill px-3 py-1 bg-secondary">
                                                    Rejected
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $user->created_at->format('M d, Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No registrations yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
