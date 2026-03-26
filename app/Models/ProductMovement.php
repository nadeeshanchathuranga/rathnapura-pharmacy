<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMovement extends Model
{
    protected $fillable = [
        'product_id',
        'movement_type',
        'quantity',
        'reference',
    ];

    // Movement types
    const TYPE_PURCHASE = 0;      // GRN
    const TYPE_PURCHASE_RETURN = 1; // PRN
    const TYPE_TRANSFER = 2;       // PTR
    const TYPE_SALE = 3;           // Sale
    const TYPE_SALE_RETURN = 4;    // Sale Return
    const TYPE_GRN_RETURN = 5;     // GRN Return / BRN Return
    const TYPE_STOCK_TRANSFER_RETURN = 6; // Damaged/Return from Shop to Store

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Helper method to record movement
    public static function recordMovement($productId, $movementType, $quantity, $reference = null)
    {
        return self::create([
            'product_id' => $productId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'reference' => $reference,
        ]);
    }

    // Backwards-compatible alias used by controllers
    public static function record($productId, $movementType, $quantity, $reference = null)
    {
        return self::recordMovement($productId, $movementType, $quantity, $reference);
    }
}
