<?php

namespace App\Models;

use App\Models\CoreConfigs\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingHistory extends Model
{
    protected $fillable = [
        'user_id',
        'booking_number',
        'user_name',
        'user_code',
        'user_grade',
        'phone_number',
        'return_at',
        'subject',
        'teacher',
        'activity_name',
        'participants',
        'status',
        'remark',
    ];

    protected static function booted()
    {
        static::saved(function ($model) {
            // เช็คถ้าเพิ่งสร้าง หรือ status มีการเปลี่ยน
            if ($model->wasRecentlyCreated || $model->isDirty('status')) {
                $remark = request()->input('remark');
                $user   = auth('sanctum')->user();

                $model->bookingStatusHistories()->create([
                    'status'     => $model->status,
                    'remark'     => $remark ?? null,
                    'approve_by' => $user->name ?? "system",
                ]);
            }
        });
    }

    public function itemBookingHistories(): HasMany
    {
        return $this->hasMany(ItemBookingHistory::class, 'booking_history_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->BelongsTo(User::class, 'user_id', 'id');
    }

    public function productStockHistory(): HasOne
    {
        return $this->HasOne(ProductStockHistory::class, 'booking_history_id', 'id');
    }

    public function bookingStatusHistories(): hasMany
    {
        return $this->hasMany(BookingStatusHistory::class, 'booking_id', 'id');
    }

    public function bookingStatusLast(): hasOne
    {
        return $this->hasOne(BookingStatusHistory::class, 'booking_id', 'id')->latestOfMany();
    }

    public function subjectName(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject', 'code');
    }
}
