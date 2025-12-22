<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Message;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EscalateTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:escalate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically escalate unresolved tickets (6 hours to IX Head, 24 hours to CEO)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $now = now('Asia/Kolkata');
            $escalatedCount = 0;

            // Get unresolved tickets (not resolved or closed)
            // Only escalate tickets that are open, assigned, or in_progress
            $unresolvedTickets = Ticket::whereIn('status', ['open', 'assigned', 'in_progress'])
                ->where('escalation_level', '!=', 'ceo') // Don't escalate if already at CEO
                ->get();

            foreach ($unresolvedTickets as $ticket) {
                $hoursSinceCreation = $ticket->created_at->diffInHours($now);
                $shouldEscalateToIxHead = false;
                $shouldEscalateToCeo = false;

                // Check if should escalate to IX Head (6 hours, not already escalated)
                if ($hoursSinceCreation >= 6 && $ticket->escalation_level === 'none') {
                    $shouldEscalateToIxHead = true;
                }

                // Check if should escalate to CEO (24 hours, already escalated to IX Head or not escalated but 24+ hours)
                if ($hoursSinceCreation >= 24) {
                    if ($ticket->escalation_level === 'ix_head') {
                        $shouldEscalateToCeo = true;
                    } elseif ($ticket->escalation_level === 'none') {
                        // If 24+ hours and not escalated yet, escalate directly to CEO
                        $shouldEscalateToCeo = true;
                    }
                }

                if ($shouldEscalateToIxHead) {
                    $this->escalateToIxHead($ticket);
                    $escalatedCount++;
                } elseif ($shouldEscalateToCeo) {
                    $this->escalateToCeo($ticket);
                    $escalatedCount++;
                }
            }

            $this->info("Tickets escalated: {$escalatedCount}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error escalating tickets: '.$e->getMessage());
            $this->error('Error escalating tickets: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Escalate ticket to IX Head.
     */
    private function escalateToIxHead(Ticket $ticket): void
    {
        try {
            // Find IX Head admin
            $ixHeadRole = Role::where('slug', 'ix_head')->first();
            if (!$ixHeadRole) {
                Log::warning("IX Head role not found. Cannot escalate ticket {$ticket->ticket_id}");
                return;
            }

            $ixHeadAdmin = Admin::whereHas('roles', function ($query) use ($ixHeadRole) {
                $query->where('roles.id', $ixHeadRole->id);
            })
            ->where('is_active', true)
            ->first();

            if (!$ixHeadAdmin) {
                Log::warning("No active IX Head admin found. Cannot escalate ticket {$ticket->ticket_id}");
                return;
            }

            // Update ticket
            $ticket->update([
                'escalation_level' => 'ix_head',
                'escalated_to' => $ixHeadAdmin->id,
                'escalated_at' => now('Asia/Kolkata'),
                'escalation_notes' => "Automatically escalated to IX Head after 6 hours without resolution.",
                'priority' => $ticket->priority === 'urgent' ? 'urgent' : 'high', // Upgrade priority if not urgent
            ]);

            // Send message to user
            Message::create([
                'user_id' => $ticket->user_id,
                'subject' => 'Grievance Escalated - Ticket '.$ticket->ticket_id,
                'message' => "Your grievance ticket {$ticket->ticket_id} has been escalated to IX Head for priority resolution as it was not resolved within 6 hours.",
                'is_read' => false,
                'sent_by' => 'system',
            ]);

            // Create ticket message for escalation (use admin type with null ID for system messages)
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'admin',
                'sender_id' => null, // System message
                'message' => "🔴 ESCALATED: Ticket automatically escalated to IX Head ({$ixHeadAdmin->name}) after 6 hours without resolution.",
                'is_internal' => false,
            ]);

            Log::info("Ticket {$ticket->ticket_id} escalated to IX Head ({$ixHeadAdmin->name})");

        } catch (\Exception $e) {
            Log::error("Error escalating ticket {$ticket->id} to IX Head: ".$e->getMessage());
        }
    }

    /**
     * Escalate ticket to CEO.
     */
    private function escalateToCeo(Ticket $ticket): void
    {
        try {
            // Find CEO admin
            $ceoRole = Role::where('slug', 'ceo')->first();
            if (!$ceoRole) {
                Log::warning("CEO role not found. Cannot escalate ticket {$ticket->ticket_id}");
                return;
            }

            $ceoAdmin = Admin::whereHas('roles', function ($query) use ($ceoRole) {
                $query->where('roles.id', $ceoRole->id);
            })
            ->where('is_active', true)
            ->first();

            if (!$ceoAdmin) {
                Log::warning("No active CEO admin found. Cannot escalate ticket {$ticket->ticket_id}");
                return;
            }

            $previousEscalation = $ticket->escalation_level === 'ix_head' 
                ? 'IX Head' 
                : 'initial assignment';

            // Update ticket
            $ticket->update([
                'escalation_level' => 'ceo',
                'escalated_to' => $ceoAdmin->id,
                'escalated_at' => now('Asia/Kolkata'),
                'escalation_notes' => "Automatically escalated to CEO after 24 hours without resolution (previously escalated to {$previousEscalation}).",
                'priority' => 'urgent', // Always set to urgent when escalated to CEO
            ]);

            // Send message to user
            Message::create([
                'user_id' => $ticket->user_id,
                'subject' => 'Grievance Escalated to CEO - Ticket '.$ticket->ticket_id,
                'message' => "Your grievance ticket {$ticket->ticket_id} has been escalated to CEO for urgent resolution as it was not resolved within 24 hours.",
                'is_read' => false,
                'sent_by' => 'system',
            ]);

            // Create ticket message for escalation (use admin type with null ID for system messages)
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'admin',
                'sender_id' => null, // System message
                'message' => "🔴 ESCALATED: Ticket automatically escalated to CEO ({$ceoAdmin->name}) after 24 hours without resolution (previously escalated to {$previousEscalation}).",
                'is_internal' => false,
            ]);

            Log::info("Ticket {$ticket->ticket_id} escalated to CEO ({$ceoAdmin->name})");

        } catch (\Exception $e) {
            Log::error("Error escalating ticket {$ticket->id} to CEO: ".$e->getMessage());
        }
    }
}
