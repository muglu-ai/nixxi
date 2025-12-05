<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\IxApplicationController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileUpdateRequestController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SuperAdmin\IxLocationController as SuperAdminIxLocationController;
use App\Http\Controllers\SuperAdmin\IxPortPricingController as SuperAdminIxPortPricingController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SuperAdminLoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserKycController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Home Route
Route::get('/', function () {
    return view('welcome');
});

// SuperAdmin Login Routes (Public - no authentication required)
Route::prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/login', [SuperAdminLoginController::class, 'index'])->name('login');
    Route::post('/login', [SuperAdminLoginController::class, 'login'])->name('login.submit');
    Route::get('/login/verify', [SuperAdminLoginController::class, 'showVerify'])->name('login.verify');
    Route::post('/login/verify', [SuperAdminLoginController::class, 'verifyOtp'])->name('login.verify.otp');
    Route::post('/login/resend-otp', [SuperAdminLoginController::class, 'resendOtp'])->name('login.resend-otp');
    Route::post('/logout', [SuperAdminLoginController::class, 'logout'])->name('logout');
});

// SuperAdmin Routes (Requires authentication)
Route::prefix('superadmin')->name('superadmin.')->middleware(['superadmin'])->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'index'])->name('dashboard');

    // User management
    Route::get('/users', [SuperAdminController::class, 'users'])->name('users');
    Route::get('/users/{id}', [SuperAdminController::class, 'showUser'])->name('users.show');
    Route::post('/applications/{applicationId}/accept-payment', [SuperAdminController::class, 'acceptPayment'])->name('applications.accept-payment');

    // Admin management
    Route::get('/admins', [SuperAdminController::class, 'admins'])->name('admins');
    Route::get('/admins/create', [SuperAdminController::class, 'createAdmin'])->name('admins.create');
    Route::post('/admins', [SuperAdminController::class, 'storeAdmin'])->name('admins.store');
    Route::get('/admins/{id}', [SuperAdminController::class, 'showAdmin'])->name('admins.show');
    Route::get('/admins/{id}/edit', [SuperAdminController::class, 'editAdmin'])->name('admins.edit');
    Route::post('/admins/{id}', [SuperAdminController::class, 'updateAdmin'])->name('admins.update');
    Route::get('/admins/{id}/edit-details', [SuperAdminController::class, 'editAdminDetails'])->name('admins.edit-details');
    Route::post('/admins/{id}/update-details', [SuperAdminController::class, 'updateAdminDetails'])->name('admins.update-details');
    Route::get('/admins/{id}/edit-type', [SuperAdminController::class, 'editAdminType'])->name('admins.edit-type');
    Route::post('/admins/{id}/update-type', [SuperAdminController::class, 'updateAdminType'])->name('admins.update-type');
    Route::patch('/admins/{id}/toggle-status', [SuperAdminController::class, 'toggleAdminStatus'])->name('admins.toggle-status');

    // Messages management
    Route::get('/messages', [SuperAdminController::class, 'messages'])->name('messages');
    Route::get('/messages/{id}', [SuperAdminController::class, 'showMessage'])->name('messages.show');

    // IP Pricing management
    Route::get('/ip-pricing', [\App\Http\Controllers\SuperAdmin\IpPricingController::class, 'index'])->name('ip-pricing.index');
    Route::post('/ip-pricing', [\App\Http\Controllers\SuperAdmin\IpPricingController::class, 'store'])->name('ip-pricing.store');
    Route::put('/ip-pricing/{id}', [\App\Http\Controllers\SuperAdmin\IpPricingController::class, 'update'])->name('ip-pricing.update');
    Route::patch('/ip-pricing/{id}/toggle-status', [\App\Http\Controllers\SuperAdmin\IpPricingController::class, 'toggleStatus'])->name('ip-pricing.toggle-status');
    Route::delete('/ip-pricing/{id}', [\App\Http\Controllers\SuperAdmin\IpPricingController::class, 'destroy'])->name('ip-pricing.destroy');
    Route::get('/ip-pricing/{id}/history', [\App\Http\Controllers\SuperAdmin\IpPricingController::class, 'history'])->name('ip-pricing.history');

    // IX Location management
    Route::get('/ix-locations', [SuperAdminIxLocationController::class, 'index'])->name('ix-locations.index');
    Route::post('/ix-locations', [SuperAdminIxLocationController::class, 'store'])->name('ix-locations.store');
    Route::put('/ix-locations/{ixLocation}', [SuperAdminIxLocationController::class, 'update'])->name('ix-locations.update');
    Route::patch('/ix-locations/{ixLocation}/toggle', [SuperAdminIxLocationController::class, 'toggleStatus'])->name('ix-locations.toggle');
    Route::delete('/ix-locations/{ixLocation}', [SuperAdminIxLocationController::class, 'destroy'])->name('ix-locations.destroy');

    // IX Port pricing management
    Route::get('/ix-port-pricing', [SuperAdminIxPortPricingController::class, 'index'])->name('ix-port-pricing.index');
    Route::post('/ix-port-pricing', [SuperAdminIxPortPricingController::class, 'store'])->name('ix-port-pricing.store');
    Route::put('/ix-port-pricing/{ixPortPricing}', [SuperAdminIxPortPricingController::class, 'update'])->name('ix-port-pricing.update');
    Route::patch('/ix-port-pricing/{ixPortPricing}/toggle', [SuperAdminIxPortPricingController::class, 'toggleStatus'])->name('ix-port-pricing.toggle');
    Route::delete('/ix-port-pricing/{ixPortPricing}', [SuperAdminIxPortPricingController::class, 'destroy'])->name('ix-port-pricing.destroy');

    // IX Application pricing management
    Route::get('/ix-application-pricing', [\App\Http\Controllers\SuperAdmin\IxApplicationPricingController::class, 'index'])->name('ix-application-pricing.index');
    Route::post('/ix-application-pricing', [\App\Http\Controllers\SuperAdmin\IxApplicationPricingController::class, 'store'])->name('ix-application-pricing.store');
    Route::put('/ix-application-pricing/{ixApplicationPricing}', [\App\Http\Controllers\SuperAdmin\IxApplicationPricingController::class, 'update'])->name('ix-application-pricing.update');
    Route::patch('/ix-application-pricing/{ixApplicationPricing}/toggle', [\App\Http\Controllers\SuperAdmin\IxApplicationPricingController::class, 'toggleStatus'])->name('ix-application-pricing.toggle');
    Route::delete('/ix-application-pricing/{ixApplicationPricing}', [\App\Http\Controllers\SuperAdmin\IxApplicationPricingController::class, 'destroy'])->name('ix-application-pricing.destroy');
});

