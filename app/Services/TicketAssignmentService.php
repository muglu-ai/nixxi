<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class TicketAssignmentService
{
    /**
     * Get the role that should handle a ticket based on category and sub_category.
     */
    public static function getAssignedRole(string $category, ?string $subCategory = null): ?string
    {
        return match ($category) {
            'network_connectivity' => 'nodal_officer',
            'billing' => 'ix_account',
            'request' => match ($subCategory) {
                'mac_change' => 'nodal_officer',
                'upgrade_downgrade', 'profile_change' => 'ix_processor',
                default => null,
            },
            'feedback_suggestion' => 'ix_head',
            'other' => 'ix_head',
            default => null,
        };
    }

    /**
     * Get sub-categories for a given category.
     */
    public static function getSubCategories(string $category): array
    {
        return match ($category) {
            'network_connectivity' => [
                'link_down' => 'Link Down',
                'speed_issue' => 'Speed Issue',
                'packet_drop_issue' => 'Packet Drop Issue',
                'specific_website_issue' => 'Specific Website Issue',
            ],
            'billing' => [
                'billing_issue' => 'Billing Issue',
            ],
            'request' => [
                'mac_change' => 'MAC Change',
                'upgrade_downgrade' => 'Upgrade / Downgrade',
                'profile_change' => 'Profile Change',
            ],
            'feedback_suggestion' => [],
            'other' => [
                'other' => 'Other',
            ],
            default => [],
        };
    }

    /**
     * Get all categories.
     */
    public static function getCategories(): array
    {
        return [
            'network_connectivity' => 'Network and Connectivity',
            'billing' => 'Billing',
            'request' => 'Request',
            'feedback_suggestion' => 'Feedback / Suggestion',
            'other' => 'Other',
        ];
    }

    /**
     * Assign a ticket to an admin based on category and sub_category.
     */
    public static function assignTicket(Ticket $ticket): bool
    {
        if (! $ticket->category) {
            Log::warning('Cannot assign ticket without category', [
                'ticket_id' => $ticket->ticket_id,
            ]);

            return false;
        }

        $assignedRole = self::getAssignedRole($ticket->category, $ticket->sub_category);

        if (! $assignedRole) {
            Log::warning('No role found for ticket category/sub_category', [
                'ticket_id' => $ticket->ticket_id,
                'category' => $ticket->category,
                'sub_category' => $ticket->sub_category,
            ]);

            return false;
        }

        // Find an admin with the required role
        $admin = Admin::whereHas('roles', function ($query) use ($assignedRole) {
            $query->where('slug', $assignedRole);
        })->first();

        if (! $admin) {
            Log::warning('No admin found with required role', [
                'ticket_id' => $ticket->ticket_id,
                'required_role' => $assignedRole,
            ]);

            return false;
        }

        $ticket->update([
            'assigned_to' => $admin->id,
            'assigned_role' => $assignedRole,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        Log::info('Ticket assigned to admin', [
            'ticket_id' => $ticket->ticket_id,
            'admin_id' => $admin->id,
            'role' => $assignedRole,
        ]);

        return true;
    }

    /**
     * Check if an admin can forward a ticket to a specific role.
     */
    public static function canForwardTo(Ticket $ticket, Admin $admin, string $targetRole): bool
    {
        $currentRole = $ticket->assigned_role;

        if (! $currentRole) {
            return false;
        }

        // Check if admin has the current role
        if (! $admin->hasRole($currentRole)) {
            return false;
        }

        // Define forwarding rules
        // Format: [current_role => [category => [sub_category => [allowed_roles]] or [allowed_roles]]]
        $forwardingRules = [
            'nodal_officer' => [
                'network_connectivity' => ['ix_head'], // All sub-categories
                'request' => [
                    'mac_change' => ['ix_head'],
                ],
            ],
            'ix_account' => [
                'billing' => ['ceo', 'ix_head'],
            ],
            'ix_processor' => [
                'request' => [
                    'upgrade_downgrade' => ['ceo', 'ix_head'],
                    'profile_change' => ['ceo', 'ix_head'],
                ],
            ],
            'ix_head' => [
                'network_connectivity' => ['ix_account', 'ix_tech_team', 'nodal_officer'],
                'billing' => ['ix_account', 'ix_tech_team', 'nodal_officer', 'ix_processor', 'ceo'],
                'request' => [
                    'mac_change' => ['ix_account', 'ix_tech_team', 'nodal_officer'],
                    'upgrade_downgrade' => ['ix_account', 'ix_tech_team', 'nodal_officer', 'ix_processor', 'ceo'],
                    'profile_change' => ['ix_account', 'ix_tech_team', 'nodal_officer', 'ix_processor', 'ceo'],
                ],
                'feedback_suggestion' => ['ix_account', 'ix_tech_team', 'nodal_officer', 'ix_processor', 'ceo'],
                'other' => ['ix_account', 'ix_tech_team', 'nodal_officer', 'ix_processor', 'ceo'],
            ],
            'ceo' => [
                'billing' => ['ix_account', 'ix_tech_team', 'nodal_officer', 'ix_processor', 'ix_head'],
                'request' => [
                    'upgrade_downgrade' => ['ix_account', 'ix_tech_team', 'nodal_officer', 'ix_processor', 'ix_head'],
                    'profile_change' => ['ix_account', 'ix_tech_team', 'nodal_officer', 'ix_processor', 'ix_head'],
                ],
            ],
        ];

        // Check if current role has forwarding rules
        if (! isset($forwardingRules[$currentRole])) {
            return false;
        }

        $categoryRules = $forwardingRules[$currentRole];

        // Check category-specific rules
        if (! isset($categoryRules[$ticket->category])) {
            return false;
        }

        $categoryRule = $categoryRules[$ticket->category];

        // If category rule is directly an array of roles (no sub-categories), check if target role is in it
        if (is_array($categoryRule) && ! empty($categoryRule) && ! is_array($categoryRule[array_key_first($categoryRule)])) {
            return in_array($targetRole, $categoryRule);
        }

        // If category rule has sub-category keys, check sub-category
        if (is_array($categoryRule) && $ticket->sub_category) {
            if (isset($categoryRule[$ticket->sub_category]) && is_array($categoryRule[$ticket->sub_category])) {
                return in_array($targetRole, $categoryRule[$ticket->sub_category]);
            }
        }

        return false;
    }

    /**
     * Forward a ticket to another admin with a specific role.
     */
    public static function forwardTicket(Ticket $ticket, Admin $fromAdmin, string $targetRole, ?string $notes = null): bool
    {
        if (! self::canForwardTo($ticket, $fromAdmin, $targetRole)) {
            return false;
        }

        // Find an admin with the target role
        $targetAdmin = Admin::whereHas('roles', function ($query) use ($targetRole) {
            $query->where('slug', $targetRole);
        })->first();

        if (! $targetAdmin) {
            Log::warning('No admin found with target role for forwarding', [
                'ticket_id' => $ticket->ticket_id,
                'target_role' => $targetRole,
            ]);

            return false;
        }

        $ticket->update([
            'assigned_to' => $targetAdmin->id,
            'assigned_role' => $targetRole,
            'forwarded_by' => $fromAdmin->id,
            'forwarded_at' => now(),
            'forwarding_notes' => $notes,
            'status' => 'assigned',
        ]);

        Log::info('Ticket forwarded', [
            'ticket_id' => $ticket->ticket_id,
            'from_admin_id' => $fromAdmin->id,
            'to_admin_id' => $targetAdmin->id,
            'from_role' => $ticket->assigned_role,
            'to_role' => $targetRole,
        ]);

        return true;
    }

    /**
     * Get all roles that can receive forwarded tickets from current admin.
     */
    public static function getForwardableRoles(Ticket $ticket, Admin $admin): array
    {
        $forwardableRoles = [];
        $currentRole = $ticket->assigned_role;

        if (! $currentRole || ! $admin->hasRole($currentRole)) {
            return $forwardableRoles;
        }

        // Get all roles
        $allRoles = \App\Models\Role::where('is_active', true)
            ->where('slug', '!=', $currentRole)
            ->get();

        // Filter roles based on forwarding rules
        foreach ($allRoles as $role) {
            if (self::canForwardTo($ticket, $admin, $role->slug)) {
                $forwardableRoles[$role->slug] = $role->name;
            }
        }

        return $forwardableRoles;
    }
}

