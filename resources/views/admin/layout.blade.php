<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard')</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="admin-panel">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                ADMIN PANEL
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @php
                        // Get admin from shared variable or fetch it
                        $adminForRoles = isset($currentAdmin) ? $currentAdmin : \App\Models\Admin::with('roles')->find(session('admin_id'));
                        
                        // Get selected role from session (should be set by middleware)
                        $selectedRole = session('admin_selected_role', null);
                        
                        // Get all roles assigned to admin
                        $allRoles = $adminForRoles ? $adminForRoles->roles : collect();
                        
                        // Filter only active roles, but if none are active, show all roles
                        $activeRoles = $allRoles->where('is_active', true);
                        if ($activeRoles->count() === 0 && $allRoles->count() > 0) {
                            $activeRoles = $allRoles;
                        }
                        
                        // Validate selected role - if it doesn't exist in active roles, reset it
                        if ($selectedRole) {
                            $roleExists = $activeRoles->contains(function($role) use ($selectedRole) {
                                return $role->slug === $selectedRole;
                            });
                            if (!$roleExists) {
                                $selectedRole = null;
                                session()->forget('admin_selected_role');
                            }
                        }
                        
                        // Auto-select role if not selected (fallback)
                        if ($adminForRoles && $activeRoles->count() > 0 && !$selectedRole) {
                            if ($activeRoles->count() === 1) {
                                // Single role - auto-select it
                                $selectedRole = $activeRoles->first()->slug;
                            } else {
                                // Multiple roles - select based on priority
                                $priorityOrder = ['processor', 'finance', 'technical'];
                                foreach ($priorityOrder as $priorityRole) {
                                    $role = $activeRoles->firstWhere('slug', $priorityRole);
                                    if ($role) {
                                        $selectedRole = $priorityRole;
                                        break;
                                    }
                                }
                                // If no priority role found, select first one
                                if (!$selectedRole) {
                                    $selectedRole = $activeRoles->first()->slug;
                                }
                            }
                            // Save to session
                            if ($selectedRole) {
                                session(['admin_selected_role' => $selectedRole]);
                            }
                        }
                    @endphp
                    @if($adminForRoles && $activeRoles->count() > 0)
                    <li class="nav-item dropdown me-2">
                        <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center" 
                                type="button" 
                                id="roleDropdown" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false"
                                style="border-radius: 8px; font-weight: 500; white-space: nowrap; border: 2px solid rgba(255,255,255,0.3); transition: all 0.3s; padding: 8px 16px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2 d-none d-sm-inline" viewBox="0 0 16 16">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                            </svg>
                            <span class="d-none d-md-inline me-1">Switch Role:</span>
                            <strong class="text-white">{{ ucfirst($selectedRole ?? $activeRoles->first()->slug) }}</strong>
                            @if($activeRoles->count() > 1)
                                <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7rem;">{{ $activeRoles->count() }}</span>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="roleDropdown" style="border-radius: 12px; border: none; min-width: 220px; margin-top: 8px; z-index: 1050;">
                            <li><h6 class="dropdown-header" style="font-weight: 600; color: #2c3e50;">Select Your Role</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            @foreach($activeRoles as $role)
                                @php
                                    // Get current URL and append/update role parameter
                                    $currentPath = request()->path();
                                    $queryParams = request()->query();
                                    $queryParams['role'] = $role->slug;
                                    
                                    // Rebuild URL with role parameter
                                    $url = url($currentPath) . '?' . http_build_query($queryParams);
                                    
                                    $isSelected = ($selectedRole === $role->slug);
                                @endphp
                                <li>
                                    <a class="dropdown-item {{ $isSelected ? 'active bg-primary text-white' : '' }}" 
                                       href="{{ $url }}"
                                       style="cursor: pointer; padding: 12px 16px; transition: all 0.2s; {{ $isSelected ? 'font-weight: 600;' : '' }}"
                                       onmouseover="if(!this.classList.contains('active')) { this.style.backgroundColor='#f8f9fa'; }"
                                       onmouseout="if(!this.classList.contains('active')) { this.style.backgroundColor=''; }">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                @if($isSelected)
                                                    <i class="bi bi-check-circle-fill me-2" style="font-size: 18px;"></i>
                                                @else
                                                    <i class="bi bi-circle me-2" style="font-size: 18px; opacity: 0.3;"></i>
                                                @endif
                                                <span style="font-size: 1rem;">{{ ucfirst($role->name) }}</span>
                                            </div>
                                            @if($isSelected)
                                                <span class="badge bg-light text-primary ms-2" style="font-size: 0.75rem;">Active</span>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.dashboard') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l1.146 1.147a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5ZM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5 5 5Z"/>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.users') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                            </svg>
                            Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.requests-messages') }}">Requests & Messages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="{{ route('admin.messages') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                            </svg>
                            Messages
                            @php
                                $unreadCount = \App\Models\Message::where('is_read', false)->count();
                            @endphp
                            @if($unreadCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="messageBadge">
                                    {{ $unreadCount }}
                                </span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.grievance.index') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                            </svg>
                            Grievance
                        </a>
                    </li>
                    @if(isset($currentAdmin) && ($currentAdmin->hasRole('processor') || $currentAdmin->hasRole('finance') || $currentAdmin->hasRole('technical')))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.applications') }}">Applications</a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l1.146 1.147a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5ZM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5 5 5Z"/>
                            </svg>
                            Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('admin.logout') }}" class="d-inline" id="logoutForm">
                            @csrf
                            <a href="#" class="nav-link text-danger" onclick="event.preventDefault(); if(confirm('Are you sure you want to logout?')) { document.getElementById('logoutForm').submit(); }" style="color: var(--danger) !important;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                    <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                                    <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                                </svg>
                                Logout
                            </a>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid" style="min-height: calc(100vh - 80px); padding-top: 1.5rem; padding-bottom: 1.5rem;">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-md border-0" role="alert" style="border-radius: 0.75rem; margin-bottom: 1.5rem; border-left: 4px solid #10b981;">
                <div class="d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 4.384 6.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                    <div class="flex-grow-1 fw-medium">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="opacity: 1;"></button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-md border-0" role="alert" style="border-radius: 0.75rem; margin-bottom: 1.5rem; border-left: 4px solid #ef4444;">
                <div class="d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
                    </svg>
                    <div class="flex-grow-1 fw-medium">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="opacity: 1;"></button>
                </div>
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show shadow-md border-0" role="alert" style="border-radius: 0.75rem; margin-bottom: 1.5rem; border-left: 4px solid #3b82f6;">
                <div class="d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                    </svg>
                    <div class="flex-grow-1 fw-medium">{{ session('info') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="opacity: 1;"></button>
                </div>
            </div>
        @endif

        @yield('content')
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    
    <!-- Role Dropdown Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced role dropdown functionality
            const roleDropdown = document.getElementById('roleDropdown');
            if (roleDropdown) {
                // Find dropdown menu - it's the next sibling ul element
                let dropdownMenu = roleDropdown.nextElementSibling;
                while (dropdownMenu && !dropdownMenu.classList.contains('dropdown-menu')) {
                    dropdownMenu = dropdownMenu.nextElementSibling;
                }
                
                if (!dropdownMenu) {
                    console.error('Dropdown menu not found');
                    return;
                }
                
                // Initialize Bootstrap dropdown
                let bsDropdown = null;
                if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                    try {
                        bsDropdown = new bootstrap.Dropdown(roleDropdown, {
                            boundary: 'viewport',
                            popperConfig: {
                                modifiers: [
                                    {
                                        name: 'preventOverflow',
                                        options: {
                                            boundary: document.body
                                        }
                                    }
                                ]
                            }
                        });
                    } catch (e) {
                        console.error('Bootstrap dropdown initialization error:', e);
                    }
                }
                
                // Manual toggle fallback if Bootstrap doesn't work
                function toggleDropdown() {
                    const isShown = dropdownMenu.classList.contains('show');
                    if (isShown) {
                        dropdownMenu.classList.remove('show');
                        dropdownMenu.style.display = 'none';
                        roleDropdown.setAttribute('aria-expanded', 'false');
                    } else {
                        dropdownMenu.classList.add('show');
                        dropdownMenu.style.display = 'block';
                        roleDropdown.setAttribute('aria-expanded', 'true');
                    }
                }
                
                // Add click handler as fallback - ensure dropdown shows
                roleDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    // Force show if Bootstrap doesn't handle it
                    setTimeout(function() {
                        if (!dropdownMenu.classList.contains('show')) {
                            // Try Bootstrap first
                            if (bsDropdown) {
                                try {
                                    bsDropdown.show();
                                } catch (e) {
                                    toggleDropdown();
                                }
                            } else {
                                toggleDropdown();
                            }
                        }
                    }, 50);
                });
                
                // Add hover effect to button
                roleDropdown.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = 'rgba(255,255,255,0.1)';
                    this.style.borderColor = 'rgba(255,255,255,0.5)';
                });
                roleDropdown.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                    this.style.borderColor = 'rgba(255,255,255,0.3)';
                });
                
                // Ensure dropdown items are clickable
                const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
                dropdownItems.forEach(function(item) {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        const href = this.getAttribute('href');
                        if (href && href !== '#' && !this.classList.contains('disabled')) {
                            // Show loading state
                            if (roleDropdown) {
                                roleDropdown.disabled = true;
                                const originalContent = roleDropdown.innerHTML;
                                roleDropdown.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Switching...';
                                
                                // Navigate to the URL
                                setTimeout(function() {
                                    window.location.href = href;
                                }, 100);
                            }
                        }
                    });
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (roleDropdown && dropdownMenu) {
                        const isClickInside = roleDropdown.contains(e.target) || dropdownMenu.contains(e.target);
                        if (!isClickInside) {
                            // Try Bootstrap API first
                            if (bsDropdown) {
                                try {
                                    bsDropdown.hide();
                                } catch (e) {
                                    // Fallback: manually hide
                                    dropdownMenu.classList.remove('show');
                                    dropdownMenu.style.display = 'none';
                                    roleDropdown.setAttribute('aria-expanded', 'false');
                                }
                            } else {
                                // Fallback: manually hide dropdown
                                dropdownMenu.classList.remove('show');
                                dropdownMenu.style.display = 'none';
                                roleDropdown.setAttribute('aria-expanded', 'false');
                            }
                        }
                    }
                });
                
                // Listen to Bootstrap dropdown events
                if (dropdownMenu) {
                    dropdownMenu.addEventListener('shown.bs.dropdown', function() {
                        dropdownMenu.style.display = 'block';
                    });
                    dropdownMenu.addEventListener('hidden.bs.dropdown', function() {
                        dropdownMenu.style.display = 'none';
                    });
                }
            }
        });
    </script>
    
    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>