// Admin Login Routes (Public - no authentication required)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'index'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');
    Route::get('/login/verify', [AdminLoginController::class, 'showVerify'])->name('login.verify');
    Route::post('/login/verify', [AdminLoginController::class, 'verifyOtp'])->name('login.verify.otp');
    Route::post('/login/resend-otp', [AdminLoginController::class, 'resendOtp'])->name('login.resend-otp');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
});

// Admin Routes (Requires authentication)
Route::prefix('admin')->name('admin.')->middleware(['admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    // User management
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/{id}', [AdminController::class, 'showUser'])->name('users.show');
    Route::post('/users/{id}/send-message', [AdminController::class, 'sendMessage'])->name('users.send-message');
    Route::post('/users/{id}/update-status', [AdminController::class, 'updateUserStatus'])->name('users.update-status');

    // Profile update requests
    Route::get('/profile-update-requests', [AdminController::class, 'profileUpdateRequests'])->name('profile-update-requests');
    Route::post('/profile-updates/{id}/approve', [AdminController::class, 'approveProfileUpdate'])->name('profile-updates.approve');
    Route::post('/profile-updates/{id}/approve-submitted', [AdminController::class, 'approveSubmittedUpdate'])->name('profile-updates.approve-submitted');
    Route::post('/profile-updates/{id}/reject', [AdminController::class, 'rejectProfileUpdate'])->name('profile-updates.reject');

    // Messages
    Route::get('/messages', [AdminController::class, 'messages'])->name('messages');

    // Requests and Messages combined page
    Route::get('/requests-messages', [AdminController::class, 'requestsAndMessages'])->name('requests-messages');

    // Application management routes
    Route::get('/applications', [AdminController::class, 'applications'])->name('applications');
    Route::get('/applications/{id}', [AdminController::class, 'showApplication'])->name('applications.show');

    // Legacy Processor routes (for backward compatibility)
    Route::post('/applications/{id}/approve-to-finance', [AdminController::class, 'approveToFinance'])->name('applications.approve-to-finance');

    // Legacy Finance routes (for backward compatibility)
    Route::post('/applications/{id}/approve-to-technical', [AdminController::class, 'approveToTechnical'])->name('applications.approve-to-technical');
    Route::post('/applications/{id}/send-back-to-processor', [AdminController::class, 'sendBackToProcessor'])->name('applications.send-back-to-processor');

    // Legacy Technical routes (for backward compatibility)
    Route::post('/applications/{id}/approve', [AdminController::class, 'approveApplication'])->name('applications.approve');
    Route::post('/applications/{id}/send-back-to-finance', [AdminController::class, 'sendBackToFinance'])->name('applications.send-back-to-finance');

    // New IX Workflow routes
    // IX Processor routes
    Route::post('/applications/{id}/ix-processor/forward-to-legal', [AdminController::class, 'ixProcessorForwardToLegal'])->name('applications.ix-processor.forward-to-legal');
    Route::post('/applications/{id}/ix-processor/request-resubmission', [AdminController::class, 'ixProcessorRequestResubmission'])->name('applications.ix-processor.request-resubmission');

    // IX Legal routes
    Route::post('/applications/{id}/ix-legal/forward-to-head', [AdminController::class, 'ixLegalForwardToHead'])->name('applications.ix-legal.forward-to-head');
    Route::post('/applications/{id}/ix-legal/send-back-to-processor', [AdminController::class, 'ixLegalSendBackToProcessor'])->name('applications.ix-legal.send-back-to-processor');

    // IX Head routes
    Route::post('/applications/{id}/ix-head/forward-to-ceo', [AdminController::class, 'ixHeadForwardToCeo'])->name('applications.ix-head.forward-to-ceo');
    Route::post('/applications/{id}/ix-head/send-back-to-processor', [AdminController::class, 'ixHeadSendBackToProcessor'])->name('applications.ix-head.send-back-to-processor');

    // CEO routes
    Route::post('/applications/{id}/ceo/approve', [AdminController::class, 'ceoApprove'])->name('applications.ceo.approve');
    Route::post('/applications/{id}/ceo/reject', [AdminController::class, 'ceoReject'])->name('applications.ceo.reject');
    Route::post('/applications/{id}/ceo/send-back-to-head', [AdminController::class, 'ceoSendBackToHead'])->name('applications.ceo.send-back-to-head');

    // Nodal Officer routes
    Route::post('/applications/{id}/nodal-officer/assign-port', [AdminController::class, 'nodalOfficerAssignPort'])->name('applications.nodal-officer.assign-port');
    Route::post('/applications/{id}/nodal-officer/hold', [AdminController::class, 'nodalOfficerHold'])->name('applications.nodal-officer.hold');
    Route::post('/applications/{id}/nodal-officer/not-feasible', [AdminController::class, 'nodalOfficerNotFeasible'])->name('applications.nodal-officer.not-feasible');
    Route::post('/applications/{id}/nodal-officer/customer-denied', [AdminController::class, 'nodalOfficerCustomerDenied'])->name('applications.nodal-officer.customer-denied');
    Route::post('/applications/{id}/nodal-officer/forward-to-processor', [AdminController::class, 'nodalOfficerForwardToProcessor'])->name('applications.nodal-officer.forward-to-processor');

    // IX Tech Team routes
    Route::post('/applications/{id}/ix-tech-team/assign-ip', [AdminController::class, 'ixTechTeamAssignIp'])->name('applications.ix-tech-team.assign-ip');

    // IX Account routes
    Route::post('/applications/{id}/ix-account/generate-invoice', [AdminController::class, 'ixAccountGenerateInvoice'])->name('applications.ix-account.generate-invoice');
    Route::post('/applications/{id}/ix-account/verify-payment', [AdminController::class, 'ixAccountVerifyPayment'])->name('applications.ix-account.verify-payment');
});

