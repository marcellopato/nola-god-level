<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'city',
        'state', 
        'district',
        'address_street',
        'address_number',
        'latitude',
        'longitude',
        'is_active',
        'is_own'
    ];

    protected $casts = [
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'is_active' => 'boolean',
        'is_own' => 'boolean'
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFullAddressAttribute(): string
    {
        return trim("{$this->address_street}, {$this->address_number} - {$this->district}, {$this->city}/{$this->state}");
    }
}
