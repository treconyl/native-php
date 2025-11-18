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
    ];

    protected $casts = [
        'password' => 'encrypted',
        'new_password' => 'encrypted',
    ];
}
