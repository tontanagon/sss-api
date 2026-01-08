<?php

namespace App\Models\CoreConfigs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Subject extends Model
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

    protected $appends = [
        'display_name'
    ];

    protected static function booted()
    {
        static::addGlobalScope('active', function ($builder) {
            $builder->where('group', 'subject');
            $builder->where('category', 'option');
        });

        static::creating(function ($model) {
            $model->group = 'subject';
            $model->category = 'option';
        });

        static::updating(function ($model) {
            $model->group = 'subject';
            $model->category = 'option';
        });
    }

    protected function displayName(): Attribute
    {
        return new Attribute(
            get: fn () => "{$this->code} - {$this->name}",
        );
    }
}
