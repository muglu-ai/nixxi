<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIxApplicationRequest;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\GstVerification;
use App\Models\IxApplicationPricing;
use App\Models\IxLocation;
use App\Models\IxPortPricing;
use App\Models\PaymentTransaction;
use App\Models\Registration;
use App\Models\UserKycProfile;
use App\Services\PayuService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class IxApplicationController extends Controller
{
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

        // Handle file uploads
        $documentFields = [
            'agreement_file',
            'license_isp_file',
            'license_vno_file',
            'cdn_declaration_file',
            'general_declaration_file',
            'board_resolution_file',
            'whois_details_file',
            'pan_document_file',
            'gstin_document_file',
            'msme_document_file',
            'incorporation_document_file',
            'authorized_rep_document_file',
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

        $applicationData['member_type'] = $memberType;
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
            'board_resolution_file' => 'Board Resolution',
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

        return response()->json([
            'success' => true,
            'payment_url' => $payuService->getPaymentUrl(),
            'payment_form' => $paymentData,
        ]);
    }

    /**
     * Retry payment for an existing IX application draft with pending payment.
     */
    public function payNow(int $id): RedirectResponse|View
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

        // dd($paymentData);

        return view('user.applications.ix.payu-redirect', [
            'paymentUrl' => $payuService->getPaymentUrl(),
            'paymentForm' => $paymentData,
        ]);
    }

    /**
     * Handle payment success callback from PayU.
     */
    public function paymentSuccess(Request $request): RedirectResponse|View
    {


        // PayU may send data via POST or GET (query string)
        // Get all parameters from both POST and GET
        $response = array_merge($request->query(), $request->post());
        
        // Log immediately when this method is called - even if empty
        // This route is accessible without authentication since PayU redirects here
        Log::info('=== PayU Success Callback Method Called ===', [
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
        ]);
        
        try {
            $payuService = new PayuService;

            // Log the full response for debugging
            Log::info('PayU Success Callback Received', [
                'all_params' => $response,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'query_params' => $request->query(),
                'post_params' => $request->post(),
                'all_input' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

            // Check if required fields are present
            $requiredFields = ['txnid', 'status', 'hash'];
            $missingFields = array_diff($requiredFields, array_keys($response));
            
            // If no parameters received, try to find recent transaction
            if (empty($response) || ! empty($missingFields)) {
                Log::warning('PayU Success Callback - Missing required fields or empty response', [
                    'missing_fields' => $missingFields,
                    'received_fields' => array_keys($response),
                    'response' => $response,
                    'user_id' => session('user_id'),
                ]);

                // First, try to find transaction by user session if available
                $userId = session('user_id');
                $recentTransaction = null;
                
                if ($userId) {
                    // Try to find the most recent pending or successful payment transaction for this user
                    $recentTransaction = PaymentTransaction::where('user_id', $userId)
                        ->whereIn('payment_status', ['pending', 'success'])
                        ->orderBy('updated_at', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->first();
                }
                
                // If no user session or no transaction found, look for recently updated successful transactions
                // This handles the case where webhook updated the transaction but user session expired
                if (! $recentTransaction) {
                    $recentTransaction = PaymentTransaction::where('payment_status', 'success')
                        ->where('updated_at', '>=', now()->subMinutes(10)) // Within last 10 minutes
                        ->orderBy('updated_at', 'desc')
                        ->first();
                    
                    if ($recentTransaction) {
                        Log::info('PayU Success - Found recent successful transaction without session', [
                            'transaction_id' => $recentTransaction->transaction_id,
                            'payment_transaction_id' => $recentTransaction->id,
                            'user_id' => $recentTransaction->user_id,
                        ]);
                    }
                }
                
                if ($recentTransaction) {
                    // Check if transaction is already successful (updated by webhook)
                    if ($recentTransaction->payment_status === 'success') {
                        Log::info('PayU Success - Transaction already marked as successful (likely updated by webhook)', [
                            'transaction_id' => $recentTransaction->transaction_id,
                            'payment_transaction_id' => $recentTransaction->id,
                        ]);
                        
                        // Get application if exists
                        $application = null;
                        if ($recentTransaction->application_id) {
                            $application = Application::find($recentTransaction->application_id);
                        }
                        
                        // If user session exists, redirect to applications page
                        if ($userId) {
                            return redirect()->route('user.applications.index')
                                ->with('success', 'Payment successful! Your application has been submitted. Transaction ID: ' . $recentTransaction->transaction_id);
                        }
                        
                                // If no session, show standalone success page with login link
                                return view('user.applications.ix.payment-confirmation-standalone', [
                                    'paymentTransaction' => $recentTransaction,
                                    'application' => $application,
                                    'showLoginLink' => true,
                                ]);
                    }
                    
                    // Transaction is still pending, try to query PayU API
                    Log::info('PayU Success - Found recent pending transaction without parameters', [
                        'transaction_id' => $recentTransaction->transaction_id,
                        'payment_transaction_id' => $recentTransaction->id,
                    ]);
                    
                    // Try to query PayU API for transaction status
                    try {
                        $payuService = new PayuService;
                        $statusResponse = $payuService->checkTransactionStatus($recentTransaction->transaction_id);
                        
                        // PayU Verify Payment API returns: {status: 1, msg: "...", transaction_details: {txnid: {...}}}
                        if ($statusResponse && isset($statusResponse['status']) && $statusResponse['status'] == 1) {
                            // API call succeeded, check transaction status
                            $txnStatus = strtolower($statusResponse['transaction_status'] ?? '');
                            
                            if ($txnStatus === 'success' || $txnStatus === 'captured') {
                                // Payment is successful according to PayU
                                $recentTransaction->update([
                                    'payment_status' => 'success',
                                    'payment_id' => $statusResponse['mihpayid'] ?? null,
                                    'response_message' => $statusResponse['field9'] ?? $statusResponse['msg'] ?? 'Payment successful (verified via PayU API)',
                                    'payu_response' => $statusResponse,
                                ]);
                                
                                // Update application if exists
                                if ($recentTransaction->application_id) {
                                    $application = Application::find($recentTransaction->application_id);
                                    if ($application) {
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
                                            'IX application submitted - payment verified via PayU API'
                                        );
                                    }
                                }
                                
                                // If user session exists, redirect to applications page
                                if ($userId) {
                                    return redirect()->route('user.applications.index')
                                        ->with('success', 'Payment successful! Your application has been submitted. Transaction ID: ' . $recentTransaction->transaction_id);
                                }
                                
                                // If no session, show standalone success page with login link
                                $application = $recentTransaction->application_id ? Application::find($recentTransaction->application_id) : null;
                                return view('user.applications.ix.payment-confirmation-standalone', [
                                    'paymentTransaction' => $recentTransaction,
                                    'application' => $application,
                                    'showLoginLink' => true,
                                ]);
                            } elseif ($txnStatus === 'failure' || $txnStatus === 'dropped' || $txnStatus === 'cancelled') {
                                // Payment failed according to PayU
                                $recentTransaction->update([
                                    'payment_status' => 'failed',
                                    'payment_id' => $statusResponse['mihpayid'] ?? null,
                                    'response_message' => $statusResponse['error_message'] ?? $statusResponse['field9'] ?? 'Payment failed (verified via PayU API)',
                                    'payu_response' => $statusResponse,
                                ]);
                                
                                if ($userId) {
                                    return redirect()->route('user.applications.index')
                                        ->with('error', 'Payment failed. Please try again. Transaction ID: ' . $recentTransaction->transaction_id);
                                }
                                
                                return redirect()->route('login.index')
                                    ->with('error', 'Payment failed. Please login to try again. Transaction ID: ' . $recentTransaction->transaction_id);
                            }
                        } elseif ($statusResponse && isset($statusResponse['status']) && $statusResponse['status'] == 0) {
                            // Transaction not found or API call failed
                            Log::warning('PayU Verify Payment API - Transaction not found', [
                                'transaction_id' => $recentTransaction->transaction_id,
                                'response' => $statusResponse,
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Error checking PayU transaction status', [
                            'error' => $e->getMessage(),
                            'transaction_id' => $recentTransaction->transaction_id,
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                    
                    // If API check didn't work, show processing message
                    // The webhook will update the status when PayU confirms
                    if ($userId) {
                        return redirect()->route('user.applications.index')
                            ->with('info', 'Your payment is being processed. Please check back in a few moments. Transaction ID: ' . $recentTransaction->transaction_id);
                    }
                    
                    return redirect()->route('login.index')
                        ->with('info', 'Your payment is being processed. Please login to check your application status. Transaction ID: ' . $recentTransaction->transaction_id);
                }

                // If we can't find a transaction, show a helpful message
                // The S2S webhook will handle the actual status update
                if ($userId) {
                    return redirect()->route('user.applications.index')
                        ->with('info', 'Payment is being processed. Please check your applications in a few moments. If payment was deducted, the status will update automatically via webhook.');
                }
                
                return redirect()->route('login.index')
                    ->with('info', 'Payment is being processed. Please login to check your application status. If payment was deducted, the status will update automatically.');
            }

            // Verify hash
            $isValid = $payuService->verifyHash($response);

            if (! $isValid) {
                Log::warning('PayU hash verification failed', [
                    'response' => $response,
                    'transaction_id' => $request->input('txnid'),
                    'status' => $request->input('status'),
                ]);

                // In test mode, we might want to be more lenient, but still log
                // For now, we'll still require valid hash even in test mode
                return redirect()->route('user.applications.ix.create')
                    ->with('error', 'Payment verification failed. Please contact support.');
            }
        } catch (\Exception $e) {
            Log::error('PayU Success Callback Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $response,
            ]);

            return redirect()->route('user.applications.ix.create')
                ->with('error', 'An error occurred while processing payment. Please contact support.');
        }

        // Find payment transaction using transaction ID and payment transaction ID from udf2
        // PayU may send via GET or POST, so check both
        $transactionId = $response['txnid'] ?? $request->input('txnid');
        $paymentTransactionId = $response['udf2'] ?? $request->input('udf2');
        
        Log::info('PayU Success - Looking up transaction', [
            'transaction_id' => $transactionId,
            'payment_transaction_id' => $paymentTransactionId,
            'has_txnid' => !empty($transactionId),
            'has_udf2' => !empty($paymentTransactionId),
        ]);
        
        $paymentTransaction = null;
        if ($paymentTransactionId) {
            $paymentTransaction = PaymentTransaction::find($paymentTransactionId);
            // Verify the transaction ID matches if provided
            if ($paymentTransaction && $transactionId && $paymentTransaction->transaction_id !== $transactionId) {
                Log::warning('PayU Success - Transaction ID mismatch', [
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

        if (! $paymentTransaction) {
            Log::error('Payment transaction not found', [
                'transaction_id' => $transactionId,
                'payment_transaction_id' => $paymentTransactionId,
                'all_response_data' => $response,
                'request_url' => $request->fullUrl(),
            ]);

            return redirect()->route('user.applications.ix.create')
                ->with('error', 'Payment transaction not found. Transaction ID: ' . ($transactionId ?? 'N/A'));
        }
        
        $userId = $paymentTransaction->user_id;

        // Get PayU payment ID - PayU can return it as mihpayid, payuMoneyId, or payuid
        $payuPaymentId = $request->input('mihpayid') 
            ?? $request->input('payuMoneyId') 
            ?? $request->input('payuid')
            ?? null;

        // Get additional response fields
        $status = $request->input('status', '');
        $bankRefNum = $request->input('bank_ref_num');
        $mode = $request->input('mode');
        $error = $request->input('error');
        $errorMessage = $request->input('error_Message') ?? $request->input('error_message');

        // Update payment transaction with all PayU response data
        $paymentTransaction->update([
            'payment_id' => $payuPaymentId,
            'payment_status' => 'success',
            'response_message' => $status ?: 'success',
            'payu_response' => $response,
            'hash' => $request->input('hash'),
        ]);

        // Log successful payment for debugging
        Log::info('PayU Payment Success - Transaction Updated', [
            'transaction_id' => $transactionId,
            'payment_transaction_id' => $paymentTransaction->id,
            'payu_payment_id' => $payuPaymentId,
            'status' => $status,
            'amount' => $request->input('amount'),
            'bank_ref_num' => $bankRefNum,
        ]);

        // Update application status
        if ($paymentTransaction->application_id) {
            $application = Application::find($paymentTransaction->application_id);
            if ($application) {
                // Use new workflow status for IX applications
                $newStatus = $application->application_type === 'IX' ? 'submitted' : 'pending';

                $application->update([
                    'status' => $newStatus,
                    'submitted_at' => now('Asia/Kolkata'),
                ]);

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
                    $applicationPdf = $this->generateApplicationPdf($application);
                    $pdfPath = 'applications/'.$userId.'/ix/'.$application->application_id.'_application.pdf';
                    Storage::disk('public')->put($pdfPath, $applicationPdf->output());
                    $applicationData = $application->application_data;
                    $applicationData['pdfs'] = ['application_pdf' => $pdfPath];
                    $applicationData['payment'] = array_merge($applicationData['payment'] ?? [], [
                        'transaction_id' => $transactionId,
                        'payment_id' => $payuPaymentId ?? $paymentTransaction->payment_id,
                        'status' => 'success',
                        'paid_at' => now('Asia/Kolkata')->toDateTimeString(),
                        'bank_ref_num' => $bankRefNum,
                        'mode' => $mode,
                    ]);
                    $application->update(['application_data' => $applicationData]);

                    // Send email to applicant and IX team
                    try {
                        Mail::to($application->user->email)->send(
                            new \App\Mail\IxApplicationSubmittedMail($application)
                        );
                    } catch (Exception $e) {
                        Log::error('Error sending IX application submitted email: '.$e->getMessage());
                    }
                } catch (Exception $e) {
                    Log::error('Error generating IX application PDF: '.$e->getMessage());
                }
            }
        }

        try {
            // Get application if it exists - safely handle null
            $application = null;
            if ($paymentTransaction->application_id) {
                $application = Application::find($paymentTransaction->application_id);
            }
            
            Log::info('Rendering payment confirmation view', [
                'payment_transaction_id' => $paymentTransaction->id,
                'application_id' => $paymentTransaction->application_id,
                'has_application' => $application !== null,
                'has_user_session' => !empty(session('user_id')),
            ]);
            
            // Use standalone view if user session is not available
            $hasUserSession = !empty(session('user_id'));
            $viewName = $hasUserSession ? 'user.applications.ix.payment-confirmation' : 'user.applications.ix.payment-confirmation-standalone';
            
            return view($viewName, [
                'paymentTransaction' => $paymentTransaction,
                'application' => $application,
                'showLoginLink' => !$hasUserSession,
            ]);
        } catch (\Exception $e) {
            Log::error('Error rendering payment confirmation view', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_transaction_id' => $paymentTransaction->id,
                'payment_transaction' => $paymentTransaction->toArray(),
            ]);

            // Still show success message even if view fails
            return redirect()->route('user.applications.index')
                ->with('success', 'Payment was successful! Transaction ID: ' . $paymentTransaction->transaction_id);
        }
    }

    /**
     * Handle payment failure callback from PayU.
     */
    public function paymentFailure(Request $request): RedirectResponse
    {
        // Log immediately when this method is called - even if empty
        // This route is accessible without authentication since PayU redirects here
        Log::info('=== PayU Failure Callback Method Called ===', [
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
        ]);
        
        // PayU may send data via POST or GET (query string)
        // Get all parameters from both POST and GET
        $response = array_merge($request->query(), $request->post());
        
        try {
            // Log the failure response for debugging
            Log::info('PayU Failure Callback Received', [
                'all_params' => $response,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'query_params' => $request->query(),
                'post_params' => $request->post(),
                'all_input' => $request->all(),
            ]);

            // Get transaction ID and payment transaction ID from response (works with both GET and POST)
            $transactionId = $response['txnid'] ?? $request->input('txnid');
            $paymentTransactionId = $response['udf2'] ?? $request->input('udf2');
            
            Log::info('PayU Failure - Looking up transaction', [
                'transaction_id' => $transactionId,
                'payment_transaction_id' => $paymentTransactionId,
                'has_txnid' => !empty($transactionId),
                'has_udf2' => !empty($paymentTransactionId),
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
            
            // If no parameters and no transaction found, try to find recent pending transaction
            if (! $paymentTransaction && empty($response)) {
                $userId = session('user_id');
                if ($userId) {
                    $recentTransaction = PaymentTransaction::where('user_id', $userId)
                        ->where('payment_status', 'pending')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($recentTransaction) {
                        Log::info('PayU Failure - Found recent pending transaction without parameters', [
                            'transaction_id' => $recentTransaction->transaction_id,
                            'payment_transaction_id' => $recentTransaction->id,
                        ]);
                        $paymentTransaction = $recentTransaction;
                    }
                }
            }

            if ($paymentTransaction) {
                // Get PayU payment ID and error details
                $payuPaymentId = $request->input('mihpayid') 
                    ?? $request->input('payuMoneyId') 
                    ?? $request->input('payuid')
                    ?? null;

                $status = $request->input('status', '');
                $error = $request->input('error');
                $errorMessage = $request->input('error_Message') ?? $request->input('error_message');

                $paymentTransaction->update([
                    'payment_id' => $payuPaymentId,
                    'payment_status' => 'failed',
                    'response_message' => $status ?: $error ?: $errorMessage ?: 'Payment failed',
                    'payu_response' => $response,
                    'hash' => $request->input('hash'),
                ]);

                // Log failed payment for debugging
                Log::warning('PayU Payment Failed', [
                    'transaction_id' => $transactionId,
                    'payment_transaction_id' => $paymentTransaction->id,
                    'status' => $status,
                    'error' => $error,
                    'error_message' => $errorMessage,
                ]);
            } else {
                Log::error('PayU Failure Callback - Payment transaction not found', [
                    'transaction_id' => $transactionId,
                    'payment_transaction_id' => $paymentTransactionId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PayU Failure Callback Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $response,
            ]);
        }

        return redirect()->route('user.applications.ix.create')
            ->with('error', 'Payment failed. Please try again or contact support if the amount was deducted.');
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

            // Verify hash
            $isValid = $payuService->verifyHash($response);

            if (! $isValid) {
                Log::warning('PayU S2S Webhook - Hash verification failed', [
                    'response' => $response,
                    'transaction_id' => $request->input('txnid'),
                    'status' => $request->input('status'),
                ]);

                return response()->json(['status' => 'error', 'message' => 'Hash verification failed'], 400);
            }

            // Find payment transaction
            $transactionId = $request->input('txnid');
            $paymentTransactionId = $request->input('udf2');
            
            $paymentTransaction = null;
            if ($paymentTransactionId) {
                $paymentTransaction = PaymentTransaction::find($paymentTransactionId);
                // Verify the transaction ID matches
                if ($paymentTransaction && $paymentTransaction->transaction_id !== $transactionId) {
                    $paymentTransaction = null;
                }
            }
            
            // Fallback: find by transaction ID only if udf2 lookup failed
            if (! $paymentTransaction) {
                $paymentTransaction = PaymentTransaction::where('transaction_id', $transactionId)->first();
            }

            if (! $paymentTransaction) {
                Log::error('PayU S2S Webhook - Payment transaction not found', [
                    'transaction_id' => $transactionId,
                    'payment_transaction_id' => $paymentTransactionId,
                ]);

                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            // Get PayU payment ID and status
            $payuPaymentId = $request->input('mihpayid') 
                ?? $request->input('payuMoneyId') 
                ?? $request->input('payuid')
                ?? null;

            $status = $request->input('status', '');
            $bankRefNum = $request->input('bank_ref_num');
            $mode = $request->input('mode');
            $error = $request->input('error');
            $errorMessage = $request->input('error_Message') ?? $request->input('error_message');

            // Determine payment status based on PayU status
            // PayU status values: success, failure, pending, cancelled, etc.
            $paymentStatus = 'pending';
            if (strtolower($status) === 'success') {
                $paymentStatus = 'success';
            } elseif (in_array(strtolower($status), ['failure', 'failed', 'error', 'cancelled'])) {
                $paymentStatus = 'failed';
            }

            // Update payment transaction - S2S webhook is the source of truth
            // Add webhook timestamp to response data
            $webhookResponse = $response;
            $webhookResponse['webhook_received_at'] = now('Asia/Kolkata')->toDateTimeString();
            
            $paymentTransaction->update([
                'payment_id' => $payuPaymentId,
                'payment_status' => $paymentStatus,
                'response_message' => $status ?: ($error ?: $errorMessage ?: ''),
                'payu_response' => $webhookResponse,
                'hash' => $request->input('hash'),
            ]);

            // If payment is successful, update application status
            if ($paymentStatus === 'success' && $paymentTransaction->application_id) {
                $application = Application::find($paymentTransaction->application_id);
                if ($application && $application->status !== 'submitted') {
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

                    // Update application data with payment info
                    $applicationData = $application->application_data;
                    $applicationData['payment'] = array_merge($applicationData['payment'] ?? [], [
                        'transaction_id' => $transactionId,
                        'payment_id' => $payuPaymentId ?? $paymentTransaction->payment_id,
                        'status' => 'success',
                        'paid_at' => now('Asia/Kolkata')->toDateTimeString(),
                        'bank_ref_num' => $bankRefNum,
                        'mode' => $mode,
                        'webhook_confirmed' => true,
                    ]);
                    $application->update(['application_data' => $applicationData]);
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
            'board_resolution_file',
            'whois_details_file',
            'pan_document_file',
            'gstin_document_file',
            'msme_document_file',
            'incorporation_document_file',
            'authorized_rep_document_file',
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

        $memberType = null;
        if (isset($validated['member_type'])) {
            $memberType = $validated['member_type'] === 'others'
                ? ($validated['member_type_other'] ?? 'Others')
                : strtoupper($validated['member_type']);
        }

        // Prepare application data
        $applicationData = [];

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

        $applicationData['member_type'] = $memberType;
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
}
