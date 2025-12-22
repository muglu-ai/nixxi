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
use App\Models\McaVerification;
use App\Models\Message;
use App\Models\PanVerification;
use App\Models\PaymentTransaction;
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

            // Calculate statistics (only active applications)
            $totalUsers = Registration::count();
            $totalApplications = Application::where('is_active', true)->count();
            $approvedApplications = Application::whereIn('status', ['approved', 'payment_verified'])->count();
            
            // Approved applications with payment verification
            $approvedApplicationsWithPayment = Application::whereIn('status', ['approved', 'payment_verified'])
                ->whereHas('paymentTransactions', function ($q) {
                    $q->where('payment_status', 'success');
                })
                ->count();
            
            // Member Statistics (Registrations that have at least one application with membership_id and is_active = true)
            $totalMembers = Registration::whereHas('applications', function ($query) {
                $query->whereNotNull('membership_id')
                    ->where('is_active', true);
            })->distinct()->count();
            
            // Active members: Have membership_id AND at least one application with active status and is_active = true
            $activeMembers = Registration::whereHas('applications', function ($query) {
                $query->whereNotNull('membership_id')
                    ->where('is_active', true)
                    ->whereIn('status', ['ip_assigned', 'payment_verified', 'approved']);
            })->distinct()->count();
            
            // Disconnected members: Have membership_id but no active applications (is_active = true)
            $disconnectedMembers = Registration::whereHas('applications', function ($query) {
                $query->whereNotNull('membership_id')
                    ->where('is_active', true);
            })
            ->whereDoesntHave('applications', function ($query) {
                $query->whereNotNull('membership_id')
                    ->where('is_active', true)
                    ->whereIn('status', ['ip_assigned', 'payment_verified', 'approved']);
            })
            ->distinct()
            ->count();
            
            // Recent Live Members (applications with status 'ip_assigned' in last 30 days and is_active = true)
            $recentLiveMembers = Application::with('user')
                ->where('status', 'ip_assigned')
                ->where('is_active', true)
                ->where('updated_at', '>=', now()->subDays(30))
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

            // Pending applications based on selected role (only active)
            $pendingApplications = 0;
            $roleToUse = $selectedRole;
            if ($admin->roles->count() === 1) {
                $roleToUse = $admin->roles->first()->slug;
            }

            // New IX workflow roles
            if ($roleToUse === 'ix_processor') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('is_active', true)
                    ->whereIn('status', ['submitted', 'resubmitted', 'processor_resubmission', 'legal_sent_back', 'head_sent_back'])
                    ->count();
            } elseif ($roleToUse === 'ix_legal') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('is_active', true)
                    ->where('status', 'processor_forwarded_legal')
                    ->count();
            } elseif ($roleToUse === 'ix_head') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('is_active', true)
                    ->where('status', 'legal_forwarded_head')
                    ->count();
            } elseif ($roleToUse === 'ceo') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('is_active', true)
                    ->where('status', 'head_forwarded_ceo')
                    ->count();
            } elseif ($roleToUse === 'nodal_officer') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('is_active', true)
                    ->where('status', 'ceo_approved')
                    ->count();
            } elseif ($roleToUse === 'ix_tech_team') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('is_active', true)
                    ->where('status', 'port_assigned')
                    ->count();
            } elseif ($roleToUse === 'ix_account') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('is_active', true)
                    ->whereIn('status', ['ip_assigned', 'invoice_pending'])
                    ->count();
            } elseif ($roleToUse === 'processor') {
                // Legacy
                $pendingApplications = Application::where('is_active', true)
                    ->whereIn('status', ['pending', 'processor_review'])
                    ->count();
            } elseif ($roleToUse === 'finance') {
                // Legacy
                $pendingApplications = Application::where('is_active', true)
                    ->whereIn('status', ['processor_approved', 'finance_review'])
                    ->count();
            } elseif ($roleToUse === 'technical') {
                // Legacy
                $pendingApplications = Application::where('is_active', true)
                    ->where('status', 'finance_approved')
                    ->count();
            } else {
                // If no role selected, show all pending IX applications
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('is_active', true)
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
                'pendingGrievances'
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
    public function showUser($id)
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

            return view('admin.users.show', compact('user', 'adminActions', 'admin'));
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
                $query->whereNotNull('membership_id')
                    ->where('is_active', true);
            });

            if ($filter === 'active') {
                $query->whereHas('applications', function ($q) {
                    $q->whereNotNull('membership_id')
                        ->where('is_active', true)
                        ->whereIn('status', ['ip_assigned', 'payment_verified', 'approved']);
                });
            } elseif ($filter === 'disconnected') {
                $query->whereHas('applications', function ($q) {
                    $q->whereNotNull('membership_id')
                        ->where('is_active', true);
                })
                ->whereDoesntHave('applications', function ($q) {
                    $q->whereNotNull('membership_id')
                        ->where('is_active', true)
                        ->whereIn('status', ['ip_assigned', 'payment_verified', 'approved']);
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
                ? 'Member activated successfully! Application is now visible to user and admin.'
                : 'Member deactivated successfully! Application is now hidden from user and admin.';

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
            // Filter out deactivated members (applications with is_active = false)
            // Note: Admins can see all applications including inactive ones for management
            // But by default show only active ones. Add ?show_inactive=1 to see all
            if (!$request->get('show_inactive')) {
                $query->where('is_active', true);
            }
            
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
            $application = Application::with(['user', 'processor', 'finance', 'technical', 'statusHistory'])
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

            // Admin can view all applications, but can only take actions on applications for their selected role
            return view('admin.applications.show', compact('application', 'admin', 'selectedRole'));
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
                'subject' => 'Port Assigned',
                'message' => "Port has been assigned for your application {$application->application_id}. Port Capacity: {$validated['assigned_port_capacity']}",
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
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxTechTeam()) {
                return back()->with('error', 'This application is not available for Tech Team review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'ip_assigned',
                'assigned_ip' => $validated['assigned_ip'],
                'customer_id' => $validated['customer_id'],
                'membership_id' => $validated['membership_id'],
                'current_ix_tech_team_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'ip_assigned',
                'admin',
                $admin->id,
                "IP assigned: {$validated['assigned_ip']}, Customer ID: {$validated['customer_id']}, Membership ID: {$validated['membership_id']}"
            );

            // Generate membership and IX invoice (TODO: Implement invoice generation)
            // Send email with details
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationIpAssignedMail(
                        $application,
                        $validated['assigned_ip'],
                        $validated['customer_id'],
                        $validated['membership_id']
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
     * IX Account: Generate Invoice.
     */
    public function ixAccountGenerateInvoice(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxAccount()) {
                return back()->with('error', 'This application is not available for Account review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'invoice_pending',
                'current_ix_account_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'invoice_pending',
                'admin',
                $admin->id,
                'Invoice generated by IX Account'
            );

            // Generate invoice PDF
            try {
                $invoicePdf = $this->generateIxInvoicePdf($application);
                $invoicePdfPath = 'applications/'.$application->user_id.'/ix/'.$application->application_id.'_invoice.pdf';

                Storage::disk('public')->put($invoicePdfPath, $invoicePdf->output());

                // Update application data with invoice PDF path
                $applicationData = $application->application_data ?? [];
                if (! isset($applicationData['pdfs'])) {
                    $applicationData['pdfs'] = [];
                }
                $applicationData['pdfs']['invoice_pdf'] = $invoicePdfPath;
                $application->update(['application_data' => $applicationData]);

                // Send invoice email to user
                $invoiceNumber = 'NIXI-IX-'.date('y').'-'.(date('y') + 1).'/'.str_pad($application->id, 4, '0', STR_PAD_LEFT);
                $portAmount = (float) ($applicationData['port_selection']['amount'] ?? 0);
                $applicationFee = (float) ($applicationData['payment']['application_fee'] ?? 1000.00);
                $gstPercentage = (float) ($applicationData['payment']['gst_percentage'] ?? 18.00);
                $gstAmount = ($applicationFee * $gstPercentage) / 100;
                $totalAmount = round($portAmount + $applicationFee + $gstAmount, 2);

                Mail::to($application->user->email)->send(new IxApplicationInvoiceMail(
                    $application->user->fullname,
                    $application->application_id,
                    $invoiceNumber,
                    $totalAmount,
                    $application->status,
                    $invoicePdfPath
                ));

                Log::info("IX invoice email sent to {$application->user->email} for application {$application->application_id}");
            } catch (Exception $e) {
                Log::error('Error generating IX invoice PDF or sending email: '.$e->getMessage());
                // Continue even if PDF generation or email sending fails
            }

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Invoice Generated',
                'message' => "Invoice has been generated for your application {$application->application_id}. Please complete the payment. The invoice PDF has been sent to your email.",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Invoice generated and sent to user!');
        } catch (Exception $e) {
            Log::error('Error generating invoice: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Generate IX Invoice PDF.
     */
    private function generateIxInvoicePdf(Application $application)
    {
        $data = $application->application_data ?? [];
        $user = $application->user;

        // Get company details from GST verification if available
        $gstVerification = GstVerification::where('user_id', $user->id)
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

        // Get application pricing for fallback
        $applicationPricing = IxApplicationPricing::getActive();

        // Calculate invoice number (format: NIXI-IX-25-26/XXXX)
        $invoiceNumber = 'NIXI-IX-'.date('y').'-'.(date('y') + 1).'/'.str_pad($application->id, 4, '0', STR_PAD_LEFT);

        $pdf = Pdf::loadView('user.applications.ix.pdf.invoice', [
            'application' => $application,
            'user' => $user,
            'data' => $data,
            'companyDetails' => $companyDetails,
            'applicationPricing' => $applicationPricing,
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
     * IX Account: Verify Payment.
     */
    public function ixAccountVerifyPayment(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxAccount()) {
                return back()->with('error', 'This application is not available for Account review.');
            }

            $oldStatus = $application->status;
            
            // Update application status
            $application->update([
                'status' => 'payment_verified',
                'current_ix_account_id' => $admin->id,
            ]);

            // Update payment status in application_data
            $applicationData = $application->application_data ?? [];
            if (isset($applicationData['payment'])) {
                $applicationData['payment']['status'] = 'verified';
                $applicationData['payment']['verified_at'] = now('Asia/Kolkata')->toDateTimeString();
                $applicationData['payment']['verified_by'] = $admin->id;
                $application->update(['application_data' => $applicationData]);
            }

            // Update payment transaction status if exists
            $paymentTransaction = PaymentTransaction::where('application_id', $application->id)
                ->latest()
                ->first();
            
            if ($paymentTransaction && $paymentTransaction->payment_status !== 'success') {
                $paymentTransaction->update([
                    'payment_status' => 'success',
                    'response_message' => 'Payment verified by IX Account - '.now('Asia/Kolkata')->format('Y-m-d H:i:s'),
                ]);
            }

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'payment_verified',
                'admin',
                $admin->id,
                'Payment verified by IX Account - Status changed from pending to verified'
            );

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Payment Verified',
                'message' => "Payment has been verified for your application {$application->application_id}. Your application is now complete.",
                'is_read' => false,
                'sent_by' => 'admin',
            ]);

            return back()->with('success', 'Payment verified successfully! Application status updated to verified.');
        } catch (Exception $e) {
            Log::error('Error verifying payment: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
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
