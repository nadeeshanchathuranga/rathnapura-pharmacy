<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderProduct extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_products';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'measurement_unit_id',
        'quantity',
        'purchase_price',
        'discount',
        'discount_percentage',
        'total',
    ];

    protected $casts = [
        'quantity'           => 'decimal:2',
        'purchase_price'     => 'decimal:2',
        'discount'           => 'decimal:2',
        'discount_percentage'=> 'decimal:2',
        'total'              => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function measurementUnit()
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit_id');
    }
}
