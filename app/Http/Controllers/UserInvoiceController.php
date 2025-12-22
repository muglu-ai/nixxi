<?php

namespace App\Http\Controllers;

use App\Models\GstVerification;
use App\Models\Invoice;
use App\Models\IxApplicationPricing;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserInvoiceController extends Controller
{
    /**
     * Display list of user's invoices.
     */
    public function index()
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (!$user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            // Get all invoices for user's applications
            $invoices = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['application', 'generatedBy'])
            ->latest('invoice_date')
            ->paginate(15);

            return view('user.invoices.index', compact('user', 'invoices'));
        } catch (Exception $e) {
            Log::error('Error loading user invoices: '.$e->getMessage());

            return redirect()->route('user.dashboard')
                ->with('error', 'Unable to load invoices.');
        }
    }

    /**
     * Download invoice PDF.
     */
    public function download($id)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (!$user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            $invoice = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->findOrFail($id);

            // Sanitize filename - replace / and \ with safe characters
            $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number).'_invoice.pdf';

            // Try to get PDF from invoice pdf_path first
            if ($invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
                $filePath = Storage::disk('public')->path($invoice->pdf_path);
                return response()->download($filePath, $safeFilename);
            }

            // Fallback: Check application_data for PDF path (for older invoices)
            $application = $invoice->application;
            $appData = $application->application_data ?? [];
            $pdfs = $appData['pdfs'] ?? [];
            
            if (isset($pdfs['invoice_pdf']) && Storage::disk('public')->exists($pdfs['invoice_pdf'])) {
                $filePath = Storage::disk('public')->path($pdfs['invoice_pdf']);
                return response()->download($filePath, $safeFilename);
            }

            // If PDF doesn't exist, generate it on the fly
            if ($application->application_type === 'IX') {
                // Use reflection or create a service, but for now, let's use the view directly
                $data = $application->application_data ?? [];
                $user = $application->user;
                
                // Check if this is first application or subsequent
                $isFirstApplication = \App\Models\Application::where('user_id', $user->id)
                    ->where('application_type', 'IX')
                    ->where('id', '<', $application->id)
                    ->doesntExist();
                
                // Get buyer details
                $buyerDetails = [];
                $gstVerification = null;
                
                if ($isFirstApplication) {
                    // First application: Get from KYC
                    $kyc = \App\Models\UserKycProfile::where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->first();
                    
                    if ($kyc && $kyc->gst_verification_id) {
                        $gstVerification = \App\Models\GstVerification::find($kyc->gst_verification_id);
                    }
                } else {
                    // Subsequent application: Get from GST verification used in this application
                    if ($application->gst_verification_id) {
                        $gstVerification = \App\Models\GstVerification::find($application->gst_verification_id);
                    } else {
                        // Fallback: Get latest verified GST for this application's GSTIN
                        $applicationGstin = $data['gstin'] ?? null;
                        if ($applicationGstin) {
                            $gstVerification = \App\Models\GstVerification::where('user_id', $user->id)
                                ->where('gstin', $applicationGstin)
                                ->where('is_verified', true)
                                ->latest()
                                ->first();
                        }
                    }
                }
                
                // If still no GST verification, get latest one
                if (!$gstVerification) {
                    $gstVerification = \App\Models\GstVerification::where('user_id', $user->id)
                        ->where('is_verified', true)
                        ->latest()
                        ->first();
                }
                
                // Build buyer details
                if ($gstVerification) {
                    $buyerDetails = [
                        'company_name' => $gstVerification->legal_name ?? $gstVerification->trade_name ?? $user->fullname,
                        'pan' => $gstVerification->pan ?? $user->pancardno,
                        'gstin' => $gstVerification->gstin,
                        'state' => $gstVerification->state,
                    ];
                    
                    // Get billing address from GST API response
                    if ($gstVerification->verification_data) {
                        $verificationData = is_string($gstVerification->verification_data)
                            ? json_decode($gstVerification->verification_data, true)
                            : $gstVerification->verification_data;
                        
                        if (isset($verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'])) {
                            $address = $verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'];
                            $buyerDetails['address'] = trim(($address['door_number'] ?? '').' '.($address['building_name'] ?? '').' '.($address['street'] ?? '').' '.($address['location'] ?? '').' '.($address['dst'] ?? '').' '.($address['city'] ?? '').' '.($address['state_name'] ?? '').' '.($address['pincode'] ?? ''));
                            $buyerDetails['state_name'] = $address['state_name'] ?? $gstVerification->state;
                        } else {
                            $buyerDetails['address'] = $gstVerification->primary_address ?? '';
                        }
                    } else {
                        $buyerDetails['address'] = $gstVerification->primary_address ?? '';
                    }
                    
                    // Get phone and email from user
                    $buyerDetails['phone'] = $user->mobile ?? '';
                    $buyerDetails['email'] = $user->email ?? '';
                } else {
                    // Fallback to user data
                    $buyerDetails = [
                        'company_name' => $user->fullname,
                        'pan' => $user->pancardno,
                        'gstin' => $data['gstin'] ?? 'N/A',
                        'address' => '',
                        'phone' => $user->mobile ?? '',
                        'email' => $user->email ?? '',
                        'state' => null,
                        'state_name' => null,
                    ];
                }
                
                // Get place of supply from IX location
                $placeOfSupply = null;
                if (isset($data['location']['id'])) {
                    $location = \App\Models\IxLocation::find($data['location']['id']);
                    if ($location) {
                        $placeOfSupply = $location->state;
                    }
                }
                
                // If no location in data, try to get from application
                if (!$placeOfSupply && isset($data['location']['state'])) {
                    $placeOfSupply = $data['location']['state'];
                }
                
                // Fallback to buyer state
                if (!$placeOfSupply) {
                    $placeOfSupply = $buyerDetails['state_name'] ?? $buyerDetails['state'] ?? 'N/A';
                }
                
                // Get Attn (Authorized Representative Name)
                $attnName = null;
                if ($isFirstApplication) {
                    // First application: Get from KYC
                    $kyc = \App\Models\UserKycProfile::where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->first();
                    if ($kyc && $kyc->contact_name) {
                        $attnName = $kyc->contact_name;
                    }
                } else {
                    // Subsequent application: Get from form (representative name)
                    if (isset($data['representative']['name'])) {
                        $attnName = $data['representative']['name'];
                    }
                }
                
                // Fallback to user name if no representative found
                if (!$attnName) {
                    $attnName = $buyerDetails['company_name'] ?? $user->fullname;
                }
                
                $applicationPricing = \App\Models\IxApplicationPricing::getActive();
                
                $invoicePdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('user.applications.ix.pdf.invoice', [
                    'application' => $application,
                    'user' => $user,
                    'data' => $data,
                    'buyerDetails' => $buyerDetails,
                    'placeOfSupply' => $placeOfSupply,
                    'attnName' => $attnName,
                    'invoiceNumber' => $invoice->invoice_number,
                    'invoiceDate' => $invoice->invoice_date->format('d/m/Y'),
                    'dueDate' => $invoice->due_date->format('d/m/Y'),
                    'invoice' => $invoice,
                    'gstVerification' => $gstVerification,
                ])->setPaper('a4', 'portrait')
                    ->setOption('enable-local-file-access', true);
                
                return $invoicePdf->download($safeFilename);
            } else {
                // For IRIN applications, check if PDF exists in application_data
                $appData = $application->application_data ?? [];
                $pdfs = $appData['pdfs'] ?? [];
                
                if (isset($pdfs['invoice_pdf']) && Storage::disk('public')->exists($pdfs['invoice_pdf'])) {
                    $filePath = Storage::disk('public')->path($pdfs['invoice_pdf']);
                    return response()->download($filePath, $safeFilename);
                }
                
                return redirect()->route('user.invoices.index')
                    ->with('error', 'Invoice PDF not found. Please contact support.');
            }
        } catch (Exception $e) {
            Log::error('Error downloading invoice PDF: '.$e->getMessage());

            return redirect()->route('user.invoices.index')
                ->with('error', 'Unable to download invoice PDF.');
        }
    }
}
