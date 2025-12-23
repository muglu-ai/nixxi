<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'application_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'billing_period',
        'billing_start_date',
        'billing_end_date',
        'amount',
        'gst_amount',
        'total_amount',
        'currency',
        'status',
        'payu_payment_link',
        'manual_payment_id',
        'manual_payment_notes',
        'pdf_path',
        'generated_by',
        'sent_at',
        'paid_at',
        'paid_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'billing_start_date' => 'date',
        'billing_end_date' => 'date',
        'amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sent_at' => 'datetime:Asia/Kolkata',
        'paid_at' => 'datetime:Asia/Kolkata',
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
     * Get the admin who generated the invoice.
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'generated_by');
    }

    /**
     * Admin who marked invoice as paid manually.
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'paid_by');
    }
}
