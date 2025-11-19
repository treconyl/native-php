<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarenaTestCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'password',
        'new_password',
        'account_id',
        'proxy_key_id',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'new_password' => 'encrypted',
        'account_id' => 'integer',
        'proxy_key_id' => 'integer',
    ];
}
