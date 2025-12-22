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

    <!-- Global Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-3">
                    <form action="{{ route('admin.applications') }}" method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Search applications, members, invoices, payments..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                            Search
                        </button>
                    </form>
                </div>
            </div>
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
                                @if($approvedApplicationsWithPayment > 0)
                                <small class="text-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" style="display: inline-block; vertical-align: middle;">
                                        <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                    </svg>
                                    {{ $approvedApplicationsWithPayment }} verified payments
                                </small>
                                @endif
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

    <!-- Member Statistics Section -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Member Statistics</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <a href="{{ route('admin.members', ['filter' => 'all']) }}" class="text-decoration-none">
                                <div class="card border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Total Members</h6>
                                                <h2 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $totalMembers }}</h2>
                                            </div>
                                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#0d6efd" viewBox="0 0 16 16">
                                                    <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.members', ['filter' => 'active']) }}" class="text-decoration-none">
                                <div class="card border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Live Members</h6>
                                                <h2 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $activeMembers }}</h2>
                                            </div>
                                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#198754" viewBox="0 0 16 16">
                                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.members', ['filter' => 'disconnected']) }}" class="text-decoration-none">
                                <div class="card border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Not Live Members</h6>
                                                <h2 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $disconnectedMembers }}</h2>
                                            </div>
                                            <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#dc3545" viewBox="0 0 16 16">
                                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- IX Points & Grievance Tracking Row -->
    <div class="row g-4 mb-4">
        <!-- IX Points Visibility -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">IX Points Visibility</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <a href="{{ route('admin.ix-points') }}" class="text-decoration-none">
                                <div class="card border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Total IX Points</h6>
                                                <h2 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $totalIxPoints }}</h2>
                                            </div>
                                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#0d6efd" viewBox="0 0 16 16">
                                                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.ix-points', ['node_type' => 'edge']) }}" class="text-decoration-none">
                                <div class="card border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Edge IX Points</h6>
                                                <h2 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $edgeIxPoints }}</h2>
                                            </div>
                                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#0dcaf0" viewBox="0 0 16 16">
                                                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.ix-points', ['node_type' => 'metro']) }}" class="text-decoration-none">
                                <div class="card border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Metro IX Points</h6>
                                                <h2 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $metroIxPoints }}</h2>
                                            </div>
                                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#198754" viewBox="0 0 16 16">
                                                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Grievance Tracking -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-warning text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Grievance Tracking</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <a href="{{ route('admin.grievance.index') }}" class="text-decoration-none">
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                <div>
                                    <h6 class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">Open Grievances</h6>
                                    <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $openGrievances }}</h3>
                                </div>
                                <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffc107" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('admin.grievance.index', ['status' => 'assigned']) }}" class="text-decoration-none">
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                <div>
                                    <h6 class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">Pending Requests</h6>
                                    <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">{{ $pendingGrievances }}</h3>
                                </div>
                                <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#0dcaf0" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M5 6.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 3a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Live Members -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Recent Live Members</h5>
                    <a href="{{ route('admin.members', ['filter' => 'active']) }}" class="btn btn-sm btn-light">
                        View All Live Members
                    </a>
                </div>
                <div class="card-body p-4">
                    @if($recentLiveMembers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="color: #2c3e50; font-weight: 600;">Application ID</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Membership ID</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Member Name</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Application Status</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentLiveMembers as $application)
                                <tr>
                                    <td><a href="{{ route('admin.applications.show', $application->id) }}" style="color: #0d6efd; text-decoration: none;">{{ $application->application_id }}</a></td>
                                    <td><strong>{{ $application->membership_id }}</strong></td>
                                    <td>{{ $application->user->fullname ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge rounded-pill px-3 py-1
                                            @if($application->status === 'approved' || $application->status === 'payment_verified') bg-success
                                            @elseif(in_array($application->status, ['ip_assigned', 'invoice_pending'])) bg-info
                                            @else bg-secondary @endif">
                                            {{ $application->status_display }}
                                        </span>
                                    </td>
                                    <td>{{ $application->updated_at->format('M d, Y h:i A') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">No recent live members found.</p>
                    @endif
                </div>
            </div>
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
