<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
     use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'email',
        'phone_number',
        'address',
        'due_date',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function goodsReceivedNotes()
    {
        return $this->hasMany(GoodsReceivedNote::class);
    }

    public function getDuePaymentAttribute()
    {
        return $this->goodsReceivedNotes()->sum('subtotal') ?? 0;
    }
}
