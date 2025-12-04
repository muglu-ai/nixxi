<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Message;
use App\Models\PaymentTransaction;
use App\Models\ProfileUpdateRequest;
use App\Models\Registration;
use App\Models\Role;
use App\Models\SuperAdmin;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PDOException;

class SuperAdminController extends Controller
{
    /**
     * Display the SuperAdmin dashboard.
     */
    public function index()
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            // Recent logged in users (top 5) - using updated_at as proxy for recent activity
            $recentLoggedInUsers = Registration::orderBy('updated_at', 'desc')->take(5)->get();

            // Recent admin activities (top 5) - only login and logout activities
            $recentAdminActivities = AdminAction::with(['admin'])
                ->whereNotNull('admin_id')
                ->where(function ($query) {
                    $query->where('action_type', 'admin_login')
                        ->orWhere('action_type', 'admin_logout');
                })
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Recent messages (top 10) - interactions between admins and users
            $recentMessages = Message::with('user')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            // Get admin names for messages sent by admin
            $recentMessageIds = $recentMessages->pluck('id');
            $recentAdminActions = AdminAction::with('admin')
                ->where('action_type', 'sent_message')
                ->where('actionable_type', Message::class)
                ->whereIn('actionable_id', $recentMessageIds)
                ->get()
                ->keyBy('actionable_id');

            // Admin and Roles Chart Data - New IX Workflow Roles
            $adminsWithRoles = Admin::with('roles')->where('is_super_admin', false)->get();
            // New IX workflow roles
            $roleSlugs = [
                'ix_processor', 'ix_legal', 'ix_head', 'ceo', 'nodal_officer', 'ix_tech_team', 'ix_account',
            ];
            $roles = Role::whereIn('slug', $roleSlugs)->get()->keyBy('slug');

            // Application Details Chart Data
            $totalApplications = Application::count();
            $fullyApproved = Application::where('status', 'approved')->orWhere('status', 'payment_verified')->count();

            // New IX Workflow Roles Statistics
            $ixProcessorApproved = Application::where('application_type', 'IX')
                ->whereIn('status', ['processor_forwarded_legal', 'legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved'])
                ->count();
            $ixProcessorPending = Application::where('application_type', 'IX')
                ->whereIn('status', ['submitted', 'resubmitted', 'processor_resubmission', 'legal_sent_back', 'head_sent_back'])
                ->count();

            $ixLegalApproved = Application::where('application_type', 'IX')
                ->whereIn('status', ['legal_forwarded_head', 'head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved'])
                ->count();
            $ixLegalPending = Application::where('application_type', 'IX')
                ->where('status', 'processor_forwarded_legal')
                ->count();

            $ixHeadApproved = Application::where('application_type', 'IX')
                ->whereIn('status', ['head_forwarded_ceo', 'ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved'])
                ->count();
            $ixHeadPending = Application::where('application_type', 'IX')
                ->where('status', 'legal_forwarded_head')
                ->count();

            $ceoApproved = Application::where('application_type', 'IX')
                ->whereIn('status', ['ceo_approved', 'port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved'])
                ->count();
            $ceoPending = Application::where('application_type', 'IX')
                ->where('status', 'head_forwarded_ceo')
                ->count();

            $nodalOfficerApproved = Application::where('application_type', 'IX')
                ->whereIn('status', ['port_assigned', 'ip_assigned', 'invoice_pending', 'payment_verified', 'approved'])
                ->count();
            $nodalOfficerPending = Application::where('application_type', 'IX')
                ->where('status', 'ceo_approved')
                ->count();

            $ixTechTeamApproved = Application::where('application_type', 'IX')
                ->whereIn('status', ['ip_assigned', 'invoice_pending', 'payment_verified', 'approved'])
                ->count();
            $ixTechTeamPending = Application::where('application_type', 'IX')
                ->where('status', 'port_assigned')
                ->count();

