<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\GstVerification;
use App\Models\IxApplicationPricing;
use App\Models\IxLocation;
use App\Models\IxPortPricing;
use App\Models\PanVerification;
use App\Models\Registration;
use App\Models\UserKycProfile;
use App\Services\IdfyPanService;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PDOException;

class BackendDataEntryController extends Controller
{
    /**
     * Display backend data entry form.
     */
    public function index()
    {
        try {
            $locations = IxLocation::active()
                ->orderBy('node_type')
                ->orderBy('state')
                ->orderBy('name')
                ->get();

            $portPricings = IxPortPricing::active()
                ->orderBy('node_type')
                ->orderBy('display_order')
                ->get()
                ->groupBy('node_type');

            $applicationPricing = IxApplicationPricing::getActive();

            return view('admin.backend-data-entry.index', compact('locations', 'portPricings', 'applicationPricing'));
        } catch (Exception $e) {
            Log::error('Error loading backend data entry page: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load data entry page.');
        }
    }

    /**
     * Verify PAN for backend data entry.
     */
    public function verifyPan(Request $request)
    {
        try {
            $request->validate([
                'pancardno' => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                'fullname' => 'required|string|max:255',
                'dateofbirth' => 'required|date|before:today',
            ]);

            $panNo = strtoupper(trim(preg_replace('/[^A-Z0-9]/', '', $request->input('pancardno'))));
            $fullName = trim(strip_tags($request->input('fullname')));
            $dateOfBirth = $request->input('dateofbirth');

            $idfyService = new IdfyPanService;
            $result = $idfyService->verifyPan($panNo, $fullName, $dateOfBirth);

            if ($result['success']) {
                // Structure session data to match what the store method expects
                $panVerificationData = [
                    'request_id' => $result['request_id'] ?? null,
                    'status' => $result['status'] ?? 'completed',
                    'pan_status' => $result['pan_status'] ?? null,
                    'name_match' => $result['name_match'] ?? false,
                    'dob_match' => $result['dob_match'] ?? false,
                    'is_verified' => true,
                    'source_output' => $result['source_output'] ?? null,
                    'full_result' => $result['full_result'] ?? null,
                ];

                session(['pan_verification_data' => $panVerificationData]);

                return response()->json([
                    'success' => true,
                    'message' => 'PAN verified successfully!',
                    'data' => $panVerificationData,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'PAN verification failed.',
            ], 400);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error verifying PAN in backend data entry: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during PAN verification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store registration and application in backend data entry.
     */
    public function store(Request $request)
    {
        try {
            // Validate registration data
            $registrationRules = [
                'registration_type' => 'required|string|in:individual,entity',
                'pancardno' => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/|unique:registrations,pancardno',
                'fullname' => 'required|string|max:255|min:2|regex:/^[a-zA-Z\s\'-]+$/',
                'email' => 'required|email:rfc,dns|max:255|unique:registrations,email',
                'mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/|unique:registrations,mobile',
                'dateofbirth' => 'required|date|before:today',
            ];

            // Validate application data (IX application)
            $applicationRules = [
                'member_type' => 'required|string|in:isp,cdn,vno,govt,others',
                'member_type_other' => 'nullable|required_if:member_type,others|string|max:255',
                'representative_name' => 'required|string|max:255',
                'representative_pan' => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                'representative_dob' => 'required|date|before:today',
                'representative_email' => 'required|email|max:255',
                'representative_mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/',
                'gstin' => 'required|string|size:15|regex:/^[0-9A-Z]{15}$/',
                'location_id' => 'required|integer|exists:ix_locations,id',
                'port_capacity' => 'required|string|max:50',
                'billing_plan' => 'required|string|in:arc,mrc,quarterly',
                'ip_prefix_count' => 'required|integer|min:1|max:500',
                'ip_prefix_source' => 'required|string|in:irinn,apnic,others',
                'ip_prefix_provider' => 'nullable|required_if:ip_prefix_source,others|string|max:255',
                'pre_peering_connectivity' => 'required|string|in:none,single,multiple',
                'asn_number' => 'nullable|string|max:50',
                'router_height_u' => 'nullable|integer|min:1|max:50',
                'router_make_model' => 'nullable|string|max:255',
                'router_serial_number' => 'nullable|string|max:255',
                // Document files (all optional for backend entry)
                'agreement_file' => 'nullable|file|mimes:pdf|max:10240',
                'license_isp_file' => 'nullable|file|mimes:pdf|max:10240',
                'license_vno_file' => 'nullable|file|mimes:pdf|max:10240',
                'cdn_declaration_file' => 'nullable|file|mimes:pdf|max:10240',
                'general_declaration_file' => 'nullable|file|mimes:pdf|max:10240',
                'whois_details_file' => 'nullable|file|mimes:pdf|max:10240',
                'pan_document_file' => 'nullable|file|mimes:pdf|max:10240',
                'gstin_document_file' => 'nullable|file|mimes:pdf|max:10240',
                'msme_document_file' => 'nullable|file|mimes:pdf|max:10240',
                'incorporation_document_file' => 'nullable|file|mimes:pdf|max:10240',
                'authorized_rep_document_file' => 'nullable|file|mimes:pdf|max:10240',
            ];

            $validated = $request->validate(array_merge($registrationRules, $applicationRules));

            // Verify PAN was verified via API
            $panVerificationData = session('pan_verification_data');
            if (! $panVerificationData || ! ($panVerificationData['is_verified'] ?? false)) {
                return back()->with('error', 'Please verify PAN Card before submitting.')
                    ->withInput();
            }

            // Generate OTPs (for display only, not sent)
            // Use provided OTPs from request if available, otherwise generate new ones
            $emailOtp = $request->input('email_otp') ?: str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $mobileOtp = $request->input('mobile_otp') ?: str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Generate password
            $generatedPassword = $request->input('generated_password') ?: $this->generateRandomPassword();

            // Generate registration ID
            $registrationId = Registration::generateRegistrationId();

            // Sanitize inputs
            $panNo = strtoupper(trim(preg_replace('/[^A-Z0-9]/', '', $validated['pancardno'])));
            $fullName = trim(strip_tags($validated['fullname']));
            $email = strtolower(trim($validated['email']));
            $mobile = preg_replace('/[^0-9]/', '', $validated['mobile']);

            DB::beginTransaction();

            try {
                // Create registration
                $registration = Registration::create([
                    'registrationid' => $registrationId,
                    'pancardno' => $panNo,
                    'registration_type' => $validated['registration_type'],
                    'pan_verified' => true,
                    'fullname' => $fullName,
                    'email' => $email,
                    'email_otp' => $emailOtp,
                    'email_verified' => true, // Auto-verified in backend entry
                    'mobile' => $mobile,
                    'mobile_otp' => $mobileOtp,
                    'mobile_verified' => true, // Auto-verified in backend entry
                    'password' => Hash::make($generatedPassword),
                    'dateofbirth' => $validated['dateofbirth'],
                    'registrationdate' => now('Asia/Kolkata')->toDateString(),
                    'registrationtime' => now('Asia/Kolkata')->toTimeString(),
                    'status' => 'approved',
                ]);

                // Create PAN verification record
                PanVerification::create([
                    'user_id' => $registration->id,
                    'pan_number' => $panNo,
                    'request_id' => $panVerificationData['request_id'],
                    'status' => 'completed',
                    'is_verified' => true,
                    'verification_data' => $panVerificationData['full_result'] ?? null,
                    'full_name' => $fullName,
                    'date_of_birth' => $validated['dateofbirth'],
                    'pan_status' => $panVerificationData['pan_status'] ?? null,
                    'name_match' => $panVerificationData['name_match'] ?? false,
                    'dob_match' => $panVerificationData['dob_match'] ?? false,
                ]);

                // Handle GST verification - auto-verify for backend entry (do this early)
                $gstin = strtoupper(preg_replace('/[^A-Z0-9]/', '', $validated['gstin']));
                $gstVerification = GstVerification::where('user_id', $registration->id)
                    ->where('gstin', $gstin)
                    ->latest()
                    ->first();

                if (! $gstVerification) {
                    // Generate a unique request_id for backend entry
                    $backendRequestId = 'BACKEND-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));
                    
                    // Ensure uniqueness
                    while (GstVerification::where('request_id', $backendRequestId)->exists()) {
                        $backendRequestId = 'BACKEND-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));
                    }
                    
                    // Create GST verification (auto-verified in backend)
                    $gstVerification = GstVerification::create([
                        'user_id' => $registration->id,
                        'gstin' => $gstin,
                        'request_id' => $backendRequestId,
                        'is_verified' => true,
                        'status' => 'completed',
                        'verification_data' => ['backend_entry' => true],
                    ]);
                } else {
                    // Update existing to verified
                    $gstVerification->update([
                        'is_verified' => true,
                        'status' => 'completed',
                    ]);
                }

                // Get location and pricing
                $location = IxLocation::active()->findOrFail($validated['location_id']);
                $pricing = IxPortPricing::active()
                    ->where('node_type', $location->node_type)
                    ->where('port_capacity', $validated['port_capacity'])
                    ->firstOrFail();

                $payableAmount = $pricing->getAmountForPlan($validated['billing_plan']);

                // Handle file uploads
                $documentFields = [
                    'agreement_file',
                    'license_isp_file',
                    'license_vno_file',
                    'cdn_declaration_file',
                    'general_declaration_file',
                    'whois_details_file',
                    'pan_document_file',
                    'gstin_document_file',
                    'msme_document_file',
                    'incorporation_document_file',
                    'authorized_rep_document_file',
                ];

                $storedDocuments = [];
                $storagePrefix = 'applications/'.$registration->id.'/ix/'.now()->format('YmdHis');

                foreach ($documentFields as $field) {
                    if ($request->hasFile($field)) {
                        $storedDocuments[$field] = $request->file($field)
                            ->store($storagePrefix, 'public');
                    }
                }

                // Determine member type
                $memberType = $validated['member_type'] === 'others'
                    ? ($validated['member_type_other'] ?? 'Others')
                    : strtoupper($validated['member_type']);

                // Prepare application data
                $applicationData = [
                    'member_type' => $memberType,
                    'representative' => [
                        'name' => $validated['representative_name'],
                        'pan' => strtoupper(preg_replace('/[^A-Z0-9]/', '', $validated['representative_pan'])),
                        'dob' => $validated['representative_dob'],
                        'email' => strtolower(trim($validated['representative_email'])),
                        'mobile' => preg_replace('/[^0-9]/', '', $validated['representative_mobile']),
                    ],
                    'gstin' => $gstin,
                    'location' => [
                        'id' => $location->id,
                        'name' => $location->name,
                        'state' => $location->state,
                        'node_type' => $location->node_type,
                        'switch_details' => $location->switch_details,
                        'nodal_officer' => $location->nodal_officer,
                    ],
                    'port_selection' => [
                        'capacity' => $validated['port_capacity'],
                        'billing_plan' => $validated['billing_plan'],
                        'amount' => $payableAmount,
                        'currency' => $pricing->currency,
                    ],
                    'ip_prefix' => [
                        'count' => $validated['ip_prefix_count'],
                        'source' => $validated['ip_prefix_source'],
                        'provider' => $validated['ip_prefix_provider'] ?? null,
                    ],
                    'peering' => [
                        'pre_nixi_connectivity' => $validated['pre_peering_connectivity'],
                        'asn_number' => $validated['asn_number'] ?? null,
                    ],
                    'router_details' => [
                        'height_u' => $validated['router_height_u'] ?? null,
                        'make_model' => $validated['router_make_model'] ?? null,
                        'serial_number' => $validated['router_serial_number'] ?? null,
                    ],
                    'documents' => $storedDocuments,
                ];

                $applicationData['gst_verification_id'] = $gstVerification->id;

                // Get application pricing
                $applicationPricing = IxApplicationPricing::getActive();
                $applicationFee = $applicationPricing ? (float) $applicationPricing->total_amount : 1000.00;

                $applicationData['payment'] = [
                    'status' => 'pending',
                    'plan' => $validated['billing_plan'],
                    'amount' => $applicationFee,
                    'application_fee' => $applicationPricing ? (float) $applicationPricing->application_fee : 1000.00,
                    'gst_percentage' => $applicationPricing ? (float) $applicationPricing->gst_percentage : 18.00,
                    'total_amount' => $applicationFee,
                    'currency' => 'INR',
                    'declaration_confirmed_at' => now('Asia/Kolkata')->toDateTimeString(),
                ];

                // Create application
                $application = Application::create([
                    'user_id' => $registration->id,
                    'pan_card_no' => $panNo,
                    'application_id' => Application::generateApplicationId(),
                    'application_type' => 'IX',
                    'status' => 'draft', // Will be submitted after payment
                    'application_data' => $applicationData,
                    'gst_verification_id' => $gstVerification->id,
                    'submitted_at' => null,
                ]);

                DB::commit();

                // Clear session
                session()->forget('pan_verification_data');

                // Return success with credentials
                return redirect()->route('admin.backend-data-entry.index')
                    ->with('success', 'User registered and application created successfully!')
                    ->with('credentials', [
                        'registration_id' => $registrationId,
                        'email' => $email,
                        'password' => $generatedPassword,
                        'email_otp' => $emailOtp,
                        'mobile_otp' => $mobileOtp,
                        'application_id' => $application->application_id,
                    ]);
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error in backend data entry transaction: '.$e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                throw $e;
            }
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            $sqlState = $e->errorInfo[0] ?? null;
            $sqlErrorCode = $e->errorInfo[1] ?? null;
            $sqlErrorMessage = $e->errorInfo[2] ?? null;

            Log::error('Database QueryException in backend data entry', [
                'message' => $errorMessage,
                'code' => $errorCode,
                'sql_state' => $sqlState,
                'sql_error_code' => $sqlErrorCode,
                'sql_error_message' => $sqlErrorMessage,
                'sql' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Check if it's actually a connection error
            $isConnectionError = str_contains($errorMessage, 'Connection') ||
                                 str_contains($errorMessage, 'No connection could be made') ||
                                 in_array($sqlErrorCode, ['2002', '1045', '2006', 'HY000']) ||
                                 str_contains($sqlState ?? '', 'HY000');

            if ($isConnectionError) {
                return back()->with('error', 'Database connection error. Please try again later.')
                    ->withInput();
            }

            // For other database errors, show a more helpful message
            return back()->with('error', 'Database error occurred. Please check the logs for details or contact support.')
                ->withInput();
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();

            Log::error('PDO Exception in backend data entry', [
                'message' => $errorMessage,
                'code' => $errorCode,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $isConnectionError = str_contains($errorMessage, 'Connection') ||
                                 str_contains($errorMessage, 'No connection could be made') ||
                                 str_contains($errorMessage, 'SQLSTATE[HY000]');

            if ($isConnectionError) {
                return back()->with('error', 'Database connection error. Please try again later.')
                    ->withInput();
            }

            return back()->with('error', 'Database error occurred. Please check the logs for details or contact support.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('General Exception in backend data entry', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with('error', 'An error occurred: '.$e->getMessage().'. Please try again or contact support.')
                ->withInput();
        }
    }

    /**
     * Generate a random password.
     */
    private function generateRandomPassword(): string
    {
        $length = 12;
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        $all = $uppercase.$lowercase.$numbers.$special;

        $password = '';
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $special[rand(0, strlen($special) - 1)];

        for ($i = 4; $i < $length; $i++) {
            $password .= $all[rand(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }
}
