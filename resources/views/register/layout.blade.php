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
            --brand-blue: #1f3ba0;
            --brand-blue-dark: #152a78;
        }

        .text-danger,
        .text-success,
        .invalid-feedback,
        .alert-danger,
        .alert-success,
        .alert-danger ul li,
        .alert-success ul li {
            color: var(--brand-blue) !important;
        }

        .form-control.is-invalid,
        .form-check-input.is-invalid {
            border-color: var(--brand-blue) !important;
        }
        
        .form-control:focus,
        .form-select:focus,
        textarea:focus {
            border-color: var(--brand-blue) !important;
            box-shadow: 0 0 0 0.2rem rgba(31, 59, 160, 0.2) !important;
        }
        
        .input-group-text {
            border-color: var(--brand-blue) !important;
        }
        
        .badge.bg-danger,
        .badge.bg-success,
        .badge.bg-warning,
        .badge.bg-primary {
            background-color: var(--brand-blue) !important;
            color: #fff !important;
        }

        .btn,
        .btn-primary,
        .btn-warning,
        .btn-danger,
        .btn-success,
        .btn-outline-secondary,
        .btn-outline-primary {
            background-color: var(--brand-blue) !important;
            border-color: var(--brand-blue) !important;
            color: #fff !important;
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
            background-color: var(--brand-blue-dark) !important;
            border-color: var(--brand-blue-dark) !important;
            color: #fff !important;
        }

        a,
        .btn-link {
            color: var(--brand-blue) !important;
        }

        a:hover,
        .btn-link:hover {
            color: var(--brand-blue-dark) !important;
        }
    </style>
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body>
    <div class="container mt-4">
        @yield('content')
    </div>
    
    <!-- Bootstrap JS -->
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>

