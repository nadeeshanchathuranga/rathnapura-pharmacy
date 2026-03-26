# Quick Reference: Discount & Return Handling

## For Developers

### Key Concept
**Discounts are now tracked at the LINE ITEM level, not just at the sale level.**

This enables accurate calculations when partial returns occur.

---

## Database Schema

### `sales_products` Table - New Columns

| Column | Type | Description |
|--------|------|-------------|
| `discount_amount` | decimal(15,2) | Proportional discount for this line |
| `net_amount` | decimal(15,2) | Line total after discount |
| `is_return` | boolean | Flag for return line items |

---

## Creating a Sale

### Before (OLD - Incorrect)
```php
SalesProduct::create([
    'sale_id' => $sale->id,
    'product_id' => $product_id,
    'quantity' => $qty,
    'price' => $price,
    'total' => $price * $qty,
    // ❌ No discount tracking!
]);
```

### After (NEW - Correct)
```php
$lineTotal = $price * $qty;
$lineDiscount = ($lineTotal / $totalAmount) * $totalDiscount;
$lineNet = $lineTotal - $lineDiscount;

SalesProduct::create([
    'sale_id' => $sale->id,
    'product_id' => $product_id,
    'quantity' => $qty,
    'price' => $price,
    'total' => $lineTotal,
    'discount_amount' => round($lineDiscount, 2),  // ✅ Track discount
    'net_amount' => round($lineNet, 2),            // ✅ Track net amount
    'is_return' => false,
]);
```

---

## Processing a Return

### Calculating Refund Amount

```php
// Get the sales product
$salesProduct = SalesProduct::find($sales_product_id);

// Calculate discounted unit price
$discountedUnitPrice = $salesProduct->net_amount / $salesProduct->quantity;

// Calculate refund for returned quantity
$refundAmount = $discountedUnitPrice * $returnQty;

// Create return record
SalesReturnProduct::create([
    'sales_return_id' => $return->id,
    'product_id' => $salesProduct->product_id,
    'quantity' => $returnQty,
    'price' => round($discountedUnitPrice, 2),  // ✅ Use discounted price
    'total' => round($refundAmount, 2),
]);
```

---

## Sale Model Helpers

### Get Net Amount After Returns
```php
$sale = Sale::find($id);
$currentNet = $sale->getNetAmountAfterReturnsAttribute();
// or
$currentNet = $sale->net_amount_after_returns;
```

### Get Return Summary
```php
$summary = $sale->getReturnSummary();
/*
Returns:
[
    'original_total' => 200.00,
    'original_discount' => 70.00,
    'original_net' => 130.00,
    'returned_amount' => 65.00,
    'current_net' => 65.00,
    'returns_count' => 1,
]
*/
```

### Get Effective Discount (after returns)
```php
$effectiveDiscount = $sale->effective_discount;
```

---

## Common Scenarios

### Scenario 1: Single Product, Partial Return
```
Sale:
- Product A: $100 × 2 = $200
- Discount: $70
- Net: $130
- Unit price after discount: $65

Return 1 unit:
- Refund: $65
- Remaining: $65
```

### Scenario 2: Multiple Products, Partial Return
```
Sale:
- Product A: $100 × 2 = $200 → discount $40 → net $160
- Product B: $150 × 1 = $150 → discount $30 → net $120
- Total: $350, Discount: $70, Net: $280

Return 1 unit of Product A:
- Refund: $80 (half of $160)
- Remaining: $80 + $120 = $200
```

---

## Formulas

### Proportional Discount Allocation
```php
$lineDiscount = ($lineTotal / $saleTotal) * $totalDiscount
```

### Discounted Unit Price
```php
$unitPriceAfterDiscount = $lineNetAmount / $quantity
```

### Refund Amount
```php
$refundAmount = $unitPriceAfterDiscount * $returnQuantity
```

---

## Frontend Display

### Show Discounted Price in Return Form
```javascript
const salesProduct = {
    quantity: 2,
    price: 100.00,
    total: 200.00,
    discount_amount: 70.00,
    net_amount: 130.00
};

const unitPriceAfterDiscount = salesProduct.net_amount / salesProduct.quantity;
// = 65.00

// When user selects 1 unit to return
const refund = unitPriceAfterDiscount * 1;
// = 65.00
```

