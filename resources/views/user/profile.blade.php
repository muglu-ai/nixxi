@extends('user.layout')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4" style="color: #2c3e50; font-weight: 600;">My Profile</h2>
            
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Profile Details</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%" style="color: #2c3e50; font-weight: 600;">Registration ID:</th>
                                    <td><strong style="color: #2c3e50;">{{ $user->registrationid }}</strong></td>
                                </tr>
                                <tr>
                                    <th style="color: #2c3e50; font-weight: 600;">Full Name:</th>
                                    <td>{{ $user->fullname }}</td>
                                </tr>
                                <tr>
                                    <th style="color: #2c3e50; font-weight: 600;">Email Address:</th>
                                    <td>
                                        {{ $user->email }}
                                        @if($user->email_verified)
                                            <span class="badge bg-success ms-2">Verified</span>
                                        @else
                                            <span class="badge bg-danger ms-2">Not Verified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th style="color: #2c3e50; font-weight: 600;">Mobile Number:</th>
                                    <td>
                                        {{ $user->mobile }}
                                        @if($user->mobile_verified)
                                            <span class="badge bg-success ms-2">Verified</span>
                                        @else
                                            <span class="badge bg-danger ms-2">Not Verified</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%" style="color: #2c3e50; font-weight: 600;">PAN Card Number:</th>
                                    <td>
                                        {{ $user->pancardno }}
                                        @if($user->pan_verified)
                                            <span class="badge bg-success ms-2">Verified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th style="color: #2c3e50; font-weight: 600;">Date of Birth:</th>
                                    <td>{{ $user->dateofbirth->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th style="color: #2c3e50; font-weight: 600;">Registration Date:</th>
                                    <td>{{ $user->registrationdate->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th style="color: #2c3e50; font-weight: 600;">Registration Time:</th>
                                    <td>{{ $user->registrationtime }}</td>
                                </tr>
                                <tr>
                                    <th style="color: #2c3e50; font-weight: 600;">Account Status:</th>
                                    <td>
                                        <span class="badge rounded-pill px-3 py-1 
                                            @if($user->status === 'approved' || $user->status === 'active') bg-success
                                            @elseif($user->status === 'pending') bg-warning text-dark
                                            @else bg-secondary @endif">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top">
                        @if($pendingRequest)
                            <div class="alert alert-info border-0 shadow-sm" style="border-radius: 12px;">
                                <div class="d-flex align-items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#0dcaf0" class="me-2 mt-1" viewBox="0 0 16 16">
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                                    </svg>
                                    <div class="flex-grow-1">
                                        <strong>Profile Update Request Pending</strong><br>
                                        Your request to update profile is pending admin approval. You'll be notified once it's reviewed.
                                        <br><small class="text-muted">Requested on: {{ $pendingRequest->created_at->format('d M Y, h:i A') }}</small>
                                    </div>
                                </div>
                            </div>
                        @elseif($submittedRequest)
                            <div class="alert alert-warning border-0 shadow-sm" style="border-radius: 12px; background-color: #fff9e6; border-left: 4px solid #f39c12;">
                                <div class="d-flex align-items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#f39c12" class="me-2 mt-1" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                    </svg>
                                    <div class="flex-grow-1">
                                        <strong>Update Submitted - Waiting for Approval</strong><br>
                                        Your profile update has been submitted and is waiting for admin approval. You will see your changes once approved.
                                        <br><small class="text-muted">Submitted on: {{ $submittedRequest->submitted_at->format('d M Y, h:i A') }}</small>
                                    </div>
                                </div>
                            </div>
                        @elseif($approvedRequest)
                            <div class="alert alert-success border-0 shadow-sm" style="border-radius: 12px; background-color: #e8f8f0; border-left: 4px solid #27ae60;">
                                <div class="d-flex align-items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#27ae60" class="me-2 mt-1" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 2.384 5.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                    </svg>
                                    <div class="flex-grow-1">
                                        <strong>Profile Update Request Approved!</strong><br>
                                        Your request has been approved. You can now update your profile once.
                                        <br>
                                        <a href="{{ route('user.profile-update.edit') }}" class="btn btn-success mt-2 px-4" style="border-radius: 8px; font-weight: 500;">Update Profile Now</a>
                                    </div>
                                </div>
                            </div>
                        @elseif($updateApprovedRequest && $updateApprovedRequest->submitted_data)
                            <div class="alert alert-success border-0 shadow-sm" style="border-radius: 12px; background-color: #e8f8f0; border-left: 4px solid #27ae60;">
                                <div class="d-flex align-items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#27ae60" class="me-2 mt-1" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 2.384 5.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                    </svg>
                                    <div class="flex-grow-1">
                                        <strong>Profile Update Approved!</strong><br>
                                        Your profile update has been approved and applied. The updated information is now visible in your profile.
                                        <br><small class="text-muted">Approved on: {{ $updateApprovedRequest->update_approved_at->format('d M Y, h:i A') }}</small>
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('user.profile-update.request') }}" class="btn btn-primary px-4 py-2" style="border-radius: 10px; font-weight: 500;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                    <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                    <path d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Z"/>
                                </svg>
                                Request Profile Update
                            </a>
                        @endif
                        <a href="{{ route('user.dashboard') }}" class="btn btn-outline-secondary px-4 py-2 ms-2" style="border-radius: 10px; font-weight: 500;">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
