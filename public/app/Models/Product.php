<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{

    protected $fillable = [
        'name',
        'code',
        'image',
        'status',
        'description',
        'stock',
        'unit',
        'type_id',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories', 'product_id', 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tags', 'product_id', 'tag_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_id', 'id');
    }

    public function productStockHistories(): HasMany
    {
        return $this->HasMany(ProductStockHistory::class, 'product_id', 'id');
    }

    public function itemBookingHistories(): HasMany
    {
        return $this->HasMany(ItemBookingHistory::class, 'product_id', 'id');
    }
    
}
