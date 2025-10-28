<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'store_id',
        'customer_id', 
        'channel_id',
        'customer_name',
        'created_at',
        'sale_status_desc',
        'total_amount_items',
        'total_discount',
        'total_increase', 
        'delivery_fee',
        'service_tax_fee',
        'total_amount',
        'value_paid',
        'production_seconds',
        'delivery_seconds',
        'people_quantity',
        'discount_reason'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'total_amount_items' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_increase' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'service_tax_fee' => 'decimal:2', 
        'total_amount' => 'decimal:2',
        'value_paid' => 'decimal:2'
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function productSales(): HasMany
    {
        return $this->hasMany(ProductSale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function deliverySale(): HasOne
    {
        return $this->hasOne(DeliverySale::class);
    }

    // Scopes para filtros comuns
    public function scopeCompleted($query)
    {
        return $query->where('sale_status_desc', 'COMPLETED');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByChannel($query, $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    // Helpers
    public function isCompleted(): bool
    {
        return $this->sale_status_desc === 'COMPLETED';
    }

    public function isDelivery(): bool
    {
        return $this->channel->type === 'D';
    }

    public function getTicketAverageAttribute(): float
    {
        return $this->people_quantity > 0 
            ? (float) ($this->total_amount / $this->people_quantity)
            : (float) $this->total_amount;
    }

    public function getPreparationTimeMinutesAttribute(): ?float
    {
        return $this->production_seconds ? $this->production_seconds / 60 : null;
    }

    public function getDeliveryTimeMinutesAttribute(): ?float  
    {
        return $this->delivery_seconds ? $this->delivery_seconds / 60 : null;
    }
}
