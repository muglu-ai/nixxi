<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Message extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'is_read',
        'read_at',
        'sent_by',
        'user_reply',
        'user_replied_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime:Asia/Kolkata',
        'user_replied_at' => 'datetime:Asia/Kolkata',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the user that owns the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    /**
     * Get admin actions related to this message.
     */
    public function adminActions(): MorphMany
    {
        return $this->morphMany(AdminAction::class, 'actionable');
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now('Asia/Kolkata'),
        ]);
    }
}
