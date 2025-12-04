@extends('superadmin.layout')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">Super Admin Dashboard</h2>
        <p class="text-muted mb-0">Welcome back!</p>
    </div>

    <div class="row g-4">
        <!-- SuperAdmin Details -->
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">SuperAdmin Details</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Name</label>
                                <p class="mb-0" style="color: #2c3e50; font-weight: 500; font-size: 1.1rem;">{{ $superAdmin->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Email</label>
                                <p class="mb-0" style="color: #2c3e50; font-weight: 500; font-size: 1.1rem;">{{ $superAdmin->email }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">User ID</label>
                                <p class="mb-0" style="color: #2c3e50; font-weight: 500; font-size: 1.1rem;">{{ $superAdmin->userid }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin and Roles Chart -->
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Admin and Roles</h5>
                </div>
                <div class="card-body p-4">
                    @if($adminsWithRoles->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0" style="border-radius: 8px; overflow: hidden;">
                                <thead>
                                    <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                        <th style="font-weight: 600; padding: 12px; text-align: left; min-width: 200px;">Admin Name</th>
                                        @foreach($roleSlugs as $roleSlug)
                                            <th style="font-weight: 600; padding: 12px; text-align: center; min-width: 120px;">
                                                {{ ucfirst($roles[$roleSlug]->name ?? $roleSlug) }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($adminsWithRoles as $admin)
                                        <tr>
                                            <td style="padding: 12px; font-weight: 500; color: #2c3e50;">
                                                {{ $admin->name }}
                                                @if(!$admin->is_active)
                                                    <span class="badge bg-secondary ms-2">Inactive</span>
                                                @endif
                                            </td>
                                            @foreach($roleSlugs as $roleSlug)
                                                <td style="padding: 12px; text-align: center;">
                                                    @php
                                                        $hasRole = $admin->roles->contains(function($role) use ($roleSlug) {
                                                            return $role->slug === $roleSlug;
                                                        });
                                                    @endphp
                                                    @if($hasRole)
                                                        <i class="bi bi-check-circle-fill text-success" style="font-size: 24px;"></i>
                                                    @else
                                                        <i class="bi bi-x-circle-fill text-danger" style="font-size: 24px;"></i>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="mb-2" viewBox="0 0 16 16">
                                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                                <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                            </svg>
                            <p class="text-muted mb-0">No admins found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Application Details Chart -->
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Application Details</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4 mb-4">
                        <!-- Total Applications -->
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm bg-primary bg-opacity-10" style="border-radius: 12px;">
                                <div class="card-body text-center p-4">
                                    <h3 class="mb-2" style="color: #0d6efd; font-weight: 700;">{{ $totalApplications }}</h3>
                                    <p class="mb-0 text-muted" style="font-weight: 500;">Total Applications</p>
                                </div>
                            </div>
                        </div>
                        <!-- Fully Approved -->
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm bg-success bg-opacity-10" style="border-radius: 12px;">
                                <div class="card-body text-center p-4">
                                    <h3 class="mb-2" style="color: #198754; font-weight: 700;">{{ $fullyApproved }}</h3>
                                    <p class="mb-0 text-muted" style="font-weight: 500;">Fully Approved</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0" style="border-radius: 8px; overflow: hidden;">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                    <th style="font-weight: 600; padding: 12px; text-align: left; min-width: 150px;">Role</th>
                                    <th style="font-weight: 600; padding: 12px; text-align: center; min-width: 150px;">Approved</th>
                                    <th style="font-weight: 600; padding: 12px; text-align: center; min-width: 150px;">Pending</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- New IX Workflow Roles --}}
                                <tr>
                                    <td style="padding: 12px; font-weight: 600; color: #2c3e50;">IX Processor</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-success" style="font-size: 1rem; padding: 8px 16px;">{{ $ixProcessorApproved ?? 0 }}</span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-warning text-dark" style="font-size: 1rem; padding: 8px 16px;">{{ $ixProcessorPending ?? 0 }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; font-weight: 600; color: #2c3e50;">IX Legal</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-success" style="font-size: 1rem; padding: 8px 16px;">{{ $ixLegalApproved ?? 0 }}</span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-warning text-dark" style="font-size: 1rem; padding: 8px 16px;">{{ $ixLegalPending ?? 0 }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; font-weight: 600; color: #2c3e50;">IX Head</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-success" style="font-size: 1rem; padding: 8px 16px;">{{ $ixHeadApproved ?? 0 }}</span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-warning text-dark" style="font-size: 1rem; padding: 8px 16px;">{{ $ixHeadPending ?? 0 }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; font-weight: 600; color: #2c3e50;">CEO</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-success" style="font-size: 1rem; padding: 8px 16px;">{{ $ceoApproved ?? 0 }}</span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-warning text-dark" style="font-size: 1rem; padding: 8px 16px;">{{ $ceoPending ?? 0 }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; font-weight: 600; color: #2c3e50;">Nodal Officer</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-success" style="font-size: 1rem; padding: 8px 16px;">{{ $nodalOfficerApproved ?? 0 }}</span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-warning text-dark" style="font-size: 1rem; padding: 8px 16px;">{{ $nodalOfficerPending ?? 0 }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; font-weight: 600; color: #2c3e50;">IX Tech Team</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-success" style="font-size: 1rem; padding: 8px 16px;">{{ $ixTechTeamApproved ?? 0 }}</span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-warning text-dark" style="font-size: 1rem; padding: 8px 16px;">{{ $ixTechTeamPending ?? 0 }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; font-weight: 600; color: #2c3e50;">IX Account</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-success" style="font-size: 1rem; padding: 8px 16px;">{{ $ixAccountApproved ?? 0 }}</span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge bg-warning text-dark" style="font-size: 1rem; padding: 8px 16px;">{{ $ixAccountPending ?? 0 }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Logged In Users -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" 
                     style="border-radius: 16px 16px 0 0; cursor: pointer;" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#collapseRecentUsers" 
                     aria-expanded="false" 
                     aria-controls="collapseRecentUsers">
                    <h5 class="mb-0" style="font-weight: 600;">Recent Logged In Users</h5>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="arrow-icon" viewBox="0 0 16 16" style="transition: transform 0.3s; transform: rotate(180deg);">
                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
                <div id="collapseRecentUsers" class="collapse">
                    <div class="card-body p-4">
                    @if($recentLoggedInUsers->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentLoggedInUsers as $user)
                            <div class="list-group-item px-0 py-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1" style="color: #2c3e50; font-weight: 600;">
                                            <a href="{{ route('superadmin.users.show', $user->id) }}" class="text-decoration-none" style="color: #2c3e50;">
                                                {{ $user->fullname }}
                                            </a>
                                        </h6>
                                        <p class="mb-1 text-muted small">{{ $user->email }}</p>
                                        <small class="text-muted">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                            </svg>
                                            Last active: {{ $user->updated_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <span class="badge rounded-pill px-3 py-1 
                                        @if($user->status === 'approved' || $user->status === 'active') bg-success
                                        @elseif($user->status === 'pending') bg-warning text-dark
                                        @else bg-secondary @endif">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="mb-2" viewBox="0 0 16 16">
                                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                                <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                            </svg>
                            <p class="text-muted mb-0">No recent user activity.</p>
                        </div>
                    @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Admin Activities -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" 
                     style="border-radius: 16px 16px 0 0; cursor: pointer;" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#collapseAdminActivities" 
                     aria-expanded="false" 
                     aria-controls="collapseAdminActivities">
                    <h5 class="mb-0" style="font-weight: 600;">Recent Admin Activities</h5>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="arrow-icon" viewBox="0 0 16 16" style="transition: transform 0.3s; transform: rotate(180deg);">
                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
                <div id="collapseAdminActivities" class="collapse">
                    <div class="card-body p-4">
                    @if($recentAdminActivities->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentAdminActivities as $activity)
                            <div class="list-group-item px-0 py-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <h6 class="mb-0 me-2" style="color: #2c3e50; font-weight: 600;">
                                                @if($activity->admin)
                                                    {{ $activity->admin->name }}
                                                @else
                                                    System
                                                @endif
                                            </h6>
                                            <span class="badge rounded-pill px-2 py-1 
                                                {{ $activity->action_type === 'admin_login' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $activity->action_type === 'admin_login' ? 'Logged In' : 'Logged Out' }}
                                            </span>
                                        </div>
                                        @if($activity->description)
                                            <p class="mb-1 text-muted small">{{ $activity->description }}</p>
                                        @endif
                                        <small class="text-muted">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                            </svg>
                                            {{ $activity->created_at->format('M d, Y h:i A') }} ({{ $activity->created_at->diffForHumans() }})
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="mb-2" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                            </svg>
                            <p class="text-muted mb-0">No recent admin activities.</p>
                        </div>
                    @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Messages -->
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" 
                     style="border-radius: 16px 16px 0 0; cursor: pointer;" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#collapseRecentMessages" 
                     aria-expanded="false" 
                     aria-controls="collapseRecentMessages">
                    <h5 class="mb-0" style="font-weight: 600;">Recent Messages</h5>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="arrow-icon" viewBox="0 0 16 16" style="transition: transform 0.3s; transform: rotate(180deg);">
                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
                <div id="collapseRecentMessages" class="collapse">
                    <div class="card-body p-4">
                    @if($recentMessages->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="color: #2c3e50; font-weight: 600;">User</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Subject</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Message</th>
                                        <th style="color: #2c3e50; font-weight: 600;">From</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentMessages as $message)
                                    <tr style="cursor: pointer;" onclick="window.location='{{ route('superadmin.messages.show', $message->id) }}'">
                                        <td>
                                            <a href="{{ route('superadmin.users.show', $message->user_id) }}" class="text-decoration-none" style="color: #2c3e50; font-weight: 500;" onclick="event.stopPropagation();">
                                                {{ $message->user->fullname }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $message->user->email }}</small>
                                        </td>
                                        <td style="color: #2c3e50;">{{ $message->subject }}</td>
                                        <td>
                                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                {{ \Illuminate\Support\Str::limit($message->message, 100) }}
                                            </div>
                                            @if($message->user_reply)
                                                <div class="mt-2 p-2 bg-light rounded" style="max-width: 300px;">
                                                    <small class="text-muted d-block mb-1"><strong>User Reply:</strong></small>
                                                    <small>{{ \Illuminate\Support\Str::limit($message->user_reply, 80) }}</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($message->sent_by === 'admin')
                                                @php
                                                    $adminAction = $recentAdminActions[$message->id] ?? null;
                                                @endphp
                                                @if($adminAction && $adminAction->admin)
                                                    <span class="badge rounded-pill px-3 py-1 bg-primary">
                                                        {{ $adminAction->admin->name }}
                                                    </span>
                                                @else
                                                    <span class="badge rounded-pill px-3 py-1 bg-primary">
                                                        Admin
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge rounded-pill px-3 py-1 bg-info">
                                                    {{ ucfirst($message->sent_by) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $message->created_at->diffForHumans() }}</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="mb-2" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                            </svg>
                            <p class="text-muted mb-0">No recent messages.</p>
                        </div>
                    @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle arrow rotation for collapsible cards
    const collapseElements = ['collapseRecentUsers', 'collapseAdminActivities', 'collapseRecentMessages'];
    
    collapseElements.forEach(function(collapseId) {
        const collapseElement = document.getElementById(collapseId);
        if (!collapseElement) return;
        
        const headerElement = collapseElement.previousElementSibling;
        if (!headerElement) return;
        
        const arrowIcon = headerElement.querySelector('.arrow-icon');
        if (!arrowIcon) return;
        
        // Initialize arrow based on current state (hidden by default, so arrow points down/180deg)
        if (collapseElement.classList.contains('show')) {
            arrowIcon.style.transform = 'rotate(0deg)';
        } else {
            arrowIcon.style.transform = 'rotate(180deg)';
        }
        
        // Listen for Bootstrap collapse events
        collapseElement.addEventListener('show.bs.collapse', function() {
            arrowIcon.style.transform = 'rotate(0deg)';
            headerElement.setAttribute('aria-expanded', 'true');
        });
        
        collapseElement.addEventListener('hide.bs.collapse', function() {
            arrowIcon.style.transform = 'rotate(180deg)';
            headerElement.setAttribute('aria-expanded', 'false');
        });
        
        collapseElement.addEventListener('shown.bs.collapse', function() {
            headerElement.setAttribute('aria-expanded', 'true');
        });
        
        collapseElement.addEventListener('hidden.bs.collapse', function() {
            headerElement.setAttribute('aria-expanded', 'false');
        });
    });
});
</script>
@endpush
@endsection
