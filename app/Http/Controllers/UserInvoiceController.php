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

            // Try to get PDF from invoice pdf_path first
            if ($invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
                $filePath = Storage::disk('public')->path($invoice->pdf_path);
                return response()->download($filePath, $invoice->invoice_number.'_invoice.pdf');
            }

            // Fallback: Check application_data for PDF path (for older invoices)
            $application = $invoice->application;
            $appData = $application->application_data ?? [];
            $pdfs = $appData['pdfs'] ?? [];
            
            if (isset($pdfs['invoice_pdf']) && Storage::disk('public')->exists($pdfs['invoice_pdf'])) {
                $filePath = Storage::disk('public')->path($pdfs['invoice_pdf']);
                return response()->download($filePath, $invoice->invoice_number.'_invoice.pdf');
            }

            // If PDF doesn't exist, generate it on the fly
            if ($application->application_type === 'IX') {
                // Use reflection or create a service, but for now, let's use the view directly
                $data = $application->application_data ?? [];
                $user = $application->user;
                
                $gstVerification = \App\Models\GstVerification::where('user_id', $user->id)
                    ->where('is_verified', true)
                    ->latest()
                    ->first();
                
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
                }
                
                $applicationPricing = \App\Models\IxApplicationPricing::getActive();
                
                $invoicePdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('user.applications.ix.pdf.invoice', [
                    'application' => $application,
                    'user' => $user,
                    'data' => $data,
                    'companyDetails' => $companyDetails,
                    'applicationPricing' => $applicationPricing,
                    'invoiceNumber' => $invoice->invoice_number,
                    'invoiceDate' => $invoice->invoice_date->format('d/m/Y'),
                    'dueDate' => $invoice->due_date->format('d/m/Y'),
                    'invoice' => $invoice,
                ])->setPaper('a4', 'portrait')
                    ->setOption('enable-local-file-access', true);
                
                return $invoicePdf->download($invoice->invoice_number.'_invoice.pdf');
            } else {
                // For IRIN applications, check if PDF exists in application_data
                $appData = $application->application_data ?? [];
                $pdfs = $appData['pdfs'] ?? [];
                
                if (isset($pdfs['invoice_pdf']) && Storage::disk('public')->exists($pdfs['invoice_pdf'])) {
                    $filePath = Storage::disk('public')->path($pdfs['invoice_pdf']);
                    return response()->download($filePath, $invoice->invoice_number.'_invoice.pdf');
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
