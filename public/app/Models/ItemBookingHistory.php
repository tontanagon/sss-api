<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemBookingHistory extends Model
{
    protected $fillable = [
        'product_id',
        'product_name',
        'product_stock_history_id',
        'booking_history_id',
        'product_category',
        'product_type',
        'product_unit',
        'product_quantity',
        'product_quantity_return',
        'status',
    ];
    public function product(): BelongsTo
    {
        return $this->BelongsTo(Product::class, 'product_id', 'id');
    }
    
    public function productStockHistory(): BelongsTo
    {
        return $this->BelongsTo(ProductStockHistory::class, 'product_stock_history_id', 'id');
    }

    public function bookingHistory(): BelongsTo
    {
        return $this->BelongsTo(BookingHistory::class, 'booking_history_id', 'id');
    }

}
