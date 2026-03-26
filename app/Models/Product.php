<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Get the breakdown of shop quantity in sales unit into boxes, bundles, and loose bottles.
     * Returns an array: [
     *   'boxes' => int,
     *   'bundles' => int,
     *   'bottles' => int,
     *   'total_bottles' => int
     * ]
     */
    public function getBreakdownForQuantity($quantity)
{
    $totalBottles = (int) $quantity;
    $bundlesPerBox = (int) ($this->purchase_to_transfer_rate ?? 1);
    $bottlesPerBundle = (int) ($this->transfer_to_sales_rate ?? 1);
    $bottlesPerBox = $bundlesPerBox * $bottlesPerBundle;

    $boxes = $bottlesPerBox > 0 ? intdiv($totalBottles, $bottlesPerBox) : 0;
    $remAfterBoxes = $bottlesPerBox > 0 ? $totalBottles % $bottlesPerBox : $totalBottles;
    $bundles = $bottlesPerBundle > 0 ? intdiv($remAfterBoxes, $bottlesPerBundle) : 0;
    $bottles = $bottlesPerBundle > 0 ? $remAfterBoxes % $bottlesPerBundle : $remAfterBoxes;

    return [
        'boxes' => $boxes,
        'bundles' => $bundles,
        'bottles' => $bottles,
        'total_bottles' => $totalBottles,
    ];
}

    protected $fillable = [
        'name',
        'barcode',
        'brand_id',
        'category_id',
        'type_id',
        'discount_id',
        'tax_id',
        'shop_quantity_in_sales_unit',
        'shop_low_stock_margin',
        'store_quantity_in_purchase_unit',
         'store_quantity_in_transfer_unit',
         'store_quantity_in_sale_unit',
        'store_low_stock_margin',
        'purchase_price',
        'wholesale_price',
        'retail_price',
        'return_product',
        'purchase_unit_id',
        'sales_unit_id',
        'transfer_unit_id',
        'purchase_to_transfer_rate',
        'transfer_to_sales_rate',
        'status',
        'image',
    ];

    // Virtual attributes that are always computed and returned with the model
    // loose_bundles = raw DB value (partial bundles from opened boxes)
    // store_quantity_in_transfer_unit = total bundles (boxes * rate + loose)
    protected $appends = [
        'qty',
        'loose_bundles',
        'store_quantity_in_transfer_unit',
        'store_quantity_in_sales_unit',
        'shop_quantity_in_transfer_unit',
        'shop_quantity_in_purchase_unit'
    ];

    /**
     * Override newEloquentBuilder to handle 'qty' column aliasing
     */
    public function newEloquentBuilder($query)
    {
        return new \App\Database\ProductBuilder($query);
    }

    // protected $casts = [
    //     'qty' => 'integer',
    //     'purchase_price' => 'decimal:2',
    //     'wholesale_price' => 'decimal:2',
    //     'retail_price' => 'decimal:2',
    //     'return_product' => 'boolean',
    //     'purchase_to_transfer_rate' => 'decimal:2',
    //     'purchase_to_sales_rate' => 'decimal:2',
    //     'transfer_to_sales_rate' => 'decimal:2',
    //     'status' => 'integer',
    // ];

    // Relationships
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function purchaseUnit()
    {
        return $this->belongsTo(MeasurementUnit::class, 'purchase_unit_id');
    }

    public function salesUnit()
    {
        return $this->belongsTo(MeasurementUnit::class, 'sales_unit_id');
    }

    public function transferUnit()
    {
        return $this->belongsTo(MeasurementUnit::class, 'transfer_unit_id');
    }

    // Shop stock by unit relationship
    public function shopStockByUnit()
    {
        return $this->hasMany(ShopStockByUnit::class);
    }

    // Sales products relationship
    public function salesProducts()
    {
        return $this->hasMany(SalesProduct::class);
    }

    // Product movements (stock changes) relationship
    public function productMovements()
    {
        return $this->hasMany(ProductMovement::class);
    }

    // Return products relationship
    public function returnProducts()
    {
        return $this->hasMany(SalesReturnProduct::class);
    }

    // Return relationships (legacy)
    public function salesReturns()
    {
        return $this->hasMany(SalesReturnProduct::class);
    }

    // Scope for returnable products
    public function scopeReturnable($query)
    {
        return $query->where('return_product', true);
    }

    // Check if product is returnable
    public function getIsReturnableAttribute()
    {
        return (bool) $this->return_product;
    }

    // Virtual accessor for 'qty' to maintain backward compatibility
    // Maps to shop_quantity_in_sales_unit for controllers that still use 'qty'
    public function getQtyAttribute()
    {
        return $this->shop_quantity_in_sales_unit;
    }

    // Virtual mutator for 'qty' to maintain backward compatibility
    public function setQtyAttribute($value)
    {
        $this->attributes['shop_quantity_in_sales_unit'] = $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Multi-Level Unit Conversion Attributes
    |--------------------------------------------------------------------------
    |
    | These virtual attributes calculate quantities in different units:
    | - Store quantities: Always stored in purchase units, converted to transfer/sales
    | - Shop quantities: Always stored in sales units, converted to transfer/purchase
    |
    | Example: Coca-Cola
    | - Purchase Unit: Box
    | - Transfer Unit: Bulk (5 bulks per box)
    | - Sales Unit: Bottle (10 bottles per bulk)
    |
    | If store has 10 boxes:
    | - store_quantity_in_purchase_unit = 10 (stored)
    | - store_quantity_in_transfer_unit = 10 × 5 = 50 bulks (calculated)
    | - store_quantity_in_sales_unit = 50 × 10 = 500 bottles (calculated)
    |
    */

    /**
     * Get store quantity in purchase units (base storage unit)
     * This is the actual stored value
     */
    public function getStoreQuantityInPurchaseUnitAttribute()
    {
        return $this->attributes['store_quantity_in_purchase_unit'] ?? 0;
    }

    /**
     * Get loose bundles only (raw database value)
     * This is for display: shows only the loose/partial bundles from opened boxes
     */
    public function getLooseBundlesAttribute()
    {
        return $this->attributes['loose_bundles'] ?? 0;
    }

    /**
     * Get TOTAL store quantity in transfer units (bundles)
     * This includes: (boxes × bundles_per_box) + loose_bundles
     * Used for calculations, not display
     */
    public function getStoreQuantityInTransferUnitAttribute()
    {
        $purchaseQty = $this->attributes['store_quantity_in_purchase_unit'] ?? 0;
        $looseBundles = $this->attributes['store_quantity_in_transfer_unit'] ?? 0;
        $rate = $this->purchase_to_transfer_rate ?? 1;
        // Total bundles = (boxes * bundles_per_box) + loose bundles
        return ($purchaseQty * $rate) + $looseBundles;
    }

    /**
     * Get store quantity converted to sales units (smallest unit)
     * Formula: transfer_units × transfer_to_sales_rate
     */
    public function getStoreQuantityInSalesUnitAttribute()
    {
        $transferQty = $this->store_quantity_in_transfer_unit;
        $rate = $this->transfer_to_sales_rate ?? 1;
        return $transferQty * $rate;
    }

    /**
     * Get shop quantity in sales units (base storage unit)
     * This is the actual stored value
     */
    public function getShopQuantityInSalesUnitAttribute()
    {
        return $this->attributes['shop_quantity_in_sales_unit'] ?? 0;
    }

    /**
     * Get shop quantity converted to transfer units
     * Formula: sales_units ÷ transfer_to_sales_rate
     */
    public function getShopQuantityInTransferUnitAttribute()
    {
        $salesQty = $this->shop_quantity_in_sales_unit ?? 0;
        $rate = $this->transfer_to_sales_rate ?? 1;
        return $rate > 0 ? $salesQty / $rate : 0;
    }

    /**
     * Get shop quantity converted to purchase units (largest unit)
     * Formula: transfer_units ÷ purchase_to_transfer_rate
     */
    public function getShopQuantityInPurchaseUnitAttribute()
    {
        $transferQty = $this->shop_quantity_in_transfer_unit;
        $rate = $this->purchase_to_transfer_rate ?? 1;
        return $rate > 0 ? $transferQty / $rate : 0;
    }

    /**
     * Get total available quantity in sales units (smallest unit)
     * Combines store and shop quantities for total availability
     */
    public function getTotalAvailableInSalesUnitAttribute()
    {
        return $this->store_quantity_in_sale_unit + $this->shop_quantity_in_sales_unit;
    }

    /**
     * Get total available quantity in transfer units
     */
    public function getTotalAvailableInTransferUnitAttribute()
    {
        return $this->store_quantity_in_transfer_unit + $this->shop_quantity_in_transfer_unit;
    }

    /**
     * Get total available quantity in purchase units
     */
    public function getTotalAvailableInPurchaseUnitAttribute()
    {
        return $this->store_quantity_in_purchase_unit + $this->shop_quantity_in_purchase_unit;
    }

 public function measurement_unit()
{
    return $this->belongsTo(MeasurementUnit::class, 'purchase_unit_id');
}
 public function getShopStockBreakdownAttribute()
    {
        // Use the helper method with shop quantity
        return $this->getBreakdownForQuantity($this->shop_quantity_in_sales_unit ?? 0);
    }

}
