<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferReturnProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_return_id',
        'product_id',
        'measurement_unit_id',
        'stock_transfer_quantity',
    ];

    protected $casts = [
        'stock_transfer_quantity' => 'integer',
    ];

    public function stockTransferReturn()
    {
        return $this->belongsTo(StockTransferReturn::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function measurementUnit()
    {
        return $this->belongsTo(MeasurementUnit::class);
    }
}
