<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class IxLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'state',
        'city',
        'node_type',
        'switch_details',
        'ports',
        'nodal_officer',
        'zone',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'ports' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $location): void {
            if (empty($location->slug)) {
                $location->slug = Str::slug($location->name);
            }
        });

        static::updating(function (self $location): void {
            if ($location->isDirty('name')) {
                $location->slug = Str::slug($location->name);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForState($query, ?string $state)
    {
        if (! $state) {
            return $query;
        }

        return $query->where('state', $state);
    }
}
