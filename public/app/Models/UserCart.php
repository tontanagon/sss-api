<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCart extends Model
{
    protected $fillable = [
        'user_id',
        'cart_items',
    ];

    protected $casts = [
        'cart_items' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->BelongsTo(User::class, 'user_id', 'id');
    }
}
