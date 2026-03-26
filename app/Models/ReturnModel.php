<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnModel extends Model
{
    use HasFactory;

    protected $table = 'sales_return';

    protected $fillable = [
        'sale_id',
        'customer_id',
        'user_id',
        'return_date',
        'status',
    ];

    public function products()
    {
        return $this->hasMany(ReturnProduct::class, 'return_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