// Register Routes (Public - no authentication required)
Route::prefix('register')->name('register.')->group(function () {
    Route::get('/', [RegisterController::class, 'index'])->name('index');
    Route::post('/', [RegisterController::class, 'store'])->name('store');
    Route::post('/send-email-otp', [RegisterController::class, 'sendEmailOtp'])->name('send.email.otp');
    Route::post('/send-mobile-otp', [RegisterController::class, 'sendMobileOtp'])->name('send.mobile.otp');
    Route::post('/verify-email-otp', [RegisterController::class, 'verifyEmailOtp'])->name('verify.email.otp');
    Route::post('/verify-mobile-otp', [RegisterController::class, 'verifyMobileOtp'])->name('verify.mobile.otp');
    Route::post('/verify-pan', [RegisterController::class, 'verifyPan'])->name('verify.pan');
    Route::post('/check-pan-status', [RegisterController::class, 'checkPanStatus'])->name('check.pan.status');
    // Legacy routes (can be removed if not needed)
    Route::get('/verify', [RegisterController::class, 'showVerify'])->name('verify');
    Route::post('/verify', [RegisterController::class, 'verifyOtp'])->name('verify.otp');
    Route::post('/resend-otp', [RegisterController::class, 'resendOtp'])->name('resend.otp');
    // Add more Register routes here
});