### Display Return Impact
```javascript
const sale = await fetchSale(saleId);
const summary = sale.return_summary;

console.log(`Original: ${summary.original_net}`);
console.log(`Returned: ${summary.returned_amount}`);
console.log(`Remaining: ${summary.current_net}`);
```

---

## SQL Queries

### Get Sales with Discount Breakdown
```sql
SELECT 
    s.invoice_no,
    s.discount AS sale_discount,
    sp.product_id,
    sp.quantity,
    sp.price,
    sp.total,
    sp.discount_amount,
    sp.net_amount,
    (sp.net_amount / sp.quantity) AS unit_price_after_discount
FROM sales s
JOIN sales_products sp ON s.id = sp.sale_id
WHERE s.discount > 0
ORDER BY s.id, sp.id;
```

### Verify Discount Allocation
```sql
SELECT 
    s.id,
    s.invoice_no,
    s.discount AS total_discount,
    SUM(sp.discount_amount) AS sum_line_discounts,
    ABS(s.discount - SUM(sp.discount_amount)) AS difference
FROM sales s
JOIN sales_products sp ON s.id = sp.sale_id
WHERE s.discount > 0
GROUP BY s.id
HAVING ABS(s.discount - SUM(sp.discount_amount)) > 0.01;
```

---

## Testing

### Run Tests
```bash
php artisan test --filter=DiscountPartialReturnTest
```

### Test Scenarios
- ✅ Single product with discount - partial return
- ✅ Multiple products with proportional discount - partial return
- ✅ Full return with discount
- ✅ No discount scenario (baseline)
- ✅ Sale model helper methods

---

## Migration Path

### For New Installations
- Migration runs automatically
- All new sales will have discount tracking

### For Existing Data
```bash
# 1. Run migration
php artisan migrate

# 2. Update existing data
mysql -u root -p jpos_db < database/scripts/update_existing_sales_discounts.sql
```

---

## Troubleshooting

### Issue: Returns showing incorrect amounts
**Check:** Are `discount_amount` and `net_amount` populated?
```sql
SELECT * FROM sales_products WHERE discount_amount = 0 AND net_amount = 0;
```

### Issue: Discount doesn't add up
**Verify:** Sum of line discounts equals sale discount
```sql
SELECT 
    s.id,
    s.discount,
    SUM(sp.discount_amount) AS line_discounts,
    s.discount - SUM(sp.discount_amount) AS diff
FROM sales s
JOIN sales_products sp ON s.id = sp.sale_id
GROUP BY s.id
HAVING ABS(diff) > 0.05;
```

---

## API Endpoints

### Create Sale with Discount
```json
POST /api/sales
{
    "invoice_no": "INV-000123",
    "items": [
        {"product_id": 1, "quantity": 2, "price": 100.00}
    ],
    "discount": 70.00,
    "payments": [
        {"payment_type": 0, "amount": 130.00}
    ]
}
```

Response includes line-level discount allocation.

### Create Return
```json
POST /api/returns
{
    "sale_id": 123,
    "return_type": 1,
    "selected_products": [
        {"sales_product_id": 456, "return_quantity": 1}
    ]
}
```

Automatically calculates refund based on discounted price.

---

## Best Practices

1. **Always use `net_amount` for return calculations**, not `total`
2. **Verify discount allocation** after creating sales
3. **Test with edge cases**: 100% discount, $0.01 amounts, rounding
4. **Display both original and discounted prices** to users
5. **Log all discount-related calculations** for audit trails

---

## Related Files

- Migration: `database/migrations/2026_02_02_100000_add_discount_columns_to_sales_products_table.php`
- Controller: `app/Http/Controllers/SaleController.php`
- Controller: `app/Http/Controllers/ReturnController.php`
- Model: `app/Models/Sale.php`
- Model: `app/Models/SalesProduct.php`
- Tests: `tests/Feature/DiscountPartialReturnTest.php`
- Docs: `POS_DISCOUNT_RETURN_FIX.md`

---

**Last Updated:** February 2, 2026
