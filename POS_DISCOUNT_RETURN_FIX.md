# POS Discount Handling Fix for Partial Returns

## Problem Statement

When a sale with discount is made and later partially returned, the system was not correctly adjusting the discount. The returned item amount was calculated incorrectly because the original discount was applied to the full quantity at the sale level only, without tracking how much discount was allocated to each unit.

## Original System Flaws

### 1. **Discount Storage**
- ❌ Discount was only stored in `sales` table as a total amount
- ❌ `sales_products` table had no discount tracking
- ❌ No way to determine discounted unit price for partial returns

### 2. **Return Calculation Issue**
```
Example:
- Product: Test Product @ 100.00/unit
- Quantity sold: 2 units
- Subtotal: 200.00
- Discount applied: 70.00 (35% off)
- Net amount: 130.00
- Effective unit price: 65.00/unit

When returning 1 unit:
❌ OLD SYSTEM: Refunded 100.00 (incorrect - using original price)
✅ NEW SYSTEM: Refunds 65.00 (correct - using discounted price)
```

## Solution Implemented

### Phase 1: Database Schema Enhancement

**Migration:** `2026_02_02_100000_add_discount_columns_to_sales_products_table.php`

Added three columns to `sales_products` table:

```php
- discount_amount (decimal): Proportional discount allocated to this line item
- net_amount (decimal): Line total after discount (total - discount_amount)
- is_return (boolean): Flag to identify return line items
```

### Phase 2: Sale Creation Logic Update

**File:** `SaleController.php` → `store()` method

**Key Changes:**
1. **Proportional Discount Distribution**
   ```php
   // Calculate proportional discount for each line item
   $lineDiscountAmount = ($lineTotal / $totalAmount) * $totalDiscount;
   ```

2. **Store Complete Line Item Data**
   ```php
   SalesProduct::create([
       'sale_id' => $sale->id,
       'product_id' => $item['product_id'],
       'quantity' => $item['quantity'],
       'price' => $item['price'],
       'total' => $lineTotal,
       'discount_amount' => round($lineDiscountAmount, 2),
       'net_amount' => round($lineTotal - $lineDiscountAmount, 2),
       'is_return' => false,
   ]);
   ```

**Formula Used:**
```
Line Discount = (Line Total / Sale Total) × Total Discount

Example with 2 products:
- Product A: 100 × 2 = 200
- Product B: 150 × 1 = 150
- Total: 350
- Discount: 70

Product A discount: (200/350) × 70 = 40.00
Product B discount: (150/350) × 70 = 30.00

Product A unit price after discount: (200-40)/2 = 80.00
Product B unit price after discount: (150-30)/1 = 120.00
```

### Phase 3: Return Processing Logic Update

**File:** `ReturnController.php` → `createFromSales()` method

**Key Changes:**

1. **Calculate Refund Using Discounted Price**
   ```php
   // Calculate discounted unit price from stored data
   if (isset($salesProduct->net_amount) && $salesProduct->quantity > 0) {
       $discountedUnitPrice = $salesProduct->net_amount / $salesProduct->quantity;
   } else {
       // Fallback calculation
       $unitDiscount = ($salesProduct->discount_amount ?? 0) / $salesProduct->quantity;
       $discountedUnitPrice = $salesProduct->price - $unitDiscount;
   }
   
   $totalRefund += $discountedUnitPrice * $productData['return_quantity'];
   ```

2. **Store Return with Correct Pricing**
   ```php
   SalesReturnProduct::create([
       'sales_return_id' => $return->id,
       'product_id' => $salesProduct->product_id,
       'quantity' => $productData['return_quantity'],
       'price' => round($returnPrice, 2),  // Uses discounted price
       'total' => round($returnTotal, 2),
   ]);
   ```

### Phase 4: Sale Model Enhancement

**File:** `Sale.php`

Added helper methods for accurate post-return calculations:

1. **`getNetAmountAfterReturnsAttribute()`**
   - Calculates effective net amount after approved returns
   - Properly handles both cash and product returns

2. **`getEffectiveDiscountAttribute()`**
   - Returns the discount amount for remaining items only
   - Excludes discount for returned items

3. **`getEffectiveTotalAmountAttribute()`**
   - Calculates subtotal for remaining items

