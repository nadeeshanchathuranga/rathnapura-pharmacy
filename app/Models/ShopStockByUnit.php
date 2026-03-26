<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ShopStockByUnit Model
 * 
 * Tracks shop inventory by specific measurement units.
 * Example: Shop holds 12 bottles, 5 boxes, etc.
 * This enables accurate deduction from the exact unit the shop has.
 */
class ShopStockByUnit extends Model
{
    protected $table = 'shop_stock_by_unit';

    protected $fillable = [
        'product_id',
        'measurement_unit_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function measurementUnit()
    {
        return $this->belongsTo(MeasurementUnit::class);
    }

    /**
     * Get available quantity for a specific product and unit
     */
    public static function getAvailableQuantity($productId, $unitId)
    {
        $record = self::where('product_id', $productId)
            ->where('measurement_unit_id', $unitId)
            ->first();

        return $record ? $record->quantity : 0;
    }

    /**
     * Add stock to shop for a specific unit
     */
    public static function addStock($productId, $unitId, $quantity)
    {
        $record = self::firstOrCreate(
            [
                'product_id' => $productId,
                'measurement_unit_id' => $unitId,
            ],
            [
                'quantity' => 0
            ]
        );

        $record->increment('quantity', $quantity);
        return $record;
    }

    /**
     * Deduct stock from shop for a specific unit
     * Returns true if successful, false if insufficient stock
     */
    public static function deductStock($productId, $unitId, $quantity)
    {
        $record = self::where('product_id', $productId)
            ->where('measurement_unit_id', $unitId)
            ->first();

        if (!$record || $record->quantity < $quantity) {
            return false;
        }

        $record->decrement('quantity', $quantity);
        return true;
    }

    /**
     * Check if shop has sufficient stock for a specific unit
     */
    public static function hasSufficientStock($productId, $unitId, $quantity)
    {
        $available = self::getAvailableQuantity($productId, $unitId);
        return $available >= $quantity;
    }

    /**
     * Convert quantity to smallest unit (sales unit) based on product hierarchy
     */
    public static function convertToSalesUnit($productId, $unitId, $quantity)
    {
        $product = Product::find($productId);
        if (!$product) {
            return 0;
        }

        // If it's already sales unit, return as is
        if ($unitId == $product->sales_unit_id) {
            return $quantity;
        }

        // If it's transfer unit, multiply by transfer_to_sales_rate
        if ($unitId == $product->transfer_unit_id) {
            return $quantity * ($product->transfer_to_sales_rate ?? 1);
        }

        // If it's purchase unit, multiply by both rates
        if ($unitId == $product->purchase_unit_id) {
            return $quantity * ($product->purchase_to_transfer_rate ?? 1) * ($product->transfer_to_sales_rate ?? 1);
        }

        return $quantity;
    }

    /**
     * Convert quantity from smallest unit (sales unit) to target unit
     */
    public static function convertFromSalesUnit($productId, $targetUnitId, $quantityInSalesUnit)
    {
        $product = Product::find($productId);
        if (!$product) {
            return 0;
        }

        // If target is sales unit, return as is
        if ($targetUnitId == $product->sales_unit_id) {
            return $quantityInSalesUnit;
        }

        // If target is transfer unit, divide by transfer_to_sales_rate
        if ($targetUnitId == $product->transfer_unit_id) {
            $rate = $product->transfer_to_sales_rate ?? 1;
            return $rate > 0 ? $quantityInSalesUnit / $rate : 0;
        }

        // If target is purchase unit, divide by both rates
        if ($targetUnitId == $product->purchase_unit_id) {
            $rate1 = $product->purchase_to_transfer_rate ?? 1;
            $rate2 = $product->transfer_to_sales_rate ?? 1;
            $totalRate = $rate1 * $rate2;
            return $totalRate > 0 ? $quantityInSalesUnit / $totalRate : 0;
        }

        return $quantityInSalesUnit;
    }

    /**
     * Get breakdown of quantity in hierarchy (e.g., 1 bundle + 2 bottles)
     */
    public static function getQuantityBreakdown($productId, $quantityInSalesUnit)
    {
        $product = Product::with(['purchaseUnit', 'transferUnit', 'salesUnit'])->find($productId);
        if (!$product) {
            return null;
        }

        $breakdown = [];
        $remaining = $quantityInSalesUnit;

        // Calculate purchase units (largest)
        $purchaseRate = ($product->purchase_to_transfer_rate ?? 1) * ($product->transfer_to_sales_rate ?? 1);
        if ($purchaseRate > 0) {
            $purchaseQty = floor($remaining / $purchaseRate);
            if ($purchaseQty > 0) {
                $breakdown[] = [
                    'quantity' => $purchaseQty,
                    'unit' => $product->purchaseUnit->name ?? 'Box',
                    'unit_id' => $product->purchase_unit_id
                ];
                $remaining = $remaining - ($purchaseQty * $purchaseRate);
            }
        }

        // Calculate transfer units (middle)
        $transferRate = $product->transfer_to_sales_rate ?? 1;
        if ($transferRate > 0) {
            $transferQty = floor($remaining / $transferRate);
            if ($transferQty > 0) {
                $breakdown[] = [
                    'quantity' => $transferQty,
                    'unit' => $product->transferUnit->name ?? 'Bundle',
                    'unit_id' => $product->transfer_unit_id
                ];
                $remaining = $remaining - ($transferQty * $transferRate);
            }
        }

        // Remaining sales units (smallest)
        if ($remaining > 0) {
            $breakdown[] = [
                'quantity' => $remaining,
                'unit' => $product->salesUnit->name ?? 'Bottle',
                'unit_id' => $product->sales_unit_id
            ];
        }

        return $breakdown;
    }
}
