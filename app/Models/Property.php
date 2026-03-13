<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = [
        'source',
        'external_id',
        'url',
        'title',
        'price_usd',
        'price_raw',
        'bedrooms',
        'bathrooms',
        'area_m2',
        'neighborhood',
        'description',
        'image_url',
        'published_at',
        'first_seen_at',
        'removed_at',
    ];

    protected $casts = [
        'published_at'  => 'datetime',
        'first_seen_at' => 'datetime',
        'removed_at'    => 'datetime',
        'price_usd'     => 'decimal:2',
        'area_m2'       => 'decimal:2',
    ];
}
