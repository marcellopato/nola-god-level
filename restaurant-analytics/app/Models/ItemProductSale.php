<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemProductSale extends Model  
{
    public $timestamps = false;

    protected $fillable = [
        'product_sale_id',
        'item_id', 
        'option_group_id',
        'quantity',
        'additional_price',
        'price',
        'amount',
        'observations'
    ];

    protected $casts = [
        'additional_price' => 'decimal:2',
        'price' => 'decimal:2'
    ];

    public function productSale(): BelongsTo
    {
        return $this->belongsTo(ProductSale::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
