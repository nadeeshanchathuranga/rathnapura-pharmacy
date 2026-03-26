<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTransferRequestProduct extends Model
{
    protected $fillable = [
        'product_transfer_request_id',
        'product_id',
        'requested_quantity',
        'unit_id',
    ];

    // Relationships
    public function ptr()
    {
        return $this->belongsTo(ProductTransferRequest::class, 'product_transfer_request_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function measurement_unit()
    {
        return $this->belongsTo(MeasurementUnit::class, 'unit_id');
    }
}
