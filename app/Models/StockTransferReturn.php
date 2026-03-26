<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferReturn extends Model
{
    protected $fillable = [
        'return_no',
        'user_id',
        'reason',
        'status',
        'return_date',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(StockTransferReturnProduct::class);
    }
}
