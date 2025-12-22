<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Registration;
use App\Models\UserKycProfile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function dashboard()
    {
        try {
            $userId = session('user_id');
            $user = Registration::with('messages')->find($userId);

            if (! $user) {
                session()->forget(['user_id', 'user_email', 'user_name', 'user_registration_id']);

                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Force KYC completion before accessing dashboard
            $hasCompletedKyc = UserKycProfile::where('user_id', $userId)
                ->where('status', 'completed')
                ->exists();

            if (! $hasCompletedKyc) {
                return redirect()->route('user.kyc.show')
                    ->with('info', 'Please complete your KYC before accessing the dashboard.');
            }

            $unreadCount = $user->unreadMessagesCount();

            // Get user's applications with status history (all visible, is_active shows live status)
            $applications = Application::with(['statusHistory'])
                ->where('user_id', $userId)
                ->latest()
                ->take(5)
                ->get();

            // Check if user has any IX application (submitted, approved, or payment_verified)
            $hasIxApplication = Application::where('user_id', $userId)
                ->where('application_type', 'IX')
                ->whereIn('status', ['submitted', 'approved', 'payment_verified', 'processor_forwarded_legal', 'legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending'])
                ->exists();

            return response()->view('user.dashboard', compact('user', 'unreadCount', 'applications', 'hasIxApplication'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading user dashboard: '.$e->getMessage());
            abort(500, 'Unable to load dashboard. Please try again later.');
        }
    }

    /**
     * Display user profile.
     */
    public function profile()
    {
        try {
            $userId = session('user_id');
            $user = Registration::with('profileUpdateRequests')->find($userId);

            if (! $user) {
                session()->forget(['user_id', 'user_email', 'user_name', 'user_registration_id']);

                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Force KYC completion before accessing profile
            $hasCompletedKyc = UserKycProfile::where('user_id', $userId)
                ->where('status', 'completed')
                ->exists();

            if (! $hasCompletedKyc) {
                return redirect()->route('user.kyc.show')
                    ->with('info', 'Please complete your KYC before accessing your profile.');
            }

            $pendingRequest = $user->pendingProfileUpdateRequest();

            // Get approved request that hasn't been submitted
            $approvedRequest = $user->profileUpdateRequests()
                ->where('status', 'approved')
                ->whereNull('submitted_at')
                ->latest()
                ->first();

            // Get submitted request waiting for approval
            $submittedRequest = $user->profileUpdateRequests()
                ->where('status', 'approved')
                ->whereNotNull('submitted_at')
                ->where('update_approved', false)
                ->latest()
                ->first();

            // Get approved update (if any)
            $updateApprovedRequest = $user->profileUpdateRequests()
                ->where('status', 'approved')
                ->where('update_approved', true)
                ->latest()
                ->first();

            return response()->view('user.profile', compact('user', 'pendingRequest', 'approvedRequest', 'submittedRequest', 'updateApprovedRequest'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading user profile: '.$e->getMessage());
            abort(500, 'Unable to load profile. Please try again later.');
        }
    }
}
