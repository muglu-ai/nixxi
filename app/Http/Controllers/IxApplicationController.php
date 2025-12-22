<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIxApplicationRequest;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\GstVerification;
use App\Models\Invoice;
use App\Models\IxApplicationPricing;
use App\Models\IxLocation;
use App\Models\IxPortPricing;
use App\Models\Message;
use App\Models\PaymentTransaction;
use App\Models\PaymentVerificationLog;
use App\Models\Registration;
use App\Models\UserKycProfile;
use App\Services\PayuService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class IxApplicationController extends Controller
{
    /**
     * Extract all PayU response fields from request.
     * This captures all parameters sent by PayU in their callback.
     */
    protected function extractPayuResponseFields(Request $request): array
    {
        // PayU may send data via POST or GET (query string)
        $response = array_merge($request->query(), $request->post());
        
        // Extract all PayU response fields
        $payuFields = [
            // Payment identifiers
            'mihpayid' => $request->input('mihpayid') ?? $request->input('payuMoneyId') ?? $request->input('payuid'),
            'txnid' => $request->input('txnid'),
            'key' => $request->input('key'),
            
            // Payment status
            'status' => $request->input('status'),
            'unmappedstatus' => $request->input('unmappedstatus'),
            
            // Payment details
            'amount' => $request->input('amount'),
            'productinfo' => $request->input('productinfo'),
            'firstname' => $request->input('firstname'),
            'lastname' => $request->input('lastname'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            
            // Payment method details
            'mode' => $request->input('mode'), // CC, DC, NB, UPI, etc.
            'bankcode' => $request->input('bankcode'),
            'bank_ref_num' => $request->input('bank_ref_num'),
            'pg_type' => $request->input('pg_type'),
            'cardnum' => $request->input('cardnum'), // Masked card number
            'name_on_card' => $request->input('name_on_card'),
            'card_type' => $request->input('card_type'),
            'issuing_bank' => $request->input('issuing_bank'),
            'card_category' => $request->input('card_category'),
            
            // Error details (for failed payments)
            'error' => $request->input('error'),
            'error_code' => $request->input('error_code'),
            'error_Message' => $request->input('error_Message') ?? $request->input('error_message'),
            
            // Additional fields
            'udf1' => $request->input('udf1'),
            'udf2' => $request->input('udf2'),
            'udf3' => $request->input('udf3'),
            'udf4' => $request->input('udf4'),
            'udf5' => $request->input('udf5'),
            'hash' => $request->input('hash'),
            'field1' => $request->input('field1'),
            'field2' => $request->input('field2'),
            'field3' => $request->input('field3'),
            'field4' => $request->input('field4'),
            'field5' => $request->input('field5'),
            'field6' => $request->input('field6'),
            'field7' => $request->input('field7'),
            'field8' => $request->input('field8'),
            'field9' => $request->input('field9'),
            
            // Additional payment gateway fields
            'discount' => $request->input('discount'),
            'net_amount_debit' => $request->input('net_amount_debit'),
            'addedon' => $request->input('addedon'),
            'payment_source' => $request->input('payment_source'),
            'card_token' => $request->input('card_token'),
            'offer_key' => $request->input('offer_key'),
            'offer_type' => $request->input('offer_type'),
            'offer_availed' => $request->input('offer_available'),
            'failure_reason' => $request->input('failure_reason'),
            'retry' => $request->input('retry'),
        ];
        
        // Remove null values to keep response clean
        $payuFields = array_filter($payuFields, function ($value) {
            return $value !== null && $value !== '';
        });
        
        // Also include the complete raw response for reference
        $payuFields['raw_response'] = $response;
        
        return $payuFields;
    }

    /**
     * Show IX application wizard.
     */
    public function create(): View|RedirectResponse
    {
        $userId = session('user_id');
        $user = Registration::find($userId);

        if (! $user) {
            return redirect()->route('login.index')
                ->with('error', 'User session expired. Please login again.');
        }

        // Clean up old IX drafts (older than 15 days)
        $deletedDraftsCount = Application::where('user_id', $userId)
            ->where('application_type', 'IX')
            ->where('status', 'draft')
            ->where('updated_at', '<', now()->subDays(15))
            ->delete();

        if ($deletedDraftsCount > 0) {
            session()->flash('info', 'We removed '.$deletedDraftsCount.' IX draft application(s) older than 15 days. You can start a fresh application below.');
        }

        if (! in_array($user->status, ['approved', 'active'], true)) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Your account must be approved before submitting an IX application.');
        }

        $gstVerification = GstVerification::where('user_id', $userId)
            ->where('is_verified', true)
            ->latest()
            ->first();

        $kycProfile = UserKycProfile::where('user_id', $userId)
            ->latest()
            ->first();

        $locations = IxLocation::active()
            ->orderBy('node_type')
            ->orderBy('state')
            ->orderBy('name')
            ->get();

        $portPricings = IxPortPricing::active()
            ->orderBy('node_type')
            ->orderBy('display_order')
            ->orderBy('port_capacity')
            ->get()
            ->groupBy('node_type');

        // Get active application pricing from database
        $applicationPricing = IxApplicationPricing::getActive();
        if (! $applicationPricing) {
            // Fallback to default if no pricing is set
            $applicationPricing = (object) [
                'application_fee' => 1000.00,
                'gst_percentage' => 18.00,
                'total_amount' => 1180.00,
            ];
        }

        return view('user.applications.ix.create', [
            'user' => $user,
            'gstVerification' => $gstVerification,
            'gstState' => $gstVerification?->state,
            'kycProfile' => $kycProfile,
            'locations' => $locations,
            'portPricings' => $portPricings,
            'applicationPricing' => $applicationPricing,
        ]);
    }

    /**
     * Show simplified IX application form for new applications.
     */
    public function createNew(): View|RedirectResponse
    {
        $userId = session('user_id');
        $user = Registration::find($userId);

        if (! $user) {
            return redirect()->route('login.index')
                ->with('error', 'User session expired. Please login again.');
        }

        if (! in_array($user->status, ['approved', 'active'], true)) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Your account must be approved before submitting an IX application.');
        }

        // Get FIRST application data (oldest submitted/approved application)
        $firstApplication = Application::where('user_id', $userId)
            ->where('application_type', 'IX')
            ->whereIn('status', ['submitted', 'approved', 'payment_verified', 'processor_forwarded_legal', 'legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending'])
            ->oldest()
            ->first();

        $previousData = null;
        $firstApplicationData = null;
        if ($firstApplication && $firstApplication->application_data) {
            $firstApplicationData = $firstApplication->application_data;
            $previousData = [
                'representative' => [
                    'pan' => $firstApplicationData['representative']['pan'] ?? null,
                    'mobile' => $firstApplicationData['representative']['mobile'] ?? null,
                    'email' => $firstApplicationData['representative']['email'] ?? null,
                    'name' => $firstApplicationData['representative']['name'] ?? null,
                ],
                'location_id' => $firstApplicationData['location']['id'] ?? null,
                'port_capacity' => $firstApplicationData['port_selection']['capacity'] ?? null,
                'billing_plan' => $firstApplicationData['port_selection']['billing_plan'] ?? null,
                'ip_prefix_count' => $firstApplicationData['ip_prefix']['count'] ?? null,
                'gstin' => $firstApplicationData['gstin'] ?? null,
            ];
        }

        $locations = IxLocation::active()
            ->orderBy('node_type')
            ->orderBy('state')
            ->orderBy('name')
            ->get();

        $portPricings = IxPortPricing::active()
            ->orderBy('node_type')
            ->orderBy('display_order')
            ->orderBy('port_capacity')
            ->get()
            ->groupBy('node_type');

        // Get KYC GST information
        $kycProfile = \App\Models\UserKycProfile::where('user_id', $userId)
            ->where('status', 'completed')
            ->first();
        
        $kycGstin = $kycProfile?->gstin;
        $gstState = null;
        
        if ($kycProfile && $kycProfile->gst_verification_id) {
            $gstVerification = \App\Models\GstVerification::find($kycProfile->gst_verification_id);
            if ($gstVerification && $gstVerification->state) {
                $gstState = $gstVerification->state;
            }
        }

        // Get application pricing
        $applicationPricing = \App\Models\IxApplicationPricing::getActive();

        return view('user.applications.ix.create-new', [
            'user' => $user,
            'previousData' => $previousData,
            'firstApplicationData' => $firstApplicationData,
            'firstApplication' => $firstApplication,
            'locations' => $locations,
            'portPricings' => $portPricings,
            'kycGstin' => $kycGstin,
            'gstState' => $gstState,
            'applicationPricing' => $applicationPricing,
        ]);
    }

    /**
     * Save draft or submit IX application.
     */
    public function store(StoreIxApplicationRequest $request): RedirectResponse|JsonResponse
    {
        $userId = session('user_id');
        $user = Registration::find($userId);

        if (! $user) {
            return redirect()->route('login.index')
                ->with('error', 'User session expired. Please login again.');
        }

        if (! in_array($user->status, ['approved', 'active'], true)) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Your account must be approved before submitting an IX application.');
        }

        $validated = $request->validated();
        $isDraft = $request->input('is_draft', false);
        $isPreview = $request->input('is_preview', false);
        $applicationId = $request->input('application_id');

        // If an application_id is provided, we are updating that specific draft.
        // Otherwise, we will always create a new application record so that
        // users can have multiple IX applications (draft, submitted, etc.).
        $existingDraft = null;
        if ($applicationId) {
            $existingDraft = Application::where('user_id', $userId)
                ->where('application_type', 'IX')
                ->where('application_id', $applicationId)
                ->where('status', 'draft')
                ->latest()
                ->first();
        }

        // Handle location and pricing - always try to get if location_id is provided
        $location = null;
        $pricing = null;
        $payableAmount = 0;

        if (isset($validated['location_id'])) {
            try {
                $location = IxLocation::active()->findOrFail($validated['location_id']);

                if (isset($validated['port_capacity'])) {
                    $pricing = IxPortPricing::active()
                        ->where('node_type', $location->node_type)
                        ->where('port_capacity', $validated['port_capacity'])
                        ->first();

                    if (! $pricing && (! $isDraft || $isPreview)) {
                        return back()->with('error', 'Selected port capacity is not available for this location.')
                            ->withInput();
                    }

                    if ($pricing && isset($validated['billing_plan'])) {
                        $payableAmount = $pricing->getAmountForPlan($validated['billing_plan']);
                    }
                }
            } catch (\Exception $e) {
                if (! $isDraft || $isPreview) {
                    Log::error('Error fetching location: '.$e->getMessage());

                    return back()->with('error', 'Selected location is invalid.')
                        ->withInput();
                }
            }
        }

        // Check if this is a simplified form submission
        $isSimplifiedForm = $request->has('representative_name') || $request->has('representative_pan');

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
            'new_gst_document', // For simplified form
        ];

        $storedDocuments = [];
        $storagePrefix = 'applications/'.$userId.'/ix/'.now()->format('YmdHis');

        // If updating draft, merge with existing documents
        if ($existingDraft && isset($existingDraft->application_data['documents'])) {
            $storedDocuments = $existingDraft->application_data['documents'];
        }

        foreach ($documentFields as $field) {
            if ($request->hasFile($field)) {
                $storedDocuments[$field] = $request->file($field)
                    ->store($storagePrefix, 'public');
            }
        }

        $memberType = null;
        if (isset($validated['member_type'])) {
            $memberType = $validated['member_type'] === 'others'
                ? ($validated['member_type_other'] ?? 'Others')
                : strtoupper($validated['member_type']);
        }

        // Prepare application data
        $applicationData = [];

        // Check if this is a simplified form submission
        $isSimplifiedForm = $request->has('representative_name') || $request->has('representative_pan');

        // Get FIRST application data for simplified form (oldest submitted/approved application)
        $previousApplication = null;
        $previousApplicationData = null;
        if ($isSimplifiedForm) {
            $previousApplication = Application::where('user_id', $userId)
                ->where('application_type', 'IX')
                ->whereIn('status', ['submitted', 'approved', 'payment_verified', 'processor_forwarded_legal', 'legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending'])
                ->oldest()
                ->first();
            
            if ($previousApplication && $previousApplication->application_data) {
                $previousApplicationData = $previousApplication->application_data;
            }
        }

        // Handle simplified form - representative person details
        if ($isSimplifiedForm) {
            $applicationData['representative'] = [
                'name' => $request->input('representative_name'),
                'pan' => strtoupper($request->input('representative_pan')),
                'dob' => $request->input('representative_dob'),
                'mobile' => $request->input('representative_mobile'),
                'email' => $request->input('representative_email'),
                'pan_verified' => $request->input('pan_verified') === '1',
                'mobile_verified' => $request->input('mobile_verified') === '1',
                'email_verified' => $request->input('email_verified') === '1',
            ];

            // Handle GSTIN for simplified form
            if ($request->has('gstin')) {
                $applicationData['gstin'] = strtoupper($request->input('gstin'));
                $applicationData['gstin_verified'] = $request->input('gstin_verified') === '1';
                if ($request->has('gstin_verification_id')) {
                    $applicationData['gstin_verification_id'] = $request->input('gstin_verification_id');
                }
            }

            // Copy other data from previous application if available
            if ($previousApplicationData) {
                // Copy member type
                if (! isset($applicationData['member_type']) && isset($previousApplicationData['member_type'])) {
                    $applicationData['member_type'] = $previousApplicationData['member_type'];
                }

                // Copy peering details
                if (! isset($applicationData['peering']) && isset($previousApplicationData['peering'])) {
                    $applicationData['peering'] = $previousApplicationData['peering'];
                }

                // Copy router details
                if (! isset($applicationData['router_details']) && isset($previousApplicationData['router_details'])) {
                    $applicationData['router_details'] = $previousApplicationData['router_details'];
                }

                // Copy documents from previous application
                if (isset($previousApplicationData['documents'])) {
                    // Check if GST has changed
                    $previousGstin = strtoupper((string) ($previousApplicationData['gstin'] ?? ''));
                    $currentGstin = strtoupper((string) ($applicationData['gstin'] ?? ''));
                    $gstChanged = $previousGstin && $currentGstin && $previousGstin !== $currentGstin;
                    $hasNewGstDocument = isset($storedDocuments['new_gst_document']);
                    
                    foreach ($previousApplicationData['documents'] as $docKey => $docPath) {
                        // Handle GST documents - only copy if GST hasn't changed
                        if ($docKey === 'gstin_document_file' || $docKey === 'new_gst_document') {
                            // If GST changed, skip copying old GST document (user must upload new one)
                            if ($gstChanged) {
                                continue;
                            }
                            // GST not changed, copy the GST document from first application
                            if (! $hasNewGstDocument && ! isset($storedDocuments[$docKey])) {
                                $storedDocuments[$docKey] = $docPath;
                            }
                            continue;
                        }
                        
                        // Copy all other documents (non-GST documents) from first application
                        if (! isset($storedDocuments[$docKey])) {
                            $storedDocuments[$docKey] = $docPath;
                        }
                    }
                }
                
                // If new GST document was uploaded and GST changed, also save it as gstin_document_file for consistency
                if (isset($storedDocuments['new_gst_document'])) {
                    $previousGstin = strtoupper((string) ($previousApplicationData['gstin'] ?? ''));
                    $currentGstin = strtoupper((string) ($applicationData['gstin'] ?? ''));
                    $gstChanged = $previousGstin && $currentGstin && $previousGstin !== $currentGstin;
                    if ($gstChanged) {
                        // Also save as gstin_document_file for this application (so it shows in the list)
                        $storedDocuments['gstin_document_file'] = $storedDocuments['new_gst_document'];
                    }
                }
            }
        }

        // Always save location if location_id is provided
        if ($location) {
            $applicationData['location'] = [
                'id' => $location->id,
                'name' => $location->name,
                'state' => $location->state,
                'node_type' => $location->node_type,
                'switch_details' => $location->switch_details,
                'nodal_officer' => $location->nodal_officer,
            ];
        } elseif (isset($validated['location_id'])) {
            // If location_id is provided but location not found, try to get basic info
            try {
                $locationFromDb = IxLocation::find($validated['location_id']);
                if ($locationFromDb) {
                    $applicationData['location'] = [
                        'id' => $locationFromDb->id,
                        'name' => $locationFromDb->name,
                        'state' => $locationFromDb->state,
                        'node_type' => $locationFromDb->node_type,
                        'switch_details' => $locationFromDb->switch_details,
                        'nodal_officer' => $locationFromDb->nodal_officer,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch location for ID: '.$validated['location_id']);
            }
        }

        // Always save port_selection if port_capacity is provided
        if (isset($validated['port_capacity'])) {
            if ($pricing) {
                $applicationData['port_selection'] = [
                    'capacity' => $validated['port_capacity'],
                    'billing_plan' => $validated['billing_plan'] ?? null,
                    'amount' => $payableAmount,
                    'currency' => $pricing->currency,
                ];
            } else {
                // Save port capacity even if pricing not found
                $applicationData['port_selection'] = [
                    'capacity' => $validated['port_capacity'],
                    'billing_plan' => $validated['billing_plan'] ?? null,
                    'amount' => $payableAmount,
                    'currency' => 'INR',
                ];
            }
        }
        $ipPrefixCount = $validated['ip_prefix_count'] ?? $request->input('ip_prefix_count');
        if ($ipPrefixCount) {
            $applicationData['ip_prefix'] = [
                'count' => $ipPrefixCount,
                'source' => $validated['ip_prefix_source'] ?? $request->input('ip_prefix_source') ?? null,
                'provider' => $validated['ip_prefix_provider'] ?? $request->input('ip_prefix_provider') ?? null,
            ];
        }
        if (isset($validated['pre_peering_connectivity'])) {
            $applicationData['peering'] = [
                'pre_nixi_connectivity' => $validated['pre_peering_connectivity'],
                'asn_number' => $validated['asn_number'] ?? null,
            ];
        }
        if (isset($validated['router_height_u']) || isset($validated['router_make_model'])) {
            $applicationData['router_details'] = [
                'height_u' => $validated['router_height_u'] ?? null,
                'make_model' => $validated['router_make_model'] ?? null,
                'serial_number' => $validated['router_serial_number'] ?? null,
            ];
        }

        // Set member type if not already set from previous application
        if (! isset($applicationData['member_type']) && $memberType) {
            $applicationData['member_type'] = $memberType;
        }
        $applicationData['documents'] = $storedDocuments;

        // Get application pricing from database
        $applicationPricing = IxApplicationPricing::getActive();
        $applicationFee = $applicationPricing ? (float) $applicationPricing->total_amount : 1000.00;

        if ((! $isDraft || $isPreview) && isset($validated['billing_plan']) && $pricing) {
            $applicationData['payment'] = [
                'status' => 'pending',
                'plan' => $validated['billing_plan'],
                'amount' => $applicationFee, // Use application fee from database
                'application_fee' => $applicationPricing ? (float) $applicationPricing->application_fee : 1000.00,
                'gst_percentage' => $applicationPricing ? (float) $applicationPricing->gst_percentage : 18.00,
                'total_amount' => $applicationFee,
                'currency' => 'INR',
                'declaration_confirmed_at' => now('Asia/Kolkata')->toDateTimeString(),
            ];
        }

        // Merge with existing data if updating draft (new data takes precedence, but preserve existing if new is missing)
        if ($existingDraft && $existingDraft->application_data) {
            $existingData = $existingDraft->application_data;

            // Start with existing data
            $mergedData = $existingData;

            // Override with new data where it exists
            foreach ($applicationData as $key => $value) {
                $mergedData[$key] = $value;
            }

            // For nested arrays, replace completely if new data exists, otherwise keep existing
            if (isset($applicationData['location'])) {
                $mergedData['location'] = $applicationData['location'];
            } elseif (isset($existingData['location'])) {
                $mergedData['location'] = $existingData['location'];
            }

            if (isset($applicationData['port_selection'])) {
                $mergedData['port_selection'] = $applicationData['port_selection'];
            } elseif (isset($existingData['port_selection'])) {
                $mergedData['port_selection'] = $existingData['port_selection'];
            }

            if (isset($applicationData['ip_prefix'])) {
                $mergedData['ip_prefix'] = $applicationData['ip_prefix'];
            } elseif (isset($existingData['ip_prefix'])) {
                $mergedData['ip_prefix'] = $existingData['ip_prefix'];
            }

            if (isset($applicationData['peering'])) {
                $mergedData['peering'] = $applicationData['peering'];
            } elseif (isset($existingData['peering'])) {
                $mergedData['peering'] = $existingData['peering'];
            }

            if (isset($applicationData['router_details'])) {
                $mergedData['router_details'] = $applicationData['router_details'];
            } elseif (isset($existingData['router_details'])) {
                $mergedData['router_details'] = $existingData['router_details'];
            }

            if (isset($applicationData['payment'])) {
                $mergedData['payment'] = $applicationData['payment'];
            } elseif (isset($existingData['payment'])) {
                $mergedData['payment'] = $existingData['payment'];
            }

            // Documents should be merged (keep existing, add new)
            if (isset($existingData['documents']) && isset($applicationData['documents'])) {
                $mergedData['documents'] = array_merge($existingData['documents'], $applicationData['documents']);
            } elseif (isset($applicationData['documents'])) {
                $mergedData['documents'] = $applicationData['documents'];
            } elseif (isset($existingData['documents'])) {
                $mergedData['documents'] = $existingData['documents'];
            }

            $applicationData = $mergedData;
        }

        // Save or update application
        // For IX applications, keep as 'draft' until payment is made
        // Status will be changed to 'submitted' only after successful payment
        if ($existingDraft) {
            $application = $existingDraft;
            $application->update([
                'application_data' => $applicationData,
                'status' => $isDraft ? 'draft' : 'draft', // Keep as draft until payment
                'submitted_at' => null, // Will be set after payment
            ]);
        } else {
            $application = Application::create([
                'user_id' => $userId,
                'pan_card_no' => $user->pancardno,
                'application_id' => Application::generateApplicationId(),
                'application_type' => 'IX',
                'status' => $isDraft ? 'draft' : 'draft', // Keep as draft until payment
                'application_data' => $applicationData,
                'submitted_at' => null, // Will be set after payment
            ]);
        }

        // Handle preview - return JSON
        if ($isPreview) {
            return response()->json([
                'success' => true,
                'application_id' => $application->application_id,
                'message' => 'Application data saved for preview.',
            ]);
        }

        // Handle draft save
        if ($isDraft) {
            return response()->json([
                'success' => true,
                'application_id' => $application->application_id,
                'message' => 'Application draft saved successfully.',
            ]);
        }

        // Final submission - status remains 'draft' until payment
        ApplicationStatusHistory::log(
            $application->id,
            null,
            'draft',
            'user',
            $userId,
            'IX application form submitted, awaiting payment'
        );

        // Generate application PDF
        try {
            $applicationPdf = $this->generateApplicationPdf($application);
            $pdfPath = 'applications/'.$userId.'/ix/'.$application->application_id.'_application.pdf';
            Storage::disk('public')->put($pdfPath, $applicationPdf->output());
            $applicationData['pdfs'] = ['application_pdf' => $pdfPath];
            $application->update(['application_data' => $applicationData]);
        } catch (Exception $e) {
            Log::error('Error generating IX application PDF: '.$e->getMessage());
        }

        Log::info('IX application submitted', [
            'application_id' => $application->application_id,
            'user_id' => $userId,
        ]);

        if ($request->boolean('is_simplified')) {
            return redirect()->route('user.applications.ix.pay-now', $application->id)
                ->with('success', 'Application saved. Please review details and complete payment.');
        }

        return redirect()->route('user.applications.index')
            ->with('success', 'IX application submitted successfully. You can download the application PDF from the applications list.');
    }

    /**
     * Show preview of IX application.
     */
    public function preview(Request $request): View|RedirectResponse
    {
        $userId = session('user_id');
        $user = Registration::find($userId);

        if (! $user) {
            return redirect()->route('login.index')
                ->with('error', 'User session expired. Please login again.');
        }

        $applicationId = $request->input('application_id');
        if (! $applicationId) {
            return redirect()->route('user.applications.ix.create')
                ->with('error', 'Application ID is required for preview.');
        }

        $application = Application::where('user_id', $userId)
            ->where('application_id', $applicationId)
            ->whereIn('status', ['draft', 'pending'])
            ->first();

        if (! $application) {
            return redirect()->route('user.applications.ix.create')
                ->with('error', 'Application not found or already submitted.');
        }

        $kyc = UserKycProfile::where('user_id', $userId)->first();
        $gstVerification = GstVerification::where('user_id', $userId)
            ->where('is_verified', true)
            ->latest()
            ->first();

        return view('user.applications.ix.preview', [
            'user' => $user,
            'application' => $application,
            'kyc' => $kyc,
            'gstVerification' => $gstVerification,
        ]);
    }

    /**
     * Download IX agreement template with user details.
     */
    public function downloadAgreement(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $userId = session('user_id');
        $user = Registration::find($userId);
        $kyc = UserKycProfile::where('user_id', $userId)->first();
        $gstVerification = GstVerification::where('user_id', $userId)
            ->where('is_verified', true)
            ->latest()
            ->first();

        $companyName = $gstVerification?->legal_name ?? $gstVerification?->trade_name ?? $user->fullname;
        $companyAddress = $gstVerification?->primary_address ?? '';

        $pdf = Pdf::loadView('user.applications.ix.pdf.agreement', [
            'generatedAt' => now('Asia/Kolkata')->format('d M Y, h:i A'),
            'companyName' => $companyName,
            'companyAddress' => $companyAddress,
            'user' => $user,
            'gstVerification' => $gstVerification,
        ])->setPaper('a4', 'portrait');

        $tmpPath = storage_path('app/public/temp');
        if (! is_dir($tmpPath)) {
            mkdir($tmpPath, 0775, true);
        }

        $filePath = $tmpPath.'/nixi-ix-agreement-template.pdf';
        $pdf->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    /**
     * Generate application PDF with all user details.
     */
    private function generateApplicationPdf(Application $application)
    {
        $user = $application->user;
        $data = $application->application_data;
        $kyc = UserKycProfile::where('user_id', $user->id)->first();
        $gstVerification = GstVerification::where('user_id', $user->id)
            ->where('is_verified', true)
            ->latest()
            ->first();

        // Convert uploaded PDFs to images for embedding
        $pdfImages = [];
        $documentNames = [
            'agreement_file' => 'Signed Agreement with NIXI',
            'license_isp_file' => 'ISP License',
            'license_vno_file' => 'VNO License',
            'cdn_declaration_file' => 'CDN Declaration',
            'general_declaration_file' => 'General Declaration',
            'whois_details_file' => 'Whois Details',
            'pan_document_file' => 'PAN Document',
            'gstin_document_file' => 'GSTIN Document',
            'msme_document_file' => 'MSME Certificate',
            'incorporation_document_file' => 'Certificate of Incorporation',
            'authorized_rep_document_file' => 'Authorized Representative Document',
        ];

        if (isset($data['documents']) && ! empty($data['documents'])) {
            foreach ($data['documents'] as $field => $path) {
                $fullPath = storage_path('app/public/'.$path);

                if (! file_exists($fullPath)) {
                    Log::warning('PDF file not found: '.$fullPath);
                    $pdfImages[$field] = [
                        'images' => [],
                        'name' => $documentNames[$field] ?? $field,
                        'path' => $path,
                    ];

                    continue;
                }

                $fileExtension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                if ($fileExtension === 'pdf' && extension_loaded('imagick')) {
                    $images = [];
                    try {
                        // Try to read all pages
                        $imagick = new \Imagick;
                        $imagick->setResolution(150, 150);
                        $imagick->setBackgroundColor(new \ImagickPixel('white'));

                        // Read all pages
                        $imagick->readImage($fullPath);
                        $imagick->setImageFormat('png');
                        $imagick->setImageCompressionQuality(90);

                        // Convert each page
                        $numPages = $imagick->getNumberImages();
                        for ($i = 0; $i < $numPages && $i < 10; $i++) { // Limit to 10 pages max
                            $imagick->setIteratorIndex($i);
                            $imagick->setImageFormat('png');
                            $images[] = base64_encode($imagick->getImageBlob());
                        }

                        $pdfImages[$field] = [
                            'images' => $images,
                            'name' => $documentNames[$field] ?? $field,
                            'path' => $path,
                        ];

                        $imagick->clear();
                        $imagick->destroy();
                    } catch (\Exception $e) {
                        Log::error('Failed to convert PDF to image for field '.$field.': '.$e->getMessage().' | File: '.$fullPath);
                        // Try simpler single page conversion
                        try {
                            $imagick = new \Imagick;
                            $imagick->setResolution(150, 150);
                            $imagick->readImage($fullPath.'[0]');
                            $imagick->setImageFormat('png');
                            $imagick->setImageCompressionQuality(90);
                            $images = [base64_encode($imagick->getImageBlob())];
                            $pdfImages[$field] = [
                                'images' => $images,
                                'name' => $documentNames[$field] ?? $field,
                                'path' => $path,
                            ];
                            $imagick->clear();
                            $imagick->destroy();
                        } catch (\Exception $e2) {
                            Log::error('Fallback PDF conversion failed: '.$e2->getMessage());
                            // Still include it so user knows document exists
                            $pdfImages[$field] = [
                                'images' => [],
                                'name' => $documentNames[$field] ?? $field,
                                'path' => $path,
                                'error' => true,
                            ];
                        }
                    }
                } else {
                    $pdfImages[$field] = [
                        'images' => [],
                        'name' => $documentNames[$field] ?? $field,
                        'path' => $path,
                    ];
                }
            }
        }

        $pdf = Pdf::loadView('user.applications.ix.pdf.application', [
            'application' => $application,
            'user' => $user,
            'data' => $data,
            'kyc' => $kyc,
            'gstVerification' => $gstVerification,
            'pdfImages' => $pdfImages,
        ])->setPaper('a4', 'portrait')
            ->setOption('enable-local-file-access', true);

        return $pdf;
    }

    /**
     * Download IX invoice PDF.
     */
    public function downloadInvoicePdf($id): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        try {
            $userId = session('user_id');
            $application = Application::where('id', $id)
                ->where('user_id', $userId)
                ->where('application_type', 'IX')
                ->firstOrFail();

            $data = $application->application_data ?? [];
            $invoicePdfPath = $data['pdfs']['invoice_pdf'] ?? null;

            if (! $invoicePdfPath || ! Storage::disk('public')->exists($invoicePdfPath)) {
                return redirect()->route('user.applications.index')
                    ->with('error', 'Invoice PDF not found. Please contact support.');
            }

            $filePath = Storage::disk('public')->path($invoicePdfPath);

            return response()->download($filePath, $application->application_id.'_invoice.pdf');
        } catch (Exception $e) {
            Log::error('Error downloading IX invoice PDF: '.$e->getMessage());

            return redirect()->route('user.applications.index')
                ->with('error', 'Unable to download invoice PDF.');
        }
    }

    /**
     * Download application PDF.
     */
    public function downloadApplicationPdf($id): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        try {
            $userId = session('user_id');
            $application = Application::with(['user', 'gstVerification'])
                ->where('id', $id)
                ->where('user_id', $userId)
                ->where('application_type', 'IX')
                ->firstOrFail();

            $applicationPdf = $this->generateApplicationPdf($application);

            $tmpPath = storage_path('app/public/temp');
            if (! is_dir($tmpPath)) {
                mkdir($tmpPath, 0775, true);
            }

            $fileName = $application->application_id.'_application.pdf';
            $filePath = $tmpPath.'/'.$fileName;
            $applicationPdf->save($filePath);

            return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
        } catch (Exception $e) {
            Log::error('Error downloading IX application PDF: '.$e->getMessage());

            return redirect()->route('user.applications.index')
                ->with('error', 'Unable to download application PDF.');
        }
    }

    /**
     * Fetch active IX locations for AJAX filtering.
     */
    public function locations(Request $request): JsonResponse
    {
        $state = $request->get('state');

        $locations = IxLocation::active()
            ->when($state, fn ($query) => $query->where('state', $state))
            ->orderBy('node_type')
            ->orderBy('state')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $locations,
        ]);
    }

    /**
     * Fetch IX port pricing grid.
     */
    public function pricing(Request $request): JsonResponse
    {
        $nodeType = $request->get('node_type');

        $pricings = IxPortPricing::active()
            ->when($nodeType, fn ($query) => $query->where('node_type', $nodeType))
            ->orderBy('node_type')
            ->orderBy('display_order')
            ->orderBy('port_capacity')
            ->get()
            ->groupBy('node_type');

        return response()->json([
            'success' => true,
            'data' => $pricings,
        ]);
    }

    /**
     * Fetch current IX application pricing (for live updates).
     */
    public function getApplicationPricing(): JsonResponse
    {
        $applicationPricing = IxApplicationPricing::getActive();
        if (! $applicationPricing) {
            // Fallback to default if no pricing is set
            $applicationPricing = (object) [
                'application_fee' => 1000.00,
                'gst_percentage' => 18.00,
                'total_amount' => 1180.00,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'application_fee' => (float) $applicationPricing->application_fee,
                'gst_percentage' => (float) $applicationPricing->gst_percentage,
                'total_amount' => (float) $applicationPricing->total_amount,
            ],
        ]);
    }

    /**
     * Final submit application from preview.
     */
    public function finalSubmit(string $applicationId): RedirectResponse
    {
        $userId = session('user_id');
        $application = Application::where('user_id', $userId)
            ->where('application_id', $applicationId)
            ->whereIn('status', ['draft', 'pending'])
            ->firstOrFail();

        // Keep as draft until payment is made
        $application->update([
            'status' => 'draft',
            'submitted_at' => null, // Will be set after payment
        ]);

        ApplicationStatusHistory::log(
            $application->id,
            null,
            'draft',
            'user',
            $userId,
            'IX application form submitted, awaiting payment'
        );

        // Generate application PDF
        try {
            $applicationPdf = $this->generateApplicationPdf($application);
            $pdfPath = 'applications/'.$userId.'/ix/'.$application->application_id.'_application.pdf';
            Storage::disk('public')->put($pdfPath, $applicationPdf->output());
            $applicationData = $application->application_data;
            $applicationData['pdfs'] = ['application_pdf' => $pdfPath];
            $application->update(['application_data' => $applicationData]);
        } catch (Exception $e) {
            Log::error('Error generating IX application PDF: '.$e->getMessage());
        }

        Log::info('IX application submitted', [
            'application_id' => $application->application_id,
            'user_id' => $userId,
        ]);

        return redirect()->route('user.applications.index')
            ->with('success', 'IX application submitted successfully. You can download the application PDF from the applications list.');
    }

    /**
     * Initiate payment for IX application.
     */
    public function initiatePayment(StoreIxApplicationRequest $request): JsonResponse
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User session expired. Please login again.',
                ], 401);
            }

            // First, save the application
            $validated = $request->validated();
            $validated['is_draft'] = false;
            $validated['is_preview'] = false;

            $application = $this->saveApplicationData($validated, $user, $request);

            if (! $application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save application.',
                ], 500);
            }

            // Get active application pricing from database
            $applicationPricing = IxApplicationPricing::getActive();
            if (! $applicationPricing) {
                // Fallback to default if no pricing is set
                $amount = 1000.00;
            } else {
                $amount = (float) $applicationPricing->total_amount;
            }

            // Generate transaction ID
            $transactionId = 'TXN'.time().rand(1000, 9999);

            // Create payment transaction
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $userId,
                'application_id' => $application->id,
                'transaction_id' => $transactionId,
                'payment_mode' => config('services.payu.mode', 'test'),
                'payment_status' => 'pending',
                'amount' => $amount,
                'currency' => 'INR',
                'product_info' => 'NIXI IX Application Fee',
            ]);

            // Prepare payment data
            $payuService = new PayuService;
            $paymentData = $payuService->preparePaymentData([
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'product_info' => 'NIXI IX Application Fee',
                'firstname' => $user->fullname,
                'email' => $user->email,
                'phone' => $user->mobile,
                'success_url' => url(route('user.applications.ix.payment-success', [], false)),
                'failure_url' => url(route('user.applications.ix.payment-failure', [], false)),
                'udf1' => $application->application_id,
                'udf2' => (string) $paymentTransaction->id,
            ]);

            // Store payment details and user session data in cookies for callback
            // Session gets cleared when PayU page opens, so we save essential data in cookies
            $cookieData = [
                'payment_transaction_id' => $paymentTransaction->id,
                'transaction_id' => $transactionId,
                'application_id' => $application->id,
                'user_id' => $userId,
                'amount' => $amount,
            ];

            // Store user session data for login restoration after PayU redirect
            $userSessionData = [
                'user_id' => $userId,
                'user_email' => $user->email,
                'user_name' => $user->fullname,
                'user_registration_id' => $user->registrationid,
            ];

            $response = response()->json([
                'success' => true,
                'payment_url' => $payuService->getPaymentUrl(),
                'payment_form' => $paymentData,
            ]);

            // Set cookies with payment details and user session (expires in 1 hour)
            // Use cookie() helper with proper path and sameSite settings so cookies persist when form is submitted to PayU
            $response->cookie(
                'pending_payment_data',
                json_encode($cookieData),
                60, // minutes
                '/', // path
                null, // domain (null = current domain)
                true, // secure (HTTPS only)
                false, // httpOnly (false so JS can access if needed)
                false, // raw
                'lax' // sameSite
            );
            $response->cookie(
                'user_session_data',
                json_encode($userSessionData),
                60,
                '/',
                null,
                true,
                false,
                false,
                'lax'
            );

            return $response;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error initiating payment: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initiating payment. Please try again.',
            ], 500);
        }
    }

    /**
     * Retry payment for an existing IX application draft with pending payment.
     */
    public function payNow(int $id): RedirectResponse|View|Response
    {
        $userId = session('user_id');
        $user = Registration::find($userId);

        if (! $user) {
            return redirect()->route('login.index')
                ->with('error', 'User session expired. Please login again.');
        }

        $application = Application::where('id', $id)
            ->where('user_id', $userId)
            ->where('application_type', 'IX')
            ->where('status', 'draft')
            ->firstOrFail();

        $data = $application->application_data ?? [];
        $payment = $data['payment'] ?? null;

        if (! $payment || ($payment['status'] ?? null) !== 'pending') {
            return redirect()->route('user.applications.index')
                ->with('error', 'This application is not waiting for payment or has already been paid.');
        }

        // Get active application pricing from database (use current pricing, not stored)
        $applicationPricing = IxApplicationPricing::getActive();
        if (! $applicationPricing) {
            // Fallback to default if no pricing is set
            $amount = 1000.00;
        } else {
            $amount = (float) $applicationPricing->total_amount;
        }

        // Generate new transaction ID for this retry
        $transactionId = 'TXN'.time().rand(1000, 9999);

        // Create payment transaction record
        $paymentTransaction = PaymentTransaction::create([
            'user_id' => $userId,
            'application_id' => $application->id,
            'transaction_id' => $transactionId,
            'payment_mode' => config('services.payu.mode', 'test'),
            'payment_status' => 'pending',
            'amount' => $amount,
            'currency' => $payment['currency'] ?? 'INR',
            'product_info' => 'NIXI IX Application Fee',
        ]);

        // Store payment details and user session data in cookies for callback
        // Session gets cleared when PayU page opens, so we save essential data in cookies
        $cookieData = [
            'payment_transaction_id' => $paymentTransaction->id,
            'transaction_id' => $transactionId,
            'application_id' => $application->id,
            'user_id' => $userId,
            'amount' => $amount,
        ];

        // Store user session data for login restoration after PayU redirect
        $userSessionData = [
            'user_id' => $userId,
            'user_email' => $user->email,
            'user_name' => $user->fullname,
            'user_registration_id' => $user->registrationid,
        ];

        $payuService = new PayuService;

        $paymentData = $payuService->preparePaymentData([
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'product_info' => 'NIXI IX Application Fee',
            'firstname' => $user->fullname,
            'email' => $user->email,
            'phone' => $user->mobile,
            'success_url' => url(route('user.applications.ix.payment-success', [], false)),
            'failure_url' => url(route('user.applications.ix.payment-failure', [], false)),
            'udf1' => $application->application_id,
            'udf2' => (string) $paymentTransaction->id,
        ]);

        // Set cookies with payment details and user session (expires in 1 hour)
        // Use cookie() helper with proper path and sameSite settings so cookies persist when form is submitted to PayU
        $response = response()->view('user.applications.ix.payu-redirect', [
            'paymentUrl' => $payuService->getPaymentUrl(),
            'paymentForm' => $paymentData,
        ]);

        $response->cookie(
            'pending_payment_data',
            json_encode($cookieData),
            60,
            '/',
            null,
            true,
            false,
            false,
            'lax'
        );
        $response->cookie(
            'user_session_data',
            json_encode($userSessionData),
            60,
            '/',
            null,
            true,
            false,
            false,
            'lax'
        );

        return $response;
    }

    /**
     * Handle payment success callback from PayU.
     */
    public function paymentSuccess(Request $request): RedirectResponse|Response
    {
        // Auto-refresh mechanism: Check session count
        // If session count doesn't exist or is 0, set it to 1 and refresh the page
        // If session count = 1, skip refresh and process payment
        $redirectCount = session('payment_redirect_count', 0);
        
        if ($redirectCount === 0) {
            // First visit: Set redirect count to 1 and auto-refresh the page
            session(['payment_redirect_count' => 1]);
            session()->save();
            
            Log::info('PayU Success - First visit, setting session count and auto-refreshing', [
                'current_url' => $request->fullUrl(),
            ]);
            
            // Show "fetching payment details" message and auto-refresh like view-logs
            return response()->view('user.applications.ix.payment-processing', [
                'message' => 'Fetching payment details...',
                'submessage' => 'Please do not refresh or go back. You will be redirected to your application automatically.',
                'redirectUrl' => $request->fullUrl(), // Redirect to same URL
                'autoRefresh' => true, // Enable auto-refresh
            ]);
        }
        
        // Second visit (redirect_count = 1): Process payment and redirect to final destination
        // Clear the redirect count so it doesn't interfere with future requests
        session()->forget('payment_redirect_count');
        session()->save();
        
        // PayU may send data via POST or GET (query string)
        $response = array_merge($request->query(), $request->post());
        
        Log::info('=== PayU Success Callback Method Called (Second Visit) ===', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'has_query' => !empty($request->query()),
            'has_post' => !empty($request->post()),
            'has_cookie' => $request->hasCookie('pending_payment_data'),
            'has_user_session_cookie' => $request->hasCookie('user_session_data'),
            'redirect_count' => $redirectCount,
        ]);
        
        try {
            // Get all data from cookies FIRST (session is cleared when PayU redirects)
            // Cookies are the source of truth for payment callbacks
            $cookieData = null;
            $userSessionData = null;
            
            // Get payment transaction data from cookie
            if ($request->hasCookie('pending_payment_data')) {
                $cookieData = json_decode($request->cookie('pending_payment_data'), true);
                Log::info('PayU Success - Found payment data in cookie', [
                    'cookie_data' => $cookieData,
                ]);
            }
            
            // Get user session data from cookie
            if ($request->hasCookie('user_session_data')) {
                $userSessionData = json_decode($request->cookie('user_session_data'), true);
                Log::info('PayU Success - Found user session data in cookie', [
                    'has_user_id' => isset($userSessionData['user_id']),
                ]);
            }
            
            // NO SESSION RESTORATION HERE - We'll redirect to login-from-cookie route
            // All processing uses cookies only, no session dependency
            
            // If no cookie data, try to get from PayU response
            if (! $cookieData) {
                $transactionId = $response['txnid'] ?? $request->input('txnid');
                $paymentTransactionId = $response['udf2'] ?? $request->input('udf2');
                
                if ($paymentTransactionId) {
                    $paymentTransaction = PaymentTransaction::find($paymentTransactionId);
                    if ($paymentTransaction) {
                        $cookieData = [
                            'payment_transaction_id' => $paymentTransaction->id,
                            'transaction_id' => $paymentTransaction->transaction_id,
                            'application_id' => $paymentTransaction->application_id,
                            'user_id' => $paymentTransaction->user_id,
                            'amount' => $paymentTransaction->amount,
                        ];
                    }
                } elseif ($transactionId) {
                    $paymentTransaction = PaymentTransaction::where('transaction_id', $transactionId)->first();
                    if ($paymentTransaction) {
                        $cookieData = [
                            'payment_transaction_id' => $paymentTransaction->id,
                            'transaction_id' => $paymentTransaction->transaction_id,
                            'application_id' => $paymentTransaction->application_id,
                            'user_id' => $paymentTransaction->user_id,
                            'amount' => $paymentTransaction->amount,
                        ];
                    }
                }
            }
            
            if (! $cookieData) {
                Log::error('PayU Success - No payment data found in cookie or response', [
                    'response' => $response,
                ]);
                
                return redirect()->route('user.applications.index')
                    ->with('error', 'Payment information not found. Please contact support with your transaction details.');
            }
            
            // Find payment transaction
            $paymentTransaction = PaymentTransaction::find($cookieData['payment_transaction_id']);
            
            if (! $paymentTransaction) {
                Log::error('PayU Success - Payment transaction not found', [
                    'payment_transaction_id' => $cookieData['payment_transaction_id'],
                ]);
                
                // Direct redirect to login-from-cookie route
                $loginUrl = route('user.login-from-cookie', [
                    'redirect' => route('user.applications.index'),
                    'error' => urlencode('Payment transaction not found. Please contact support.'),
                ]);
                
                return redirect($loginUrl);
            }
            
            // If payment is already processed, redirect immediately
            if ($paymentTransaction->payment_status === 'success') {
                Log::info('PayU Success - Payment already processed, redirecting to login', [
                    'transaction_id' => $paymentTransaction->transaction_id,
                    'payment_status' => $paymentTransaction->payment_status,
                ]);
                
                $successMessage = 'Payment was already processed. Transaction ID: ' . $paymentTransaction->transaction_id;
                $loginUrl = route('user.login-from-cookie', [
                    'redirect' => route('user.applications.index'),
                    'success' => urlencode($successMessage),
                ]);
                
                // Direct redirect to login-from-cookie (no view, no delay)
                return redirect($loginUrl)
                    ->cookie('pending_payment_data', '', -1, '/', null, true, false, false, 'lax');
            }
            
            // Extract all PayU response fields
            $payuResponseFields = $this->extractPayuResponseFields($request);
            $payuService = new PayuService;
            
            // If PayU didn't send parameters, use Verify Payment API to get transaction status
            if (empty($payuResponseFields) || !isset($payuResponseFields['status'])) {
                Log::info('PayU Success - No parameters received, checking transaction status via API', [
                    'transaction_id' => $paymentTransaction->transaction_id,
                ]);
                
                $verifyResponse = $payuService->checkTransactionStatus($paymentTransaction->transaction_id);
                
                if ($verifyResponse && isset($verifyResponse['transaction_status'])) {
                    // Map Verify API response to our format
                    $payuResponseFields = [
                        'mihpayid' => $verifyResponse['mihpayid'] ?? null,
                        'txnid' => $paymentTransaction->transaction_id,
                        'status' => $verifyResponse['transaction_status'] ?? 'success',
                        'unmappedstatus' => $verifyResponse['unmappedstatus'] ?? null,
                        'bank_ref_num' => $verifyResponse['bank_ref_num'] ?? null,
                        'mode' => $verifyResponse['mode'] ?? null,
                        'amount' => $verifyResponse['amount'] ?? $paymentTransaction->amount,
                        'error_code' => $verifyResponse['error_code'] ?? null,
                        'error_Message' => $verifyResponse['error_message'] ?? null,
                        'field9' => $verifyResponse['field9'] ?? null,
                        'raw_response' => $verifyResponse,
                        'source' => 'verify_api',
                    ];
                    
                    Log::info('PayU Success - Transaction status retrieved from Verify API', [
                        'transaction_id' => $paymentTransaction->transaction_id,
                        'status' => $payuResponseFields['status'],
                    ]);
                } else {
                    Log::warning('PayU Success - Verify API did not return transaction status', [
                        'transaction_id' => $paymentTransaction->transaction_id,
                        'verify_response' => $verifyResponse,
                    ]);
                }
            }
            
            // Verify hash if PayU sent parameters
            if (! empty($payuResponseFields) && isset($payuResponseFields['hash'])) {
                $isValid = $payuService->verifyHash($payuResponseFields);
                
                if (! $isValid) {
                    Log::warning('PayU hash verification failed', [
                        'response' => $payuResponseFields,
                        'transaction_id' => $paymentTransaction->transaction_id,
                    ]);
                    // Continue anyway - webhook will verify
                }
            }
            
            // Extract key fields for easier access
            $payuPaymentId = $payuResponseFields['mihpayid'] ?? null;
            $status = $payuResponseFields['status'] ?? '';
            $bankRefNum = $payuResponseFields['bank_ref_num'] ?? null;
            $mode = $payuResponseFields['mode'] ?? null;
            $unmappedStatus = $payuResponseFields['unmappedstatus'] ?? '';
            $cardType = $payuResponseFields['card_type'] ?? null;
            $cardnum = $payuResponseFields['cardnum'] ?? null;
            $nameOnCard = $payuResponseFields['name_on_card'] ?? null;
            $bankcode = $payuResponseFields['bankcode'] ?? null;
            $pgType = $payuResponseFields['pg_type'] ?? null;
            
            // Build comprehensive response message
            $responseMessage = 'Payment successful';
            if ($status) {
                $responseMessage = ucfirst($status);
            }
            if ($unmappedStatus) {
                $responseMessage .= ' ('.$unmappedStatus.')';
            }
            if ($bankRefNum) {
                $responseMessage .= ' - Bank Ref: '.$bankRefNum;
            }
            if ($mode) {
                $responseMessage .= ' - Mode: '.$mode;
            }
            if ($cardType) {
                $responseMessage .= ' - Card: '.$cardType;
            }
            
            // Log all PayU response fields for debugging
            Log::info('PayU Success - All Response Fields Captured', [
                'transaction_id' => $paymentTransaction->transaction_id,
                'payu_fields_count' => count($payuResponseFields),
                'key_fields' => [
                    'mihpayid' => $payuPaymentId,
                    'status' => $status,
                    'mode' => $mode,
                    'bank_ref_num' => $bankRefNum,
                    'card_type' => $cardType,
                ],
            ]);
            
            // Update payment transaction with all PayU response details
            $paymentTransaction->update([
                'payment_id' => $payuPaymentId ?? $paymentTransaction->payment_id,
                'payment_status' => 'success',
                'response_message' => $responseMessage,
                'payu_response' => $payuResponseFields, // Store all PayU response fields
                'hash' => $payuResponseFields['hash'] ?? null,
            ]);
            
            Log::info('PayU Payment Success - Transaction Updated', [
                'transaction_id' => $paymentTransaction->transaction_id,
                'payment_transaction_id' => $paymentTransaction->id,
                'payu_payment_id' => $payuPaymentId,
            ]);
            
            // Update application status
            if ($paymentTransaction->application_id) {
                $application = Application::find($paymentTransaction->application_id);
                if ($application) {
                    $newStatus = $application->application_type === 'IX' ? 'submitted' : 'pending';
                    
                    $application->update([
                        'status' => $newStatus,
                        'submitted_at' => now('Asia/Kolkata'),
                    ]);
                    
                    // Use user_id from cookie data (session is cleared when PayU redirects)
                    $userId = $cookieData['user_id'] ?? $paymentTransaction->user_id;
                    ApplicationStatusHistory::log(
                        $application->id,
                        null,
                        $newStatus,
                        'user',
                        $userId,
                        'IX application submitted with payment'
                    );
                    
                    // Generate application PDF
                    try {
                        // Use user_id from cookie data (session is cleared when PayU redirects)
                        $userId = $cookieData['user_id'] ?? $paymentTransaction->user_id;
                        $applicationPdf = $this->generateApplicationPdf($application);
                        $pdfPath = 'applications/'.$userId.'/ix/'.$application->application_id.'_application.pdf';
                        Storage::disk('public')->put($pdfPath, $applicationPdf->output());
                        $applicationData = $application->application_data;
                        $applicationData['pdfs'] = ['application_pdf' => $pdfPath];
                        $applicationData['payment'] = array_merge($applicationData['payment'] ?? [], [
                            'transaction_id' => $paymentTransaction->transaction_id,
                            'payment_id' => $payuPaymentId ?? $paymentTransaction->payment_id,
                            'status' => 'success',
                            'paid_at' => now('Asia/Kolkata')->toDateTimeString(),
                            'bank_ref_num' => $bankRefNum,
                            'mode' => $mode,
                            'unmappedstatus' => $unmappedStatus,
                            'card_type' => $cardType,
                            'cardnum' => $cardnum,
                            'name_on_card' => $nameOnCard,
                            'bankcode' => $bankcode,
                            'pg_type' => $pgType,
                        ]);
                        $application->update(['application_data' => $applicationData]);
                        
                        // Send email - use user from application relationship or cookie data
                        try {
                            $userEmail = $application->user->email ?? ($userSessionData['user_email'] ?? null);
                            if ($userEmail) {
                                Mail::to($userEmail)->send(
                                    new \App\Mail\IxApplicationSubmittedMail($application)
                                );
                            }
                        } catch (Exception $e) {
                            Log::error('Error sending IX application submitted email: '.$e->getMessage());
                        }
                    } catch (Exception $e) {
                        Log::error('Error generating IX application PDF: '.$e->getMessage());
                    }
                }
            }
            
            // After processing payment, redirect to login-from-cookie route which will set session and redirect
            // Pass success message and transaction ID as query parameters
            $successMessage = 'Payment successful! Your application has been submitted. Transaction ID: ' . $paymentTransaction->transaction_id;
            $loginUrl = route('user.login-from-cookie', [
                'redirect' => route('user.applications.index'),
                'success' => urlencode($successMessage),
            ]);
            
            // Clear redirect count before final redirect
            session()->forget('payment_redirect_count');
            session()->save();
            
            // Direct redirect to login-from-cookie
            // This route will set session from cookie and redirect to final destination
            return redirect($loginUrl)
                ->cookie('pending_payment_data', '', -1, '/', null, true, false, false, 'lax'); // Delete payment cookie, keep user_session_data for login
            
        } catch (\Exception $e) {
            Log::error('PayU Success Callback Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Clear redirect count on error
            session()->forget('payment_redirect_count');
            session()->save();
            
            // Direct redirect to login-from-cookie route on error
            $errorMessage = 'An error occurred while processing payment. Please contact support.';
            $loginUrl = route('user.login-from-cookie', [
                'redirect' => route('user.applications.index'),
                'error' => urlencode($errorMessage),
            ]);
            
            // Direct redirect to login-from-cookie (no view, no delay)
            return redirect($loginUrl)
                ->cookie('pending_payment_data', '', -1, '/', null, true, false, false, 'lax'); // Delete payment cookie, keep user_session_data for login
        }
    }


    /**
     * Handle payment failure callback from PayU.
     */
    public function paymentFailure(Request $request): RedirectResponse|Response
    {
        // Auto-refresh mechanism: Check session count
        // If session count doesn't exist or is 0, set it to 1 and refresh the page
        // If session count = 1, skip refresh and process payment
        $redirectCount = session('payment_redirect_count', 0);
        
        if ($redirectCount === 0) {
            // First visit: Set redirect count to 1 and auto-refresh the page
            session(['payment_redirect_count' => 1]);
            session()->save();
            
            Log::info('PayU Failure - First visit, setting session count and auto-refreshing', [
                'current_url' => $request->fullUrl(),
            ]);
            
            // Show "fetching payment details" message and auto-refresh like view-logs
            return response()->view('user.applications.ix.payment-processing', [
                'message' => 'Processing payment response...',
                'submessage' => 'Please do not refresh or go back. You will be redirected automatically.',
                'redirectUrl' => $request->fullUrl(), // Redirect to same URL
                'autoRefresh' => true, // Enable auto-refresh
            ]);
        }
        
        // Second visit (redirect_count = 1): Process payment failure and redirect to final destination
        // Clear the redirect count so it doesn't interfere with future requests
        session()->forget('payment_redirect_count');
        session()->save();
        
        // Log immediately when this method is called - even if empty
        // This route is accessible without authentication since PayU redirects here
        Log::info('=== PayU Failure Callback Method Called (Second Visit) ===', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'has_query' => !empty($request->query()),
            'has_post' => !empty($request->post()),
            'query_params' => $request->query(),
            'post_params' => $request->post(),
            'all_input' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'has_user_session' => !empty(session('user_id')),
            'has_cookie' => $request->hasCookie('pending_payment_data'),
            'has_user_session_cookie' => $request->hasCookie('user_session_data'),
            'redirect_count' => $redirectCount,
        ]);
        
        // PayU may send data via POST or GET (query string)
        // Get all parameters from both POST and GET
        $response = array_merge($request->query(), $request->post());
        
        try {
            // Get all data from cookies FIRST (session is cleared when PayU redirects)
            // Cookies are the source of truth for payment callbacks
            $cookieData = null;
            $userSessionData = null;
            
            // Get payment transaction data from cookie
            if ($request->hasCookie('pending_payment_data')) {
                $cookieData = json_decode($request->cookie('pending_payment_data'), true);
                Log::info('PayU Failure - Found payment data in cookie', [
                    'cookie_data' => $cookieData,
                ]);
            }
            
            // Get user session data from cookie
            if ($request->hasCookie('user_session_data')) {
                $userSessionData = json_decode($request->cookie('user_session_data'), true);
                Log::info('PayU Failure - Found user session data in cookie', [
                    'has_user_id' => isset($userSessionData['user_id']),
                ]);
            }
            
            // NO SESSION RESTORATION HERE - We'll redirect to login-from-cookie route
            // All processing uses cookies only, no session dependency
            // Log the failure response for debugging
            Log::info('PayU Failure Callback Received', [
                'all_params' => $response,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'query_params' => $request->query(),
                'post_params' => $request->post(),
                'all_input' => $request->all(),
            ]);

            // Get transaction ID and payment transaction ID from response or cookie
            $transactionId = $response['txnid'] ?? $request->input('txnid') ?? ($cookieData['transaction_id'] ?? null);
            $paymentTransactionId = $response['udf2'] ?? $request->input('udf2') ?? ($cookieData['payment_transaction_id'] ?? null);
            
            Log::info('PayU Failure - Looking up transaction', [
                'transaction_id' => $transactionId,
                'payment_transaction_id' => $paymentTransactionId,
                'has_txnid' => !empty($transactionId),
                'has_udf2' => !empty($paymentTransactionId),
                'has_cookie_data' => !empty($cookieData),
            ]);
            
            $paymentTransaction = null;
            if ($paymentTransactionId) {
                $paymentTransaction = PaymentTransaction::find($paymentTransactionId);
                // Verify the transaction ID matches if provided
                if ($paymentTransaction && $transactionId && $paymentTransaction->transaction_id !== $transactionId) {
                    Log::warning('PayU Failure - Transaction ID mismatch', [
                        'expected' => $paymentTransaction->transaction_id,
                        'received' => $transactionId,
                    ]);
                    $paymentTransaction = null;
                }
            }
            
            // Fallback: find by transaction ID only if udf2 lookup failed
            if (! $paymentTransaction && $transactionId) {
                $paymentTransaction = PaymentTransaction::where('transaction_id', $transactionId)->first();
            }
            
            // If no parameters and no transaction found, try to find recent pending transaction using cookie data
            if (! $paymentTransaction && empty($response)) {
                // Use cookie data first, not session (session is cleared when PayU redirects)
                $userId = $cookieData['user_id'] ?? null;
                if ($userId) {
                    $recentTransaction = PaymentTransaction::where('user_id', $userId)
                        ->where('payment_status', 'pending')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($recentTransaction) {
                        Log::info('PayU Failure - Found recent pending transaction without parameters', [
                            'transaction_id' => $recentTransaction->transaction_id,
                            'payment_transaction_id' => $recentTransaction->id,
                            'user_id_from_cookie' => $userId,
                        ]);
                        $paymentTransaction = $recentTransaction;
                    }
                } else {
                    Log::warning('PayU Failure - No user_id found in cookie data to lookup recent transaction', [
                        'cookie_data' => $cookieData,
                    ]);
                }
            }

            if ($paymentTransaction) {
                // Extract all PayU response fields
                $payuResponseFields = $this->extractPayuResponseFields($request);
                $payuService = new PayuService;
                
                // If PayU didn't send parameters, use Verify Payment API to get transaction status
                if (empty($payuResponseFields) || !isset($payuResponseFields['status'])) {
                    Log::info('PayU Failure - No parameters received, checking transaction status via API', [
                        'transaction_id' => $paymentTransaction->transaction_id,
                    ]);
                    
                    $verifyResponse = $payuService->checkTransactionStatus($paymentTransaction->transaction_id);
                    
                    if ($verifyResponse && isset($verifyResponse['transaction_status'])) {
                        // Map Verify API response to our format
                        $payuResponseFields = [
                            'mihpayid' => $verifyResponse['mihpayid'] ?? null,
                            'txnid' => $paymentTransaction->transaction_id,
                            'status' => $verifyResponse['transaction_status'] ?? 'failure',
                            'unmappedstatus' => $verifyResponse['unmappedstatus'] ?? null,
                            'bank_ref_num' => $verifyResponse['bank_ref_num'] ?? null,
                            'mode' => $verifyResponse['mode'] ?? null,
                            'amount' => $verifyResponse['amount'] ?? $paymentTransaction->amount,
                            'error_code' => $verifyResponse['error_code'] ?? null,
                            'error_Message' => $verifyResponse['error_message'] ?? null,
                            'field9' => $verifyResponse['field9'] ?? null,
                            'raw_response' => $verifyResponse,
                            'source' => 'verify_api',
                        ];
                        
                        Log::info('PayU Failure - Transaction status retrieved from Verify API', [
                            'transaction_id' => $paymentTransaction->transaction_id,
                            'status' => $payuResponseFields['status'],
                        ]);
                    } else {
                        Log::warning('PayU Failure - Verify API did not return transaction status', [
                            'transaction_id' => $paymentTransaction->transaction_id,
                            'verify_response' => $verifyResponse,
                        ]);
                    }
                }
                
                // Extract key fields for easier access
                $payuPaymentId = $payuResponseFields['mihpayid'] ?? null;
                $status = $payuResponseFields['status'] ?? '';
                $error = $payuResponseFields['error'] ?? null;
                $errorMessage = $payuResponseFields['error_Message'] ?? null;
                $errorCode = $payuResponseFields['error_code'] ?? null;
                $unmappedStatus = $payuResponseFields['unmappedstatus'] ?? '';
                $bankRefNum = $payuResponseFields['bank_ref_num'] ?? null;
                $mode = $payuResponseFields['mode'] ?? null;
                $failureReason = $payuResponseFields['failure_reason'] ?? null;

                // Build comprehensive failure response message
                $responseMessage = 'Payment failed';
                if ($status) {
                    $responseMessage = ucfirst($status);
                }
                if ($errorMessage) {
                    $responseMessage .= ' - '.$errorMessage;
                } elseif ($error) {
                    $responseMessage .= ' - '.$error;
                }
                if ($failureReason) {
                    $responseMessage .= ' - Reason: '.$failureReason;
                }
                if ($errorCode) {
                    $responseMessage .= ' (Error Code: '.$errorCode.')';
                }
                if ($unmappedStatus) {
                    $responseMessage .= ' - Status: '.$unmappedStatus;
                }
                if ($bankRefNum) {
                    $responseMessage .= ' - Bank Ref: '.$bankRefNum;
                }
                if ($mode) {
                    $responseMessage .= ' - Mode: '.$mode;
                }

                // Log all PayU response fields for debugging
                Log::warning('PayU Payment Failed - All Response Fields Captured', [
                    'transaction_id' => $transactionId,
                    'payment_transaction_id' => $paymentTransaction->id,
                    'payu_fields_count' => count($payuResponseFields),
                    'key_fields' => [
                        'mihpayid' => $payuPaymentId,
                        'status' => $status,
                        'error' => $error,
                        'error_message' => $errorMessage,
                        'error_code' => $errorCode,
                        'failure_reason' => $failureReason,
                        'mode' => $mode,
                    ],
                ]);

                $paymentTransaction->update([
                    'payment_id' => $payuPaymentId,
                    'payment_status' => 'failed',
                    'response_message' => $responseMessage,
                    'payu_response' => $payuResponseFields, // Store all PayU response fields
                    'hash' => $payuResponseFields['hash'] ?? null,
                ]);
            } else {
                Log::error('PayU Failure Callback - Payment transaction not found', [
                    'transaction_id' => $transactionId,
                    'payment_transaction_id' => $paymentTransactionId,
                    'cookie_data' => $cookieData,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PayU Failure Callback Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $response,
            ]);
        }

        // Clear redirect count before final redirect
        session()->forget('payment_redirect_count');
        session()->save();
        
        // Direct redirect to login-from-cookie route which will set session and redirect
        $errorMessage = 'Payment failed. Please try again or contact support if the amount was deducted.';
        $loginUrl = route('user.login-from-cookie', [
            'redirect' => route('user.applications.ix.create'),
            'error' => urlencode($errorMessage),
        ]);

        // Direct redirect to login-from-cookie (no view, no delay)
        // This route will set session from cookie and redirect to final destination
        return redirect($loginUrl)
            ->cookie('pending_payment_data', '', -1, '/', null, true, false, false, 'lax'); // Delete payment cookie, keep user_session_data for login
    }

    /**
     * Handle PayU Server-to-Server (S2S) Webhook.
     * This is the most reliable way to confirm transaction status.
     * PayU sends this POST request directly from their server to ours.
     */
    public function handleWebhook(Request $request): \Illuminate\Http\JsonResponse
    {
        $response = $request->all();
        
        try {
            $payuService = new PayuService;

            // Log the webhook request for debugging
            Log::info('PayU S2S Webhook Received', [
                'all_params' => $response,
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);

            // Check if required fields are present
            $requiredFields = ['txnid', 'status', 'hash'];
            $missingFields = array_diff($requiredFields, array_keys($response));
            
            if (! empty($missingFields)) {
                Log::error('PayU S2S Webhook - Missing required fields', [
                    'missing_fields' => $missingFields,
                    'received_fields' => array_keys($response),
                    'response' => $response,
                ]);

                return response()->json(['status' => 'error', 'message' => 'Missing required fields'], 400);
            }

            // Extract all PayU response fields
            $payuResponseFields = $this->extractPayuResponseFields($request);
            
            // Verify hash
            $isValid = $payuService->verifyHash($payuResponseFields);

            if (! $isValid) {
                Log::warning('PayU S2S Webhook - Hash verification failed', [
                    'response' => $payuResponseFields,
                    'transaction_id' => $payuResponseFields['txnid'] ?? null,
                    'status' => $payuResponseFields['status'] ?? null,
                ]);

                return response()->json(['status' => 'error', 'message' => 'Hash verification failed'], 400);
            }

            // Find payment transaction
            $transactionId = $payuResponseFields['txnid'] ?? null;
            $paymentTransactionId = $payuResponseFields['udf2'] ?? null;
            $invoiceNumber = $payuResponseFields['udf3'] ?? null;
            $isInvoicePayment = $invoiceNumber !== null;
            
            $paymentTransaction = null;
            if ($paymentTransactionId) {
                $paymentTransaction = PaymentTransaction::find($paymentTransactionId);
                // Verify the transaction ID matches
                if ($paymentTransaction && $paymentTransaction->transaction_id !== $transactionId) {
                    $paymentTransaction = null;
                }
            }
            
            // Fallback: find by transaction ID only if udf2 lookup failed
            if (! $paymentTransaction && $transactionId) {
                $paymentTransaction = PaymentTransaction::where('transaction_id', $transactionId)->first();
            }

            if (! $paymentTransaction) {
                Log::error('PayU S2S Webhook - Payment transaction not found', [
                    'transaction_id' => $transactionId,
                    'payment_transaction_id' => $paymentTransactionId,
                ]);

                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            // Extract key fields for easier access
            $payuPaymentId = $payuResponseFields['mihpayid'] ?? null;
            $status = $payuResponseFields['status'] ?? '';
            $bankRefNum = $payuResponseFields['bank_ref_num'] ?? null;
            $mode = $payuResponseFields['mode'] ?? null;
            $error = $payuResponseFields['error'] ?? null;
            $errorMessage = $payuResponseFields['error_Message'] ?? null;
            $errorCode = $payuResponseFields['error_code'] ?? null;
            $unmappedStatus = $payuResponseFields['unmappedstatus'] ?? '';
            $failureReason = $payuResponseFields['failure_reason'] ?? null;

            // Determine payment status based on PayU status
            // PayU status values: success, failure, pending, cancelled, etc.
            $paymentStatus = 'pending';
            if (strtolower($status) === 'success') {
                $paymentStatus = 'success';
            } elseif (in_array(strtolower($status), ['failure', 'failed', 'error', 'cancelled'])) {
                $paymentStatus = 'failed';
            }

            // Build comprehensive response message
            $responseMessage = $status ?: ($error ?: $errorMessage ?: 'Payment processed');
            if ($unmappedStatus) {
                $responseMessage .= ' ('.$unmappedStatus.')';
            }
            if ($errorCode) {
                $responseMessage .= ' - Error Code: '.$errorCode;
            }
            if ($failureReason) {
                $responseMessage .= ' - Reason: '.$failureReason;
            }
            if ($bankRefNum) {
                $responseMessage .= ' - Bank Ref: '.$bankRefNum;
            }
            if ($mode) {
                $responseMessage .= ' - Mode: '.$mode;
            }

            // Add webhook timestamp to response data
            $payuResponseFields['webhook_received_at'] = now('Asia/Kolkata')->toDateTimeString();
            $payuResponseFields['webhook_source'] = 's2s';
            
            // Log all PayU response fields for debugging
            Log::info('PayU S2S Webhook - All Response Fields Captured', [
                'transaction_id' => $transactionId,
                'payment_transaction_id' => $paymentTransaction->id,
                'payu_fields_count' => count($payuResponseFields),
                'key_fields' => [
                    'mihpayid' => $payuPaymentId,
                    'status' => $status,
                    'unmappedstatus' => $unmappedStatus,
                    'mode' => $mode,
                    'bank_ref_num' => $bankRefNum,
                    'error_code' => $errorCode,
                ],
            ]);

            // Update payment transaction - S2S webhook is the source of truth
            $paymentTransaction->update([
                'payment_id' => $payuPaymentId ?? $paymentTransaction->payment_id,
                'payment_status' => $paymentStatus,
                'response_message' => $responseMessage,
                'payu_response' => $payuResponseFields, // Store all PayU response fields
                'hash' => $payuResponseFields['hash'] ?? null,
            ]);

            // isInvoicePayment and invoiceNumber already set above

            // If payment is successful, handle invoice payment or application payment
            if ($paymentStatus === 'success' && $paymentTransaction->application_id) {
                $application = Application::find($paymentTransaction->application_id);
                
                if ($application) {
                    // Handle invoice payment (check udf3 for invoice number)
                    if ($isInvoicePayment && $invoiceNumber) {
                        $invoice = \App\Models\Invoice::where('invoice_number', $invoiceNumber)
                            ->where('application_id', $application->id)
                            ->first();
                        
                        if ($invoice && $invoice->status === 'pending') {
                            // Mark invoice as paid
                            $invoice->update([
                                'status' => 'paid',
                                'paid_at' => now('Asia/Kolkata'),
                            ]);

                            // Automatically verify payment for this billing period
                            if ($invoice->billing_period) {
                                // Check if already verified
                                $existingVerification = \App\Models\PaymentVerificationLog::where('application_id', $application->id)
                                    ->where('billing_period', $invoice->billing_period)
                                    ->first();

                                if (!$existingVerification) {
                                    \App\Models\PaymentVerificationLog::create([
                                        'application_id' => $application->id,
                                        'verified_by' => null, // System verified
                                        'verification_type' => 'recurring',
                                        'billing_period' => $invoice->billing_period,
                                        'amount' => $invoice->total_amount,
                                        'currency' => $invoice->currency,
                                        'payment_method' => 'payu',
                                        'notes' => 'Payment verified automatically via PayU for invoice '.$invoiceNumber,
                                        'verified_at' => now('Asia/Kolkata'),
                                    ]);

                                    // Log status change
                                    \App\Models\ApplicationStatusHistory::log(
                                        $application->id,
                                        $application->status,
                                        $application->status, // Keep same status
                                        'system',
                                        null,
                                        "Payment automatically verified via PayU for billing period {$invoice->billing_period}"
                                    );

                                    // Send message to user
                                    \App\Models\Message::create([
                                        'user_id' => $application->user_id,
                                        'subject' => 'Payment Verified',
                                        'message' => "Payment for invoice {$invoiceNumber} has been received and verified automatically. Thank you for your payment.",
                                        'is_read' => false,
                                        'sent_by' => 'system',
                                    ]);
                                }
                            }
                        }
                    } else {
                        // Handle initial application payment
                        if ($application->status !== 'submitted') {
                            $newStatus = $application->application_type === 'IX' ? 'submitted' : 'pending';

                            $application->update([
                                'status' => $newStatus,
                                'submitted_at' => $application->submitted_at ?? now('Asia/Kolkata'),
                            ]);

                            ApplicationStatusHistory::log(
                                $application->id,
                                null,
                                $newStatus,
                                'system',
                                null,
                                'IX application submitted via PayU S2S webhook'
                            );
                        }

                        // Update application data with payment info
                        $applicationData = $application->application_data ?? [];
                        $applicationData['payment'] = array_merge($applicationData['payment'] ?? [], [
                            'transaction_id' => $transactionId,
                            'payment_id' => $payuPaymentId ?? $paymentTransaction->payment_id,
                            'status' => 'success',
                            'paid_at' => now('Asia/Kolkata')->toDateTimeString(),
                            'bank_ref_num' => $bankRefNum,
                            'mode' => $mode,
                            'unmappedstatus' => $unmappedStatus,
                            'card_type' => $payuResponseFields['card_type'] ?? null,
                            'pg_type' => $payuResponseFields['pg_type'] ?? null,
                            'webhook_confirmed' => true,
                            'webhook_source' => 's2s',
                        ]);
                        $application->update(['application_data' => $applicationData]);
                    }
                }
            }

            // Log webhook processing
            Log::info('PayU S2S Webhook Processed Successfully', [
                'transaction_id' => $transactionId,
                'payment_transaction_id' => $paymentTransaction->id,
                'payu_payment_id' => $payuPaymentId,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'amount' => $request->input('amount'),
            ]);

            // Return success response to PayU
            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('PayU S2S Webhook Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $response,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * Save application data (extracted from store method for reuse).
     */
    private function saveApplicationData(array $validated, Registration $user, Request $request): ?Application
    {
        $userId = $user->id;
        $applicationId = $request->input('application_id');

        // If an application_id is provided, update that specific draft.
        // Otherwise, always create a new IX application so users can
        // have multiple applications regardless of other drafts.
        $existingDraft = null;
        if ($applicationId) {
            $existingDraft = Application::where('user_id', $userId)
                ->where('application_type', 'IX')
                ->where('application_id', $applicationId)
                ->where('status', 'draft')
                ->latest()
                ->first();
        }

        // Handle location and pricing
        $location = null;
        $pricing = null;
        $payableAmount = 0;

        if (isset($validated['location_id'])) {
            try {
                $location = IxLocation::active()->findOrFail($validated['location_id']);

                if (isset($validated['port_capacity'])) {
                    $pricing = IxPortPricing::active()
                        ->where('node_type', $location->node_type)
                        ->where('port_capacity', $validated['port_capacity'])
                        ->first();

                    if ($pricing && isset($validated['billing_plan'])) {
                        $payableAmount = $pricing->getAmountForPlan($validated['billing_plan']);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error fetching location: '.$e->getMessage());
            }
        }

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
            'new_gst_document', // For simplified form
        ];

        $storedDocuments = [];
        $storagePrefix = 'applications/'.$userId.'/ix/'.now()->format('YmdHis');

        if ($existingDraft && isset($existingDraft->application_data['documents'])) {
            $storedDocuments = $existingDraft->application_data['documents'];
        }

        foreach ($documentFields as $field) {
            if ($request->hasFile($field)) {
                $storedDocuments[$field] = $request->file($field)
                    ->store($storagePrefix, 'public');
            }
        }

        // Check if this is a simplified form submission
        $isSimplifiedForm = $request->has('representative_name') || $request->has('representative_pan');
        $previousApplication = null;
        $previousApplicationData = null;

        // For simplified forms, copy documents from previous application
        // Copy if: no existing draft OR existing draft has no documents
        $needsDocumentCopy = $isSimplifiedForm && (! $existingDraft || empty($storedDocuments));
        
        if ($needsDocumentCopy) {
            // Get FIRST application (oldest) to copy documents from
            $previousApplication = Application::where('user_id', $userId)
                ->where('application_type', 'IX')
                ->whereIn('status', ['submitted', 'approved', 'payment_verified', 'processor_forwarded_legal', 'legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending'])
                ->oldest()
                ->first();

            if ($previousApplication && isset($previousApplication->application_data['documents'])) {
                $previousApplicationData = $previousApplication->application_data;
                
                // Check if GST has changed
                $previousGstin = strtoupper((string) ($previousApplicationData['gstin'] ?? ''));
                $currentGstin = strtoupper((string) ($validated['gstin'] ?? ''));
                $gstChanged = $previousGstin && $currentGstin && $previousGstin !== $currentGstin;
                $hasNewGstDocument = isset($storedDocuments['new_gst_document']);

                foreach ($previousApplicationData['documents'] as $docKey => $docPath) {
                    // Handle GST documents - only copy if GST hasn't changed
                    if ($docKey === 'gstin_document_file' || $docKey === 'new_gst_document') {
                        // If GST changed, skip copying old GST document (user must upload new one)
                        if ($gstChanged) {
                            continue;
                        }
                        // GST not changed, copy the GST document from first application
                        if (! $hasNewGstDocument && ! isset($storedDocuments[$docKey])) {
                            $storedDocuments[$docKey] = $docPath;
                        }
                        continue;
                    }

                    // Copy all other documents (non-GST documents) from first application
                    if (! isset($storedDocuments[$docKey])) {
                        $storedDocuments[$docKey] = $docPath;
                    }
                }

                // If new GST document was uploaded and GST changed, also save it as gstin_document_file for consistency
                if (isset($storedDocuments['new_gst_document'])) {
                    if ($gstChanged) {
                        // Also save as gstin_document_file for this application (so it shows in the list)
                        $storedDocuments['gstin_document_file'] = $storedDocuments['new_gst_document'];
                    }
                }
            }
        }

        $memberType = null;
        if (isset($validated['member_type'])) {
            $memberType = $validated['member_type'] === 'others'
                ? ($validated['member_type_other'] ?? 'Others')
                : strtoupper($validated['member_type']);
        }

        // Prepare application data
        $applicationData = [];

        // Handle simplified form - representative person details
        if ($isSimplifiedForm) {
            $applicationData['representative'] = [
                'name' => $validated['representative_name'] ?? null,
                'dob' => $validated['representative_dob'] ?? null,
                'pan' => $validated['representative_pan'] ?? null,
                'email' => $validated['representative_email'] ?? null,
                'mobile' => $validated['representative_mobile'] ?? null,
            ];

            $applicationData['gstin'] = $validated['gstin'] ?? null;

            // Copy other data from previous application if available
            if (isset($previousApplication) && $previousApplication->application_data) {
                $previousApplicationData = $previousApplication->application_data;

                // Copy member type
                if (! isset($applicationData['member_type']) && isset($previousApplicationData['member_type'])) {
                    $applicationData['member_type'] = $previousApplicationData['member_type'];
                }

                // Copy peering details
                if (! isset($applicationData['peering']) && isset($previousApplicationData['peering'])) {
                    $applicationData['peering'] = $previousApplicationData['peering'];
                }

                // Copy router details
                if (! isset($applicationData['router_details']) && isset($previousApplicationData['router_details'])) {
                    $applicationData['router_details'] = $previousApplicationData['router_details'];
                }
            }
        }

        if ($location) {
            $applicationData['location'] = [
                'id' => $location->id,
                'name' => $location->name,
                'state' => $location->state,
                'node_type' => $location->node_type,
                'switch_details' => $location->switch_details,
                'nodal_officer' => $location->nodal_officer,
            ];
        }

        if (isset($validated['port_capacity'])) {
            if ($pricing) {
                $applicationData['port_selection'] = [
                    'capacity' => $validated['port_capacity'],
                    'billing_plan' => $validated['billing_plan'] ?? null,
                    'amount' => $payableAmount,
                    'currency' => $pricing->currency,
                ];
            } else {
                $applicationData['port_selection'] = [
                    'capacity' => $validated['port_capacity'],
                    'billing_plan' => $validated['billing_plan'] ?? null,
                    'amount' => $payableAmount,
                    'currency' => 'INR',
                ];
            }
        }

        if (isset($validated['ip_prefix_count'])) {
            $applicationData['ip_prefix'] = [
                'count' => $validated['ip_prefix_count'],
                'source' => $validated['ip_prefix_source'] ?? null,
                'provider' => $validated['ip_prefix_provider'] ?? null,
            ];
        }

        if (isset($validated['pre_peering_connectivity'])) {
            $applicationData['peering'] = [
                'pre_nixi_connectivity' => $validated['pre_peering_connectivity'],
                'asn_number' => $validated['asn_number'] ?? null,
            ];
        }

        if (isset($validated['router_height_u']) || isset($validated['router_make_model'])) {
            $applicationData['router_details'] = [
                'height_u' => $validated['router_height_u'] ?? null,
                'make_model' => $validated['router_make_model'] ?? null,
                'serial_number' => $validated['router_serial_number'] ?? null,
            ];
        }

        // Set member type if not already set from previous application
        if (! isset($applicationData['member_type']) && $memberType) {
            $applicationData['member_type'] = $memberType;
        }
        $applicationData['documents'] = $storedDocuments;

        // Get application pricing from database
        $applicationPricing = IxApplicationPricing::getActive();
        $applicationFee = $applicationPricing ? (float) $applicationPricing->total_amount : 1000.00;

        if (isset($validated['billing_plan']) && $pricing) {
            $applicationData['payment'] = [
                'status' => 'pending',
                'plan' => $validated['billing_plan'],
                'amount' => $applicationFee, // Use application fee from database
                'application_fee' => $applicationPricing ? (float) $applicationPricing->application_fee : 1000.00,
                'gst_percentage' => $applicationPricing ? (float) $applicationPricing->gst_percentage : 18.00,
                'total_amount' => $applicationFee,
                'currency' => 'INR',
                'declaration_confirmed_at' => now('Asia/Kolkata')->toDateTimeString(),
            ];
        }

        // Merge with existing data if updating draft
        if ($existingDraft && $existingDraft->application_data) {
            $existingData = $existingDraft->application_data;
            $mergedData = $existingData;

            foreach ($applicationData as $key => $value) {
                $mergedData[$key] = $value;
            }

            if (isset($applicationData['documents'])) {
                $mergedData['documents'] = array_merge($existingData['documents'] ?? [], $applicationData['documents']);
            }

            $applicationData = $mergedData;
        }

        // Save or update application
        if ($existingDraft) {
            $application = $existingDraft;
            $application->update([
                'application_data' => $applicationData,
                'status' => 'draft',
                'submitted_at' => null,
            ]);
        } else {
            $application = Application::create([
                'user_id' => $userId,
                'pan_card_no' => $user->pancardno,
                'application_id' => Application::generateApplicationId(),
                'application_type' => 'IX',
                'status' => 'draft',
                'application_data' => $applicationData,
                'submitted_at' => null,
            ]);
        }

        return $application;
    }

    /**
     * Verify PAN for representative person.
     */
    public function verifyRepresentativePan(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'pan' => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                'name' => 'required|string|max:255',
                'dob' => 'required|date|before:today',
            ]);

            $panNo = strtoupper(trim($request->input('pan')));
            $fullName = trim($request->input('name'));
            $dob = $request->input('dob');

            $idfyService = new \App\Services\IdfyPanService;
            $taskResult = $idfyService->createVerificationTask($panNo, $fullName, $dob);

            Log::info("Representative PAN verification task created: {$panNo}, Request ID: {$taskResult['request_id']}");

            // Track PAN request for later server-side verification checks
            session([
                'ix_pan_request_'.$taskResult['request_id'] => [
                    'pan' => $panNo,
                    'name' => $fullName,
                    'dob' => $dob,
                ],
            ]);
            session()->save();

            return response()->json([
                'success' => true,
                'message' => 'PAN verification initiated. Please wait...',
                'request_id' => $taskResult['request_id'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => implode(', ', $e->errors()['pan'] ?? $e->errors()['name'] ?? $e->errors()['dob'] ?? ['Validation failed']),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error verifying representative PAN: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while verifying PAN. Please try again.',
            ], 500);
        }
    }

    /**
     * Check PAN verification status.
     */
    public function checkRepresentativePanStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'request_id' => 'required|string',
            ]);

            $idfyService = new \App\Services\IdfyPanService;
            $statusResult = $idfyService->getTaskStatus($request->input('request_id'));

            if ($statusResult['status'] === 'completed') {
                $result = $statusResult['result'];
                $sourceOutput = $result['source_output'] ?? null;

                if ($sourceOutput) {
                    $panStatus = $sourceOutput['pan_status'] ?? '';
                    $nameMatch = $sourceOutput['name_match'] ?? false;
                    $dobMatch = $sourceOutput['dob_match'] ?? false;
                    $status = $sourceOutput['status'] ?? '';

                    $isValid = $status === 'id_found' &&
                              str_contains($panStatus, 'Valid') &&
                              $nameMatch &&
                              $dobMatch;

                    // Persist server-side verification flag when valid
                    $panData = session('ix_pan_request_'.$request->input('request_id'));
                    if ($isValid && $panData && isset($panData['pan'])) {
                        session([
                            'ix_pan_verified_'.md5($panData['pan']) => true,
                        ]);
                        session()->save();
                    }

                    return response()->json([
                        'success' => $isValid,
                        'message' => $isValid
                            ? 'PAN verified successfully'
                            : 'PAN verification failed: '.($panStatus ?: 'Invalid PAN or details mismatch'),
                        'pan_status' => $panStatus,
                        'name_match' => $nameMatch,
                        'dob_match' => $dobMatch,
                    ]);
                }
            } elseif ($statusResult['status'] === 'failed') {
                return response()->json([
                    'success' => false,
                    'message' => 'PAN verification failed',
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'Verification in progress. Please wait...',
                'status' => $statusResult['status'],
            ]);
        } catch (Exception $e) {
            Log::error('Error checking PAN status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking verification status.',
            ], 500);
        }
    }

    /**
     * Send OTP to email for verification.
     */
    public function sendEmailOtp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $email = $request->input('email');
            $otp = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $sessionKey = 'ix_email_otp_'.md5($email);
            session([$sessionKey => $otp]);
            session()->save();

            // Send email OTP using Laravel Mail
            try {
                \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\RegistrationOtpMail($otp));
                Log::info("IX Email OTP sent to: {$email}");
            } catch (Exception $e) {
                Log::error('Failed to send email OTP: '.$e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to email successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email address',
            ], 422);
        } catch (Exception $e) {
            Log::error('Error sending email OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify email OTP.
     */
    public function verifyEmailOtp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
            ]);

            $email = $request->input('email');
            $otp = $request->input('otp');
            $sessionKey = 'ix_email_otp_'.md5($email);
            $storedOtp = session($sessionKey);

            if ($storedOtp && $storedOtp === $otp) {
                session(['ix_email_verified_'.md5($email) => true]);
                session()->forget($sessionKey);

                return response()->json([
                    'success' => true,
                    'message' => 'Email verified successfully!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
            ], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
            ], 422);
        } catch (Exception $e) {
            Log::error('Error verifying email OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while verifying OTP.',
            ], 500);
        }
    }

    /**
     * Send OTP to mobile for verification.
     */
    public function sendMobileOtp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/',
            ]);

            $mobile = $request->input('mobile');
            $otp = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $sessionKey = 'ix_mobile_otp_'.md5($mobile);
            session([$sessionKey => $otp]);
            session()->save();

            // Send SMS OTP (you can integrate SMS service here)
            // For now, we show OTP on page since we don't have SMS panel
            Log::info("IX Mobile OTP sent to: {$mobile}");

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to mobile successfully',
                'otp' => config('app.debug') ? $otp : $otp, // Always show OTP on page since no SMS panel
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid mobile number',
            ], 422);
        } catch (Exception $e) {
            Log::error('Error sending mobile OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify mobile OTP.
     */
    public function verifyMobileOtp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/',
                'otp' => 'required|string|size:6',
            ]);

            $mobile = $request->input('mobile');
            $otp = $request->input('otp');
            $sessionKey = 'ix_mobile_otp_'.md5($mobile);
            $storedOtp = session($sessionKey);

            if ($storedOtp && $storedOtp === $otp) {
                session(['ix_mobile_verified_'.md5($mobile) => true]);
                session()->forget($sessionKey);

                return response()->json([
                    'success' => true,
                    'message' => 'Mobile verified successfully!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
            ], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
            ], 422);
        } catch (Exception $e) {
            Log::error('Error verifying mobile OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while verifying OTP.',
            ], 500);
        }
    }

    /**
     * Verify GSTIN for billing.
     */
    public function verifyGstin(Request $request): JsonResponse
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

            // Track GST request for later server-side verification checks
            session([
                'ix_gstin_request_'.$verification->id => $gstin,
            ]);
            session()->save();

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
     * Check GSTIN verification status.
     */
    public function checkGstinStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'verification_id' => 'required|integer|exists:gst_verifications,id',
            ]);

            $verification = \App\Models\GstVerification::findOrFail($request->input('verification_id'));

            if ($verification->is_verified) {
                return response()->json([
                    'success' => true,
                    'message' => 'GSTIN verified successfully',
                    'is_verified' => true,
                ]);
            }

            $service = new \App\Services\IdfyVerificationService;
            $statusResult = $service->getTaskStatus($verification->request_id);

            if ($statusResult['status'] === 'completed') {
                $result = $statusResult['result'];
                $sourceOutput = $result['source_output'] ?? null;

                if ($sourceOutput) {
                    $verification->update([
                        'status' => 'completed',
                        'is_verified' => true,
                        'verification_data' => $result,
                    ]);

                    $gstin = session('ix_gstin_request_'.$verification->id) ?? $verification->gstin;
                    if ($gstin) {
                        session([
                            'ix_gstin_verified_'.$verification->id => true,
                            'ix_gstin_verified_value_'.md5($gstin) => true,
                        ]);
                        session()->save();
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'GSTIN verified successfully',
                        'is_verified' => true,
                    ]);
                }
            } elseif ($statusResult['status'] === 'failed') {
                $verification->update([
                    'status' => 'failed',
                    'is_verified' => false,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'GSTIN verification failed',
                    'is_verified' => false,
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'Verification in progress. Please wait...',
                'status' => $statusResult['status'],
            ]);
        } catch (Exception $e) {
            Log::error('Error checking GSTIN status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking verification status.',
            ], 500);
        }
    }
}
