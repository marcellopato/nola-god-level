<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'customer_name',
        'email',
        'phone_number',
        'cpf',
        'birth_date',
        'gender',
        'agree_terms',
        'receive_promotions_email'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'agree_terms' => 'boolean',
        'receive_promotions_email' => 'boolean'
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function getLifetimeValueAttribute(): float
    {
        return (float) $this->sales()->completed()->sum('total_amount');
    }

    public function getTotalOrdersAttribute(): int
    {
        return $this->sales()->completed()->count();
    }

    public function getAverageTicketAttribute(): float
    {
        $total = $this->total_orders;
        return $total > 0 ? $this->lifetime_value / $total : 0;
    }
}
