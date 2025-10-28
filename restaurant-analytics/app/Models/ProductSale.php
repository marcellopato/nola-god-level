<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSale extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'base_price', 
        'total_price',
        'observations'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function itemProductSales(): HasMany
    {
        return $this->hasMany(ItemProductSale::class);
    }

    public function hasCustomizations(): bool
    {
        return $this->itemProductSales()->exists();
    }

    public function getCustomizationCountAttribute(): int
    {
        return $this->itemProductSales()->count();
    }

    public function getCustomizationValueAttribute(): float
    {
        return (float) $this->itemProductSales()->sum('price');
    }
}
