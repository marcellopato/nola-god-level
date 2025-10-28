<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'description'
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
