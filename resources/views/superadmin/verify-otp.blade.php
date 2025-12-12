<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin OTP Verification | {{ config('app.name', 'Laravel') }}</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body>
    <nav class="navbar navbar-light bg-light border-bottom" style="min-height: 60px;">
        <div class="container-fluid">
            @include('partials.logo')
        </div>
    </nav>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                            </svg>
                            Super Admin OTP Verification
                        </h4>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <p class="lead">Verify Your Identity</p>
                        <p class="text-muted">Please enter the OTP sent to <strong>{{ $email }}</strong></p>
                        
                        <form method="POST" action="{{ route('superadmin.login.verify.otp') }}" id="otpForm">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="otp" class="form-label">Enter OTP <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('otp') is-invalid @enderror" 
                                       id="otp" 
                                       name="otp" 
                                       placeholder="Enter 6-digit OTP"
                                       maxlength="6"
                                       pattern="[0-9]{6}"
                                       required
                                       autofocus>
                                <small class="form-text text-muted">Enter the 6-digit OTP sent to your email</small>
                                @error('otp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="master_otp" class="form-label">Or Use Master OTP (Optional)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="master_otp" 
                                       name="master_otp" 
                                       placeholder="Enter master OTP"
                                       maxlength="6">
                                <small class="form-text text-muted">You can use the master OTP instead</small>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-danger w-100">Verify OTP</button>
                            </div>

                            <div class="text-center">
                                <form method="POST" action="{{ route('superadmin.login.resend-otp') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-link">Resend OTP</button>
                                </form>
                                <span class="mx-2">|</span>
                                <a href="{{ route('superadmin.login') }}">Back to Login</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- OTP Input Validation -->
    <script>
        document.getElementById('otp').addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        document.getElementById('master_otp').addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Prevent back button navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
</body>
</html>

