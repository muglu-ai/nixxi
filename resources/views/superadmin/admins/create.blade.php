@extends('superadmin.layout')

@section('title', 'Add New Admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">Add New Admin</h2>
                <p class="text-muted mb-0">Create a new admin account</p>
            </div>
            <a href="{{ route('superadmin.admins') }}" class="btn btn-outline-secondary px-4" style="border-radius: 10px; font-weight: 500;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
                Back to Admins
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">Admin Information</h5>
                </div>
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger border-0" style="border-radius: 12px;">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('superadmin.admins.store') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   required>
                            <small class="form-text text-muted">Minimum 8 characters</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Assign Roles</label>
                            <div class="row g-3">
                                @foreach($roles as $role)
                                <div class="col-md-6">
                                    <div class="form-check p-3 border rounded d-flex align-items-start">
                                        <input class="form-check-input mt-1 me-2 m-4" 
                                               type="checkbox" 
                                               name="roles[]" 
                                               value="{{ $role->id }}" 
                                               id="role_{{ $role->id }}">
                                        <div class="flex-grow-1">
                                            <label class="form-check-label d-block" for="role_{{ $role->id }}">
                                                {{ $role->name }}
                                            </label>
                                            @if($role->description)
                                                <small class="d-block text-muted mt-1">{{ $role->description }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <small class="form-text text-muted mt-2">You can select multiple roles or none. Admin can have any combination of roles.</small>
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 10px; font-weight: 500;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                    <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                    <path d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Z"/>
                                </svg>
                                Create Admin
                            </button>
                            <a href="{{ route('superadmin.admins') }}" class="btn btn-outline-secondary px-4 py-2 ms-2" style="border-radius: 10px; font-weight: 500;">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
