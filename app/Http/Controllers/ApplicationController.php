<?php

namespace App\Http\Controllers;

use App\Mail\ApplicationInvoiceMail;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\IpPricing;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    /**
     * Display user's applications page.
     */
    public function index()
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Check if user status is approved
            if ($user->status !== 'approved' && $user->status !== 'active') {
                return redirect()->route('user.dashboard')
                    ->with('error', 'Your account must be approved to access applications.');
            }

            // Get user's applications with status history (all applications visible, is_active shows live status)
            $applications = Application::with(['statusHistory'])
                ->where('user_id', $userId)
                ->latest()
                ->paginate(10);

            // Check if user has any submitted IX application
            $hasSubmittedIxApplication = Application::where('user_id', $userId)
                ->where('application_type', 'IX')
                ->whereIn('status', ['submitted', 'approved', 'payment_verified', 'processor_forwarded_legal', 'legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending'])
                ->exists();
            
            // Get pending invoices for each application to show Pay Now buttons
            $applicationIds = $applications->pluck('id')->toArray();
            $pendingInvoicesByApplication = \App\Models\Invoice::whereIn('application_id', $applicationIds)
                ->where('status', 'pending')
                ->with(['application'])
                ->get()
                ->groupBy('application_id');

            return response()->view('user.applications.index', compact('user', 'applications', 'hasSubmittedIxApplication', 'pendingInvoicesByApplication'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading applications page: '.$e->getMessage());

            return redirect()->route('user.dashboard')
                ->with('error', 'Unable to load applications. Please try again.');
        }
    }

    /**
     * Show application details for user.
     */
    public function show($id)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Check if user status is approved
            if ($user->status !== 'approved' && $user->status !== 'active') {
                return redirect()->route('user.dashboard')
                    ->with('error', 'Your account must be approved to access applications.');
            }

            // Get application with status history and verification relationships
            // is_active shows live status, not visibility
            $application = Application::with(['statusHistory', 'gstVerification', 'udyamVerification', 'mcaVerification', 'rocIecVerification'])
                ->where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            return response()->view('user.applications.show', compact('user', 'application'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading application details: '.$e->getMessage());

            return redirect()->route('user.applications.index')
                ->with('error', 'Application not found.');
        }
    }

    /**
     * Show IRINN application form.
     */
    public function createIrin()
    {
        abort(404, 'IRINN application workflow is temporarily unavailable.');
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Check if user status is approved
            if ($user->status !== 'approved' && $user->status !== 'active') {
                return redirect()->route('user.dashboard')
                    ->with('error', 'Your account must be approved to submit applications.');
            }

            return view('user.applications.irin.create', compact('user'));
        } catch (Exception $e) {
            Log::error('Error loading IRINN application form: '.$e->getMessage());

            return redirect()->route('user.applications.index')
                ->with('error', 'Unable to load application form. Please try again.');
        }
    }

    /**
     * Fetch GST details from API.
     */
    public function fetchGstDetails(Request $request)
    {
        try {
            $request->validate([
                'gstin' => 'required|string|max:15|min:15',
            ]);

            $gstin = $request->gstin;
            $payload = ['gstin' => $gstin];
            $url = Config::get('gstzen.api_url');
            $apiKey = Config::get('gstzen.api_key');

            // Also check env directly as fallback
            if (empty($apiKey)) {
                $apiKey = env('GSTZEN_API_KEY', 'f15da0e8-e4d0-11ed-b5ea-0242ac120002');
            }

            // Log request details for debugging (without exposing full API key)
            Log::info('GST API Request', [
                'gstin' => $gstin,
                'url' => $url,
                'has_api_key' => ! empty($apiKey),
                'api_key_length' => strlen($apiKey ?? ''),
                'api_key_preview' => ! empty($apiKey) ? substr($apiKey, 0, 8).'...' : 'empty',
            ]);

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            if ($apiKey) {
                $headers['Token'] = $apiKey;
            } else {
                Log::error('GST API Key is missing!');

                return response()->json([
                    'success' => false,
                    'message' => 'API configuration error. Please contact administrator.',
                ], 500);
            }

            $resp = Http::withHeaders($headers)
                ->asJson()
                ->timeout((int) Config::get('gstzen.timeout', 15))
                ->post($url, $payload);

            $status = $resp->status();
            $body = $resp->body();
            $decoded = json_decode($body, true);

            // Log response for debugging
            Log::info('GST API Response', [
                'status' => $status,
                'response' => $decoded,
                'raw_body' => substr($body, 0, 500), // First 500 chars of response
            ]);

            // Handle 403 Forbidden - Invalid credentials
            if ($status === 403) {
                Log::error('GST API Authentication Failed', [
                    'status' => $status,
                    'message' => $decoded['message'] ?? 'Invalid credentials',
                    'api_key_used' => ! empty($apiKey) ? substr($apiKey, 0, 8).'...' : 'empty',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'API authentication failed. The API key may be invalid or expired. Please contact administrator.',
                ], 403);
            }

            // Check if API response is valid
            if ($status === 200) {
                // Check if response has status field
                if (isset($decoded['status']) && $decoded['status'] == 1) {
                    // Check if GSTIN is valid
                    if (isset($decoded['valid']) && $decoded['valid'] === true && isset($decoded['company_details'])) {
                        return response()->json([
                            'success' => true,
                            'data' => $decoded,
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => isset($decoded['message']) ? $decoded['message'] : 'Invalid GSTIN number or GSTIN not found',
                        ], 400);
                    }
                } else {
                    // API returned status != 1
                    return response()->json([
                        'success' => false,
                        'message' => isset($decoded['message']) ? $decoded['message'] : 'API returned an error. Please check the GSTIN and try again.',
                    ], 400);
                }
            }

            // Non-200 status code
            return response()->json([
                'success' => false,
                'message' => 'API request failed with status code: '.$status.'. Response: '.substr($body, 0, 200),
            ], 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['gstin'] ?? ['Invalid GSTIN format']),
            ], 422);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('GST API Connection Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to GST API. Please check your internet connection and try again.',
            ], 500);
        } catch (Exception $e) {
            Log::error('GST API Error: '.$e->getMessage());
            Log::error('GST API Error Trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GST details: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify GSTIN using Idfy API.
     */
    public function verifyGst(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'gstin' => 'required|string|size:15|regex:/^[0-9A-Z]{15}$/',
            ]);

            $gstin = strtoupper($request->input('gstin'));

            $service = new \App\Services\IdfyVerificationService;
            $result = $service->verifyGst($gstin);

            // Create verification record
            $verification = \App\Models\GstVerification::create([
                'user_id' => $userId,
                'gstin' => $gstin,
                'request_id' => $result['request_id'],
                'status' => 'in_progress',
                'is_verified' => false,
            ]);

            return response()->json([
                'success' => true,
                'request_id' => $result['request_id'],
                'verification_id' => $verification->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['gstin'] ?? ['Invalid GSTIN format']),
            ], 422);
        } catch (Exception $e) {
            Log::error('GST Verification Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate GST verification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify UDYAM using Idfy API.
     */
    public function verifyUdyam(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'uam_number' => 'required|string',
            ]);

            $uamNumber = $request->input('uam_number');

            $service = new \App\Services\IdfyVerificationService;
            $result = $service->verifyUdyam($uamNumber);

            // Create verification record
            $verification = \App\Models\UdyamVerification::create([
                'user_id' => $userId,
                'uam_number' => $uamNumber,
                'request_id' => $result['request_id'],
                'status' => 'in_progress',
                'is_verified' => false,
            ]);

            return response()->json([
                'success' => true,
                'request_id' => $result['request_id'],
                'verification_id' => $verification->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['uam_number'] ?? ['Invalid UDYAM number']),
            ], 422);
        } catch (Exception $e) {
            Log::error('UDYAM Verification Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate UDYAM verification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify MCA using Idfy API.
     */
    public function verifyMca(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'cin' => 'required|string',
            ]);

            $cin = $request->input('cin');

            $service = new \App\Services\IdfyVerificationService;
            $result = $service->verifyMca($cin);

            // Create verification record
            $verification = \App\Models\McaVerification::create([
                'user_id' => $userId,
                'cin' => $cin,
                'request_id' => $result['request_id'],
                'status' => 'in_progress',
                'is_verified' => false,
            ]);

            return response()->json([
                'success' => true,
                'request_id' => $result['request_id'],
                'verification_id' => $verification->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['cin'] ?? ['Invalid CIN']),
            ], 422);
        } catch (Exception $e) {
            Log::error('MCA Verification Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate MCA verification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify ROC IEC using Idfy API.
     */
    public function verifyRocIec(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'import_export_code' => 'required|string',
            ]);

            $iec = $request->input('import_export_code');

            $service = new \App\Services\IdfyVerificationService;
            $result = $service->verifyRocIec($iec);

            // Create verification record
            $verification = \App\Models\RocIecVerification::create([
                'user_id' => $userId,
                'import_export_code' => $iec,
                'request_id' => $result['request_id'],
                'status' => 'in_progress',
                'is_verified' => false,
            ]);

            return response()->json([
                'success' => true,
                'request_id' => $result['request_id'],
                'verification_id' => $verification->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['import_export_code'] ?? ['Invalid Import Export Code']),
            ], 422);
        } catch (Exception $e) {
            Log::error('ROC IEC Verification Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate ROC IEC verification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check verification status.
     */
    public function checkVerificationStatus(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'type' => 'required|string|in:gstin,udyam,mca,rocIec',
                'request_id' => 'required|string',
            ]);

            $type = $request->input('type');
            $requestId = $request->input('request_id');

            $service = new \App\Services\IdfyVerificationService;
            $statusResult = $service->getTaskStatus($requestId);

            $status = $statusResult['status'];
            $result = $statusResult['result'] ?? null;
            $task = $statusResult['task'] ?? null;

            // Find verification record
            $verification = null;
            $isVerified = false;
            $errorMessage = null;
            $sourceOutput = null;

            if ($status === 'completed') {
                $sourceOutput = $result['source_output'] ?? null;

                if ($sourceOutput) {
                    // Determine verification success based on type
                    switch ($type) {
                        case 'gstin':
                            $verification = \App\Models\GstVerification::where('request_id', $requestId)->first();
                            if ($verification) {
                                $isVerified = ($sourceOutput['status'] ?? '') === 'id_found';
                                if ($isVerified) {
                                    // Extract GST data from the response structure
                                    $verification->legal_name = $sourceOutput['legal_name'] ?? null;
                                    $verification->trade_name = $sourceOutput['trade_name'] ?? null;

                                    // Extract PAN from GSTIN (first 10 characters)
                                    $gstin = $sourceOutput['gstin'] ?? null;
                                    if ($gstin && strlen($gstin) >= 10) {
                                        $verification->pan = substr($gstin, 2, 10);
                                    }

                                    // Extract state from address
                                    $address = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? null;
                                    if ($address) {
                                        $verification->state = $address['state_name'] ?? null;
                                        // Build primary address
                                        $addressParts = array_filter([
                                            $address['door_number'] ?? null,
                                            $address['building_name'] ?? null,
                                            $address['street'] ?? null,
                                            $address['location'] ?? null,
                                            $address['city'] ?? null,
                                            $address['dst'] ?? null,
                                        ]);
                                        $verification->primary_address = implode(', ', $addressParts);
                                    }

                                    $verification->registration_date = isset($sourceOutput['date_of_registration']) ? date('Y-m-d', strtotime($sourceOutput['date_of_registration'])) : null;
                                    $verification->gst_type = $sourceOutput['taxpayer_type'] ?? null;
                                    $verification->company_status = $sourceOutput['gstin_status'] ?? null;
                                    $verification->constitution_of_business = $sourceOutput['constitution_of_business'] ?? null;
                                } else {
                                    $errorMessage = $sourceOutput['message'] ?? 'GSTIN verification failed';
                                }
                            }
                            break;
                        case 'udyam':
                            $verification = \App\Models\UdyamVerification::where('request_id', $requestId)->first();
                            if ($verification) {
                                $isVerified = ($sourceOutput['status'] ?? '') === 'id_found';
                                if (! $isVerified) {
                                    $errorMessage = $sourceOutput['message'] ?? 'UDYAM verification failed';
                                }
                            }
                            break;
                        case 'mca':
                            $verification = \App\Models\McaVerification::where('request_id', $requestId)->first();
                            if ($verification) {
                                $isVerified = ($sourceOutput['status'] ?? '') === 'id_found';
                                if (! $isVerified) {
                                    $errorMessage = $sourceOutput['message'] ?? 'MCA verification failed';
                                }
                            }
                            break;
                        case 'rocIec':
                            $verification = \App\Models\RocIecVerification::where('request_id', $requestId)->first();
                            if ($verification) {
                                $isVerified = ($sourceOutput['status'] ?? '') === 'id_found';
                                if (! $isVerified) {
                                    $errorMessage = $sourceOutput['message'] ?? 'ROC IEC verification failed';
                                }
                            }
                            break;
                    }

                    // Update verification record
                    if ($verification) {
                        $verification->status = $status;
                        $verification->is_verified = $isVerified;
                        $verification->verification_data = $task;
                        if ($errorMessage) {
                            $verification->error_message = $errorMessage;
                        }
                        $verification->save();
                    }
                }
            } elseif ($status === 'failed') {
                // Find and update verification record
                switch ($type) {
                    case 'gstin':
                        $verification = \App\Models\GstVerification::where('request_id', $requestId)->first();
                        break;
                    case 'udyam':
                        $verification = \App\Models\UdyamVerification::where('request_id', $requestId)->first();
                        break;
                    case 'mca':
                        $verification = \App\Models\McaVerification::where('request_id', $requestId)->first();
                        break;
                    case 'rocIec':
                        $verification = \App\Models\RocIecVerification::where('request_id', $requestId)->first();
                        break;
                }

                if ($verification) {
                    $verification->status = 'failed';
                    $verification->is_verified = false;
                    $verification->verification_data = $task;
                    $verification->error_message = 'Verification task failed';
                    $verification->save();
                }
                $errorMessage = 'Verification task failed';
            }

            // Prepare response with verification data for frontend
            $responseData = [
                'success' => true,
                'status' => $status,
                'is_verified' => $isVerified,
                'message' => $errorMessage,
            ];

            // Include verification data for GST to populate Step 2
            if ($type === 'gstin' && $isVerified && $verification && isset($sourceOutput)) {
                // Map constitution_of_business to affiliate_identity
                $affiliateIdentity = $this->mapConstitutionToAffiliateIdentity($verification->constitution_of_business);
                $affiliateIdentityDisplay = $this->getAffiliateIdentityDisplayName($affiliateIdentity);

                $responseData['verification_data'] = [
                    'gstin' => $verification->gstin,
                    'legal_name' => $verification->legal_name,
                    'trade_name' => $verification->trade_name,
                    'company_name' => $verification->legal_name ?? $verification->trade_name,
                    'pan' => $verification->pan,
                    'state' => $verification->state,
                    'registration_date' => $verification->registration_date?->format('Y-m-d'),
                    'gst_type' => $verification->gst_type,
                    'company_status' => $verification->company_status,
                    'primary_address' => $verification->primary_address,
                    'constitution_of_business' => $verification->constitution_of_business,
                    'affiliate_identity' => $affiliateIdentity,
                    'affiliate_identity_display' => $affiliateIdentityDisplay,
                    'source_output' => $sourceOutput,
                ];
            }

            // Include verification data for UDYAM to populate Step 2
            if ($type === 'udyam' && $isVerified && $verification && isset($sourceOutput)) {
                $officialAddress = $sourceOutput['official_address'] ?? null;
                $primaryAddress = null;

                if (is_array($officialAddress)) {
                    $addressParts = array_filter([
                        $officialAddress['door'] ?? null,
                        $officialAddress['name_of_premises'] ?? null,
                        $officialAddress['road'] ?? null,
                        $officialAddress['area'] ?? null,
                        $officialAddress['city'] ?? null,
                        $officialAddress['district'] ?? null,
                        $officialAddress['state'] ?? null,
                        $officialAddress['pin'] ?? null,
                    ]);
                    $primaryAddress = implode(', ', $addressParts);
                }

                $companyName = $sourceOutput['general_details']['enterprise_name'] ?? null;

                $responseData['verification_data'] = [
                    'uam_number' => $verification->uam_number,
                    'company_name' => $companyName,
                    'primary_address' => $primaryAddress,
                    'source_output' => $sourceOutput,
                ];
            }

            // Include verification data for MCA CIN to populate Step 2
            if ($type === 'mca' && $isVerified && $verification && isset($sourceOutput)) {
                $companyName = $sourceOutput['company_name'] ?? null;
                $registeredAddress = $sourceOutput['registered_address'] ?? null;

                $responseData['verification_data'] = [
                    'cin' => $verification->cin,
                    'company_name' => $companyName,
                    'primary_address' => $registeredAddress,
                    'source_output' => $sourceOutput,
                ];
            }

            return response()->json($responseData);
        } catch (Exception $e) {
            Log::error('Check Verification Status Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check verification status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store IRINN application.
     */
    public function storeIrin(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            // Check if user status is approved
            if ($user->status !== 'approved' && $user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account must be approved to submit applications.',
                ], 403);
            }

            // Base validation rules
            $rules = [
                'gstin' => 'nullable|string',
                'udyam_number' => 'nullable|string',
                'mca_cin' => 'nullable|string',
                'roc_iec' => 'nullable|string',
                'industry_type' => 'required|string',
                'applicant_name' => 'required|string',
                'applicant_email' => 'required|email',
                'applicant_designation' => 'required|string',
                'applicant_mobile' => 'required|string',
                // Keep MR fields for backward compatibility
                'mr_name' => 'nullable|string',
                'mr_email' => 'nullable|email',
                'mr_designation' => 'nullable|string',
                'mr_mobile' => 'nullable|string',
                'account_name' => 'required|string',
                'dot_in_domain_required' => 'required',
                'billing_affiliate_name' => 'required|string',
                'billing_email' => 'required|email',
                'billing_address' => 'required|string',
                'billing_state' => 'required|string',
                'billing_city' => 'required|string',
                'billing_mobile' => 'required|string',
                'billing_postal_code' => 'required|string',
                'ipv4_selected' => 'nullable',
                'ipv4_size' => 'nullable|string',
                'ipv6_selected' => 'nullable',
                'ipv6_size' => 'nullable|string',
                'affiliate_identity' => 'required|string',
                'nature_of_business' => 'required|string',
                'as_number_required' => 'required',
                'network_plan_file' => 'required|file|mimes:pdf|max:10240',
                'payment_receipts_file' => 'required|file|mimes:pdf|max:10240',
                'equipment_details_file' => 'required|file|mimes:pdf|max:10240',
                // Common KYC files (always required)
                'kyc_business_address_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_authorization_doc' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_signature_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_gst_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                // KYC files - conditional based on affiliate_identity (all nullable by default)
                'kyc_partnership_deed' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_partnership_entity_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_incorporation_cert' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_company_pan_gstin' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_udyam_cert' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_sole_proprietorship_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_establishment_reg' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_school_pan_gstin' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_rbi_license' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'kyc_bank_pan_gstin' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                // ASN fields
                'company_asn' => 'nullable|string',
                'isp_company_name' => 'nullable|string',
                'upstream_name' => 'nullable|string',
                'upstream_mobile' => 'nullable|string',
                'upstream_email' => 'nullable|email',
                'upstream_asn' => 'nullable|string',
            ];

            // Add conditional validation based on affiliate_identity
            $affiliateIdentity = $request->affiliate_identity;
            if ($affiliateIdentity === 'partnership') {
                $rules['kyc_partnership_deed'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
                $rules['kyc_partnership_entity_doc'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            } elseif ($affiliateIdentity === 'pvt_ltd') {
                $rules['kyc_incorporation_cert'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
                $rules['kyc_company_pan_gstin'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            } elseif ($affiliateIdentity === 'sole_proprietorship') {
                $rules['kyc_sole_proprietorship_doc'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
                // kyc_udyam_cert is optional
            } elseif ($affiliateIdentity === 'schools_colleges') {
                $rules['kyc_establishment_reg'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
                $rules['kyc_school_pan_gstin'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            } elseif ($affiliateIdentity === 'banks') {
                $rules['kyc_rbi_license'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
                $rules['kyc_bank_pan_gstin'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            }

            $validated = $request->validate($rules);

            // Handle file uploads
            $filePaths = [];
            $fileFields = [
                'network_plan_file',
                'payment_receipts_file',
                'equipment_details_file',
            ];

            // Add KYC document fields
            $kycFileFields = [
                'kyc_partnership_deed',
                'kyc_partnership_entity_doc',
                'kyc_incorporation_cert',
                'kyc_company_pan_gstin',
                'kyc_udyam_cert',
                'kyc_sole_proprietorship_doc',
                'kyc_establishment_reg',
                'kyc_school_pan_gstin',
                'kyc_rbi_license',
                'kyc_bank_pan_gstin',
                'kyc_business_address_proof',
                'kyc_authorization_doc',
                'kyc_signature_proof',
                'kyc_gst_certificate',
            ];

            $allFileFields = array_merge($fileFields, $kycFileFields);

            foreach ($allFileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $path = $file->store('applications/'.$userId.'/irin', 'public');
                    $filePaths[$field] = $path;
                }
            }

            // Calculate fees using maximum amount and GST from backend
            $effectivePricing = IpPricing::getCurrentlyEffective();
            $selectedAmounts = [];
            $maxGstPercentage = 0;
            $ipv4Fee = 0;
            $ipv6Fee = 0;

            if ($request->has('ipv4_selected') && $request->ipv4_size) {
                $pricing = $effectivePricing['ipv4'][$request->ipv4_size] ?? null;
                if ($pricing) {
                    $ipv4Fee = $pricing['price'];
                    $amount = $pricing['amount'] ?? $pricing['price'];
                    $selectedAmounts[] = $amount;
                    if ($pricing['gst_percentage'] > $maxGstPercentage) {
                        $maxGstPercentage = $pricing['gst_percentage'];
                    }
                }
            }

            if ($request->has('ipv6_selected') && $request->ipv6_size) {
                $pricing = $effectivePricing['ipv6'][$request->ipv6_size] ?? null;
                if ($pricing) {
                    $ipv6Fee = $pricing['price'];
                    $amount = $pricing['amount'] ?? $pricing['price'];
                    $selectedAmounts[] = $amount;
                    if ($pricing['gst_percentage'] > $maxGstPercentage) {
                        $maxGstPercentage = $pricing['gst_percentage'];
                    }
                }
            }

            // Calculate maximum amount (not sum)
            $maxAmount = ! empty($selectedAmounts) ? max($selectedAmounts) : 0;

            // Calculate GST on maximum amount
            $gstAmount = $maxAmount * ($maxGstPercentage / 100);
            $totalFee = $maxAmount + $gstAmount;

            // Store fee breakdown for invoice
            $applicationData['max_amount'] = $maxAmount;
            $applicationData['gst_percentage'] = $maxGstPercentage;
            $applicationData['gst_amount'] = $gstAmount;

            // Get PAN from user registration
            $panCardNo = $user->pancardno;

            // Get verification IDs from verified documents
            $gstVerificationId = null;
            $udyamVerificationId = null;
            $mcaVerificationId = null;
            $rocIecVerificationId = null;

            if ($request->has('gstin_verified') && $request->gstin_verified == '1' && $request->gstin) {
                $gstVerification = \App\Models\GstVerification::where('user_id', $userId)
                    ->where('gstin', strtoupper($request->gstin))
                    ->where('is_verified', true)
                    ->latest()
                    ->first();
                if ($gstVerification) {
                    $gstVerificationId = $gstVerification->id;
                }
            }

            if ($request->has('udyam_verified') && $request->udyam_verified == '1' && $request->udyam_number) {
                $udyamVerification = \App\Models\UdyamVerification::where('user_id', $userId)
                    ->where('uam_number', $request->udyam_number)
                    ->where('is_verified', true)
                    ->latest()
                    ->first();
                if ($udyamVerification) {
                    $udyamVerificationId = $udyamVerification->id;
                }
            }

            if ($request->has('mca_verified') && $request->mca_verified == '1' && $request->mca_cin) {
                $mcaVerification = \App\Models\McaVerification::where('user_id', $userId)
                    ->where('cin', $request->mca_cin)
                    ->where('is_verified', true)
                    ->latest()
                    ->first();
                if ($mcaVerification) {
                    $mcaVerificationId = $mcaVerification->id;
                }
            }

            if ($request->has('roc_iec_verified') && $request->roc_iec_verified == '1' && $request->roc_iec) {
                $rocIecVerification = \App\Models\RocIecVerification::where('user_id', $userId)
                    ->where('import_export_code', $request->roc_iec)
                    ->where('is_verified', true)
                    ->latest()
                    ->first();
                if ($rocIecVerification) {
                    $rocIecVerificationId = $rocIecVerification->id;
                }
            }

            // Prepare application data
            $applicationData = [
                'gstin' => $request->gstin,
                'udyam_number' => $request->udyam_number ?? null,
                'mca_cin' => $request->mca_cin ?? null,
                'roc_iec' => $request->roc_iec ?? null,
                'industry_type' => $request->industry_type,
                // Use applicant fields, fallback to MR fields for backward compatibility
                'mr_name' => $request->applicant_name ?? $request->mr_name,
                'mr_email' => $request->applicant_email ?? $request->mr_email,
                'mr_designation' => $request->applicant_designation ?? $request->mr_designation,
                'mr_mobile' => $request->applicant_mobile ?? $request->mr_mobile,
                'account_name' => $request->account_name,
                'dot_in_domain_required' => $request->dot_in_domain_required == '1',
                'billing_affiliate_name' => $request->billing_affiliate_name,
                'billing_email' => $request->billing_email,
                'billing_address' => $request->billing_address,
                'billing_state' => $request->billing_state,
                'billing_city' => $request->billing_city,
                'billing_mobile' => $request->billing_mobile,
                'billing_postal_code' => $request->billing_postal_code,
                'ipv4_selected' => $request->has('ipv4_selected'),
                'ipv4_size' => $request->ipv4_size,
                'ipv6_selected' => $request->has('ipv6_selected'),
                'ipv6_size' => $request->ipv6_size,
                'ipv4_fee' => $ipv4Fee,
                'ipv6_fee' => $ipv6Fee,
                'total_fee' => $totalFee,
                'nature_of_business' => $request->nature_of_business,
                'as_number_required' => $request->as_number_required == '1',
                'affiliate_identity' => $request->affiliate_identity ?? '',
                'company_asn' => $request->company_asn ?? '',
                'isp_company_name' => $request->isp_company_name ?? '',
                'upstream_name' => $request->upstream_name ?? '',
                'upstream_mobile' => $request->upstream_mobile ?? '',
                'upstream_email' => $request->upstream_email ?? '',
                'upstream_asn' => $request->upstream_asn ?? '',
                'files' => $filePaths,
            ];

            // Add GST data if provided (from session or request)
            $gstData = session('gst_data');
            if (! $gstData && $request->has('gst_data')) {
                $gstData = is_string($request->gst_data) ? json_decode($request->gst_data, true) : $request->gst_data;
            }
            if ($gstData && is_array($gstData)) {
                $applicationData['gst_data'] = $gstData;
            }

            // Create application
            $application = Application::create([
                'user_id' => $userId,
                'pan_card_no' => $panCardNo, // Link via PAN
                'application_id' => Application::generateApplicationId(),
                'application_type' => 'IRINN',
                'status' => 'pending',
                'application_data' => $applicationData,
                'gst_verification_id' => $gstVerificationId,
                'udyam_verification_id' => $udyamVerificationId,
                'mca_verification_id' => $mcaVerificationId,
                'roc_iec_verification_id' => $rocIecVerificationId,
                'submitted_at' => now('Asia/Kolkata'),
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                null,
                'pending',
                'user',
                $userId,
                'IRINN application submitted'
            );

            // Generate PDFs
            try {
                $applicationPdf = $this->generateApplicationPdf($application);
                $invoicePdf = $this->generateInvoicePdf($application);

                // Store PDFs
                $applicationPdfPath = 'applications/'.$userId.'/irin/'.$application->application_id.'_application.pdf';
                $invoicePdfPath = 'applications/'.$userId.'/irin/'.$application->application_id.'_invoice.pdf';

                Storage::disk('public')->put($applicationPdfPath, $applicationPdf->output());
                Storage::disk('public')->put($invoicePdfPath, $invoicePdf->output());

                // Update application data with PDF paths
                $applicationData['pdfs'] = [
                    'application_pdf' => $applicationPdfPath,
                    'invoice_pdf' => $invoicePdfPath,
                ];
                $application->update(['application_data' => $applicationData]);

                // Send invoice email to user
                try {
                    $invoiceNumber = 'NXNIR'.date('y').'-'.(date('y') + 1).'/'.str_pad($application->id, 4, '0', STR_PAD_LEFT);
                    // total_fee already includes GST
                    $totalAmount = round($applicationData['total_fee'] ?? 0);

                    Mail::to($user->email)->send(new ApplicationInvoiceMail(
                        $user->fullname,
                        $application->application_id,
                        $invoiceNumber,
                        $totalAmount,
                        $application->status,
                        $invoicePdfPath
                    ));
                    Log::info("Invoice email sent to {$user->email} for application {$application->application_id}");
                } catch (Exception $e) {
                    Log::error('Invoice Email Error: '.$e->getMessage());
                    // Continue even if email sending fails
                }
            } catch (Exception $e) {
                Log::error('PDF Generation Error: '.$e->getMessage());
                // Continue even if PDF generation fails
            }

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully',
                'application' => $application,
                'application_id' => $application->id,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('IRINN Application Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Application submission failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Application Details PDF.
     */
    private function generateApplicationPdf(Application $application)
    {
        $data = $application->application_data;
        $user = $application->user;

        // Get company details from GST verification if available
        $gstVerification = $application->gstVerification;
        $companyDetails = [];
        if ($gstVerification) {
            $companyDetails = [
                'legal_name' => $gstVerification->legal_name,
                'trade_name' => $gstVerification->trade_name,
                'pan' => $gstVerification->pan,
                'state' => $gstVerification->state,
                'registration_date' => $gstVerification->registration_date?->format('d/m/Y'),
                'gst_type' => $gstVerification->gst_type,
                'company_status' => $gstVerification->company_status,
                'primary_address' => $gstVerification->primary_address,
            ];

            // Parse primary address if it's a JSON string
            if ($gstVerification->verification_data) {
                $verificationData = is_string($gstVerification->verification_data)
                    ? json_decode($gstVerification->verification_data, true)
                    : $gstVerification->verification_data;

                if (isset($verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'])) {
                    $address = $verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'];
                    $companyDetails['pradr'] = [
                        'addr' => trim(($address['door_number'] ?? '').' '.($address['building_name'] ?? '').' '.($address['street'] ?? '').' '.($address['location'] ?? '').' '.($address['dst'] ?? '').' '.($address['city'] ?? '').' '.($address['state_name'] ?? '').' '.($address['pincode'] ?? '')),
                    ];
                }
            }
        }

        // Convert PDF documents to images if possible
        $pdfImages = [];
        if (isset($data['files']) && extension_loaded('imagick')) {
            foreach ($data['files'] as $field => $path) {
                $fullPath = storage_path('app/public/'.$path);
                if (file_exists($fullPath) && strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) === 'pdf') {
                    try {
                        $imagick = new \Imagick;
                        $imagick->setResolution(150, 150);
                        $imagick->readImage($fullPath.'[0]'); // Read first page
                        $imagick->setImageFormat('png');
                        $imagick->setImageCompressionQuality(90);
                        $pdfImages[$field] = base64_encode($imagick->getImageBlob());
                        $imagick->clear();
                        $imagick->destroy();
                    } catch (\Exception $e) {
                        // If conversion fails, leave it null
                        $pdfImages[$field] = null;
                    }
                }
            }
        }

        $pdf = Pdf::loadView('user.applications.irin.pdf.application', [
            'application' => $application,
            'user' => $user,
            'data' => $data,
            'companyDetails' => $companyDetails,
            'pdfImages' => $pdfImages,
        ])->setOption('enable-local-file-access', true);

        return $pdf;
    }

    /**
     * Generate Invoice PDF.
     */
    private function generateInvoicePdf(Application $application)
    {
        $data = $application->application_data;
        $user = $application->user;

        // Get company details from GST verification if available
        $gstVerification = $application->gstVerification;
        $companyDetails = [];
        if ($gstVerification) {
            $companyDetails = [
                'legal_name' => $gstVerification->legal_name,
                'trade_name' => $gstVerification->trade_name,
                'pan' => $gstVerification->pan,
                'state' => $gstVerification->state,
                'registration_date' => $gstVerification->registration_date?->format('d/m/Y'),
                'gst_type' => $gstVerification->gst_type,
                'company_status' => $gstVerification->company_status,
                'primary_address' => $gstVerification->primary_address,
            ];

            // Parse primary address if it's a JSON string
            if ($gstVerification->verification_data) {
                $verificationData = is_string($gstVerification->verification_data)
                    ? json_decode($gstVerification->verification_data, true)
                    : $gstVerification->verification_data;

                if (isset($verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'])) {
                    $address = $verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'];
                    $companyDetails['pradr'] = [
                        'addr' => trim(($address['door_number'] ?? '').' '.($address['building_name'] ?? '').' '.($address['street'] ?? '').' '.($address['location'] ?? '').' '.($address['dst'] ?? '').' '.($address['city'] ?? '').' '.($address['state_name'] ?? '').' '.($address['pincode'] ?? '')),
                        'state_name' => $address['state_name'] ?? null,
                    ];
                    $companyDetails['state_info'] = [
                        'name' => $address['state_name'] ?? $gstVerification->state,
                    ];
                }
            }
        }

        // Calculate invoice number (format: NXNIR25-26/XXXX)
        $invoiceNumber = 'NXNIR'.date('y').'-'.(date('y') + 1).'/'.str_pad($application->id, 4, '0', STR_PAD_LEFT);

        $pdf = Pdf::loadView('user.applications.irin.pdf.invoice', [
            'application' => $application,
            'user' => $user,
            'data' => $data,
            'companyDetails' => $companyDetails,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDate' => now('Asia/Kolkata')->format('d/m/Y'),
            'dueDate' => now('Asia/Kolkata')->addDays(28)->format('d/m/Y'),
        ])->setPaper('a4', 'portrait')
            ->setOption('margin-top', 6)
            ->setOption('margin-bottom', 6)
            ->setOption('margin-left', 6)
            ->setOption('margin-right', 6)
            ->setOption('enable-local-file-access', true);

        return $pdf;
    }

    /**
     * Calculate IPv4 fee.
     */
    private function calculateIPv4Fee($size)
    {
        $pricing = IpPricing::getPricing('ipv4', $size);

        if ($pricing) {
            return $pricing->calculateFee();
        }

        // Fallback to old calculation if pricing not found
        $addresses = $size === '/24' ? 256 : 512;

        return 27500 * pow(1.35, log($addresses, 2) - 8);
    }

    /**
     * Download Application PDF.
     */
    public function downloadApplicationPdf($id)
    {
        try {
            $userId = session('user_id');
            $application = Application::with('gstVerification')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Redirect to IX-specific route if it's an IX application
            if ($application->application_type === 'IX') {
                return redirect()->route('user.applications.ix.download-application-pdf', $id);
            }

            $applicationPdf = $this->generateApplicationPdf($application);

            return $applicationPdf->download($application->application_id.'_application.pdf');
        } catch (Exception $e) {
            Log::error('Error downloading application PDF: '.$e->getMessage());

            return redirect()->route('user.applications.show', $id)
                ->with('error', 'Unable to download application PDF.');
        }
    }

    /**
     * Download Invoice PDF.
     */
    public function downloadInvoicePdf($id)
    {
        try {
            $userId = session('user_id');
            $application = Application::with('gstVerification')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $invoicePdf = $this->generateInvoicePdf($application);

            return $invoicePdf->download($application->application_id.'_invoice.pdf');
        } catch (Exception $e) {
            Log::error('Error downloading invoice PDF: '.$e->getMessage());

            return redirect()->route('user.applications.show', $id)
                ->with('error', 'Unable to download invoice PDF.');
        }
    }

    /**
     * Serve application document securely.
     */
    public function serveDocument($id, Request $request)
    {
        try {
            $userId = session('user_id');
            $documentKey = $request->input('doc');
            
            if (!$documentKey) {
                abort(400, 'Document key is required.');
            }

            $application = Application::where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $applicationData = $application->application_data ?? [];
            $documents = $applicationData['documents'] ?? [];
            $pdfs = $applicationData['pdfs'] ?? [];
            
            // Check if it's a PDF or a document
            $filePath = null;
            if (isset($pdfs[$documentKey])) {
                $filePath = $pdfs[$documentKey];
            } elseif (isset($documents[$documentKey])) {
                $filePath = $documents[$documentKey];
            }
            
            if (!$filePath) {
                abort(404, 'Document not found.');
            }
            
            if (!Storage::disk('public')->exists($filePath)) {
                abort(404, 'File not found on server.');
            }

            $fullPath = Storage::disk('public')->path($filePath);
            $fileName = basename($filePath);
            
            return response()->file($fullPath, [
                'Content-Type' => Storage::disk('public')->mimeType($filePath),
                'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Application not found.');
        } catch (Exception $e) {
            Log::error('Error serving document: '.$e->getMessage());
            abort(500, 'Unable to serve document.');
        }
    }

    /**
     * Calculate IPv6 fee.
     */
    private function calculateIPv6Fee($size)
    {
        $pricing = IpPricing::getPricing('ipv6', $size);

        if ($pricing) {
            return $pricing->calculateFee();
        }

        // Fallback to old calculation if pricing not found
        if ($size === '/48') {
            return 24199;
        } elseif ($size === '/32') {
            $addresses = 16777216;
            $log2Value = log($addresses, 2);

            return 24199 * pow(1.35, $log2Value - 22);
        }

        return 0;
    }

    /**
     * Get IP pricing for frontend (API endpoint).
     * Returns only currently effective pricing based on effective dates.
     */
    public function getIpPricing()
    {
        try {
            $pricings = IpPricing::getCurrentlyEffective();

            return response()->json([
                'success' => true,
                'data' => $pricings,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching IP pricing: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pricing.',
            ], 500);
        }
    }

    /**
     * Map GST constitution_of_business to affiliate_identity.
     */
    private function mapConstitutionToAffiliateIdentity(?string $constitution): string
    {
        if (empty($constitution)) {
            return 'sole_proprietorship'; // Default fallback
        }

        $constitution = strtolower(trim($constitution));

        // Map various GST constitution types to affiliate_identity
        if (str_contains($constitution, 'proprietorship') || str_contains($constitution, 'proprietor')) {
            return 'sole_proprietorship';
        }

        if (str_contains($constitution, 'partnership')) {
            return 'partnership';
        }

        if (str_contains($constitution, 'private') || str_contains($constitution, 'pvt') ||
            str_contains($constitution, 'limited') || str_contains($constitution, 'ltd') ||
            str_contains($constitution, 'public') || str_contains($constitution, 'psu') ||
            str_contains($constitution, 'company') || str_contains($constitution, 'corporation')) {
            return 'pvt_ltd';
        }

        if (str_contains($constitution, 'school') || str_contains($constitution, 'college') ||
            str_contains($constitution, 'education') || str_contains($constitution, 'institution')) {
            return 'schools_colleges';
        }

        if (str_contains($constitution, 'bank') || str_contains($constitution, 'financial')) {
            return 'banks';
        }

        // Default fallback
        return 'sole_proprietorship';
    }

    /**
     * Get display name for affiliate_identity.
     */
    private function getAffiliateIdentityDisplayName(string $affiliateIdentity): string
    {
        $displayNames = [
            'partnership' => 'Partnership Firms',
            'pvt_ltd' => 'Pvt Ltd Co./Ltd Co. and PSU Company',
            'sole_proprietorship' => 'Sole Proprietorship',
            'schools_colleges' => 'Schools, College establishments',
            'banks' => 'Private and Nationalised Bank',
        ];

        return $displayNames[$affiliateIdentity] ?? 'Sole Proprietorship';
    }
}
