<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin Dashboard')</title>
    
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
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('superadmin.dashboard') }}">Super Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('superadmin.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('superadmin.users') }}">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('superadmin.admins') }}">Admins</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('superadmin.messages') }}">Messages</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="pricingDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Pricing
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="pricingDropdown">
                            <li><a class="dropdown-item" href="{{ route('superadmin.ip-pricing.index') }}">IP Pricing</a></li>
                            <li><a class="dropdown-item" href="{{ route('superadmin.ix-locations.index') }}">IX Locations</a></li>
                            <li><a class="dropdown-item" href="{{ route('superadmin.ix-port-pricing.index') }}">IX Port Pricing</a></li>
                            <li><a class="dropdown-item" href="{{ route('superadmin.ix-application-pricing.index') }}">IX Application Pricing</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('superadmin.logout') }}" class="d-inline" id="logoutForm">
                            @csrf
                            <a href="#" class="nav-link" onclick="event.preventDefault(); if(confirm('Are you sure you want to logout?')) { document.getElementById('logoutForm').submit(); }">Logout</a>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert" style="border-radius: 12px; margin-top: 1rem;">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="opacity: 1;"></button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert" style="border-radius: 12px; margin-top: 1rem;">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="opacity: 1;"></button>
                </div>
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show shadow-sm border-0" role="alert" style="border-radius: 12px; margin-top: 1rem;">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">{{ session('info') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="opacity: 1;"></button>
                </div>
            </div>
        @endif

        @yield('content')
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>