// Login Routes (Public - no authentication required)
Route::prefix('login')->name('login.')->group(function () {
    Route::get('/', [LoginController::class, 'index'])->name('index');
    Route::post('/', [LoginController::class, 'login'])->name('submit');
    Route::get('/verify', [LoginController::class, 'showVerify'])->name('verify');
    Route::post('/verify', [LoginController::class, 'verifyOtp'])->name('verify.otp');
    Route::post('/resend-otp', [LoginController::class, 'resendOtp'])->name('resend.otp');
    Route::get('/forgot-password', [LoginController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('/forgot-password', [LoginController::class, 'forgotPassword'])->name('forgot-password.submit');
    Route::get('/reset-password/{token}', [LoginController::class, 'showResetPassword'])->name('reset-password');
    Route::post('/reset-password', [LoginController::class, 'resetPassword'])->name('reset-password.submit');
    Route::get('/update-password/{token}', [LoginController::class, 'showUpdatePassword'])->name('update-password');
    Route::post('/update-password', [LoginController::class, 'updatePassword'])->name('update-password.submit');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// User Routes (Requires authentication)
Route::prefix('user')->name('user.')->middleware(['user.auth'])->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');

    // KYC routes
    Route::get('/kyc', [UserKycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc', [UserKycController::class, 'store'])->name('kyc.store');

    // Messages routes
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/{id}', [MessageController::class, 'show'])->name('show');
        Route::post('/{id}/reply', [MessageController::class, 'reply'])->name('reply');
        Route::post('/{id}/mark-read', [MessageController::class, 'markAsRead'])->name('mark-read');
        Route::get('/unread/count', [MessageController::class, 'unreadCount'])->name('unread.count');
    });

    // Profile update request routes
    Route::prefix('profile-update')->name('profile-update.')->group(function () {
        Route::get('/request', [ProfileUpdateRequestController::class, 'create'])->name('request');
        Route::post('/request', [ProfileUpdateRequestController::class, 'store'])->name('store');
        Route::get('/edit', [ProfileUpdateRequestController::class, 'edit'])->name('edit');
        Route::post('/update', [ProfileUpdateRequestController::class, 'update'])->name('update');
        Route::post('/send-email-otp', [ProfileUpdateRequestController::class, 'sendEmailOtp'])->name('send-email-otp');
        Route::post('/send-mobile-otp', [ProfileUpdateRequestController::class, 'sendMobileOtp'])->name('send-mobile-otp');
        Route::post('/verify-email-otp', [ProfileUpdateRequestController::class, 'verifyEmailOtp'])->name('verify-email-otp');
        Route::post('/verify-mobile-otp', [ProfileUpdateRequestController::class, 'verifyMobileOtp'])->name('verify-mobile-otp');
    });

    // Applications routes (only for approved users)
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [ApplicationController::class, 'index'])->name('index');

        // IRINN Application routes (must be before {id} route)
        Route::get('/irin/create', [ApplicationController::class, 'createIrin'])->name('irin.create');
        Route::post('/irin/fetch-gst', [ApplicationController::class, 'fetchGstDetails'])->name('irin.fetch-gst');
        Route::post('/irin/verify-gst', [ApplicationController::class, 'verifyGst'])->name('irin.verify-gst');
        Route::post('/irin/verify-udyam', [ApplicationController::class, 'verifyUdyam'])->name('irin.verify-udyam');
        Route::post('/irin/verify-mca', [ApplicationController::class, 'verifyMca'])->name('irin.verify-mca');
        Route::post('/irin/verify-roc-iec', [ApplicationController::class, 'verifyRocIec'])->name('irin.verify-roc-iec');
        Route::post('/irin/check-verification-status', [ApplicationController::class, 'checkVerificationStatus'])->name('irin.check-verification-status');
        Route::post('/irin/store', [ApplicationController::class, 'storeIrin'])->name('irin.store');
        Route::get('/irin/pricing', [ApplicationController::class, 'getIpPricing'])->name('irin.pricing');

        // IX Application routes
        Route::prefix('ix')->name('ix.')->group(function () {
            Route::get('/create', [IxApplicationController::class, 'create'])->name('create');
            Route::post('/submit', [IxApplicationController::class, 'store'])->name('store');
            Route::post('/initiate-payment', [IxApplicationController::class, 'initiatePayment'])->name('initiate-payment');
            Route::get('/{id}/pay-now', [IxApplicationController::class, 'payNow'])->name('pay-now');
            Route::get('/preview', [IxApplicationController::class, 'preview'])->name('preview');
            Route::post('/{applicationId}/submit', [IxApplicationController::class, 'finalSubmit'])->name('final-submit');
            Route::get('/agreement', [IxApplicationController::class, 'downloadAgreement'])->name('agreement');
            Route::get('/locations', [IxApplicationController::class, 'locations'])->name('locations');
            Route::get('/pricing', [IxApplicationController::class, 'pricing'])->name('pricing');
            Route::get('/application-pricing', [IxApplicationController::class, 'getApplicationPricing'])->name('application-pricing');
            Route::get('/{id}/download-application-pdf', [IxApplicationController::class, 'downloadApplicationPdf'])->name('download-application-pdf');
        });

        // PDF download routes (must be before {id} route)
        Route::get('/{id}/download-application-pdf', [ApplicationController::class, 'downloadApplicationPdf'])->name('download-application-pdf');
        Route::get('/{id}/download-invoice-pdf', [ApplicationController::class, 'downloadInvoicePdf'])->name('download-invoice-pdf');

        // Show application (must be last)
        Route::get('/{id}', [ApplicationController::class, 'show'])->name('show');
    });

    // Add more User routes here
});

