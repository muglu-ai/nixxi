<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserKycProfile extends Model
{
    protected $fillable = [
        'user_id',
        'is_msme',
        'gstin',
        'gst_verification_id',
        'udyam_verification_id',
        'mca_verification_id',
        'udyam_number',
        'cin',
        'gst_verified',
        'udyam_verified',
        'mca_verified',
        'contact_name',
        'contact_dob',
        'contact_pan',
        'contact_email',
        'contact_mobile',
        'contact_name_pan_dob_verified',
        'contact_email_verified',
        'contact_mobile_verified',
        'status',
        'completed_at',
        'kyc_ip_address',
        'kyc_user_agent',
        'billing_address_source',
        'billing_address',
    ];

    protected $casts = [
        'is_msme' => 'boolean',
        'gst_verified' => 'boolean',
        'udyam_verified' => 'boolean',
        'mca_verified' => 'boolean',
        'contact_name_pan_dob_verified' => 'boolean',
        'contact_email_verified' => 'boolean',
        'contact_mobile_verified' => 'boolean',
        'contact_dob' => 'date',
        'completed_at' => 'datetime:Asia/Kolkata',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    public function gstVerification(): BelongsTo
    {
        return $this->belongsTo(GstVerification::class, 'gst_verification_id');
    }

    public function udyamVerification(): BelongsTo
    {
        return $this->belongsTo(UdyamVerification::class, 'udyam_verification_id');
    }

    public function mcaVerification(): BelongsTo
    {
        return $this->belongsTo(McaVerification::class, 'mca_verification_id');
    }
}
