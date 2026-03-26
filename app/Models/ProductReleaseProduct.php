<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductReleaseProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_release_id',
        'product_id',
        'quantity',
        'unit_price',
        'total',
    ];

    /**
     * Relationship: Belongs to a product release
     */
    public function productRelease()
    {
        return $this->belongsTo(ProductReleaseNote::class, 'product_release_id');
    }

    /**
     * Relationship: Belongs to a product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