// PayU Callback URLs (MUST be outside auth middleware - PayU redirects user here)
// These routes are accessible without authentication since PayU redirects the user's browser
Route::any('/user/applications/ix/payment-success', [IxApplicationController::class, 'paymentSuccess'])->name('user.applications.ix.payment-success');
Route::any('/user/applications/ix/payment-failure', [IxApplicationController::class, 'paymentFailure'])->name('user.applications.ix.payment-failure');

// PayU S2S Webhook (must be outside auth middleware - PayU server calls this directly)
Route::post('/payu/webhook', [IxApplicationController::class, 'handleWebhook'])->name('payu.webhook');

// Application Routes
Route::prefix('application')->name('application.')->middleware(['application'])->group(function () {
    Route::get('/dashboard', [ApplicationController::class, 'index'])->name('dashboard');
    // Add more Application routes here
});

// ⚠️ TEMPORARY: Log Viewer Route - REMOVE AFTER DEBUGGING ⚠️
Route::get('/admin/view-logs', function (Request $request) {
    // Basic security - require user authentication
    if (!session('user_id')) {
        return redirect()->route('login.index')
            ->with('error', 'Please login to view logs.');
    }
    
    $logFile = storage_path('logs/laravel.log');
    
    if (!file_exists($logFile)) {
        return response()->json([
            'error' => 'Log file not found',
            'path' => $logFile
        ], 404);
    }
    
    // Get filter parameter
    $filter = $request->get('filter', 'all'); // all, payu, errors
    $lines = (int) $request->get('lines', 200); // number of lines to show
    $lines = min($lines, 1000); // limit to 1000 lines max
    
    // Read log file
    $logContent = file_get_contents($logFile);
    $logLines = explode("\n", $logContent);
    $totalLines = count($logLines);
    
    // Get last N lines
    $recentLines = array_slice($logLines, -$lines);
    
    // Apply filter
    $filteredLines = [];
    foreach ($recentLines as $line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }
        
        if ($filter === 'payu') {
            if (stripos($line, 'PayU') !== false || 
                stripos($line, 'payment') !== false || 
                stripos($line, 'Payment') !== false) {
                $filteredLines[] = $line;
            }
        } elseif ($filter === 'errors') {
            if (stripos($line, 'ERROR') !== false || 
                stripos($line, 'Exception') !== false || 
                stripos($line, 'Failed') !== false) {
                $filteredLines[] = $line;
            }
        } else {
            $filteredLines[] = $line;
        }
    }
    
    // Return HTML view
    return response()->view('admin.logs-viewer', [
        'logs' => $filteredLines,
        'totalLines' => $totalLines,
        'showingLines' => count($filteredLines),
        'filter' => $filter,
        'lines' => $lines,
        'logFile' => $logFile,
        'fileSize' => filesize($logFile),
        'lastModified' => date('Y-m-d H:i:s', filemtime($logFile)),
    ]);
})->name('admin.view-logs');
