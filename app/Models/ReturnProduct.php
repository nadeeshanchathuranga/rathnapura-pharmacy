<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnProduct extends Model
{
    use HasFactory;

    protected $table = 'sales_return_products';

    protected $fillable = [
        'sales_return_id',
        'product_id',
        'quantity',
        'price',
        'total',
    ];

    public function returnModel()
    {
        return $this->belongsTo(ReturnModel::class, 'sales_return_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
