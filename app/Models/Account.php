<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'login',
        'current_password',
        'next_password',
        'status',
        'last_error',
        'last_attempted_at',
        'last_succeeded_at',
    ];

    protected $casts = [
        'last_attempted_at' => 'datetime',
        'last_succeeded_at' => 'datetime',
    ];
}
