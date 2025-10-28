<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'type'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function scopeProducts($query)
    {
        return $query->where('type', 'P');
    }

    public function scopeItems($query)
    {
        return $query->where('type', 'I');
    }
}
