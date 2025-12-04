<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    protected $table = 'application_status_history';

    protected $fillable = [
        'application_id',
        'status_from',
        'status_to',
        'changed_by_type',
        'changed_by_id',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the application.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the admin who made the change (polymorphic relationship).
     */
    public function changedBy()
    {
        if (!$this->changed_by_id || !$this->changed_by_type) {
            return null;
        }
        
        if ($this->changed_by_type === 'admin') {
            return \App\Models\Admin::find($this->changed_by_id);
        } elseif ($this->changed_by_type === 'superadmin') {
            return \App\Models\SuperAdmin::find($this->changed_by_id);
        }
        return null;
    }

    /**
     * Log a status change.
     */
    public static function log(int $applicationId, ?string $statusFrom, string $statusTo, string $changedByType, int $changedById, ?string $notes = null): self
    {
        return self::create([
            'application_id' => $applicationId,
            'status_from' => $statusFrom ?? '',
            'status_to' => $statusTo,
            'changed_by_type' => $changedByType,
            'changed_by_id' => $changedById,
            'notes' => $notes,
        ]);
    }
}
