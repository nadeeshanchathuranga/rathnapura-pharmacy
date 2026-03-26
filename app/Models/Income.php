<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Income extends Model
{
    use HasFactory; 
      
    protected $fillable = [
        'sale_id',
        'source',
        'amount',
        'income_date',
        'payment_type',
        'card_type',
        'transaction_type',
    ];

    // Relationship: Income belongs to Sale
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}


