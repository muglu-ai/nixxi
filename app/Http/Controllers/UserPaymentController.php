<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Registration;
use App\Services\PayuService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserPaymentController extends Controller
{
    /**
     * Display pending payments list.
     */
    public function pending()
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (!$user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            // Get all pending and partial invoices with applications
            $pendingInvoices = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where(function ($q) {
                $q->where('status', 'pending')
                  ->orWhere('payment_status', 'partial');
            })
            ->with(['application'])
            ->latest('due_date')
            ->get();

            // Calculate total outstanding amount (use balance_amount for partial payments)
            $outstandingAmount = $pendingInvoices->sum(function ($invoice) {
                return $invoice->balance_amount ?? $invoice->total_amount;
            });

            return view('user.payments.pending', compact('user', 'pendingInvoices', 'outstandingAmount'));
        } catch (Exception $e) {
            Log::error('Error loading pending payments: '.$e->getMessage());

            return redirect()->route('user.dashboard')
                ->with('error', 'Unable to load pending payments.');
        }
    }

    /**
     * Pay for a single invoice.
     */
    public function payNow(Request $request, $invoiceId)
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
            ->where('status', 'pending')
            ->with(['application'])
            ->findOrFail($invoiceId);

            $application = $invoice->application;

            // Generate PayU payment link
            $payuService = new PayuService();
            $transactionId = 'INV-'.time().'-'.strtoupper(Str::random(8));

            // Create PaymentTransaction for invoice payment
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'application_id' => $application->id,
                'transaction_id' => $transactionId,
                'payment_status' => 'pending',
                'payment_mode' => 'live',
                'amount' => $invoice->total_amount,
                'currency' => 'INR',
                'product_info' => 'NIXI IX Service Invoice - '.$invoice->invoice_number,
                'response_message' => 'Invoice payment pending',
            ]);

            // Parse PayU payment link from invoice
            $payuPaymentData = json_decode($invoice->payu_payment_link, true);
            
            // Update payment data with new transaction ID
            $paymentData = $payuService->preparePaymentData([
                'transaction_id' => $transactionId,
                'amount' => $invoice->total_amount,
                'product_info' => 'NIXI IX Service Invoice - '.$invoice->invoice_number,
                'firstname' => $user->fullname,
                'email' => $user->email,
                'phone' => $user->mobile,
                'success_url' => url(route('user.applications.ix.payment-success', [], false)),
                'failure_url' => url(route('user.applications.ix.payment-failure', [], false)),
                'udf1' => $application->application_id,
                'udf2' => (string) $paymentTransaction->id,
                'udf3' => $invoice->invoice_number, // Invoice number for identification
            ]);

            // Redirect to PayU payment page
            return redirect($payuService->getPaymentUrl())->with('payment_data', $paymentData);
        } catch (Exception $e) {
            Log::error('Error initiating payment for invoice '.$invoiceId.': '.$e->getMessage());

            return redirect()->route('user.payments.pending')
                ->with('error', 'Unable to initiate payment. Please try again.');
        }
    }

    /**
     * Pay for all pending invoices at once.
     */
    public function payAll(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (!$user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            // Get all pending invoices
            $pendingInvoices = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('status', 'pending')
            ->with(['application'])
            ->get();

            if ($pendingInvoices->isEmpty()) {
                return redirect()->route('user.payments.pending')
                    ->with('info', 'No pending invoices to pay.');
            }

            // Calculate total amount
            $totalAmount = $pendingInvoices->sum('total_amount');
            
            // Get first application (for payment transaction)
            $firstApplication = $pendingInvoices->first()->application;

            // Generate PayU payment link
            $payuService = new PayuService();
            $transactionId = 'BULK-'.time().'-'.strtoupper(Str::random(8));

            // Create PaymentTransaction for bulk payment
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'application_id' => $firstApplication->id,
                'transaction_id' => $transactionId,
                'payment_status' => 'pending',
                'payment_mode' => 'live',
                'amount' => $totalAmount,
                'currency' => 'INR',
                'product_info' => 'NIXI IX Service - Bulk Payment for '.$pendingInvoices->count().' invoices',
                'response_message' => 'Bulk invoice payment pending',
            ]);

            // Store invoice IDs in payment transaction metadata for processing after payment
            $invoiceIds = $pendingInvoices->pluck('id')->toArray();
            $paymentTransaction->update([
                'product_info' => json_encode([
                    'type' => 'bulk_invoice_payment',
                    'invoice_ids' => $invoiceIds,
                    'invoice_count' => count($invoiceIds),
                ]),
            ]);

            // Prepare payment data
            $paymentData = $payuService->preparePaymentData([
                'transaction_id' => $transactionId,
                'amount' => $totalAmount,
                'product_info' => 'NIXI IX Service - Bulk Payment for '.$pendingInvoices->count().' invoices',
                'firstname' => $user->fullname,
                'email' => $user->email,
                'phone' => $user->mobile,
                'success_url' => url(route('user.applications.ix.payment-success', [], false)),
                'failure_url' => url(route('user.applications.ix.payment-failure', [], false)),
                'udf1' => $firstApplication->application_id,
                'udf2' => (string) $paymentTransaction->id,
                'udf3' => 'BULK-'.implode(',', $invoiceIds), // Store invoice IDs
            ]);

            // Store payment data in cookies for callback
            $cookieData = [
                'payment_transaction_id' => $paymentTransaction->id,
                'transaction_id' => $transactionId,
                'application_id' => $firstApplication->id,
                'user_id' => $user->id,
                'amount' => $totalAmount,
                'bulk_payment' => true,
                'invoice_ids' => $invoiceIds,
            ];

            $userSessionData = [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->fullname,
                'user_registration_id' => $user->registrationid,
            ];

            $response = response()->view('user.payments.redirect-payu', [
                'paymentUrl' => $payuService->getPaymentUrl(),
                'paymentData' => $paymentData,
            ]);

            // Set cookies for callback handling
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
        } catch (Exception $e) {
            Log::error('Error initiating bulk payment: '.$e->getMessage());

            return redirect()->route('user.payments.pending')
                ->with('error', 'Unable to initiate bulk payment. Please try again.');
        }
    }
}
