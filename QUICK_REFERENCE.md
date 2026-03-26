# Multi-Level Units - Quick Reference Card

## 📊 Database Fields

| Old Field | New Field | Type | Description |
|-----------|-----------|------|-------------|
| `shop_quantity` | `shop_quantity_in_sales_unit` | integer | Shop stock in smallest unit (bottles) |
| `store_quantity` | `store_quantity_in_purchase_unit` | integer | Store stock in largest unit (boxes) |

## 🔗 Unit Hierarchy

```
┌─────────────────┐
│ Purchase Unit   │  (Largest - e.g., Box)
│ (Store Storage) │
└────────┬────────┘
         │ purchase_to_transfer_rate (e.g., 5)
         ↓
┌─────────────────┐
│ Transfer Unit   │  (Middle - e.g., Bulk)
│ (Transit/Transfer)│
└────────┬────────┘
         │ transfer_to_sales_rate (e.g., 10)
         ↓
┌─────────────────┐
│ Sales Unit      │  (Smallest - e.g., Bottle)
│ (Shop Storage)  │
└─────────────────┘
```

## 🎯 Virtual Attributes (Auto-Calculated)

```php
// STORE QUANTITIES
$product->store_quantity_in_purchase_unit  // 10 boxes (stored in DB)
$product->store_quantity_in_transfer_unit  // 50 bulks (calculated)
$product->store_quantity_in_sales_unit     // 500 bottles (calculated)

// SHOP QUANTITIES
$product->shop_quantity_in_sales_unit      // 30 bottles (stored in DB)
$product->shop_quantity_in_transfer_unit   // 3 bulks (calculated)
$product->shop_quantity_in_purchase_unit   // 0.6 boxes (calculated)

// TOTALS
$product->total_available_in_sales_unit    // All stock in bottles
$product->total_available_in_transfer_unit // All stock in bulks
$product->total_available_in_purchase_unit // All stock in boxes
```

## 🧮 Conversion Formulas

### Store (Purchase → Transfer → Sales)
```php
transfer = purchase × purchase_to_transfer_rate
sales = transfer × transfer_to_sales_rate
```

### Shop (Sales → Transfer → Purchase)
```php
transfer = sales ÷ transfer_to_sales_rate
purchase = transfer ÷ purchase_to_transfer_rate
```

## 📝 Common Operations

### 1. Receiving Goods (GRN)
```php
// Receive 10 boxes
Product::where('id', $productId)
    ->increment('store_quantity_in_purchase_unit', 10);
```

### 2. Transfer Store → Shop
```php
// Transfer 3 bulks
$quantityInPurchaseUnits = 3 / $product->purchase_to_transfer_rate;
$quantityInSalesUnits = 3 * $product->transfer_to_sales_rate;

$product->decrement('store_quantity_in_purchase_unit', $quantityInPurchaseUnits);
$product->increment('shop_quantity_in_sales_unit', $quantityInSalesUnits);
```

### 3. Make a Sale
```php
// Sell 15 bottles
Product::where('id', $productId)
    ->decrement('shop_quantity_in_sales_unit', 15);
```

### 4. Return Shop → Store
```php
// Return 20 bottles
$quantityInSalesUnits = 20;
$quantityInPurchaseUnits = $quantityInSalesUnits / 
    ($product->purchase_to_transfer_rate * $product->transfer_to_sales_rate);

$product->decrement('shop_quantity_in_sales_unit', $quantityInSalesUnits);
$product->increment('store_quantity_in_purchase_unit', $quantityInPurchaseUnits);
```

## 🎨 Frontend Examples

### Display All Units
```vue
<template>
  <div class="inventory-display">
    <h3>Store Inventory</h3>
    <ul>
      <li>{{ product.store_quantity_in_purchase_unit }} {{ purchaseUnit.symbol }}</li>
      <li>{{ product.store_quantity_in_transfer_unit }} {{ transferUnit.symbol }}</li>
      <li>{{ product.store_quantity_in_sales_unit }} {{ salesUnit.symbol }}</li>
    </ul>
    
    <h3>Shop Inventory</h3>
    <ul>
      <li>{{ product.shop_quantity_in_sales_unit }} {{ salesUnit.symbol }}</li>
      <li>{{ product.shop_quantity_in_transfer_unit }} {{ transferUnit.symbol }}</li>
      <li>{{ product.shop_quantity_in_purchase_unit }} {{ purchaseUnit.symbol }}</li>
    </ul>
  </div>
</template>
```

### Input Form
```vue
<template>
  <form @submit.prevent="submit">
    <!-- Store Quantity Input -->
    <TextInput
      v-model.number="form.store_quantity_in_purchase_unit"
      :label="`Store Quantity (${purchaseUnit?.symbol})`"
      type="number"
      step="0.01"
    />
    
    <!-- Shop Quantity Input -->
    <TextInput
      v-model.number="form.shop_quantity_in_sales_unit"
      :label="`Shop Quantity (${salesUnit?.symbol})`"
      type="number"
      step="0.01"
    />
    
    <!-- Conversion Rates -->
    <TextInput
      v-model.number="form.purchase_to_transfer_rate"
      label="Purchase to Transfer Rate"
      placeholder="e.g., 5"
    />
    
    <TextInput
      v-model.number="form.transfer_to_sales_rate"
      label="Transfer to Sales Rate"
      placeholder="e.g., 10"
    />
  </form>
</template>
```

## 📦 Measurement Units

| ID | Name | Symbol | Common Use |
|----|------|--------|------------|
| 9 | Box | box | Purchase Unit |
| 13 | Bulk | bulk | Transfer Unit |
| 14 | Bottle | btl | Sales Unit |
| 11 | Carton | ctn | Purchase Unit |
| 12 | Case | case | Purchase Unit |
| 15 | Can | can | Sales Unit |
| 8 | Piece | pc | Sales Unit |
| 16 | Pack | pk | Transfer Unit |

## ⚠️ Important Rules

1. ✅ **Store in base units**: Store uses purchase units, Shop uses sales units
2. ✅ **Never save calculated values**: Only update base fields
3. ✅ **Use virtual attributes**: Access calculated values via model accessors
4. ✅ **Handle fractional values**: System supports 0.6 boxes (3 bulks ÷ 5)
5. ✅ **Set conversion rates**: Always define both rate fields
6. ✅ **Zero division check**: Code checks for zero rates before dividing

## 🐛 Troubleshooting

### Issue: Values not updating
```bash
php artisan optimize:clear
```

### Issue: Wrong calculations
- Check `purchase_to_transfer_rate` is set
- Check `transfer_to_sales_rate` is set
- Verify unit IDs are correct

### Issue: Frontend not showing new fields
- Update Vue components to use new field names
- Clear browser cache
- Check API response structure

## 📚 Full Documentation

- **Implementation Guide**: `MULTI_LEVEL_UNITS_GUIDE.md`
- **Summary**: `IMPLEMENTATION_SUMMARY.md`
- **Migration**: `database/migrations/2026_01_20_*_update_products_table_for_multi_level_units.php`

---

**Last Updated**: January 20, 2026  
**Version**: 1.0  
**Status**: Production Ready
