<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturnReplacementProduct extends Model
{
    use HasFactory;

    protected $table = 'sales_return_replacement_products';

    protected $fillable = [
        'sales_return_id',
        'product_id',
        'quantity',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
