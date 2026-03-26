# Multi-Level Unit System Guide

## Overview

The system now supports **three-level hierarchical unit management** for inventory tracking. This allows you to manage products with complex unit structures like boxes → bulks → bottles.

---

## Database Structure

### Updated Columns in `products` Table

| Old Column | New Column | Description |
|------------|------------|-------------|
| `shop_quantity` | `shop_quantity_in_sales_unit` | Shop stock in **sales units** (smallest unit) |
| `store_quantity` | `store_quantity_in_purchase_unit` | Store stock in **purchase units** (largest unit) |

### Unit Hierarchy

```
Purchase Unit (Largest) → Transfer Unit (Middle) → Sales Unit (Smallest)
        ↓                          ↓                        ↓
   Example: Box          →      Bulk           →        Bottle
```

### Conversion Rates

- **`purchase_to_transfer_rate`**: How many transfer units in one purchase unit
- **`transfer_to_sales_rate`**: How many sales units in one transfer unit

---

## Example: Coca-Cola Product Setup

### Product Configuration

```
Purchase Unit: Box (id: 9)
Transfer Unit: Bulk (id: 13)
Sales Unit: Bottle (id: 14)

purchase_to_transfer_rate: 5   (1 Box = 5 Bulks)
transfer_to_sales_rate: 10      (1 Bulk = 10 Bottles)

Therefore: 1 Box = 5 Bulks = 50 Bottles
```

### Database Values

```php
// When you receive 10 boxes in store:
store_quantity_in_purchase_unit = 10  // Stores actual boxes received

// When you transfer 3 bulks to shop, it converts:
store_quantity_in_purchase_unit -= 0.6  // (3 ÷ 5 = 0.6 boxes)
shop_quantity_in_sales_unit += 30       // (3 × 10 = 30 bottles)
```

---

## Virtual Attributes (Auto-Calculated)

The Product model automatically calculates quantities in all units:

### Store Quantities

```php
$product->store_quantity_in_purchase_unit  // Actual stored value (10 boxes)
$product->store_quantity_in_transfer_unit  // 10 × 5 = 50 bulks (calculated)
$product->store_quantity_in_sales_unit     // 50 × 10 = 500 bottles (calculated)
```

### Shop Quantities

```php
$product->shop_quantity_in_sales_unit      // Actual stored value (30 bottles)
$product->shop_quantity_in_transfer_unit   // 30 ÷ 10 = 3 bulks (calculated)
$product->shop_quantity_in_purchase_unit   // 3 ÷ 5 = 0.6 boxes (calculated)
```

### Total Available

```php
$product->total_available_in_sales_unit    // Store + Shop in bottles
$product->total_available_in_transfer_unit // Store + Shop in bulks
$product->total_available_in_purchase_unit // Store + Shop in boxes
```

---

## Usage in Controllers

### 1. Creating a Product

```php
// ProductController@store
$validated = $request->validate([
    'name' => 'required|string',
    'purchase_unit_id' => 'required|exists:measurement_units,id',  // Box
    'transfer_unit_id' => 'required|exists:measurement_units,id',  // Bulk
    'sales_unit_id' => 'required|exists:measurement_units,id',     // Bottle
    'purchase_to_transfer_rate' => 'required|numeric',             // 5
    'transfer_to_sales_rate' => 'required|numeric',                // 10
    'store_quantity_in_purchase_unit' => 'nullable|numeric',       // 10 boxes
    'shop_quantity_in_sales_unit' => 'nullable|numeric',           // 0 bottles
    // ... other fields
]);

Product::create($validated);
// Store as-is, no conversion!
```

### 2. Receiving Goods (GRN)

```php
// GoodReceiveNoteController@store
// When receiving 10 boxes:
Product::where('id', $productId)
    ->increment('store_quantity_in_purchase_unit', 10);

// Result: store now has 10 boxes (automatically shows as 50 bulks or 500 bottles)
```

### 3. Transferring Stock (Store → Shop)

```php
// ProductTransferRequestsController@updateStatus
// Transfer 3 bulks from store to shop:

$requestedQuantity = 3; // in transfer units (bulks)

// Convert to purchase units for store deduction
$quantityInPurchaseUnits = $requestedQuantity / $product->purchase_to_transfer_rate;
// = 3 ÷ 5 = 0.6 boxes

// Convert to sales units for shop addition
$quantityInSalesUnits = $requestedQuantity * $product->transfer_to_sales_rate;
// = 3 × 10 = 30 bottles

$product->decrement('store_quantity_in_purchase_unit', $quantityInPurchaseUnits);
$product->increment('shop_quantity_in_sales_unit', $quantityInSalesUnits);
```

### 4. Making a Sale

```php
// SaleController@store
// Sell 15 bottles:
$product->decrement('shop_quantity_in_sales_unit', 15);

// Result: shop_quantity_in_sales_unit reduced by 15
```

---

## Frontend Display Example

### Product Form (Add/Edit)

