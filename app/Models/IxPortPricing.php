<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IxPortPricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'node_type',
        'port_capacity',
        'price_arc',
        'price_mrc',
        'price_quarterly',
        'currency',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'price_arc' => 'decimal:2',
        'price_mrc' => 'decimal:2',
        'price_quarterly' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForNodeType($query, string $nodeType)
    {
        return $query->where('node_type', $nodeType);
    }

    public function getAmountForPlan(string $plan): float
    {
        return match ($plan) {
            'arc' => (float) $this->price_arc,
            'mrc' => (float) $this->price_mrc,
            'quarterly' => (float) $this->price_quarterly,
            default => 0.0,
        };
    }
}
