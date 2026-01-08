<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreConfigs extends Model
{
    protected $fillable = [
        'name',
        'code',
        'link',
        'cover',
        'title',
        'description',
        'content',
        'group',
        'category',
        'status',
    ];
    protected static function booted()
    {
        static::addGlobalScope('active', function ($builder) {
            $builder
                ->whereNotIn('category', ['banner'])
                ->whereNotIn('group', ['subject']);
        });
    }
}
