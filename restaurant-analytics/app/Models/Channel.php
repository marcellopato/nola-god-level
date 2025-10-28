<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'type'
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeDelivery($query)
    {
        return $query->where('type', 'D');
    }

    public function scopePresential($query) 
    {
        return $query->where('type', 'P');
    }

    public function isDelivery(): bool
    {
        return $this->type === 'D';
    }

    public function isPresential(): bool
    {
        return $this->type === 'P';
    }

    public function getTypeNameAttribute(): string
    {
        return $this->type === 'D' ? 'Delivery' : 'Presencial';
    }
}
