<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Register')</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    
    <style>
        :root {
            --theme-primary: #FFD700; /* Yellow for user theme */
            --theme-primary-dark: #E6B800;
            --black: #000000;
        }

        .text-danger,
        .text-success,
        .invalid-feedback,
        .alert-danger,
        .alert-success,
        .alert-danger ul li,
        .alert-success ul li {
            color: var(--theme-primary) !important;
        }

        .form-control.is-invalid,
        .form-check-input.is-invalid {
            border-color: var(--black) !important;
            border-bottom: 2px solid var(--theme-primary) !important;
        }
        
        .form-control:focus,
        .form-select:focus,
        textarea:focus {
            border-color: var(--black) !important;
            border-bottom: 2px solid var(--theme-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.2) !important;
        }
        
        .input-group-text {
            border-color: var(--black) !important;
            border-bottom: 2px solid var(--theme-primary) !important;
        }
        
        .badge.bg-danger,
        .badge.bg-success,
        .badge.bg-warning,
        .badge.bg-primary {
            background-color: var(--theme-primary) !important;
            color: #000 !important;
            border: 1px solid var(--black) !important;
        }

        .btn,
        .btn-primary,
        .btn-warning,
        .btn-danger,
        .btn-success,
        .btn-outline-secondary,
        .btn-outline-primary {
            background-color: var(--theme-primary) !important;
            border-color: var(--theme-primary) !important;
            border-bottom: 2px solid var(--black) !important;
            color: #000 !important;
        }

        .btn:hover,
        .btn:focus,
        .btn-primary:hover,
        .btn-warning:hover,
        .btn-danger:hover,
        .btn-success:hover,
        .btn-outline-secondary:hover,
        .btn-outline-primary:hover,
        .btn:focus-visible {
            background-color: var(--theme-primary-dark) !important;
            border-color: var(--theme-primary-dark) !important;
            border-bottom: 2px solid var(--black) !important;
            color: #000 !important;
        }

        a,
        .btn-link {
            color: var(--theme-primary) !important;
        }

        a:hover,
        .btn-link:hover {
            color: var(--theme-primary-dark) !important;
            text-decoration: underline;
            text-decoration-color: var(--black) !important;
        }
    </style>
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body>
    <div class="nixi-logo-fixed">
        @include('partials.logo')
    </div>
    <div class="container mt-4">
        @yield('content')
    </div>
    
    <!-- Bootstrap JS -->
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>

