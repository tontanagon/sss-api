<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingStatusHistory extends Model
{
    protected $fillable = [
        'booking_id',
        'status',
        'approve_by',
        'remark',
    ];

    public function BookingHistories(): BelongsTo
    {
        return $this->BelongsTo(BookingHistory::class, 'booking_id', 'id');
    }

}
