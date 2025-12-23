<?php

namespace App\Http\Controllers;

use App\Mail\IxApplicationInvoiceMail;
use App\Mail\ProfileUpdateApprovedMail;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\GstVerification;
use App\Models\IxApplicationPricing;
use App\Models\IxLocation;
use App\Models\IxPortPricing;
use App\Models\McaVerification;
use App\Models\Message;
use App\Models\PanVerification;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\PaymentVerificationLog;
use App\Models\PlanChangeRequest;
use App\Models\ProfileUpdateRequest;
use App\Models\Registration;
use App\Models\RocIecVerification;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\UdyamVerification;
use App\Models\UserKycProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PDOException;

class AdminController extends Controller
{
    /**
     * Get current admin with roles.
     */
    protected function getCurrentAdmin()
    {
        $adminId = session('admin_id');

        return Admin::with('roles')->findOrFail($adminId);
    }

    /**
     * Check if admin has a specific role.
     */
    protected function hasRole(Admin $admin, string $roleSlug): bool
    {
        return $admin->hasRole($roleSlug);
    }

    /**
     * Display the Admin dashboard.
     */
    public function index(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $adminId = $admin->id;

            // Get selected role from query parameter or session
            $selectedRole = $request->get('role', session('admin_selected_role', null));

            // If admin has multiple roles and no role is selected, auto-select based on priority
            if ($admin->roles->count() > 1 && ! $selectedRole) {
                // Priority order: new IX workflow roles first, then legacy
                $priorityOrder = [
                    'ix_processor', 'ix_legal', 'ix_head', 'ceo', 'nodal_officer', 'ix_tech_team', 'ix_account',
                    'processor', 'finance', 'technical',
                ];
                foreach ($priorityOrder as $priorityRole) {
                    if ($admin->hasRole($priorityRole)) {
                        $selectedRole = $priorityRole;
                        break;
                    }
                }
            }

            // Validate selected role belongs to admin
            if ($selectedRole && ! $admin->hasRole($selectedRole)) {
                $selectedRole = null;
            }

            // Store selected role in session
            if ($selectedRole) {
                session(['admin_selected_role' => $selectedRole]);
            }

            // Calculate statistics (all applications visible, is_active shows live status)
            $totalUsers = Registration::count();
            $totalApplications = Application::count();
            $approvedApplications = Application::whereIn('status', ['approved', 'payment_verified'])->count();
            $pendingPlanChanges = \App\Models\PlanChangeRequest::where('status', 'pending')->count();
            
            // Approved applications with payment verification
            $approvedApplicationsWithPayment = Application::whereIn('status', ['approved', 'payment_verified'])
                ->whereHas('paymentTransactions', function ($q) {
                    $q->where('payment_status', 'success');
                })
                ->count();
            
            // Member Statistics (Registrations that have at least one application with membership_id)
            // Live members: is_active = true
            $totalMembers = Registration::whereHas('applications', function ($query) {
                $query->whereNotNull('membership_id');
            })->distinct()->count();
            
            // Live members: Have membership_id AND is_active = true
            $activeMembers = Registration::whereHas('applications', function ($query) {
                $query->whereNotNull('membership_id')
                    ->where('is_active', true);
            })->distinct()->count();
            
            // Not live members: Have membership_id but is_active = false
            $disconnectedMembers = Registration::whereHas('applications', function ($query) {
                $query->whereNotNull('membership_id')
                    ->where('is_active', false);
            })->distinct()->count();
            
            // Recent Live Members (applications with membership_id and is_active = true, ordered by most recent)
            $recentLiveMembers = Application::with('user')
                ->whereNotNull('membership_id')
                ->where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->take(10)
                ->get();
            
            // IX Points Statistics
            $totalIxPoints = IxLocation::where('is_active', true)->count();
            $edgeIxPoints = IxLocation::where('is_active', true)->where('node_type', 'edge')->count();
            $metroIxPoints = IxLocation::where('is_active', true)->where('node_type', 'metro')->count();
            
            // Grievance Tracking
            $openGrievances = Ticket::whereIn('status', ['open', 'assigned', 'in_progress'])->count();
            $pendingGrievances = Ticket::where('status', 'assigned')->count();
            
            // Plan Change Requests
            $pendingPlanChanges = \App\Models\PlanChangeRequest::where('status', 'pending')->count();

            // Pending applications based on selected role (all visible, is_active shows live status)
            $pendingApplications = 0;
            $roleToUse = $selectedRole;
            if ($admin->roles->count() === 1) {
                $roleToUse = $admin->roles->first()->slug;
            }

            // New IX workflow roles
            if ($roleToUse === 'ix_processor') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->whereIn('status', ['submitted', 'resubmitted', 'processor_resubmission', 'legal_sent_back', 'head_sent_back'])
                    ->count();
            } elseif ($roleToUse === 'ix_legal') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('status', 'processor_forwarded_legal')
                    ->count();
            } elseif ($roleToUse === 'ix_head') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('status', 'legal_forwarded_head')
                    ->count();
            } elseif ($roleToUse === 'ceo') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('status', 'head_forwarded_ceo')
                    ->count();
            } elseif ($roleToUse === 'nodal_officer') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('status', 'ceo_approved')
                    ->count();
            } elseif ($roleToUse === 'ix_tech_team') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('status', 'port_assigned')
                    ->count();
            } elseif ($roleToUse === 'ix_account') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->whereIn('status', ['ip_assigned', 'invoice_pending'])
                    ->count();
            } elseif ($roleToUse === 'processor') {
                // Legacy
                $pendingApplications = Application::whereIn('status', ['pending', 'processor_review'])
                    ->count();
            } elseif ($roleToUse === 'finance') {
                // Legacy
                $pendingApplications = Application::whereIn('status', ['processor_approved', 'finance_review'])
                    ->count();
            } elseif ($roleToUse === 'technical') {
                // Legacy
                $pendingApplications = Application::where('status', 'finance_approved')
                    ->count();
            } else {
                // If no role selected, show all pending IX applications
                $pendingApplications = Application::where('application_type', 'IX')
                    ->whereNotIn('status', ['approved', 'rejected', 'ceo_rejected', 'payment_verified'])
                    ->count();
            }

            $recentUsers = Registration::latest()->take(10)->get();

            return view('admin.dashboard', compact(
                'admin',
                'totalUsers',
                'totalApplications',
                'approvedApplications',
                'approvedApplicationsWithPayment',
                'pendingApplications',
                'selectedRole',
                'recentUsers',
                'totalMembers',
                'activeMembers',
                'disconnectedMembers',
                'recentLiveMembers',
                'totalIxPoints',
                'edgeIxPoints',
                'metroIxPoints',
                'openGrievances',
                'pendingGrievances',
                'pendingPlanChanges'
            ));
        } catch (QueryException $e) {
            Log::error('Database error loading Admin dashboard: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading Admin dashboard: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading Admin dashboard: '.$e->getMessage());
            abort(500, 'Unable to load dashboard. Please try again later.');
        }
    }

    /**
     * Display all users.
     */
    public function users(Request $request)
    {
        try {
            $query = Registration::with(['messages', 'profileUpdateRequests']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('fullname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('pancardno', 'like', "%{$search}%")
                        ->orWhere('registrationid', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            }

            $users = $query->latest()->paginate(20)->withQueryString();

            return view('admin.users.index', compact('users'));
        } catch (QueryException $e) {
            Log::error('Database error loading users: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading users: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading users: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load users. Please try again.');
        }
    }

    /**
     * Display user details with full history.
     */
    public function showUser($id, Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $user = Registration::with([
                'messages',
                'profileUpdateRequests.approver',
                'profileUpdateRequests' => function ($query) {
                    $query->with('approver')->latest();
                },
                'applications' => function ($query) {
                    $query->whereNotNull('membership_id')->latest();
                },
            ])->findOrFail($id);

            // Check if this is a member (has applications with membership_id)
            $isMember = $user->applications->whereNotNull('membership_id')->count() > 0;
            // Check if accessed from members page
            $fromMembersPage = $request->get('from', '') === 'members';

            // Get all admin actions related to this user
            $adminActions = AdminAction::where('actionable_type', Registration::class)
                ->where('actionable_id', $id)
                ->orWhere(function ($query) use ($user) {
                    $query->where('actionable_type', ProfileUpdateRequest::class)
                        ->whereIn('actionable_id', $user->profileUpdateRequests->pluck('id'));
                })
                ->orWhere(function ($query) use ($user) {
                    $query->where('actionable_type', Message::class)
                        ->whereIn('actionable_id', $user->messages->pluck('id'));
                })
                ->with(['admin', 'superAdmin'])
                ->latest()
                ->get();

            return view('admin.users.show', compact('user', 'adminActions', 'admin', 'isMember', 'fromMembersPage'));
        } catch (QueryException $e) {
            Log::error('Database error loading user details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading user details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading user details: '.$e->getMessage());

            return redirect()->route('admin.users')
                ->with('error', 'User not found.');
        }
    }

    /**
     * Send message to user.
     */
    public function sendMessage(Request $request, $userId)
    {
        try {
            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'message' => 'required|string|min:10',
            ], [
                'subject.required' => 'Subject is required.',
                'message.required' => 'Message is required.',
                'message.min' => 'Message must be at least 10 characters.',
            ]);

            $user = Registration::findOrFail($userId);
            $adminId = session('admin_id');

            $message = Message::create([
                'user_id' => $user->id,
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            // Log action
            AdminAction::log(
                $adminId,
                'sent_message',
                $message,
                "Sent message to user: {$user->fullname}",
                ['subject' => $validated['subject']]
            );

            return back()->with('success', 'Message sent successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error sending message: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error sending message: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error sending message: '.$e->getMessage());

            return back()->with('error', 'An error occurred while sending message. Please try again.');
        }
    }

    /**
     * Approve profile update request.
     */
    public function approveProfileUpdate($requestId)
    {
        try {
            $request = ProfileUpdateRequest::with('user')->findOrFail($requestId);
            $adminId = session('admin_id');

            $request->update([
                'status' => 'approved',
                'approved_at' => now('Asia/Kolkata'),
                'approved_by' => $adminId,
            ]);

            // Log action
            AdminAction::log(
                $adminId,
                'approved_profile_update',
                $request,
                "Approved profile update request for user: {$request->user->fullname}",
                ['user_id' => $request->user->id]
            );

            return back()->with('success', 'Profile update request approved!');
        } catch (QueryException $e) {
            Log::error('Database error approving profile update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error approving profile update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error approving profile update: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Approve submitted profile update (apply changes to user).
     */
    public function approveSubmittedUpdate($requestId)
    {
        try {
            $updateRequest = ProfileUpdateRequest::with('user')->findOrFail($requestId);
            $adminId = session('admin_id');

            // Check if there's submitted data waiting for approval
            if (! $updateRequest->submitted_data || $updateRequest->update_approved) {
                return back()->with('error', 'This update has already been processed or has no submitted data.');
            }

            $user = $updateRequest->user;
            $submittedData = $updateRequest->submitted_data;

            // Get old email before update (to send email to new email)
            $oldEmail = $user->email;
            $newEmail = $submittedData['email'] ?? $user->email;

            // Apply the submitted changes to user
            $user->update($submittedData);

            // Mark the update as approved
            $updateRequest->update([
                'update_approved' => true,
                'update_approved_at' => now('Asia/Kolkata'),
            ]);

            // Log action
            AdminAction::log(
                $adminId,
                'approved_submitted_update',
                $updateRequest,
                "Approved and applied profile update for user: {$user->fullname}",
                ['user_id' => $user->id, 'changes' => $submittedData]
            );

            // Send message to user
            Message::create([
                'user_id' => $user->id,
                'subject' => 'Profile Update Approved',
                'message' => 'Your profile update has been approved and applied. Your profile information has been updated successfully.',
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            // Send email to updated email address
            try {
                Mail::to($newEmail)->send(new ProfileUpdateApprovedMail($submittedData));
                Log::info("Profile update approved email sent to {$newEmail}");
            } catch (Exception $e) {
                Log::error('Failed to send profile update approved email: '.$e->getMessage());
                // Don't fail the approval if email fails
            }

            return back()->with('success', 'Profile update approved and applied successfully!');
        } catch (QueryException $e) {
            Log::error('Database error approving submitted update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error approving submitted update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error approving submitted update: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Reject profile update request.
     */
    public function rejectProfileUpdate(Request $request, $requestId)
    {
        try {
            $validated = $request->validate([
                'admin_notes' => 'required|string|min:10',
            ], [
                'admin_notes.required' => 'Please provide a reason for rejection.',
                'admin_notes.min' => 'Please provide more details (minimum 10 characters).',
            ]);

            $updateRequest = ProfileUpdateRequest::with('user')->findOrFail($requestId);
            $adminId = session('admin_id');

            $updateRequest->update([
                'status' => 'rejected',
                'rejected_at' => now('Asia/Kolkata'),
                'admin_notes' => $validated['admin_notes'],
                'approved_by' => $adminId,
            ]);

            // Log action
            AdminAction::log(
                $adminId,
                'rejected_profile_update',
                $updateRequest,
                "Rejected profile update request for user: {$updateRequest->user->fullname}",
                ['reason' => $validated['admin_notes']]
            );

            return back()->with('success', 'Profile update request rejected.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error rejecting profile update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error rejecting profile update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error rejecting profile update: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Update user status.
     */
    public function updateUserStatus(Request $request, $userId)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,approved,rejected,active,inactive',
            ]);

            $user = Registration::findOrFail($userId);
            $oldStatus = $user->status;
            $user->update(['status' => $validated['status']]);

            // Log action
            AdminAction::log(
                session('admin_id'),
                'updated_user_status',
                $user,
                "Changed user status from {$oldStatus} to {$validated['status']}",
                ['old_status' => $oldStatus, 'new_status' => $validated['status']]
            );

            $statusMessage = $validated['status'] === 'inactive' ? 'Member deactivated successfully!' : 'User status updated successfully!';

            return back()->with('success', $statusMessage);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (QueryException $e) {
            Log::error('Database error updating user status: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error updating user status: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error updating user status: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Show members list with filters (active, disconnected, all).
     */
    public function members(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $filter = $request->get('filter', 'all'); // all, active, disconnected

            $query = Registration::whereHas('applications', function ($query) {
                $query->whereNotNull('membership_id');
            });

            if ($filter === 'active') {
                // Live members: is_active = true
                $query->whereHas('applications', function ($q) {
                    $q->whereNotNull('membership_id')
                        ->where('is_active', true);
                });
            } elseif ($filter === 'disconnected') {
                // Not live members: is_active = false
                $query->whereHas('applications', function ($q) {
                    $q->whereNotNull('membership_id')
                        ->where('is_active', false);
                });
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('fullname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('registrationid', 'like', "%{$search}%")
                        ->orWhere('pancardno', 'like', "%{$search}%");
                });
            }

            // Show all applications (including inactive) for management purposes
            $members = $query->with(['applications' => function ($q) {
                $q->whereNotNull('membership_id')
                    ->latest();
            }])->distinct()->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

            return view('admin.members.index', compact('members', 'admin', 'filter'));
        } catch (Exception $e) {
            Log::error('Error loading members: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load members. Please try again.');
        }
    }

    /**
     * Toggle member activation/deactivation status.
     */
    public function toggleMemberStatus(Request $request, $applicationId)
    {
        try {
            // Check if called from admin or superadmin context
            $adminId = session('admin_id');
            $superAdminId = session('superadmin_id');
            
            if (!$adminId && !$superAdminId) {
                return back()->with('error', 'Unauthorized access.');
            }
            
            $application = Application::whereNotNull('membership_id')
                ->findOrFail($applicationId);
            
            $oldStatus = $application->is_active;
            $newStatus = !$oldStatus;
            
            $application->update([
                'is_active' => $newStatus,
                'deactivated_at' => $newStatus ? null : now('Asia/Kolkata'),
                'deactivated_by' => $newStatus ? null : ($adminId ?? $superAdminId),
            ]);

            // Log action
            if ($adminId) {
                AdminAction::log(
                    $adminId,
                    $newStatus ? 'activated_member' : 'deactivated_member',
                    $application,
                    ($newStatus ? 'Activated' : 'Deactivated')." member application {$application->application_id}",
                    [
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'membership_id' => $application->membership_id,
                    ]
                );
            } elseif ($superAdminId) {
                AdminAction::logSuperAdmin(
                    $superAdminId,
                    $newStatus ? 'activated_member' : 'deactivated_member',
                    $application,
                    ($newStatus ? 'Activated' : 'Deactivated')." member application {$application->application_id}",
                    [
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'membership_id' => $application->membership_id,
                    ]
                );
            }

            $message = $newStatus 
                ? 'Member marked as LIVE successfully! Application status updated.'
                : 'Member marked as NOT LIVE successfully! Application status updated.';

            return back()->with('success', $message);
        } catch (Exception $e) {
            Log::error('Error toggling member status: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Delete user and all related data.
     */
    public function deleteUser($userId)
    {
        try {
            $user = Registration::findOrFail($userId);
            $userName = $user->fullname;
            $userRegistrationId = $user->registrationid;
            $adminId = session('admin_id');

            DB::beginTransaction();

            // Delete Application Status History
            $applicationIds = Application::where('user_id', $userId)->pluck('id');
            ApplicationStatusHistory::whereIn('application_id', $applicationIds)->delete();

            // Delete Applications
            $applications = Application::where('user_id', $userId)->get();
            foreach ($applications as $application) {
                // Delete application storage files if any
                $applicationPath = storage_path("app/public/applications/{$application->application_id}");
                if (File::exists($applicationPath)) {
                    File::deleteDirectory($applicationPath);
                }
            }
            Application::where('user_id', $userId)->delete();

            // Delete User KYC Profiles
            UserKycProfile::where('user_id', $userId)->delete();

            // Delete Payment Transactions
            PaymentTransaction::where('user_id', $userId)->delete();

            // Delete Messages
            Message::where('user_id', $userId)->delete();

            // Delete Profile Update Requests
            ProfileUpdateRequest::where('user_id', $userId)->delete();

            // Delete Verifications
            PanVerification::where('user_id', $userId)->delete();
            GstVerification::where('user_id', $userId)->delete();
            UdyamVerification::where('user_id', $userId)->delete();
            McaVerification::where('user_id', $userId)->delete();
            RocIecVerification::where('user_id', $userId)->delete();

            // Delete Tickets and related data
            $tickets = Ticket::where('user_id', $userId)->get();
            foreach ($tickets as $ticket) {
                // Delete ticket attachments
                TicketAttachment::where('ticket_id', $ticket->id)->delete();
                // Delete ticket messages
                TicketMessage::where('ticket_id', $ticket->id)->delete();
            }
            Ticket::where('user_id', $userId)->delete();

            // Delete Admin Actions related to this user
            AdminAction::where('actionable_type', Registration::class)
                ->where('actionable_id', $userId)
                ->delete();

            // Delete password reset tokens
            DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->delete();

            // Delete user sessions (if using database sessions)
            if (config('session.driver') === 'database') {
                DB::table('sessions')
                    ->where('user_id', $userId)
                    ->delete();
            }

            // Log action before deleting the user
            AdminAction::logAdminActivity(
                $adminId,
                'deleted_user',
                "Deleted user: {$userName} (Registration ID: {$userRegistrationId})",
                ['deleted_user_id' => $userId, 'deleted_user_name' => $userName, 'deleted_registration_id' => $userRegistrationId]
            );

            // Delete the user
            $user->delete();

            DB::commit();

            return redirect()->route('admin.users')
                ->with('success', "User '{$userName}' and all related data have been deleted successfully.");
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Database error deleting user: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            DB::rollBack();
            Log::error('PDO error deleting user: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting user: '.$e->getMessage());

            return back()->with('error', 'An error occurred while deleting the user. Please try again.');
        }
    }

    /**
     * Display IX points listing.
     */
    public function ixPoints(Request $request)
    {
        try {
            $nodeType = $request->get('node_type'); // 'edge', 'metro', or null for all
            
            $query = IxLocation::where('is_active', true);
            
            if ($nodeType && in_array($nodeType, ['edge', 'metro'])) {
                $query->where('node_type', $nodeType);
            }
            
            $locations = $query->orderBy('node_type')
                ->orderBy('state')
                ->orderBy('name')
                ->get();
            
            // Get application counts for each location
            $locationStats = [];
            foreach ($locations as $location) {
                // Count applications for this location using JSON path
                $applications = Application::where('application_type', 'IX')
                    ->whereRaw('JSON_EXTRACT(application_data, "$.location.id") = ?', [$location->id])
                    ->get();
                
                $locationStats[$location->id] = [
                    'total_applications' => $applications->count(),
                    'approved_applications' => $applications->whereIn('status', ['approved', 'payment_verified'])->count(),
                    'pending_applications' => $applications->whereNotIn('status', ['approved', 'rejected', 'ceo_rejected', 'payment_verified'])->count(),
                ];
            }
            
            return view('admin.ix-points.index', compact('locations', 'nodeType', 'locationStats'));
        } catch (Exception $e) {
            Log::error('Error loading IX points: '.$e->getMessage());
            
            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load IX points right now.');
        }
    }

    /**
     * Display IX point details.
     */
    public function showIxPoint(Request $request, $id)
    {
        try {
            $location = IxLocation::where('is_active', true)->findOrFail($id);
            
            // Get application counts for this location
            $applications = Application::where('application_type', 'IX')
                ->whereRaw('JSON_EXTRACT(application_data, "$.location.id") = ?', [$location->id])
                ->get();
            
            $locationStats = [
                'total_applications' => $applications->count(),
                'approved_applications' => $applications->whereIn('status', ['approved', 'payment_verified'])->count(),
                'pending_applications' => $applications->whereNotIn('status', ['approved', 'rejected', 'ceo_rejected', 'payment_verified'])->count(),
                'rejected_applications' => $applications->whereIn('status', ['rejected', 'ceo_rejected'])->count(),
            ];
            
            return view('admin.ix-points.show', compact('location', 'locationStats'));
        } catch (Exception $e) {
            Log::error('Error loading IX point details: '.$e->getMessage());
            
            return redirect()->route('admin.ix-points')
                ->with('error', 'Unable to load IX point details right now.');
        }
    }

    /**
     * Display applications based on admin role.
     */
    public function applications(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            // Get selected role from query parameter or session
            $selectedRole = $request->get('role', session('admin_selected_role', null));

            // If admin has multiple roles and no role is selected, auto-select based on priority
            if ($admin->roles->count() > 1 && ! $selectedRole) {
                // Priority order: new IX workflow roles first, then legacy
                $priorityOrder = [
                    'ix_processor', 'ix_legal', 'ix_head', 'ceo', 'nodal_officer', 'ix_tech_team', 'ix_account',
                    'processor', 'finance', 'technical',
                ];
                foreach ($priorityOrder as $priorityRole) {
                    if ($admin->hasRole($priorityRole)) {
                        $selectedRole = $priorityRole;
                        break;
                    }
                }
            }

            // Validate selected role belongs to admin
            if ($selectedRole && ! $admin->hasRole($selectedRole)) {
                $selectedRole = null;
            }

            // Store selected role in session
            if ($selectedRole) {
                session(['admin_selected_role' => $selectedRole]);
            }

            // Get status filter if provided
            $statusFilter = $request->get('status');

            // Determine which role to use for filtering
            $roleToUse = $selectedRole;
            if ($admin->roles->count() === 1) {
                // Single role - use that role directly
                $roleToUse = $admin->roles->first()->slug;
            }

            // Show all applications but filter based on role for default view
            // Admins can see all applications, but actions are restricted to their stage
            $query = Application::with([
                'user',
                'processor', 'finance', 'technical', // Legacy
                'ixProcessor', 'ixLegal', 'ixHead', 'ceo', 'nodalOfficer', 'ixTechTeam', 'ixAccount', // New
                'statusHistory',
            ])->where('application_type', 'IX'); // Only show IX applications for new workflow

            // Show all applications by default (admins can see all)
            // is_active now represents "live" status, not visibility
            // All applications are visible, but marked as live/not live
            
            // Apply status filter if provided
            if ($statusFilter === 'approved') {
                $query->whereIn('status', ['approved', 'payment_verified']);
            } elseif ($statusFilter === 'pending') {
                $query->whereNotIn('status', ['approved', 'rejected', 'ceo_rejected', 'payment_verified']);
            } elseif ($statusFilter === 'ip_assigned') {
                $query->where('status', 'ip_assigned');
            }
            // If no status filter, show all active applications (admins can see all)
            // Actions are restricted based on application stage in the show view

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('application_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('registrationid', 'like', "%{$search}%");
                        })
                        ->orWhere('status', 'like', "%{$search}%");
                });
            }

            $applications = $query->orderBy('submitted_at', 'desc')->paginate(20)->withQueryString();

            return view('admin.applications.index', compact('applications', 'admin', 'selectedRole'));
        } catch (QueryException $e) {
            Log::error('Database error loading applications: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading applications: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading applications: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load applications. Please try again.');
        }
    }

    /**
     * Show application details.
     */
    public function showApplication(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $application = Application::with(['user', 'processor', 'finance', 'technical', 'statusHistory', 'paymentVerificationLogs', 'invoices'])
                ->findOrFail($id);

            // Get selected role from query parameter or session
            $selectedRole = $request->get('role', session('admin_selected_role', null));

            // If admin has multiple roles and no role is selected, auto-select based on priority
            if ($admin->roles->count() > 1 && ! $selectedRole) {
                // Priority order: new IX workflow roles first, then legacy
                $priorityOrder = [
                    'ix_processor', 'ix_legal', 'ix_head', 'ceo', 'nodal_officer', 'ix_tech_team', 'ix_account',
                    'processor', 'finance', 'technical',
                ];
                foreach ($priorityOrder as $priorityRole) {
                    if ($admin->hasRole($priorityRole)) {
                        $selectedRole = $priorityRole;
                        break;
                    }
                }
            }

            // Validate selected role belongs to admin
            if ($selectedRole && ! $admin->hasRole($selectedRole)) {
                $selectedRole = null;
            }

            // Store selected role in session
            if ($selectedRole) {
                session(['admin_selected_role' => $selectedRole]);
            }

            // Get payment verification status for IX Account
            $canVerifyPayment = false;
            $paymentVerificationMessage = null;
            $currentBillingPeriod = null;
            
            if ($selectedRole === 'ix_account' && $application->is_active && $application->isVisibleToIxAccount()) {
                if ($application->service_activation_date && $application->billing_cycle) {
                    $currentBillingPeriod = $this->getCurrentBillingPeriod($application);
                    if ($currentBillingPeriod) {
                        $canVerifyPayment = !$this->isPaymentVerifiedForPeriod($application, $currentBillingPeriod);
                        if (!$canVerifyPayment) {
                            $periodLabel = $this->getBillingPeriodLabel($application->billing_cycle, $currentBillingPeriod);
                            $paymentVerificationMessage = "Payment for this {$periodLabel} has already been verified.";
                        }
                    }
                } else {
                    // Initial payment - check if any verification exists
                    $canVerifyPayment = !$application->paymentVerificationLogs()->exists();
                }
            }

            // Admin can view all applications, but can only take actions on applications for their selected role
            return view('admin.applications.show', compact('application', 'admin', 'selectedRole', 'canVerifyPayment', 'paymentVerificationMessage', 'currentBillingPeriod'));
        } catch (QueryException $e) {
            Log::error('Database error loading application: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading application: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading application: '.$e->getMessage());

            return redirect()->route('admin.applications')
                ->with('error', 'Application not found.');
        }
    }

    /**
     * Serve application document securely.
     */
    public function serveDocument($id, Request $request)
    {
        try {
            $documentKey = $request->input('doc');
            
            if (!$documentKey) {
                abort(400, 'Document key is required.');
            }

            $application = Application::findOrFail($id);
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
     * Processor: Approve application to Finance.
     */
    public function approveToFinance(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'processor')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->findOrFail($id);

            if (! $application->isVisibleToProcessor()) {
                return back()->with('error', 'This application is not available for processing.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'processor_approved',
                'current_processor_id' => $admin->id,
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'processor_approved',
                'admin',
                $admin->id,
                'Application approved by Processor and forwarded to Finance'
            );

            // Log admin action
            AdminAction::log(
                $admin->id,
                'approved_application',
                $application,
                "Approved application {$application->application_id} to Finance",
                ['user_id' => $application->user_id]
            );

            // Send message to user
            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Status Update',
                'message' => "Your application {$application->application_id} has been approved by Processor and forwarded to Finance for review.",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application approved and forwarded to Finance!');
        } catch (QueryException $e) {
            Log::error('Database error approving application: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error approving application: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error approving application: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Finance: Approve application to Technical.
     */
    public function approveToTechnical(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'finance')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->findOrFail($id);

            if (! $application->isVisibleToFinance()) {
                return back()->with('error', 'This application is not available for Finance review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'finance_approved',
                'current_finance_id' => $admin->id,
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'finance_approved',
                'admin',
                $admin->id,
                'Application approved by Finance and forwarded to Technical'
            );

            // Log admin action
            AdminAction::log(
                $admin->id,
                'approved_application',
                $application,
                "Approved application {$application->application_id} to Technical",
                ['user_id' => $application->user_id]
            );

            // Send message to user
            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Status Update',
                'message' => "Your application {$application->application_id} has been approved by Finance and forwarded to Technical for final review.",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application approved and forwarded to Technical!');
        } catch (QueryException $e) {
            Log::error('Database error approving application: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error approving application: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error approving application: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Finance: Send application back to Processor.
     */
    public function sendBackToProcessor(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'finance')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ], [
                'rejection_reason.required' => 'Please provide a reason for rejection.',
                'rejection_reason.min' => 'Please provide more details (minimum 10 characters).',
            ]);

            $application = Application::with('user')->findOrFail($id);

            if (! $application->isVisibleToFinance()) {
                return back()->with('error', 'This application is not available for Finance review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'processor_review',
                'rejection_reason' => $validated['rejection_reason'],
                'current_finance_id' => $admin->id,
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'processor_review',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            // Log admin action
            AdminAction::log(
                $admin->id,
                'rejected_application',
                $application,
                "Sent application {$application->application_id} back to Processor",
                ['reason' => $validated['rejection_reason']]
            );

            // Send message to user
            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Status Update',
                'message' => "Your application {$application->application_id} has been sent back to Processor for review. Reason: {$validated['rejection_reason']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application sent back to Processor!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error sending application back: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error sending application back: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error sending application back: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Technical: Approve application (final approval).
     */
    public function approveApplication(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'technical')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->findOrFail($id);

            if (! $application->isVisibleToTechnical()) {
                return back()->with('error', 'This application is not available for Technical review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'approved',
                'approved_at' => now('Asia/Kolkata'),
                'current_technical_id' => $admin->id,
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'approved',
                'admin',
                $admin->id,
                'Application approved by Technical'
            );

            // Log admin action
            AdminAction::log(
                $admin->id,
                'approved_application',
                $application,
                "Approved application {$application->application_id}",
                ['user_id' => $application->user_id]
            );

            // Send message to user
            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Approved!',
                'message' => "Congratulations! Your application {$application->application_id} has been approved by Technical. You can now view it in your Applications tab.",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application approved successfully!');
        } catch (QueryException $e) {
            Log::error('Database error approving application: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error approving application: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error approving application: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Technical: Send application back to Finance.
     */
    public function sendBackToFinance(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'technical')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ], [
                'rejection_reason.required' => 'Please provide a reason for rejection.',
                'rejection_reason.min' => 'Please provide more details (minimum 10 characters).',
            ]);

            $application = Application::with('user')->findOrFail($id);

            if (! $application->isVisibleToTechnical()) {
                return back()->with('error', 'This application is not available for Technical review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'finance_review',
                'rejection_reason' => $validated['rejection_reason'],
                'current_technical_id' => $admin->id,
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'finance_review',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            // Log admin action
            AdminAction::log(
                $admin->id,
                'rejected_application',
                $application,
                "Sent application {$application->application_id} back to Finance",
                ['reason' => $validated['rejection_reason']]
            );

            // Send message to user
            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Status Update',
                'message' => "Your application {$application->application_id} has been sent back to Finance for review. Reason: {$validated['rejection_reason']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application sent back to Finance!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error sending application back: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error sending application back: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error sending application back: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Display all profile update requests and messages for admin.
     */
    public function requestsAndMessages(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            // Get all pending profile update requests with user info
            $requestsQuery = ProfileUpdateRequest::with(['user'])
                ->where('status', 'pending');

            // Search for requests
            if ($request->filled('requests_search')) {
                $search = $request->input('requests_search');
                $requestsQuery->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('fullname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('registrationid', 'like', "%{$search}%");
                    });
                });
            }

            $profileUpdateRequests = $requestsQuery->latest()->paginate(20, ['*'], 'requests_page')->withQueryString();

            // Get all recent messages sent to users
            $messagesQuery = Message::with(['user']);

            // Search for messages
            if ($request->filled('messages_search')) {
                $search = $request->input('messages_search');
                $messagesQuery->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            $messages = $messagesQuery->latest()->paginate(20, ['*'], 'messages_page')->withQueryString();

            return view('admin.requests-messages', compact('admin', 'profileUpdateRequests', 'messages'));
        } catch (QueryException $e) {
            Log::error('Database error loading requests and messages: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading requests and messages: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading requests and messages: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load requests and messages. Please try again.');
        }
    }

    /**
     * Display admin messages inbox.
     */
    public function messages(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            $query = Message::with(['user']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('registrationid', 'like', "%{$search}%");
                        });
                });
            }

            $messages = $query->latest()->paginate(20)->withQueryString();

            return view('admin.messages.index', compact('admin', 'messages'));
        } catch (QueryException $e) {
            Log::error('Database error loading admin messages: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading admin messages: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading admin messages: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load messages. Please try again.');
        }
    }

    /**
     * Display all profile update requests.
     */
    public function profileUpdateRequests(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            $query = ProfileUpdateRequest::with(['user', 'approver']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('status', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('registrationid', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                        });
                });
            }

            $requests = $query->latest()->paginate(20)->withQueryString();

            return view('admin.profile-update-requests.index', compact('admin', 'requests'));
        } catch (QueryException $e) {
            Log::error('Database error loading profile update requests: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading profile update requests: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading profile update requests: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load profile update requests. Please try again.');
        }
    }

    // ============================================
    // NEW IX WORKFLOW METHODS
    // ============================================

    /**
     * IX Processor: Forward application to Legal.
     */
    public function ixProcessorForwardToLegal(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_processor')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxProcessor()) {
                return back()->with('error', 'This application is not available for processing.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'processor_forwarded_legal',
                'current_ix_processor_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'processor_forwarded_legal',
                'admin',
                $admin->id,
                'Application forwarded to IX Legal by Processor'
            );

            AdminAction::log(
                $admin->id,
                'forwarded_application',
                $application,
                "Forwarded application {$application->application_id} to IX Legal",
                ['user_id' => $application->user_id]
            );

            // Send email to applicant
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationForwardedMail(
                        $application,
                        'IX Legal',
                        'IX Application Processor'
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application forwarded email: '.$e->getMessage());
            }

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Forwarded to Legal',
                'message' => "Your application {$application->application_id} has been forwarded to IX Legal for verification of agreement.",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application forwarded to IX Legal!');
        } catch (Exception $e) {
            Log::error('Error forwarding application to legal: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Processor: Request resubmission from user.
     */
    public function ixProcessorRequestResubmission(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_processor')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'resubmission_query' => 'required|string|min:10',
            ], [
                'resubmission_query.required' => 'Please provide a query or reason for resubmission.',
                'resubmission_query.min' => 'Please provide more details (minimum 10 characters).',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxProcessor()) {
                return back()->with('error', 'This application is not available for processing.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'processor_resubmission',
                'resubmission_query' => $validated['resubmission_query'],
                'current_ix_processor_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'processor_resubmission',
                'admin',
                $admin->id,
                $validated['resubmission_query']
            );

            AdminAction::log(
                $admin->id,
                'requested_resubmission',
                $application,
                "Requested resubmission for application {$application->application_id}",
                ['query' => $validated['resubmission_query']]
            );

            // Send resubmission email with query
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationResubmissionMail(
                        $application,
                        $validated['resubmission_query']
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application resubmission email: '.$e->getMessage());
            }

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Resubmission Required',
                'message' => "Your application {$application->application_id} requires resubmission. Query: {$validated['resubmission_query']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Resubmission requested!');
        } catch (Exception $e) {
            Log::error('Error requesting resubmission: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Legal: Forward to IX Head.
     */
    public function ixLegalForwardToHead(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_legal')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxLegal()) {
                return back()->with('error', 'This application is not available for Legal review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'legal_forwarded_head',
                'current_ix_legal_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'legal_forwarded_head',
                'admin',
                $admin->id,
                'Application forwarded to IX Head by Legal'
            );

            AdminAction::log(
                $admin->id,
                'forwarded_application',
                $application,
                "Forwarded application {$application->application_id} to IX Head",
                ['user_id' => $application->user_id]
            );

            // Send email to applicant
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationForwardedMail(
                        $application,
                        'IX Head',
                        'IX Legal'
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application forwarded email: '.$e->getMessage());
            }

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Forwarded to IX Head',
                'message' => "Your application {$application->application_id} has been verified by Legal and forwarded to IX Head for review.",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application forwarded to IX Head!');
        } catch (Exception $e) {
            Log::error('Error forwarding application to head: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Legal: Send back to Processor.
     */
    public function ixLegalSendBackToProcessor(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_legal')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxLegal()) {
                return back()->with('error', 'This application is not available for Legal review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'legal_sent_back',
                'rejection_reason' => $validated['rejection_reason'],
                'current_ix_legal_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'legal_sent_back',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Sent Back to Processor',
                'message' => "Your application {$application->application_id} has been sent back to Processor. Reason: {$validated['rejection_reason']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application sent back to Processor!');
        } catch (Exception $e) {
            Log::error('Error sending application back: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Head: Forward to CEO.
     */
    public function ixHeadForwardToCeo(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_head')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxHead()) {
                return back()->with('error', 'This application is not available for IX Head review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'head_forwarded_ceo',
                'current_ix_head_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'head_forwarded_ceo',
                'admin',
                $admin->id,
                'Application forwarded to CEO by IX Head'
            );

            // Send email to applicant
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationForwardedMail(
                        $application,
                        'CEO',
                        'IX Head'
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application forwarded email: '.$e->getMessage());
            }

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Forwarded to CEO',
                'message' => "Your application {$application->application_id} has been reviewed by IX Head and forwarded to CEO for final approval.",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application forwarded to CEO!');
        } catch (Exception $e) {
            Log::error('Error forwarding application to CEO: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Head: Send back to Processor.
     */
    public function ixHeadSendBackToProcessor(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_head')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxHead()) {
                return back()->with('error', 'This application is not available for IX Head review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'head_sent_back',
                'rejection_reason' => $validated['rejection_reason'],
                'current_ix_head_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'head_sent_back',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Sent Back to Processor',
                'message' => "Your application {$application->application_id} has been sent back to Processor. Reason: {$validated['rejection_reason']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application sent back to Processor!');
        } catch (Exception $e) {
            Log::error('Error sending application back: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * CEO: Approve and forward to Nodal Officer.
     */
    public function ceoApprove(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ceo')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToCeo()) {
                return back()->with('error', 'This application is not available for CEO review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'ceo_approved',
                'current_ceo_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'ceo_approved',
                'admin',
                $admin->id,
                'Application approved by CEO and forwarded to Nodal Officer'
            );

            // Send approval email to applicant
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationApprovedMail(
                        $application,
                        'CEO'
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application approved email: '.$e->getMessage());
            }

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Approved by CEO',
                'message' => "Congratulations! Your application {$application->application_id} has been approved by CEO and forwarded to Nodal Officer for port assignment.",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application approved and forwarded to Nodal Officer!');
        } catch (Exception $e) {
            Log::error('Error approving application: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * CEO: Reject application.
     */
    public function ceoReject(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ceo')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToCeo()) {
                return back()->with('error', 'This application is not available for CEO review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'ceo_rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'current_ceo_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'ceo_rejected',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            // Send rejection email to applicant
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationRejectedMail(
                        $application,
                        'CEO',
                        $validated['rejection_reason']
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application rejected email: '.$e->getMessage());
            }

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Rejected',
                'message' => "Your application {$application->application_id} has been rejected by CEO. Reason: {$validated['rejection_reason']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application rejected!');
        } catch (Exception $e) {
            Log::error('Error rejecting application: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * CEO: Send back to IX Head.
     */
    public function ceoSendBackToHead(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ceo')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'send_back_reason' => 'nullable|string|max:1000',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToCeo()) {
                return back()->with('error', 'This application is not available for CEO review.');
            }

            // Get the IX Head who forwarded it (from status history or current_ix_head_id)
            $ixHeadId = $application->current_ix_head_id;
            if (! $ixHeadId) {
                // Try to get from status history
                $headForwardHistory = ApplicationStatusHistory::where('application_id', $application->id)
                    ->where('status_to', 'head_forwarded_ceo')
                    ->where('changed_by_type', 'admin')
                    ->latest()
                    ->first();
                $ixHeadId = $headForwardHistory ? $headForwardHistory->changed_by_id : null;
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'ceo_sent_back_head',
                'current_ceo_id' => $admin->id,
                'current_ix_head_id' => $ixHeadId,
            ]);

            $notes = $validated['send_back_reason'] ?? 'Application sent back to IX Head by CEO for review';

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'ceo_sent_back_head',
                'admin',
                $admin->id,
                $notes
            );

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Sent Back to IX Head',
                'message' => "Your application {$application->application_id} has been sent back to IX Head by CEO for further review.",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application sent back to IX Head!');
        } catch (Exception $e) {
            Log::error('Error sending application back to IX Head: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Nodal Officer: Assign Port and forward to Tech Team.
     */
    public function nodalOfficerAssignPort(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'nodal_officer')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'assigned_port_capacity' => 'required|string',
                'assigned_port_number' => 'nullable|string',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToNodalOfficer()) {
                return back()->with('error', 'This application is not available for Nodal Officer review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'port_assigned',
                'assigned_port_capacity' => $validated['assigned_port_capacity'],
                'assigned_port_number' => $validated['assigned_port_number'] ?? null,
                'current_nodal_officer_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'port_assigned',
                'admin',
                $admin->id,
                "Port assigned: {$validated['assigned_port_capacity']}"
            );

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'IP Assigned - Service Activated',
                'message' => "IP has been assigned for your application {$application->application_id}. Your service is now LIVE. Service Activation Date: {$validated['service_activation_date']}. IP Address: {$validated['assigned_ip']}, Customer ID: {$validated['customer_id']}, Membership ID: {$validated['membership_id']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Port assigned and forwarded to Tech Team!');
        } catch (Exception $e) {
            Log::error('Error assigning port: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Nodal Officer: Hold application.
     */
    public function nodalOfficerHold(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'nodal_officer')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToNodalOfficer()) {
                return back()->with('error', 'This application is not available for Nodal Officer review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'port_hold',
                'rejection_reason' => $validated['rejection_reason'],
                'current_nodal_officer_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'port_hold',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application On Hold',
                'message' => "Your application {$application->application_id} has been put on hold. Reason: {$validated['rejection_reason']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application put on hold!');
        } catch (Exception $e) {
            Log::error('Error holding application: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Nodal Officer: Mark as Not Feasible.
     */
    public function nodalOfficerNotFeasible(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'nodal_officer')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToNodalOfficer()) {
                return back()->with('error', 'This application is not available for Nodal Officer review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'port_not_feasible',
                'rejection_reason' => $validated['rejection_reason'],
                'current_nodal_officer_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'port_not_feasible',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Not Feasible',
                'message' => "Your application {$application->application_id} has been marked as not feasible. Reason: {$validated['rejection_reason']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application marked as not feasible!');
        } catch (Exception $e) {
            Log::error('Error marking application as not feasible: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Nodal Officer: Customer Denied.
     */
    public function nodalOfficerCustomerDenied(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'nodal_officer')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToNodalOfficer()) {
                return back()->with('error', 'This application is not available for Nodal Officer review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'customer_denied',
                'current_nodal_officer_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'customer_denied',
                'admin',
                $admin->id,
                'Customer denied the port assignment'
            );

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Status Update',
                'message' => "Your application {$application->application_id} has been marked as customer denied.",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application marked as customer denied!');
        } catch (Exception $e) {
            Log::error('Error marking application as customer denied: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Nodal Officer: Forward back to Processor.
     */
    public function nodalOfficerForwardToProcessor(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'nodal_officer')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToNodalOfficer()) {
                return back()->with('error', 'This application is not available for Nodal Officer review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'processor_resubmission',
                'rejection_reason' => $validated['rejection_reason'],
                'current_nodal_officer_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'processor_resubmission',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Application Sent Back to Processor',
                'message' => "Your application {$application->application_id} has been sent back to Processor. Reason: {$validated['rejection_reason']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Application sent back to Processor!');
        } catch (Exception $e) {
            Log::error('Error forwarding application to processor: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Tech Team: Assign IP and make live.
     */
    public function ixTechTeamAssignIp(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_tech_team')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'assigned_ip' => 'required|string',
                'customer_id' => 'required|string',
                'membership_id' => 'required|string',
                'service_activation_date' => 'required|date',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxTechTeam()) {
                return back()->with('error', 'This application is not available for Tech Team review.');
            }

            // Get billing cycle from application data
            $applicationData = $application->application_data ?? [];
            $billingCycle = $applicationData['billing']['plan'] ?? 'monthly'; // monthly, quarterly, annual

            $oldStatus = $application->status;
            $application->update([
                'status' => 'ip_assigned',
                'assigned_ip' => $validated['assigned_ip'],
                'customer_id' => $validated['customer_id'],
                'membership_id' => $validated['membership_id'],
                'service_activation_date' => $validated['service_activation_date'],
                'billing_cycle' => $billingCycle,
                'is_active' => true, // Make it live
                'current_ix_tech_team_id' => $admin->id,
                'current_ix_account_id' => null, // Reset so IX Account can see it
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'ip_assigned',
                'admin',
                $admin->id,
                "IP assigned: {$validated['assigned_ip']}, Customer ID: {$validated['customer_id']}, Membership ID: {$validated['membership_id']}, Service Activation: {$validated['service_activation_date']}"
            );

            // Generate membership and IX invoice (TODO: Implement invoice generation)
            // Send email with details
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationIpAssignedMail(
                        $application,
                        $validated['assigned_ip'],
                        $validated['customer_id'],
                        $validated['membership_id'],
                        $validated['service_activation_date']
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application IP assigned email: '.$e->getMessage());
            }

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'IP Assigned - Application Live',
                'message' => "Your application {$application->application_id} is now live! IP: {$validated['assigned_ip']}, Customer ID: {$validated['customer_id']}, Membership ID: {$validated['membership_id']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'IP assigned and application is now live!');
        } catch (Exception $e) {
            Log::error('Error assigning IP: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Account: Generate Invoice (supports recurring invoices).
     */
    public function ixAccountGenerateInvoice(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            // Only allow invoice generation for LIVE applications
            if (!$application->is_active) {
                return back()->with('error', 'Invoice can only be generated for LIVE applications.');
            }

            if (! $application->isVisibleToIxAccount()) {
                return back()->with('error', 'This application is not available for Account review.');
            }

            // Get payment amount - ONLY port charges, NO application fee
            $applicationData = $application->application_data ?? [];
            
            // Get port amount based on billing cycle
            $billingPlanRaw = $application->billing_cycle ?? ($applicationData['port_selection']['billing_plan'] ?? 'monthly');
            
            // Normalize billing plan (handle case variations and aliases)
            $billingPlan = strtolower(trim($billingPlanRaw));
            if (in_array($billingPlan, ['arc', 'annual'])) {
                $billingPlan = 'annual';
            } elseif (in_array($billingPlan, ['mrc', 'monthly'])) {
                $billingPlan = 'monthly';
            } elseif ($billingPlan === 'quarterly') {
                $billingPlan = 'quarterly';
            } else {
                $billingPlan = 'monthly'; // Default fallback
            }
            
            Log::info("Billing plan for application {$application->id}: raw='{$billingPlanRaw}', normalized='{$billingPlan}'");
            
            // Map billing plan to pricing plan
            $pricingPlan = match($billingPlan) {
                'annual', 'arc' => 'arc',
                'monthly', 'mrc' => 'mrc',
                'quarterly' => 'quarterly',
                default => 'mrc',
            };
            
            // Check for existing invoice for the same billing period to prevent duplicates
            // First, we need to determine what the billing period would be
            $lastPaidInvoice = Invoice::where('application_id', $application->id)
                ->where('status', 'paid')
                ->latest('invoice_date')
                ->first();
            
            // Calculate billing period dates for invoice
            $billingStartDate = null;
            $billingEndDate = null;
            $dueDate = null;
            $billingPeriod = null;
            
            // Determine start date: first invoice uses service_activation_date, subsequent uses last invoice's due_date
            if ($lastPaidInvoice && $lastPaidInvoice->due_date) {
                // Subsequent invoice: start from last paid invoice's due date
                $billingStartDate = \Carbon\Carbon::parse($lastPaidInvoice->due_date);
                Log::info("Subsequent invoice for application {$application->id}: using last paid invoice due_date as start: {$billingStartDate->format('Y-m-d')}");
            } elseif ($application->service_activation_date) {
                // First invoice: start from service_activation_date
                $billingStartDate = \Carbon\Carbon::parse($application->service_activation_date);
                Log::info("First invoice for application {$application->id}: using service_activation_date as start: {$billingStartDate->format('Y-m-d')}");
            } else {
                // Fallback: use current date
                $billingStartDate = now('Asia/Kolkata');
                Log::warning("No service_activation_date or previous invoice for application {$application->id}, using current date as start: {$billingStartDate->format('Y-m-d')}");
            }
            
            // Calculate end date (due date) based on billing cycle
            switch ($billingPlan) {
                case 'annual':
                case 'arc':
                    $billingEndDate = $billingStartDate->copy()->addYear();
                    $billingPeriod = $billingStartDate->format('Y');
                    break;
                case 'quarterly':
                    $billingEndDate = $billingStartDate->copy()->addMonths(3);
                    $quarter = ceil($billingStartDate->month / 3);
                    $billingPeriod = $billingStartDate->format('Y').'-Q'.$quarter;
                    break;
                case 'monthly':
                case 'mrc':
                default:
                    $billingEndDate = $billingStartDate->copy()->addMonth();
                    $billingPeriod = $billingEndDate->format('Y-m');
                    break;
            }
            
            // Due date is the billing end date
            $dueDate = $billingEndDate;
            
            Log::info("Billing period calculation for application {$application->id}: billingPlan='{$billingPlan}', startDate={$billingStartDate->format('Y-m-d')}, endDate={$billingEndDate->format('Y-m-d')}, dueDate={$dueDate->format('Y-m-d')}, billingPeriod='{$billingPeriod}'");
            
            // Check for duplicate invoice with same billing period
            $existingInvoice = Invoice::where('application_id', $application->id)
                ->where('billing_period', $billingPeriod)
                ->where('status', '!=', 'cancelled')
                ->first();
            
            if ($existingInvoice) {
                Log::warning("Duplicate invoice attempt for application {$application->id}, billing period '{$billingPeriod}'. Existing invoice: {$existingInvoice->invoice_number}");
                return back()->with('error', "An invoice for billing period '{$billingPeriod}' already exists (Invoice: {$existingInvoice->invoice_number}). Please check existing invoices.");
            }
            // Determine location
            $location = null;
            if (isset($applicationData['location']['id'])) {
                $location = IxLocation::find($applicationData['location']['id']);
            }
            if (! $location) {
                Log::error("Missing location for invoice generation for application {$application->id}");
                return back()->with('error', 'Unable to calculate port charges. Location is missing.');
            }

            // Helper to normalize capacity and fetch amount
            $getPortAmount = function ($capacity, $plan) use ($location) {
                if (! $capacity) {
                    return null;
                }
                $normalizedCapacity = trim($capacity);
                $normalizedCapacity = preg_replace('/\s+/', '', $normalizedCapacity);
                if (stripos($normalizedCapacity, 'Gbps') !== false || stripos($normalizedCapacity, 'gbps') !== false) {
                    $normalizedCapacity = str_ireplace(['Gbps', 'gbps', 'GBPS'], 'Gig', $normalizedCapacity);
                }
                if (! preg_match('/(Gig|M)$/i', $normalizedCapacity)) {
                    if (preg_match('/^\d+$/', $normalizedCapacity)) {
                        $normalizedCapacity .= 'Gig';
                    }
                }

                $pricing = IxPortPricing::active()
                    ->where('node_type', $location->node_type)
                    ->where('port_capacity', $normalizedCapacity)
                    ->first();

                if (! $pricing) {
                    $variations = [
                        trim($capacity),
                        str_replace(' ', '', trim($capacity)),
                        preg_replace('/\s+/', '', trim($capacity)),
                        str_replace(['Gbps', 'gbps', 'GBPS'], 'Gig', str_replace(' ', '', trim($capacity))),
                        str_replace(['Gbps', 'gbps'], 'Gig', trim($capacity)),
                        str_replace([' Gbps', 'Gbps', 'gbps'], 'Gig', trim($capacity)),
                    ];

                    foreach (array_unique($variations) as $variation) {
                        if (empty($variation)) {
                            continue;
                        }
                        $pricing = IxPortPricing::active()
                            ->where('node_type', $location->node_type)
                            ->where('port_capacity', $variation)
                            ->first();
                        if ($pricing) {
                            break;
                        }
                    }
                }

                if (! $pricing) {
                    return null;
                }

                $amount = $pricing->getAmountForPlan($plan);
                if ($amount === null) {
                    return null;
                }
                return $amount;
            };

            // Proration segments with approved plan changes in the billing window
            $approvedPlanChanges = PlanChangeRequest::where('application_id', $application->id)
                ->where('status', 'approved')
                ->whereNotNull('effective_from')
                ->orderBy('effective_from')
                ->get();

            // Determine starting capacity/plan at billingStartDate
            $baseCapacity = $application->assigned_port_capacity ?? ($applicationData['port_selection']['capacity'] ?? null);
            $basePlan = $billingPlan;
            foreach ($approvedPlanChanges as $change) {
                if ($change->effective_from && $change->effective_from->lt($billingStartDate)) {
                    $baseCapacity = $change->new_port_capacity ?? $baseCapacity;
                    $basePlan = $change->new_billing_plan ? strtolower($change->new_billing_plan) : $basePlan;
                }
            }

            // Validate baseCapacity exists
            if (!$baseCapacity) {
                Log::error("Missing port capacity for application {$application->id} during invoice generation", [
                    'assigned_port_capacity' => $application->assigned_port_capacity,
                    'port_selection' => $applicationData['port_selection'] ?? null,
                ]);
                return back()->with('error', 'Port capacity is not set for this application. Please assign port capacity first.');
            }
            
            // Normalize basePlan to ensure it matches pricing plan format
            $basePlanNormalized = strtolower(trim($basePlan));
            if (in_array($basePlanNormalized, ['arc', 'annual'])) {
                $basePlanNormalized = 'arc';
            } elseif (in_array($basePlanNormalized, ['mrc', 'monthly'])) {
                $basePlanNormalized = 'mrc';
            } elseif ($basePlanNormalized === 'quarterly') {
                $basePlanNormalized = 'quarterly';
            } else {
                $basePlanNormalized = 'mrc'; // Default fallback
            }
            
            $futureChanges = $approvedPlanChanges->filter(function ($c) use ($billingStartDate, $billingEndDate) {
                return $c->effective_from && $c->effective_from->gte($billingStartDate) && $c->effective_from->lt($billingEndDate);
            })->values();

            $segmentStart = $billingStartDate->copy();
            $currentCapacity = $baseCapacity;
            $currentPlan = $basePlanNormalized;
            $segments = [];
            $prorationTotal = 0.0;
            $totalDays = max(1, $billingEndDate->diffInDays($billingStartDate));

            // Helper to get billing cycle period in days for proration
            $getBillingCycleDays = function ($plan) {
                $plan = strtolower(trim($plan));
                return match($plan) {
                    'annual', 'arc' => 365, // Annual period = 365 days
                    'quarterly' => 90,      // Quarterly period = ~90 days (3 months)
                    'monthly', 'mrc' => 30, // Monthly period = ~30 days
                    default => 30,
                };
            };

            $addSegment = function ($start, $end, $capacity, $plan) use ($getPortAmount, &$segments, &$prorationTotal, $getBillingCycleDays) {
                if ($end->lte($start)) {
                    return;
                }
                $fullAmount = $getPortAmount($capacity, $plan);
                if ($fullAmount === null || $fullAmount <= 0) {
                    throw new Exception("No pricing found for capacity {$capacity} and plan {$plan}. Please ensure pricing is configured for this combination.");
                }
                $segmentDays = $end->diffInDays($start);
                
                // Get the billing cycle period for this plan (annual=365, quarterly=90, monthly=30)
                $billingCycleDays = $getBillingCycleDays($plan);
                
                // Prorate based on the plan's own billing cycle period, not the total billing period
                // This handles cases like ARC (365 days) to MRC (30 days) correctly
                $prorated = round(($fullAmount * $segmentDays) / $billingCycleDays, 2);
                
                $prorationTotal += $prorated;
                $segments[] = [
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d'),
                    'capacity' => $capacity,
                    'plan' => $plan,
                    'plan_label' => match(strtolower($plan)) {
                        'annual', 'arc' => 'Annual (ARC)',
                        'quarterly' => 'Quarterly',
                        'monthly', 'mrc' => 'Monthly (MRC)',
                        default => ucfirst($plan),
                    },
                    'days' => $segmentDays,
                    'billing_cycle_days' => $billingCycleDays,
                    'amount_full' => $fullAmount,
                    'amount_prorated' => $prorated,
                ];
            };

            foreach ($futureChanges as $change) {
                $segmentEnd = $change->effective_from->copy();
                
                // Only create segment if dates are valid
                if ($segmentEnd->gt($segmentStart)) {
                    $addSegment($segmentStart, $segmentEnd, $currentCapacity, $currentPlan);
                }
                
                // Update capacity only if it's a capacity change
                if ($change->isCapacityChange() && $change->new_port_capacity) {
                    $currentCapacity = $change->new_port_capacity;
                    Log::info("Plan change {$change->id}: Capacity updated to {$currentCapacity}");
                }
                
                // Update plan if billing plan changed
                $planFromChange = $change->new_billing_plan ? strtolower(trim($change->new_billing_plan)) : null;
                if ($planFromChange) {
                    // Normalize plan from change
                    if (in_array($planFromChange, ['arc', 'annual'])) {
                        $currentPlan = 'arc';
                    } elseif (in_array($planFromChange, ['mrc', 'monthly'])) {
                        $currentPlan = 'mrc';
                    } elseif ($planFromChange === 'quarterly') {
                        $currentPlan = 'quarterly';
                    } else {
                        $currentPlan = 'mrc'; // Default fallback
                    }
                    Log::info("Plan change {$change->id}: Plan updated to {$currentPlan}");
                }
                
                $segmentStart = $change->effective_from->copy();
            }

            // Final segment to billing end
            $addSegment($segmentStart, $billingEndDate->copy(), $currentCapacity, $currentPlan);

            // Debug logging
            Log::info("Invoice generation debug for application {$application->id}", [
                'billingStartDate' => $billingStartDate->format('Y-m-d'),
                'billingEndDate' => $billingEndDate->format('Y-m-d'),
                'billingPlan' => $billingPlan,
                'baseCapacity' => $baseCapacity,
                'basePlan' => $basePlan,
                'basePlanNormalized' => $basePlanNormalized,
                'currentCapacity' => $currentCapacity,
                'currentPlan' => $currentPlan,
                'approvedPlanChangesCount' => $approvedPlanChanges->count(),
                'approvedPlanChanges' => $approvedPlanChanges->map(function($c) {
                    return [
                        'id' => $c->id,
                        'effective_from' => $c->effective_from ? $c->effective_from->format('Y-m-d') : null,
                        'current_capacity' => $c->current_port_capacity,
                        'new_capacity' => $c->new_port_capacity,
                        'current_plan' => $c->current_billing_plan,
                        'new_plan' => $c->new_billing_plan,
                        'is_capacity_change' => $c->isCapacityChange(),
                        'is_billing_cycle_change_only' => $c->isBillingCycleChangeOnly(),
                    ];
                })->toArray(),
                'futureChangesCount' => $futureChanges->count(),
                'futureChanges' => $futureChanges->map(function($c) {
                    return [
                        'id' => $c->id,
                        'effective_from' => $c->effective_from ? $c->effective_from->format('Y-m-d') : null,
                        'new_capacity' => $c->new_port_capacity,
                        'new_plan' => $c->new_billing_plan,
                    ];
                })->toArray(),
                'segmentsCount' => count($segments),
                'segments' => $segments,
                'prorationTotal' => $prorationTotal,
                'location' => $location ? ['id' => $location->id, 'name' => $location->name, 'node_type' => $location->node_type] : null,
            ]);

            if ($prorationTotal <= 0) {
                Log::error("Invalid prorated total for application {$application->id}", [
                    'prorationTotal' => $prorationTotal,
                    'segmentsCount' => count($segments),
                    'segments' => $segments,
                    'baseCapacity' => $baseCapacity,
                    'basePlan' => $basePlan,
                    'currentCapacity' => $currentCapacity,
                    'currentPlan' => $currentPlan,
                    'location' => $location ? ['id' => $location->id, 'name' => $location->name, 'node_type' => $location->node_type] : null,
                ]);
                return back()->with('error', 'Unable to calculate charges. Please check plan and pricing configuration. Ensure port capacity and billing plan have valid pricing.');
            }

            // Calculate adjustments from previous billing period's plan changes (capacity changes only)
            // Adjustments are applied in the NEXT billing cycle after the change was effective
            $adjustments = [];
            $adjustmentTotal = 0.0;
            
            if ($lastPaidInvoice && $lastPaidInvoice->billing_start_date && $lastPaidInvoice->billing_end_date) {
                // Find approved plan changes that were effective in the previous billing period
                // but haven't been adjusted yet (capacity changes only, not billing cycle changes)
                $previousPeriodStart = \Carbon\Carbon::parse($lastPaidInvoice->billing_start_date);
                $previousPeriodEnd = \Carbon\Carbon::parse($lastPaidInvoice->billing_end_date);
                
                $pendingAdjustments = PlanChangeRequest::where('application_id', $application->id)
                    ->where('status', 'approved')
                    ->where('adjustment_applied', false)
                    ->whereNotNull('effective_from')
                    ->where(function ($query) use ($previousPeriodStart, $previousPeriodEnd) {
                        // Changes that were effective during the previous billing period
                        $query->whereBetween('effective_from', [$previousPeriodStart, $previousPeriodEnd])
                            ->orWhere(function ($q) use ($previousPeriodStart, $previousPeriodEnd) {
                                // Or changes that were effective before but adjustment not yet applied
                                $q->where('effective_from', '<=', $previousPeriodEnd)
                                  ->where('effective_from', '>=', $previousPeriodStart->copy()->subMonths(6)); // Within last 6 months
                            });
                    })
                    ->get();
                
                foreach ($pendingAdjustments as $adjustment) {
                    // Only apply adjustments for capacity changes (upgrade/downgrade)
                    // Billing cycle changes don't create adjustments, they just change future billing
                    if ($adjustment->isCapacityChange() && $adjustment->adjustment_amount != 0) {
                        $adjustmentAmount = (float) $adjustment->adjustment_amount;
                        $adjustmentTotal += $adjustmentAmount;
                        
                        $adjustments[] = [
                            'plan_change_id' => $adjustment->id,
                            'type' => $adjustment->change_type, // 'upgrade' or 'downgrade'
                            'description' => $adjustment->change_type === 'upgrade' 
                                ? "Upgrade adjustment: {$adjustment->current_port_capacity} → {$adjustment->new_port_capacity}"
                                : "Downgrade adjustment: {$adjustment->current_port_capacity} → {$adjustment->new_port_capacity}",
                            'effective_from' => $adjustment->effective_from ? $adjustment->effective_from->format('Y-m-d') : null,
                            'amount' => $adjustmentAmount,
                        ];
                        
                        Log::info("Adding adjustment for plan change {$adjustment->id}: {$adjustment->change_type}, amount: ₹{$adjustmentAmount}");
                    }
                }
            }
            
            // Base amount is proration total + adjustments
            $baseAmount = $prorationTotal + $adjustmentTotal;

            // Calculate GST based on state (IGST for non-Delhi, CGST+SGST for Delhi)
            $gstVerification = GstVerification::where('user_id', $application->user_id)
                ->where('is_verified', true)
                ->latest()
                ->first();
            $gstState = $location->state ?? ($gstVerification?->state);

            $gstPercentage = 18.00;
            $isDelhi = strtolower($gstState ?? '') === 'delhi' || strtolower($gstState ?? '') === 'new delhi';

            if ($isDelhi) {
                $cgstAmount = round(($baseAmount * 9) / 100, 2);
                $sgstAmount = round(($baseAmount * 9) / 100, 2);
                $gstAmount = $cgstAmount + $sgstAmount;
            } else {
                $gstAmount = round(($baseAmount * 18) / 100, 2);
                $cgstAmount = 0;
                $sgstAmount = 0;
            }

            $amount = $baseAmount; // Prorated total + adjustments
            $totalAmount = round($amount + $gstAmount, 2);

            if ($amount <= 0 || $totalAmount <= 0) {
                Log::error("Invalid invoice calculation for application {$application->id}: amount={$amount}, gstAmount={$gstAmount}, totalAmount={$totalAmount}");
                return back()->with('error', 'Invalid invoice calculation. Please check port capacity and pricing configuration.');
            }

            Log::info("Invoice calculation for application {$application->id}: prorated amount={$prorationTotal}, adjustments={$adjustmentTotal}, base amount={$baseAmount}, gstAmount={$gstAmount}, totalAmount={$totalAmount}", [
                'segments' => $segments,
                'adjustments' => $adjustments,
            ]);

            // Generate invoice number (ensure uniqueness)
            $baseInvoiceNumber = 'NIXI-IX-'.date('y').'-'.(date('y') + 1).'/'.str_pad($application->id, 4, '0', STR_PAD_LEFT);
            $invoiceNumber = $baseInvoiceNumber;
            
            // Always include billing period if available to ensure uniqueness for recurring invoices
            if ($billingPeriod) {
                $invoiceNumber .= '-'.$billingPeriod;
            }
            
            // Check if invoice number already exists and make it unique
            $counter = 1;
            $originalInvoiceNumber = $invoiceNumber;
            while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                // If billing period was included, append counter
                if ($billingPeriod) {
                    $invoiceNumber = $originalInvoiceNumber.'-'.$counter;
                } else {
                    // If no billing period, append counter directly
                    $invoiceNumber = $baseInvoiceNumber.'-'.$counter;
                }
                $counter++;
                
                // Safety check to prevent infinite loop
                if ($counter > 100) {
                    Log::error("Unable to generate unique invoice number for application {$application->id} after 100 attempts");
                    return back()->with('error', 'Unable to generate unique invoice number. Please contact support.');
                }
            }
            
            Log::info("Generated invoice number: {$invoiceNumber} for application {$application->id}, billing period: {$billingPeriod}");

            // Generate PayU payment link
            $payuService = new \App\Services\PayuService();
            $transactionId = 'INV-'.time().'-'.strtoupper(\Illuminate\Support\Str::random(8));
            
            // Create PaymentTransaction for invoice payment
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $application->user_id,
                'application_id' => $application->id,
                'transaction_id' => $transactionId,
                'payment_status' => 'pending',
                'payment_mode' => 'live',
                'amount' => $totalAmount,
                'currency' => 'INR',
                'product_info' => 'NIXI IX Service Invoice - '.$invoiceNumber,
                'response_message' => 'Invoice payment pending',
            ]);
            
            $paymentData = $payuService->preparePaymentData([
                'transaction_id' => $transactionId,
                'amount' => $totalAmount,
                'product_info' => 'NIXI IX Service Invoice - '.$invoiceNumber,
                'firstname' => $application->user->fullname,
                'email' => $application->user->email,
                'phone' => $application->user->mobile,
                'success_url' => url(route('user.applications.ix.payment-success', [], false)),
                'failure_url' => url(route('user.applications.ix.payment-failure', [], false)),
                'udf1' => $application->application_id,
                'udf2' => (string) $paymentTransaction->id, // Store payment transaction ID
                'udf3' => $invoiceNumber,
            ]);
            
            // Ensure due_date is properly formatted as date string
            $dueDateFormatted = $dueDate instanceof \Carbon\Carbon ? $dueDate->format('Y-m-d') : $dueDate;
            
            Log::info("Creating invoice for application {$application->id}: invoiceNumber='{$invoiceNumber}', invoiceDate=" . now('Asia/Kolkata')->format('Y-m-d') . ", dueDate={$dueDateFormatted}, billingPeriod='{$billingPeriod}', billingPlan='{$billingPlan}'");
            
            // Create invoice record with adjustments
            // Structure line_items: segments array + adjustments metadata
            $lineItemsData = $segments;
            if (!empty($adjustments)) {
                $lineItemsData['_metadata'] = [
                    'adjustments' => $adjustments,
                    'adjustment_total' => $adjustmentTotal,
                    'proration_total' => $prorationTotal,
                ];
            }
            
            $invoice = Invoice::create([
                'application_id' => $application->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => now('Asia/Kolkata'),
                'due_date' => $dueDateFormatted,
                'billing_period' => $billingPeriod,
                'billing_start_date' => $billingStartDate->format('Y-m-d'),
                'billing_end_date' => $billingEndDate->format('Y-m-d'),
                'line_items' => $lineItemsData,
                'amount' => $amount,
                'gst_amount' => $gstAmount,
                'total_amount' => $totalAmount,
                'currency' => 'INR',
                'status' => 'pending',
                'payu_payment_link' => json_encode($paymentData), // Store full payment data
                'generated_by' => $admin->id,
            ]);
            
            // Mark adjustments as applied
            if (!empty($adjustments)) {
                foreach ($adjustments as $adj) {
                    PlanChangeRequest::where('id', $adj['plan_change_id'])->update([
                        'adjustment_applied' => true,
                        'adjustment_invoice_id' => $invoice->id,
                    ]);
                }
                Log::info("Marked " . count($adjustments) . " adjustments as applied for invoice {$invoice->id}");
            }
            
            Log::info("Invoice created successfully: ID={$invoice->id}, due_date={$invoice->due_date}, billing_start_date={$invoice->billing_start_date}, billing_end_date={$invoice->billing_end_date}");
            
            // Don't update service_activation_date - it should remain as the original activation date
            // The billing dates are now stored in the invoice itself
            
            // Generate invoice PDF
            try {
                $invoicePdf = $this->generateIxInvoicePdf($application, $invoice);
                $invoicePdfPath = 'applications/'.$application->user_id.'/ix/'.$invoiceNumber.'_invoice.pdf';

                Storage::disk('public')->put($invoicePdfPath, $invoicePdf->output());
                
                // Update invoice with PDF path
                $invoice->update(['pdf_path' => $invoicePdfPath]);
            } catch (Exception $e) {
                Log::error('Error generating IX invoice PDF: '.$e->getMessage());
            }

            // Log invoice generation
            ApplicationStatusHistory::log(
                $application->id,
                $application->status,
                $application->status, // Keep same status
                'admin',
                $admin->id,
                'Invoice generated by IX Account - '.$invoiceNumber
            );

            // Send invoice email with PayU link
            try {
                Mail::to($application->user->email)->send(new IxApplicationInvoiceMail(
                    $application->user->fullname,
                    $application->application_id,
                    $invoiceNumber,
                    $totalAmount,
                    $application->status,
                    $invoicePdfPath ?? null,
                    $payuService->getPaymentUrl(),
                    $paymentData
                ));

                $invoice->update(['sent_at' => now('Asia/Kolkata')]);
                Log::info("IX invoice email sent to {$application->user->email} for application {$application->application_id}");
            } catch (Exception $e) {
                Log::error('Error sending invoice email: '.$e->getMessage());
            }

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Invoice Generated',
                'message' => "Invoice {$invoiceNumber} has been generated for your application {$application->application_id}. Please complete the payment using the PayU link sent to your email.",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            $periodLabel = $billingPeriod ? ' ('.$this->getBillingPeriodLabel($application->billing_cycle, $billingPeriod).')' : '';
            return back()->with('success', "Invoice generated and sent to user{$periodLabel}!");
        } catch (Exception $e) {
            Log::error('Error generating invoice: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Account: Mark invoice as paid manually with payment ID and notes.
     */
    public function ixAccountMarkInvoicePaid(Request $request, $invoiceId)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $invoice = Invoice::with('application.user')->findOrFail($invoiceId);

            if (! $invoice->application || $invoice->application->application_type !== 'IX') {
                return back()->with('error', 'Invalid invoice or application.');
            }

            if (! $invoice->application->is_active) {
                return back()->with('error', 'Invoice can only be managed for LIVE applications.');
            }

            if ($invoice->status === 'paid') {
                return back()->with('error', 'This invoice is already marked as paid.');
            }

            $validated = $request->validate([
                'payment_id' => 'required|string|max:255',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Update invoice as paid with manual details
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now('Asia/Kolkata'),
                'paid_by' => $admin->id,
                'manual_payment_id' => $validated['payment_id'],
                'manual_payment_notes' => $validated['notes'] ?? null,
            ]);

            // Create manual payment transaction record
            PaymentTransaction::create([
                'user_id' => $invoice->application->user_id,
                'application_id' => $invoice->application_id,
                'transaction_id' => 'MANUAL-'.time().'-'.strtoupper(Str::random(6)),
                'payment_status' => 'success',
                'payment_mode' => 'manual',
                'payment_id' => $validated['payment_id'],
                'amount' => $invoice->total_amount,
                'currency' => 'INR',
                'product_info' => 'Manual payment for invoice '.$invoice->invoice_number,
                'response_message' => $validated['notes'] ?? 'Manual payment recorded by IX Account',
            ]);

            // Create payment verification log to avoid re-verification later
            $billingPeriod = $invoice->billing_period;
            $verificationType = $billingPeriod ? 'recurring' : 'initial';
            $existingVerification = null;
            if ($billingPeriod) {
                $existingVerification = PaymentVerificationLog::where('application_id', $invoice->application_id)
                    ->where('billing_period', $billingPeriod)
                    ->first();
            }

            if (! $existingVerification) {
                PaymentVerificationLog::create([
                    'application_id' => $invoice->application_id,
                    'verified_by' => $admin->id,
                    'verification_type' => $verificationType,
                    'billing_period' => $billingPeriod,
                    'amount' => $invoice->total_amount,
                    'currency' => 'INR',
                    'payment_method' => 'manual',
                    'notes' => $validated['notes'] ?? null,
                    'verified_at' => now('Asia/Kolkata'),
                ]);
            }

            // Inform user via message
            Message::create([
                'user_id' => $invoice->application->user_id,
                'subject' => 'Payment Recorded',
                'message' => "Your invoice {$invoice->invoice_number} has been marked as paid. Payment ID: {$validated['payment_id']}",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            Log::info('Invoice marked as paid manually', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'admin_id' => $admin->id,
                'payment_id' => $validated['payment_id'],
            ]);

            return back()->with('success', 'Invoice marked as paid successfully.');
        } catch (Exception $e) {
            Log::error('Error marking invoice as paid manually: '.$e->getMessage());

            return back()->with('error', 'Unable to mark invoice as paid. Please try again.');
        }
    }

    /**
     * Generate IX Invoice PDF.
     */
    private function generateIxInvoicePdf(Application $application, ?Invoice $invoice = null)
    {
        $data = $application->application_data ?? [];
        $user = $application->user;

        // Check if this is first application or subsequent
        $isFirstApplication = Application::where('user_id', $user->id)
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
                $gstVerification = GstVerification::find($kyc->gst_verification_id);
            }
        } else {
            // Subsequent application: Get from GST verification used in this application
            if ($application->gst_verification_id) {
                $gstVerification = GstVerification::find($application->gst_verification_id);
            } else {
                // Fallback: Get latest verified GST for this application's GSTIN
                $applicationGstin = $data['gstin'] ?? null;
                if ($applicationGstin) {
                    $gstVerification = GstVerification::where('user_id', $user->id)
                        ->where('gstin', $applicationGstin)
                        ->where('is_verified', true)
                        ->latest()
                        ->first();
                }
            }
        }
        
        // If still no GST verification, get latest one
        if (!$gstVerification) {
            $gstVerification = GstVerification::where('user_id', $user->id)
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
        
        // Get place of supply from IX location
        $placeOfSupply = null;
        if (isset($data['location']['id'])) {
            $location = IxLocation::find($data['location']['id']);
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

        // Get application pricing for fallback
        $applicationPricing = IxApplicationPricing::getActive();

        // Use invoice number from invoice record if provided
        $invoiceNumber = $invoice ? $invoice->invoice_number : 'NIXI-IX-'.date('y').'-'.(date('y') + 1).'/'.str_pad($application->id, 4, '0', STR_PAD_LEFT);
        $invoiceDate = $invoice ? $invoice->invoice_date->format('d/m/Y') : now('Asia/Kolkata')->format('d/m/Y');
        $dueDate = $invoice ? $invoice->due_date->format('d/m/Y') : now('Asia/Kolkata')->addDays(28)->format('d/m/Y');

        // Recalculate invoice amounts using latest calculation logic (even for existing invoices)
        // This ensures old invoices show correct amounts after fixes
        $recalculatedAmounts = $this->recalculateInvoiceAmounts($application, $invoice);
        
        // Create a temporary invoice object with recalculated amounts for the view
        // This ensures the view uses correct amounts even if invoice record has old values
        $invoiceForView = $invoice ? clone $invoice : null;
        if ($invoiceForView) {
            $invoiceForView->amount = $recalculatedAmounts['amount'];
            $invoiceForView->gst_amount = $recalculatedAmounts['gst_amount'];
            $invoiceForView->total_amount = $recalculatedAmounts['total_amount'];
        }

        $pdf = Pdf::loadView('user.applications.ix.pdf.invoice', [
            'application' => $application,
            'user' => $user,
            'data' => $data,
            'buyerDetails' => $buyerDetails,
            'placeOfSupply' => $placeOfSupply,
            'attnName' => $attnName,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDate' => $invoiceDate,
            'dueDate' => $dueDate,
            'invoice' => $invoiceForView ?? $invoice, // Use recalculated amounts
            'gstVerification' => $gstVerification,
        ])->setPaper('a4', 'portrait')
            ->setOption('margin-top', 6)
            ->setOption('margin-bottom', 6)
            ->setOption('margin-left', 6)
            ->setOption('margin-right', 6)
            ->setOption('enable-local-file-access', true);

        return $pdf;
    }

    /**
     * Recalculate invoice amounts using latest calculation logic.
     * This ensures old invoices show correct amounts after fixes.
     */
    private function recalculateInvoiceAmounts(Application $application, ?Invoice $invoice = null): array
    {
        $applicationData = $application->application_data ?? [];
        
        // Get port amount based on billing cycle
        $billingPlan = $application->billing_cycle ?? ($applicationData['port_selection']['billing_plan'] ?? 'monthly');
        
        // Map billing plan to pricing plan
        $pricingPlan = match($billingPlan) {
            'annual', 'arc' => 'arc',
            'monthly', 'mrc' => 'mrc',
            'quarterly' => 'quarterly',
            default => 'mrc',
        };
        
        // Determine port capacity (same logic as ixAccountGenerateInvoice)
        $portCapacity = null;
        
        // Check for pending plan change request
        $pendingPlanChange = \App\Models\PlanChangeRequest::where('application_id', $application->id)
            ->where('status', 'pending')
            ->latest()
            ->first();
        
        if ($pendingPlanChange) {
            $portCapacity = $pendingPlanChange->current_port_capacity ?? $application->assigned_port_capacity ?? ($applicationData['port_selection']['capacity'] ?? null);
            Log::info("Pending plan change found for application {$application->id}, using current capacity: {$portCapacity}");
        } else {
            // Check for approved plan change that has taken effect
            $effectivePlanChange = \App\Models\PlanChangeRequest::where('application_id', $application->id)
                ->where('status', 'approved')
                ->where(function ($query) {
                    $query->whereNull('effective_from')
                        ->orWhere('effective_from', '<=', now('Asia/Kolkata'));
                })
                ->latest('effective_from')
                ->first();
            
            if ($effectivePlanChange && $effectivePlanChange->effective_from && $effectivePlanChange->effective_from <= now('Asia/Kolkata')) {
                // Use new capacity if effective_from has passed
                $portCapacity = $effectivePlanChange->new_port_capacity ?? $application->assigned_port_capacity ?? ($applicationData['port_selection']['capacity'] ?? null);
                Log::info("Effective plan change found for application {$application->id}, using new capacity: {$portCapacity} (effective from {$effectivePlanChange->effective_from})");
            } else {
                // No plan change or not yet effective, use assigned capacity
                $portCapacity = $application->assigned_port_capacity ?? ($applicationData['port_selection']['capacity'] ?? null);
            }
        }
        
        // Get pricing for the port capacity
        $location = null;
        if (isset($applicationData['location']['id'])) {
            $location = IxLocation::find($applicationData['location']['id']);
        }
        
        $portAmount = 0;
        if ($location && $portCapacity) {
            // Normalize port capacity format (same logic as ixAccountGenerateInvoice)
            $normalizedCapacity = trim($portCapacity);
            $normalizedCapacity = preg_replace('/\s+/', '', $normalizedCapacity);
            
            if (stripos($normalizedCapacity, 'Gbps') !== false || stripos($normalizedCapacity, 'gbps') !== false) {
                $normalizedCapacity = str_ireplace(['Gbps', 'gbps', 'GBPS'], 'Gig', $normalizedCapacity);
            }
            
            if (!preg_match('/(Gig|M)$/i', $normalizedCapacity)) {
                if (preg_match('/^\d+$/', $normalizedCapacity)) {
                    $normalizedCapacity = $normalizedCapacity . 'Gig';
                }
            }
            
            // Get pricing
            $pricing = \App\Models\IxPortPricing::active()
                ->where('node_type', $location->node_type)
                ->where('port_capacity', $normalizedCapacity)
                ->first();
            
            // Try variations if exact match not found
            if (!$pricing) {
                $variations = [
                    trim($portCapacity),
                    str_replace(' ', '', trim($portCapacity)),
                    preg_replace('/\s+/', '', trim($portCapacity)),
                    str_replace(['Gbps', 'gbps', 'GBPS'], 'Gig', str_replace(' ', '', trim($portCapacity))),
                    str_replace(['Gbps', 'gbps'], 'Gig', trim($portCapacity)),
                    str_replace([' Gbps', 'Gbps', 'gbps'], 'Gig', trim($portCapacity)),
                ];
                
                foreach (array_unique($variations) as $variation) {
                    if (empty($variation)) continue;
                    $pricing = \App\Models\IxPortPricing::active()
                        ->where('node_type', $location->node_type)
                        ->where('port_capacity', $variation)
                        ->first();
                    if ($pricing) {
                        break;
                    }
                }
            }
            
            if ($pricing) {
                $portAmount = (float) $pricing->getAmountForPlan($pricingPlan);
            }
        }
        
        // Calculate GST based on state
        $gstState = null;
        if ($location) {
            $gstState = $location->state;
        } else {
            $gstVerification = GstVerification::where('user_id', $application->user_id)
                ->where('is_verified', true)
                ->latest()
                ->first();
            $gstState = $gstVerification?->state;
        }
        
        $isDelhi = strtolower($gstState ?? '') === 'delhi' || strtolower($gstState ?? '') === 'new delhi';
        
        if ($isDelhi) {
            $cgstAmount = round(($portAmount * 9) / 100, 2);
            $sgstAmount = round(($portAmount * 9) / 100, 2);
            $gstAmount = $cgstAmount + $sgstAmount;
        } else {
            $gstAmount = round(($portAmount * 18) / 100, 2);
        }
        
        $amount = $portAmount;
        $totalAmount = round($amount + $gstAmount, 2);
        
        return [
            'amount' => $amount,
            'gst_amount' => $gstAmount,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Recalculate invoice due date using latest calculation logic.
     * This ensures old invoices show correct due dates after fixes.
     */
    private function recalculateInvoiceDueDate(Application $application, ?Invoice $invoice = null): \Carbon\Carbon
    {
        $applicationData = $application->application_data ?? [];
        
        // Get billing cycle
        $billingPlanRaw = $application->billing_cycle ?? ($applicationData['port_selection']['billing_plan'] ?? 'monthly');
        $billingPlan = strtolower(trim($billingPlanRaw));
        if (in_array($billingPlan, ['arc', 'annual'])) {
            $billingPlan = 'annual';
        } elseif (in_array($billingPlan, ['mrc', 'monthly'])) {
            $billingPlan = 'monthly';
        } elseif ($billingPlan === 'quarterly') {
            $billingPlan = 'quarterly';
        } else {
            $billingPlan = 'monthly';
        }
        
        // Determine start date
        $startDate = null;
        
        if ($invoice) {
            // Check for previous paid invoices to determine start date
            $lastPaidInvoice = Invoice::where('application_id', $application->id)
                ->where('status', 'paid')
                ->where('id', '<', $invoice->id)
                ->latest('invoice_date')
                ->first();
            
            if ($lastPaidInvoice && $lastPaidInvoice->due_date) {
                // Subsequent invoice: start from last invoice's due date
                $startDate = \Carbon\Carbon::parse($lastPaidInvoice->due_date);
            } elseif ($application->service_activation_date) {
                // First invoice: start from service activation date
                $startDate = \Carbon\Carbon::parse($application->service_activation_date);
            } else {
                // Fallback: use invoice date
                $startDate = \Carbon\Carbon::parse($invoice->invoice_date);
            }
        } elseif ($application->service_activation_date) {
            // No invoice yet, use service activation date
            $startDate = \Carbon\Carbon::parse($application->service_activation_date);
        } else {
            // Fallback: use current date
            $startDate = now('Asia/Kolkata');
        }
        
        // Calculate end date (due date) based on billing cycle
        switch ($billingPlan) {
            case 'annual':
            case 'arc':
                $dueDate = $startDate->copy()->addYear();
                break;
            case 'quarterly':
                $dueDate = $startDate->copy()->addMonths(3);
                break;
            case 'monthly':
            case 'mrc':
            default:
                $dueDate = $startDate->copy()->addMonth();
                break;
        }
        
        return $dueDate;
    }

    /**
     * Get current billing period based on billing cycle and service activation date.
     */
    private function getCurrentBillingPeriod(Application $application): ?string
    {
        if (!$application->service_activation_date || !$application->billing_cycle) {
            return null;
        }

        $activationDate = \Carbon\Carbon::parse($application->service_activation_date);
        $now = now('Asia/Kolkata');

        switch ($application->billing_cycle) {
            case 'monthly':
                $monthsSinceActivation = $activationDate->diffInMonths($now);
                $periodDate = $activationDate->copy()->addMonths($monthsSinceActivation);
                return $periodDate->format('Y-m');
            
            case 'quarterly':
                $quartersSinceActivation = floor($activationDate->diffInMonths($now) / 3);
                $periodDate = $activationDate->copy()->addMonths($quartersSinceActivation * 3);
                $quarter = ceil($periodDate->month / 3);
                return $periodDate->format('Y').'-Q'.$quarter;
            
            case 'annual':
                $yearsSinceActivation = $activationDate->diffInYears($now);
                $periodDate = $activationDate->copy()->addYears($yearsSinceActivation);
                return $periodDate->format('Y');
            
            default:
                return null;
        }
    }

    /**
     * Check if payment is already verified for current billing period.
     */
    private function isPaymentVerifiedForPeriod(Application $application, string $billingPeriod): bool
    {
        return \App\Models\PaymentVerificationLog::where('application_id', $application->id)
            ->where('billing_period', $billingPeriod)
            ->exists();
    }

    /**
     * IX Account: Verify Payment (supports recurring payments).
     */
    public function ixAccountVerifyPayment(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            // Only allow verification for LIVE applications
            if (!$application->is_active) {
                return back()->with('error', 'Payment can only be verified for LIVE applications.');
            }

            if (! $application->isVisibleToIxAccount()) {
                return back()->with('error', 'This application is not available for Account review.');
            }

            // Check if this is initial or recurring payment
            $isInitialPayment = !$application->service_activation_date;
            $billingPeriod = null;
            $verificationType = 'initial';

            if (!$isInitialPayment) {
                $billingPeriod = $this->getCurrentBillingPeriod($application);
                if ($billingPeriod) {
                    $verificationType = 'recurring';
                    
                    // Check if already verified for this period
                    if ($this->isPaymentVerifiedForPeriod($application, $billingPeriod)) {
                        $periodLabel = $this->getBillingPeriodLabel($application->billing_cycle, $billingPeriod);
                        return back()->with('error', "Payment for this {$periodLabel} has already been verified.");
                    }
                }
            }

            // Get payment amount
            $applicationData = $application->application_data ?? [];
            $amount = $applicationData['payment']['total_amount'] ?? $applicationData['payment']['amount'] ?? 0;
            
            // Create payment verification log
            $verificationLog = \App\Models\PaymentVerificationLog::create([
                'application_id' => $application->id,
                'verified_by' => $admin->id,
                'verification_type' => $verificationType,
                'billing_period' => $billingPeriod,
                'amount' => $amount,
                'currency' => 'INR',
                'payment_method' => 'manual',
                'notes' => $request->input('notes'),
                'verified_at' => now('Asia/Kolkata'),
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                $application->status,
                $application->status, // Keep same status, don't change application status
                'admin',
                $admin->id,
                $verificationType === 'initial' 
                    ? 'Initial payment verified by IX Account'
                    : "Recurring payment verified for {$billingPeriod} by IX Account"
            );

            // Send message to user
            $periodLabel = $billingPeriod ? $this->getBillingPeriodLabel($application->billing_cycle, $billingPeriod) : 'initial';
            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Payment Verified',
                'message' => "Payment has been verified for your application {$application->application_id} ({$periodLabel} payment).",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            $periodLabel = $billingPeriod ? $this->getBillingPeriodLabel($application->billing_cycle, $billingPeriod) : 'initial';
            return back()->with('success', "Payment verified successfully for {$periodLabel} period!");
        } catch (Exception $e) {
            Log::error('Error verifying payment: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Get billing period label for display.
     */
    private function getBillingPeriodLabel(string $billingCycle, string $billingPeriod): string
    {
        switch ($billingCycle) {
            case 'monthly':
                $date = \Carbon\Carbon::createFromFormat('Y-m', $billingPeriod);
                return $date->format('F Y');
            
            case 'quarterly':
                return str_replace('-Q', ' Q', $billingPeriod);
            
            case 'annual':
                return $billingPeriod;
            
            default:
                return $billingPeriod;
        }
    }

    /**
     * Show update application form.
     */
    public function editApplication($id)
    {
        try {
            $application = Application::with(['user'])->findOrFail($id);
            $applicationData = $application->application_data ?? [];
            $documents = $applicationData['documents'] ?? [];

            return view('admin.applications.edit', compact('application', 'applicationData', 'documents'));
        } catch (Exception $e) {
            Log::error('Error loading application edit page: '.$e->getMessage());

            return redirect()->route('admin.applications')
                ->with('error', 'Application not found.');
        }
    }

    /**
     * Update application documents and details.
     */
    public function updateApplication(Request $request, $id)
    {
        try {
            $application = Application::findOrFail($id);
            $applicationData = $application->application_data ?? [];
            $existingDocuments = $applicationData['documents'] ?? [];

            // Validate document uploads
            $request->validate([
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
                // Allow updating application details
                'representative_name' => 'nullable|string|max:255',
                'representative_email' => 'nullable|email|max:255',
                'representative_mobile' => 'nullable|string|size:10|regex:/^[0-9]{10}$/',
                'gstin' => 'nullable|string|size:15|regex:/^[0-9A-Z]{15}$/',
                'port_capacity' => 'nullable|string|max:50',
                'billing_plan' => 'nullable|string|in:arc,mrc,quarterly',
                'ip_prefix_count' => 'nullable|integer|min:1|max:500',
            ]);

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

            $updatedDocuments = $existingDocuments;
            $storagePrefix = 'applications/'.$application->user_id.'/ix/'.now()->format('YmdHis');

            // Handle file uploads
            foreach ($documentFields as $field) {
                if ($request->hasFile($field)) {
                    $updatedDocuments[$field] = $request->file($field)
                        ->store($storagePrefix, 'public');
                }
            }

            // Update application data
            $updatedData = $applicationData;

            // Update documents
            $updatedData['documents'] = $updatedDocuments;

            // Update application details if provided
            if ($request->filled('representative_name')) {
                if (!isset($updatedData['representative'])) {
                    $updatedData['representative'] = [];
                }
                $updatedData['representative']['name'] = $request->input('representative_name');
            }

            if ($request->filled('representative_email')) {
                if (!isset($updatedData['representative'])) {
                    $updatedData['representative'] = [];
                }
                $updatedData['representative']['email'] = $request->input('representative_email');
            }

            if ($request->filled('representative_mobile')) {
                if (!isset($updatedData['representative'])) {
                    $updatedData['representative'] = [];
                }
                $updatedData['representative']['mobile'] = $request->input('representative_mobile');
            }

            if ($request->filled('gstin')) {
                $updatedData['gstin'] = strtoupper(preg_replace('/[^A-Z0-9]/', '', $request->input('gstin')));
            }

            if ($request->filled('port_capacity')) {
                if (!isset($updatedData['port_selection'])) {
                    $updatedData['port_selection'] = [];
                }
                $updatedData['port_selection']['capacity'] = $request->input('port_capacity');
            }

            if ($request->filled('billing_plan')) {
                if (!isset($updatedData['port_selection'])) {
                    $updatedData['port_selection'] = [];
                }
                $updatedData['port_selection']['billing_plan'] = $request->input('billing_plan');
            }

            if ($request->filled('ip_prefix_count')) {
                if (!isset($updatedData['ip_prefix'])) {
                    $updatedData['ip_prefix'] = [];
                }
                $updatedData['ip_prefix']['count'] = $request->input('ip_prefix_count');
            }

            // Update application
            $application->update([
                'application_data' => $updatedData,
            ]);

            return redirect()->route('admin.applications.show', $id)
                ->with('success', 'Application updated successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error updating application: '.$e->getMessage());

            return back()->with('error', 'An error occurred while updating the application.');
        }
    }
}
