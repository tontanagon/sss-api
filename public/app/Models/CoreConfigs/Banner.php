<?php

namespace App\Models\CoreConfigs;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'core_configs';

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
            $builder->where('group', 'banner');
            $builder->where('category', 'banner');
        });

        static::creating(function ($model) {
            $model->group = 'banner';
            $model->category = 'banner';
        });

        static::updating(function ($model) {
            $model->group = 'banner';
            $model->category = 'banner';
        });
    }
}
