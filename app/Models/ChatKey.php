<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatKey extends Model
{
    protected $guarded = [];

    protected $casts = ['public_jwk' => 'array', 'revoked_at' => 'datetime'];
}
