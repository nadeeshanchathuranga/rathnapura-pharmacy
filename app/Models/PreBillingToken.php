<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreBillingToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_id',
        'user_id',
        'division_id',
        'customer_id',
        'customer_type',
        'sale_date',
        'discount',
        'items',
        'expires_at',
    ];

    protected $casts = [
        'items' => 'array',
        'discount' => 'decimal:2',
        'sale_date' => 'date',
        'expires_at' => 'datetime',
    ];
}