            $ixAccountApproved = Application::where('application_type', 'IX')
                ->whereIn('status', ['payment_verified', 'approved'])
                ->count();
            $ixAccountPending = Application::where('application_type', 'IX')
                ->whereIn('status', ['ip_assigned', 'invoice_pending'])
                ->count();

            return view('superadmin.dashboard', compact(
                'superAdmin',
                'recentLoggedInUsers',
                'recentAdminActivities',
                'recentMessages',
                'recentAdminActions',
                'adminsWithRoles',
                'roles',
                'roleSlugs',
                'totalApplications',
                'fullyApproved',
                // New IX Workflow Roles
                'ixProcessorApproved',
                'ixProcessorPending',
                'ixLegalApproved',
                'ixLegalPending',
                'ixHeadApproved',
                'ixHeadPending',
                'ceoApproved',
                'ceoPending',
                'nodalOfficerApproved',
                'nodalOfficerPending',
                'ixTechTeamApproved',
                'ixTechTeamPending',
                'ixAccountApproved',
                'ixAccountPending'
            ));
        } catch (QueryException $e) {
            Log::error('Database error loading SuperAdmin dashboard: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading SuperAdmin dashboard: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading SuperAdmin dashboard: '.$e->getMessage());
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

            return view('superadmin.users.index', compact('users'));
        } catch (QueryException $e) {
            Log::error('Database error loading users: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading users: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading users: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Unable to load users. Please try again.');
        }
    }

    /**
     * Display user details with full history.
     */
    public function showUser($id)
    {
        try {
            $user = Registration::with([
                'messages',
                'profileUpdateRequests.approver',
                'profileUpdateRequests' => function ($query) {
                    $query->with('approver')->latest();
                },
                'applications' => function ($query) {
                    $query->latest();
                },
            ])->findOrFail($id);

            // Get payment transactions for user's IX applications
            $ixApplications = Application::where('user_id', $id)
                ->where('application_type', 'IX')
                ->get();

            $paymentTransactions = PaymentTransaction::whereIn('application_id', $ixApplications->pluck('id'))
                ->latest()
                ->get()
                ->keyBy('application_id');

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

            return view('superadmin.users.show', compact('user', 'adminActions', 'ixApplications', 'paymentTransactions'));
        } catch (QueryException $e) {
            Log::error('Database error loading user details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading user details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading user details: '.$e->getMessage());

            return redirect()->route('superadmin.users')
                ->with('error', 'User not found.');
        }
    }

    /**
     * Display all admins.
     */
    public function admins(Request $request)
    {
        try {
            $query = Admin::with('roles');

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('admin_id', 'like', "%{$search}%")
                        ->orWhereHas('roles', function ($roleQuery) use ($search) {
                            $roleQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('slug', 'like', "%{$search}%");
                        });
                });
            }

            $admins = $query->latest()->paginate(20)->withQueryString();

            return view('superadmin.admins.index', compact('admins'));
        } catch (QueryException $e) {
            Log::error('Database error loading admins: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading admins: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading admins: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Unable to load admins. Please try again.');
        }
    }

    /**
     * Display admin details.
     */
    public function showAdmin($id)
    {
        try {
            $admin = Admin::with('roles')->findOrFail($id);

            // Get recent login/logout activities (top 10)
            $recentActivities = AdminAction::where('admin_id', $admin->id)
                ->where(function ($query) {
                    $query->where('action_type', 'admin_login')
                        ->orWhere('action_type', 'admin_logout');
                })
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            // Get messages sent by this admin
            $messages = Message::whereHas('adminActions', function ($query) use ($admin) {
                $query->where('admin_id', $admin->id)
                    ->where('action_type', 'sent_message');
            })
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get();

            // Get all admin actions for this admin (for activity count)
            $totalActions = AdminAction::where('admin_id', $admin->id)->count();

            return view('superadmin.admins.show', compact('admin', 'recentActivities', 'messages', 'totalActions'));
        } catch (QueryException $e) {
            Log::error('Database error loading admin details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading admin details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading admin details: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Admin not found.');
        }
    }

    /**
     * Show form to create new admin.
     */
    public function createAdmin()
    {
        try {
            $roles = Role::where('is_active', true)->get();

            return view('superadmin.admins.create', compact('roles'));
        } catch (QueryException $e) {
            Log::error('Database error loading create admin form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading create admin form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading create admin form: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Unable to load form. Please try again.');
        }
    }

    /**
     * Store new admin.
     */
    public function storeAdmin(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:admins,email',
                'password' => 'required|string|min:8|confirmed',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,id',
            ], [
                'name.required' => 'Name is required.',
                'email.required' => 'Email is required.',
                'email.unique' => 'This email is already registered.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters.',
                'password.confirmed' => 'Password confirmation does not match.',
            ]);

            $admin = Admin::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'admin_id' => Admin::generateAdminId(),
                'is_super_admin' => false,
                'is_active' => true,
            ]);

            // Assign roles
            if (! empty($validated['roles'])) {
                $admin->roles()->attach($validated['roles']);
            }

            // Log action
            AdminAction::logSuperAdmin(
                session('superadmin_id'),
                'created_admin',
                $admin,
                "Created new admin: {$admin->name}",
                ['roles' => $admin->roles->pluck('name')->toArray()]
            );

            return redirect()->route('superadmin.admins')
                ->with('success', 'Admin created successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error creating admin: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error creating admin: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error creating admin: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'An error occurred while creating admin. Please try again.');
        }
    }

    /**
     * Show form to edit admin.
     */
    public function editAdmin($id)
    {
        try {
            $admin = Admin::with('roles')->findOrFail($id);
            $roles = Role::where('is_active', true)->get();

            return view('superadmin.admins.edit', compact('admin', 'roles'));
        } catch (QueryException $e) {
            Log::error('Database error loading edit admin form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading edit admin form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading edit admin form: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Admin not found.');
        }
    }

    /**
     * Update admin.
     */
    public function updateAdmin(Request $request, $id)
    {
        try {
            $admin = Admin::findOrFail($id);

            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:admins,email,'.$id,
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,id',
                'is_active' => 'boolean',
            ];

            if ($request->filled('password')) {
                $rules['password'] = 'required|string|min:8|confirmed';
            }

            $validated = $request->validate($rules, [
                'name.required' => 'Name is required.',
                'email.required' => 'Email is required.',
                'email.unique' => 'This email is already registered.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters.',
                'password.confirmed' => 'Password confirmation does not match.',
            ]);

            $admin->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $request->has('is_active') ? true : false,
            ]);

            // Update password if provided
            if (! empty($validated['password'])) {
                $admin->password = Hash::make($validated['password']);
                $admin->save();
            }

            // Update roles
            $admin->roles()->sync($validated['roles'] ?? []);

            // Log action
            AdminAction::logSuperAdmin(
                session('superadmin_id'),
                'updated_admin',
                $admin,
                "Updated admin: {$admin->name}",
                ['roles' => $admin->roles->pluck('name')->toArray()]
            );

            return redirect()->route('superadmin.admins')
                ->with('success', 'Admin updated successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error updating admin: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error updating admin: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error updating admin: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'An error occurred while updating admin. Please try again.');
        }
    }

    /**
     * Show form to edit admin details (name, email, password - no roles).
     */
    public function editAdminDetails($id)
    {
        try {
            $admin = Admin::findOrFail($id);

            return view('superadmin.admins.edit-details', compact('admin'));
        } catch (QueryException $e) {
            Log::error('Database error loading edit admin details form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading edit admin details form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading edit admin details form: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Admin not found.');
        }
    }

    /**
     * Update admin details (name, email, password - no roles).
     */
    public function updateAdminDetails(Request $request, $id)
    {
        try {
            $admin = Admin::findOrFail($id);

            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:admins,email,'.$id,
            ];

            if ($request->filled('password')) {
                $rules['password'] = 'required|string|min:8|confirmed';
            }

            $validated = $request->validate($rules, [
                'name.required' => 'Name is required.',
                'email.required' => 'Email is required.',
                'email.unique' => 'This email is already registered.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters.',
                'password.confirmed' => 'Password confirmation does not match.',
            ]);

            $admin->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            // Update password if provided
            if (! empty($validated['password'] ?? null)) {
                $admin->password = Hash::make($validated['password']);
                $admin->save();
            }

            // Log action
            AdminAction::logSuperAdmin(
                session('superadmin_id'),
                'updated_admin_details',
                $admin,
                "Updated admin details: {$admin->name}",
                []
            );

            return redirect()->route('superadmin.admins')
                ->with('success', 'Admin details updated successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error updating admin details: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error updating admin details: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error updating admin details: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'An error occurred while updating admin details. Please try again.');
        }
    }

    /**
     * Show form to edit admin type/roles only.
     */
    public function editAdminType($id)
    {
        try {
            $admin = Admin::with('roles')->findOrFail($id);
            $roles = Role::where('is_active', true)->get();

            return view('superadmin.admins.edit-type', compact('admin', 'roles'));
        } catch (QueryException $e) {
            Log::error('Database error loading edit admin type form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading edit admin type form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading edit admin type form: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Admin not found.');
        }
    }

    /**
     * Update admin type/roles only.
     */
    public function updateAdminType(Request $request, $id)
    {
        try {
            $admin = Admin::findOrFail($id);

            $validated = $request->validate([
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,id',
            ], [
                'roles.array' => 'Roles must be an array.',
                'roles.*.exists' => 'One or more selected roles are invalid.',
            ]);

            // Update roles
            $admin->roles()->sync($validated['roles'] ?? []);

            // Log action
            AdminAction::logSuperAdmin(
                session('superadmin_id'),
                'updated_admin_type',
                $admin,
                "Updated admin roles: {$admin->name}",
                ['roles' => $admin->roles->pluck('name')->toArray()]
            );

            return redirect()->route('superadmin.admins')
                ->with('success', 'Admin roles updated successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error updating admin type: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error updating admin type: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error updating admin type: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'An error occurred while updating admin roles. Please try again.');
        }
    }

    /**
     * Toggle admin status (activate/deactivate).
     */
    public function toggleAdminStatus($id)
    {
        try {
            $admin = Admin::findOrFail($id);

            // Prevent deactivating super admin
            if ($admin->is_super_admin) {
                return redirect()->route('superadmin.admins')
                    ->with('error', 'Cannot deactivate super admin account.');
            }

            $oldStatus = $admin->is_active;
            $admin->is_active = ! $admin->is_active;
            $admin->save();

            // Log action
            AdminAction::logSuperAdmin(
                session('superadmin_id'),
                $admin->is_active ? 'activated_admin' : 'deactivated_admin',
                $admin,
                ($admin->is_active ? 'Activated' : 'Deactivated')." admin: {$admin->name}",
                ['old_status' => $oldStatus, 'new_status' => $admin->is_active]
            );

            return redirect()->route('superadmin.admins')
                ->with('success', "Admin {$admin->name} has been ".($admin->is_active ? 'activated' : 'deactivated').' successfully!');
        } catch (QueryException $e) {
            Log::error('Database error toggling admin status: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error toggling admin status: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error toggling admin status: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'An error occurred while updating admin status. Please try again.');
        }
    }

    /**
     * Display all messages with search functionality.
     */
    public function messages(Request $request)
    {
        try {
            $query = Message::with(['user']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhere('user_reply', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('adminActions', function ($adminQuery) use ($search) {
                            $adminQuery->where('action_type', 'sent_message')
                                ->whereHas('admin', function ($adminNameQuery) use ($search) {
                                    $adminNameQuery->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                });
            }

            // Filter by sent_by
            if ($request->filled('sent_by')) {
                $query->where('sent_by', $request->input('sent_by'));
            }

            $messages = $query->orderBy('created_at', 'desc')->paginate(20);

            // Get admin names for messages sent by admin
            $messageIds = $messages->pluck('id');
            $adminActions = AdminAction::with('admin')
                ->where('action_type', 'sent_message')
                ->where('actionable_type', Message::class)
                ->whereIn('actionable_id', $messageIds)
                ->get()
                ->keyBy('actionable_id');

            return view('superadmin.messages.index', compact('messages', 'adminActions'));
        } catch (QueryException $e) {
            Log::error('Database error loading messages: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading messages: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading messages: '.$e->getMessage());
            abort(500, 'Unable to load messages. Please try again later.');
        }
    }

    /**
     * Display message details.
     */
    public function showMessage($id)
    {
        try {
            $message = Message::with('user')->findOrFail($id);

            // Get admin who sent the message (if sent by admin)
            $adminAction = null;
            if ($message->sent_by === 'admin') {
                $adminAction = AdminAction::with('admin')
                    ->where('action_type', 'sent_message')
                    ->where('actionable_type', Message::class)
                    ->where('actionable_id', $message->id)
                    ->first();
            }

            return view('superadmin.messages.show', compact('message', 'adminAction'));
        } catch (QueryException $e) {
            Log::error('Database error loading message details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading message details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading message details: '.$e->getMessage());
            abort(500, 'Unable to load message details. Please try again later.');
        }
    }

    /**
     * Accept payment for IX application (Super Admin action).
     */
    public function acceptPayment($applicationId)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $application = Application::with('user')
                ->where('application_type', 'IX')
                ->findOrFail($applicationId);

            // Check if payment is already accepted
            $paymentTransaction = PaymentTransaction::where('application_id', $applicationId)->first();

            DB::beginTransaction();

            // Update or create payment transaction
            if ($paymentTransaction) {
                $paymentTransaction->update([
                    'payment_status' => 'success',
                    'payment_id' => 'approved by superadmin',
                    'transaction_id' => 'approved by superadmin',
                    'response_message' => 'Payment accepted by Super Admin',
                ]);
            } else {
                // Create new payment transaction
                $paymentTransaction = PaymentTransaction::create([
                    'user_id' => $application->user_id,
                    'application_id' => $applicationId,
                    'payment_status' => 'success',
                    'payment_id' => 'approved by superadmin',
                    'transaction_id' => 'approved by superadmin',
                    'payment_mode' => 'manual',
                    'amount' => $application->application_data['pricing']['total_amount'] ?? 0,
                    'currency' => 'INR',
                    'product_info' => 'IX Application Fee',
                    'response_message' => 'Payment accepted by Super Admin',
                ]);
            }

            // Update application status to submitted (visible to IX processor)
            $oldStatus = $application->status;
            $application->update([
                'status' => 'submitted',
                'submitted_at' => $application->submitted_at ?? now('Asia/Kolkata'),
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'submitted',
                'superadmin',
                $superAdminId,
                'Payment accepted by Super Admin - Application submitted for processing'
            );

            // Log super admin action
            AdminAction::logSuperAdmin(
                $superAdminId,
                'accepted_payment',
                $application,
                "Accepted payment for IX application {$application->application_id}",
                [
                    'application_id' => $application->application_id,
                    'user_id' => $application->user_id,
                    'payment_id' => 'approved by superadmin',
                    'transaction_id' => 'approved by superadmin',
                ]
            );

            // Send message to user
            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Payment Accepted - Application Submitted',
                'message' => "Your payment for application {$application->application_id} has been accepted by Super Admin. Your application has been submitted and is now under review by IX Processor.",
                'is_read' => false,
                'sent_by' => 'superadmin',
            ]);

            DB::commit();

            return redirect()->route('superadmin.users.show', $application->user_id)
                ->with('success', 'Payment accepted successfully! Application has been submitted for IX Processor review.');
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Database error accepting payment: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            DB::rollBack();
            Log::error('PDO error accepting payment: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error accepting payment: '.$e->getMessage());

            return back()->with('error', 'An error occurred while accepting payment. Please try again.');
        }
    }
}
