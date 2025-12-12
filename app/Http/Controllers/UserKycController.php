<?php

namespace App\Http\Controllers;

use App\Models\GstVerification;
use App\Models\McaVerification;
use App\Models\Registration;
use App\Models\UdyamVerification;
use App\Models\UserKycProfile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserKycController extends Controller
{
    /**
     * Show the 2-step KYC form for the logged-in user.
     */
    public function show(Request $request)
    {
        try {
            $userId = (int) $request->session()->get('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Load or create a pending KYC profile for this user
            $kyc = UserKycProfile::where('user_id', $userId)->latest()->first();

            if (! $kyc) {
                $kyc = UserKycProfile::create([
                    'user_id' => $userId,
                    'status' => 'pending',
                ]);
            }

            // Pre-fill contact details from registration if not already set
            if (! $kyc->contact_name) {
                $kyc->contact_name = $user->fullname;
            }
            if (! $kyc->contact_dob) {
                $kyc->contact_dob = $user->dateofbirth;
            }
            if (! $kyc->contact_pan) {
                $kyc->contact_pan = $user->pancardno;
            }
            if (! $kyc->contact_email) {
                $kyc->contact_email = $user->email;
            }
            if (! $kyc->contact_mobile) {
                $kyc->contact_mobile = $user->mobile;
            }

            return view('user.kyc.index', [
                'user' => $user,
                'kyc' => $kyc,
            ]);
        } catch (Exception $e) {
            Log::error('Error loading KYC form: '.$e->getMessage());

            return redirect()->route('user.dashboard')
                ->with('error', 'Unable to load KYC form. Please try again.');
        }
    }

    /**
     * Store / update KYC details after all verifications are completed.
     */
    public function store(Request $request)
    {
        try {
            $userId = (int) $request->session()->get('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $validated = $request->validate([
                // Step 1 - MSME question
                'is_msme' => 'required|in:0,1',
                // Step 1 - Both CIN and GSTIN must be verified
                'cin' => 'required|string',
                'mca_verification_id' => 'required|integer',
                'mca_verified' => 'required|boolean|accepted',
                'gstin' => 'required|string|size:15|regex:/^[0-9A-Z]{15}$/',
                'gst_verification_id' => 'required|integer',
                'gst_verified' => 'required|boolean|accepted',
                'udyam_number' => 'nullable|string',
                'udyam_verification_id' => 'nullable|integer',
                'udyam_verified' => 'nullable|boolean',
                // Step 2 - contact / authorised representative
                'contact_name' => 'required|string|max:255',
                'contact_dob' => 'required|date|before:today',
                'contact_pan' => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                'contact_email' => 'required|email:rfc,dns|max:255',
                'contact_mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/',
                'contact_name_pan_dob_verified' => 'required|boolean',
                'contact_email_verified' => 'required|boolean',
                'contact_mobile_verified' => 'required|boolean',
                // Billing details (optional but recommended)
                'billing_address_source' => 'nullable|string|max:50',
                'billing_address' => 'nullable|string',
            ]);

            // Ensure both CIN and GSTIN are verified
            if (empty($validated['mca_verification_id']) || ! $validated['mca_verified']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify CIN before submitting KYC.',
                ], 422);
            }

            if (empty($validated['gst_verification_id']) || ! $validated['gst_verified']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify GSTIN before submitting KYC.',
                ], 422);
            }

            // Ensure CIN verification record is valid and belongs to user
            $mcaVerification = null;
            if (! empty($validated['mca_verification_id'])) {
                $mcaVerification = McaVerification::where('id', $validated['mca_verification_id'])
                    ->where('user_id', $userId)
                    ->where('is_verified', true)
                    ->first();

                if (! $mcaVerification) {
                    return response()->json([
                        'success' => false,
                        'message' => 'CIN is not verified. Please verify again.',
                    ], 422);
                }
            }

            // Ensure GST verification record is valid and belongs to user
            $gstVerification = null;
            if (! empty($validated['gst_verification_id'])) {
                $gstVerification = GstVerification::where('id', $validated['gst_verification_id'])
                    ->where('user_id', $userId)
                    ->where('is_verified', true)
                    ->first();

                if (! $gstVerification) {
                    return response()->json([
                        'success' => false,
                        'message' => 'GSTIN is not verified. Please verify again.',
                    ], 422);
                }
            }

            $udyamVerification = null;
            if (! empty($validated['udyam_verification_id'])) {
                $udyamVerification = UdyamVerification::where('id', $validated['udyam_verification_id'])
                    ->where('user_id', $userId)
                    ->where('is_verified', true)
                    ->first();
            }


            // Ensure company names from different documents (GST / UDYAM / CIN) match
            $companyNames = [];

            if ($gstVerification) {
                $name = $gstVerification->legal_name ?? $gstVerification->trade_name;
                if ($name) {
                    $companyNames[] = $name;
                }
            }

            if ($udyamVerification && $udyamVerification->verification_data) {
                $udyamData = is_array($udyamVerification->verification_data)
                    ? $udyamVerification->verification_data
                    : json_decode((string) $udyamVerification->verification_data, true);
                $udyamName = $udyamData['result']['source_output']['general_details']['enterprise_name'] ?? null;
                if ($udyamName) {
                    $companyNames[] = $udyamName;
                }
            }

            if ($mcaVerification && $mcaVerification->verification_data) {
                $mcaData = is_array($mcaVerification->verification_data)
                    ? $mcaVerification->verification_data
                    : json_decode((string) $mcaVerification->verification_data, true);
                $mcaName = $mcaData['result']['source_output']['company_name'] ?? null;
                if ($mcaName) {
                    $companyNames[] = $mcaName;
                }
            }

            // Normalize and ensure unique company names
            $normalized = [];
            foreach ($companyNames as $name) {
                $normalized[] = trim(mb_strtolower($name));
            }
            $uniqueNames = array_values(array_unique($normalized));

            if (count($uniqueNames) > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company name does not match across verified documents (GST / UDYAM / CIN). Please verify correct details.',
                ], 422);
            }

            // Load or create KYC profile
            $kyc = UserKycProfile::firstOrNew([
                'user_id' => $userId,
            ]);

            $kyc->fill([
                'is_msme' => (bool) $validated['is_msme'],
                'gstin' => $validated['gstin'] ?? null,
                'gst_verification_id' => $gstVerification?->id,
                'gst_verified' => (bool) ($validated['gst_verified'] ?? false),
                'udyam_number' => $validated['udyam_number'] ?? null,
                'udyam_verification_id' => $udyamVerification?->id,
                'udyam_verified' => (bool) ($validated['udyam_verified'] ?? false),
                'cin' => $validated['cin'] ?? null,
                'mca_verification_id' => $mcaVerification?->id,
                'mca_verified' => (bool) ($validated['mca_verified'] ?? false),
                'contact_name' => $validated['contact_name'],
                'contact_dob' => $validated['contact_dob'],
                'contact_pan' => strtoupper($validated['contact_pan']),
                'contact_email' => $validated['contact_email'],
                'contact_mobile' => $validated['contact_mobile'],
                'contact_name_pan_dob_verified' => (bool) $validated['contact_name_pan_dob_verified'],
                'contact_email_verified' => (bool) $validated['contact_email_verified'],
                'contact_mobile_verified' => (bool) $validated['contact_mobile_verified'],
                'billing_address_source' => $validated['billing_address_source'] ?? null,
                'billing_address' => $validated['billing_address'] ?? null,
                'status' => 'completed',
                'completed_at' => now('Asia/Kolkata'),
                'kyc_ip_address' => $request->ip(),
                'kyc_user_agent' => (string) $request->userAgent(),
            ]);

            $kyc->save();

            return response()->json([
                'success' => true,
                'message' => 'KYC details submitted successfully.',
            ]);
        } catch (Exception $e) {
            Log::error('Error saving KYC details: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save KYC details. Please try again.',
            ], 500);
        }
    }
}
