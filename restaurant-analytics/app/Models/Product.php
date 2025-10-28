<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'category_id',
        'pos_uuid'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productSales(): HasMany
    {
        return $this->hasMany(ProductSale::class);
    }

    // Analytics helpers
    public function getTotalSalesCount(): int
    {
        return $this->productSales()
            ->whereHas('sale', function ($q) {
                $q->where('sale_status_desc', 'COMPLETED');
            })
            ->sum('quantity');
    }

    public function getTotalRevenue(): float
    {
        return (float) $this->productSales()
            ->whereHas('sale', function ($q) {
                $q->where('sale_status_desc', 'COMPLETED');
            })
            ->sum('total_price');
    }

    public function getAveragePrice(): float
    {
        return (float) $this->productSales()
            ->whereHas('sale', function ($q) {
                $q->where('sale_status_desc', 'COMPLETED');
            })
            ->avg('base_price');
    }
}
