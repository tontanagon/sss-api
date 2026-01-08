<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\belongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductStockHistory extends Model
{
    protected $fillable = [
        'product_id',
        'stock',
        'type',
        'add_type',
        'by_user_id',
        'booking_history_id',
        'before_stock',
        'after_stock',
        'remark',
    ];

    public function product(): belongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function user(): belongsTo
    {
        return $this->belongsTo(User::class, 'by_user_id', 'id');
    }

    public function bookingHistory(): belongsTo
    {
        return $this->belongsTo(BookingHistory::class, 'booking_history_id', 'id');
    }

    public function itemBookingHisgory(): HasOne
    {
        return $this->HasOne(ItemBookingHistory::class, 'product_stock_history_id', 'id');
    }
}