4. **`getReturnSummary()`**
   - Provides comprehensive return impact analysis
   - Returns: original amounts, returned amounts, current amounts

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     SALE CREATION                            │
├─────────────────────────────────────────────────────────────┤
│ Input:                                                       │
│   - Items: [{product_id, quantity, price}, ...]             │
│   - Total Discount: 70.00                                   │
│                                                              │
│ Processing:                                                  │
│   1. Calculate total_amount (sum of all line totals)       │
│   2. For each line item:                                    │
│      - Calculate proportional discount                      │
│      - Store: total, discount_amount, net_amount           │
│   3. Store sale with total discount                         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                  SALES_PRODUCTS TABLE                        │
├─────────────────────────────────────────────────────────────┤
│ product_id | qty | price  | total  | discount | net_amount │
│     1      |  2  | 100.00 | 200.00 |  40.00   |  160.00   │
│     2      |  1  | 150.00 | 150.00 |  30.00   |  120.00   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    PARTIAL RETURN                            │
├─────────────────────────────────────────────────────────────┤
│ Return 1 unit of Product 1:                                 │
│                                                              │
│ Calculation:                                                 │
│   - Original unit price: 100.00                             │
│   - Discounted unit price: 160.00 / 2 = 80.00             │
│   - Refund amount: 80.00 × 1 = 80.00                       │
│                                                              │
│ Result:                                                      │
│   - Customer refunded: 80.00 (not 100.00)                  │
│   - Remaining sale value: 120.00 + 80.00 = 200.00          │
│   - Total discount reduced to: 50.00 (from 70.00)          │
└─────────────────────────────────────────────────────────────┘
```

## Benefits

### ✅ Accurate Financial Tracking
- Returns are refunded at the actual price paid by customer
- Discount is proportionally reduced with returns

### ✅ Data Integrity
- All line items store complete pricing information
- No data loss when calculating partial returns

### ✅ Audit Trail
- Can reconstruct exact pricing for any historical sale
- Clear visibility into discount allocation

### ✅ Flexibility
- Works with any discount amount (fixed or percentage)
- Handles multiple products with different prices
- Supports partial returns of any quantity

## Testing Scenarios

### Test Case 1: Single Product with Discount - Partial Return
```
Sale:
- Product A: 100.00 × 2 = 200.00
- Discount: 70.00
- Net: 130.00
- Unit price after discount: 65.00

Return: 1 unit
Expected refund: 65.00 ✓
Remaining sale value: 65.00 ✓
```

### Test Case 2: Multiple Products with Discount - Partial Return
```
Sale:
- Product A: 100.00 × 2 = 200.00 (discount: 40.00)
- Product B: 150.00 × 1 = 150.00 (discount: 30.00)
- Total: 350.00
- Discount: 70.00
- Net: 280.00

Return: 1 unit of Product A
Expected refund: 80.00 ✓
Remaining: Product A (1 @ 80) + Product B (1 @ 120) = 200.00 ✓
```

### Test Case 3: Full Return
```
Sale:
- Product A: 100.00 × 2 = 200.00
- Discount: 70.00
- Net: 130.00

Return: 2 units (full return)
Expected refund: 130.00 ✓
Remaining sale value: 0.00 ✓
```

## Migration Guide for Existing Data

For existing sales in the database (created before this fix), the new columns will have default values:
- `discount_amount`: 0.00
- `net_amount`: 0.00
- `is_return`: false

**To update existing data, run this SQL:**
```sql
-- Update existing sales_products records to populate discount fields
UPDATE sales_products sp
JOIN sales s ON sp.sale_id = s.id
SET 
    sp.discount_amount = CASE 
        WHEN s.total_amount > 0 
        THEN ROUND((sp.total / s.total_amount) * s.discount, 2)
        ELSE 0
    END,
    sp.net_amount = sp.total - CASE 
        WHEN s.total_amount > 0 
        THEN ROUND((sp.total / s.total_amount) * s.discount, 2)
        ELSE 0
    END
WHERE sp.discount_amount = 0 
  AND sp.net_amount = 0
  AND sp.is_return = 0;
```

## API/Frontend Considerations

### Display Changes Needed

1. **Invoice Display**
   - Show per-item discount if applicable
   - Display both original and discounted prices

2. **Return Form**
   - Show discounted price when selecting items to return
   - Calculate refund preview in real-time

3. **Sales History**
   - Use `getNetAmountAfterReturnsAttribute()` for accurate totals
   - Display return summary using `getReturnSummary()`

## Conclusion

This fix ensures that the POS system correctly handles discounts during partial returns by:
1. Tracking discount at the line-item level
2. Calculating refunds based on actual prices paid
3. Maintaining accurate financial records
4. Providing clear audit trails

The system now properly distributes discounts proportionally across all products and accurately calculates refund amounts for partial returns, ensuring both customer satisfaction and business accuracy.

---

**Implementation Date:** February 2, 2026  
**Files Modified:**
- Database: `sales_products` table
- Controllers: `SaleController.php`, `ReturnController.php`
- Models: `Sale.php`, `SalesProduct.php`
- Migrations: 1 new migration file
