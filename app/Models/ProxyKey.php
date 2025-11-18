<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProxyKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'api_key',
        'is_active',
        'last_used_at',
        'status',
        'stop_requested',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'stop_requested' => 'boolean',
        'meta' => 'array',
    ];
}
