<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\PlanChangeHistory;
use App\Models\PlanChangeRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminPlanChangeRequestController extends Controller
{
    /**
     * Display list of plan change requests.
     */
    public function index(Request $request)
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::find($adminId);

            if (!$admin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Admin session expired. Please login again.');
            }

            $query = PlanChangeRequest::with(['application.user', 'user', 'reviewedBy']);

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by change type
            if ($request->filled('change_type')) {
                $query->where('change_type', $request->change_type);
            }

            $requests = $query->latest()->paginate(20)->withQueryString();

            return view('admin.plan-change.index', compact('admin', 'requests'));
        } catch (Exception $e) {
            Log::error('Error loading plan change requests: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load plan change requests.');
        }
    }

    /**
     * Show plan change request details.
     */
    public function show($id)
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::find($adminId);

            if (!$admin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Admin session expired. Please login again.');
            }

            $request = PlanChangeRequest::with(['application.user', 'user', 'reviewedBy', 'history'])
                ->findOrFail($id);

            return view('admin.plan-change.show', compact('admin', 'request'));
        } catch (Exception $e) {
            Log::error('Error loading plan change request details: '.$e->getMessage());

            return redirect()->route('admin.plan-change.index')
                ->with('error', 'Unable to load plan change request details.');
        }
    }

    /**
     * Approve plan change request.
     */
    public function approve(Request $request, $id)
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::find($adminId);

            if (!$admin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Admin session expired. Please login again.');
            }

            $planChangeRequest = PlanChangeRequest::with('application')->findOrFail($id);

            if ($planChangeRequest->status !== 'pending') {
                return back()->with('error', 'This request has already been processed.');
            }

            $validated = $request->validate([
                'admin_notes' => 'nullable|string|max:1000',
                'effective_from' => 'nullable|date|after_or_equal:today',
            ]);

            DB::beginTransaction();

            $application = $planChangeRequest->application;
            $appData = $application->application_data ?? [];
            
            // Determine effective_from date
            // For billing cycle changes: effective after current paid period ends
            // For capacity changes: can be immediate or future date
            $effectiveFrom = $validated['effective_from'] 
                ? \Carbon\Carbon::parse($validated['effective_from']) 
                : now('Asia/Kolkata');
            
            // If it's a billing cycle change only (no capacity change), 
            // set effective_from to after current paid period
            if ($planChangeRequest->isBillingCycleChangeOnly()) {
                // Find the last paid invoice to determine when current paid period ends
                $lastPaidInvoice = Invoice::where('application_id', $application->id)
                    ->where('status', 'paid')
                    ->latest('invoice_date')
                    ->first();
                
                if ($lastPaidInvoice && $lastPaidInvoice->due_date) {
                    // Billing cycle change takes effect after current paid period
                    $effectiveFrom = \Carbon\Carbon::parse($lastPaidInvoice->due_date)->addDay();
                    Log::info("Billing cycle change for application {$application->id}: will take effect after paid period ends on {$effectiveFrom->format('Y-m-d')}");
                }
            }

            // Update request status
            $planChangeRequest->update([
                'status' => 'approved',
                'admin_notes' => $validated['admin_notes'] ?? null,
                'reviewed_by' => $adminId,
                'reviewed_at' => now('Asia/Kolkata'),
                'effective_from' => $effectiveFrom,
            ]);

            // Update application data immediately for capacity changes
            // For billing cycle changes, the new cycle will be used in next invoice generation
            if ($planChangeRequest->isCapacityChange()) {
                // Capacity change: update immediately
                $appData['port_selection'] = [
                    'capacity' => $planChangeRequest->new_port_capacity,
                    'billing_plan' => $planChangeRequest->new_billing_plan ?? $appData['port_selection']['billing_plan'] ?? 'monthly',
                    'amount' => $planChangeRequest->new_amount,
                    'currency' => 'INR',
                ];

                $application->update([
                    'application_data' => $appData,
                    'assigned_port_capacity' => $planChangeRequest->new_port_capacity,
                ]);
            } else {
                // Billing cycle change only: update billing_cycle but it takes effect after current paid period
                $application->update([
                    'billing_cycle' => $planChangeRequest->new_billing_plan,
                ]);
                Log::info("Billing cycle change approved for application {$application->id}: new cycle '{$planChangeRequest->new_billing_plan}' will be used after {$effectiveFrom->format('Y-m-d')}");
            }

            // Log application status history
            ApplicationStatusHistory::log(
                $application->id,
                $application->status,
                $application->status, // Status remains same
                'admin',
                $adminId,
                "Plan change approved: {$planChangeRequest->current_port_capacity} ({$planChangeRequest->current_billing_plan}) → {$planChangeRequest->new_port_capacity} ({$planChangeRequest->new_billing_plan}). Adjustment: ₹" . number_format($planChangeRequest->adjustment_amount, 2)
            );

            // Log plan change history
            PlanChangeHistory::create([
                'plan_change_request_id' => $planChangeRequest->id,
                'application_id' => $application->id,
                'old_data' => [
                    'port_capacity' => $planChangeRequest->current_port_capacity,
                    'billing_plan' => $planChangeRequest->current_billing_plan,
                    'amount' => $planChangeRequest->current_amount,
                ],
                'new_data' => [
                    'port_capacity' => $planChangeRequest->new_port_capacity,
                    'billing_plan' => $planChangeRequest->new_billing_plan,
                    'amount' => $planChangeRequest->new_amount,
                ],
                'action' => 'approved',
                'performed_by' => "Admin: {$admin->name}",
                'notes' => $validated['admin_notes'] ?? 'Plan change approved by admin.',
            ]);

            // Send message to user
            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Plan Change Approved - '.$application->application_id,
                'message' => "Your plan change request for application {$application->application_id} has been approved. New plan: {$planChangeRequest->new_port_capacity} ({$planChangeRequest->new_billing_plan}). Adjustment Amount: ₹" . number_format($planChangeRequest->adjustment_amount, 2) . ". " . ($validated['admin_notes'] ?? ''),
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            DB::commit();

            return redirect()->route('admin.plan-change.show', $id)
                ->with('success', 'Plan change request approved successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error approving plan change request: '.$e->getMessage());

            return back()->with('error', 'Failed to approve plan change request. Please try again.');
        }
    }

    /**
     * Reject plan change request.
     */
    public function reject(Request $request, $id)
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::find($adminId);

            if (!$admin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Admin session expired. Please login again.');
            }

            $planChangeRequest = PlanChangeRequest::with('application')->findOrFail($id);

            if ($planChangeRequest->status !== 'pending') {
                return back()->with('error', 'This request has already been processed.');
            }

            $validated = $request->validate([
                'admin_notes' => 'required|string|min:10|max:1000',
            ]);

            DB::beginTransaction();

            // Update request status
            $planChangeRequest->update([
                'status' => 'rejected',
                'admin_notes' => $validated['admin_notes'],
                'reviewed_by' => $adminId,
                'reviewed_at' => now('Asia/Kolkata'),
            ]);

            // Log plan change history
            PlanChangeHistory::create([
                'plan_change_request_id' => $planChangeRequest->id,
                'application_id' => $planChangeRequest->application_id,
                'old_data' => [
                    'port_capacity' => $planChangeRequest->current_port_capacity,
                    'billing_plan' => $planChangeRequest->current_billing_plan,
                    'amount' => $planChangeRequest->current_amount,
                ],
                'new_data' => null,
                'action' => 'rejected',
                'performed_by' => "Admin: {$admin->name}",
                'notes' => $validated['admin_notes'],
            ]);

            // Send message to user
            $application = $planChangeRequest->application;
            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Plan Change Request Rejected - '.$application->application_id,
                'message' => "Your plan change request for application {$application->application_id} has been rejected. Reason: {$validated['admin_notes']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            DB::commit();

            return redirect()->route('admin.plan-change.show', $id)
                ->with('success', 'Plan change request rejected.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting plan change request: '.$e->getMessage());

            return back()->with('error', 'Failed to reject plan change request. Please try again.');
        }
    }
}