```javascript
// Input fields
<Input 
  label="Store Quantity (in Purchase Unit)"
  v-model="form.store_quantity_in_purchase_unit"
  placeholder="Enter boxes"
/>

<Input 
  label="Shop Quantity (in Sales Unit)"
  v-model="form.shop_quantity_in_sales_unit"
  placeholder="Enter bottles"
/>

// Calculated display (read-only)
<div class="calculated-values">
  <p>Store Available:</p>
  <ul>
    <li>{{ product.store_quantity_in_purchase_unit }} {{ purchaseUnit.symbol }}</li>
    <li>{{ product.store_quantity_in_transfer_unit }} {{ transferUnit.symbol }}</li>
    <li>{{ product.store_quantity_in_sales_unit }} {{ salesUnit.symbol }}</li>
  </ul>
  
  <p>Shop Available:</p>
  <ul>
    <li>{{ product.shop_quantity_in_sales_unit }} {{ salesUnit.symbol }}</li>
    <li>{{ product.shop_quantity_in_transfer_unit }} {{ transferUnit.symbol }}</li>
    <li>{{ product.shop_quantity_in_purchase_unit }} {{ purchaseUnit.symbol }}</li>
  </ul>
</div>
```

---

## Available Measurement Units

The system now includes these units (see MeasurementUnitSeeder):

| ID | Name | Symbol | Use Case |
|----|------|--------|----------|
| 1 | Unit | UOM | Default |
| 2 | Kilogram | kg | Weight |
| 3 | Gram | g | Small weight |
| 4 | Liter | L | Volume |
| 5 | Milliliter | mL | Small volume |
| 6 | Meter | m | Length |
| 7 | Centimeter | cm | Small length |
| 8 | Piece | pc | Individual items |
| 9 | Box | box | **Purchase Unit** |
| 10 | Dozen | dz | Groups of 12 |
| 11 | Carton | ctn | Large containers |
| 12 | Case | case | Product cases |
| 13 | Bulk | bulk | **Transfer Unit** |
| 14 | Bottle | btl | **Sales Unit** |
| 15 | Can | can | Canned goods |
| 16 | Pack | pk | Packages |
| 17 | Bag | bag | Bagged items |
| 18 | Pallet | plt | Warehouse pallets |

---

## Migration Applied

```php
// 2026_01_20_133620_update_products_table_for_multi_level_units.php
- Renamed: store_quantity → store_quantity_in_purchase_unit
- Renamed: shop_quantity → shop_quantity_in_sales_unit
```

---

## Key Benefits

✅ **Preserve actual unit information** - Know exactly how many boxes you have  
✅ **Accurate inventory tracking** - Track at each hierarchy level  
✅ **Dynamic calculations** - No data loss from conversions  
✅ **Flexible reporting** - Display quantities in any unit  
✅ **Support complex scenarios** - Handle fractional conversions  
✅ **Audit trail friendly** - Movements tracked in native units  

---

## Testing the System

### 1. Create a Test Product

```php
Product::create([
    'name' => 'Coca-Cola',
    'purchase_unit_id' => 9,   // Box
    'transfer_unit_id' => 13,  // Bulk
    'sales_unit_id' => 14,     // Bottle
    'purchase_to_transfer_rate' => 5,   // 5 bulks per box
    'transfer_to_sales_rate' => 10,     // 10 bottles per bulk
    'store_quantity_in_purchase_unit' => 10,  // 10 boxes in store
    'shop_quantity_in_sales_unit' => 0,        // 0 bottles in shop
    // ... other required fields
]);
```

### 2. Verify Calculations

```php
$product = Product::find($id);

echo $product->store_quantity_in_purchase_unit;  // 10 boxes
echo $product->store_quantity_in_transfer_unit;  // 50 bulks
echo $product->store_quantity_in_sales_unit;     // 500 bottles
```

### 3. Test Transfer

```php
// Transfer 3 bulks to shop
$product->decrement('store_quantity_in_purchase_unit', 0.6);  // -0.6 boxes
$product->increment('shop_quantity_in_sales_unit', 30);        // +30 bottles

// Verify
echo $product->store_quantity_in_purchase_unit;  // 9.4 boxes
echo $product->shop_quantity_in_sales_unit;      // 30 bottles
```

---

## Important Notes

1. **Always store in base units**: Store in purchase units, shop in sales units
2. **Let the model calculate**: Use virtual attributes for conversions
3. **Fractional values OK**: The system handles 0.6 boxes correctly
4. **Frontend validation**: Ensure conversion rates are set before enabling transfers
5. **Zero division protection**: All conversion methods check for zero rates

---

## Support

For questions or issues with the multi-level unit system, refer to:
- Product Model: `app/Models/Product.php` (virtual attributes)
- Migration: `database/migrations/2026_01_20_*_update_products_table_for_multi_level_units.php`
- Controllers: Check GoodReceiveNoteController, ProductTransferRequestsController, SaleController
