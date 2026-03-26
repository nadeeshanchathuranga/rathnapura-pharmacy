# Stock Transfer Return - Unit-Specific Inventory Fix

## Problem Statement

The shop's stock transfer return process had a critical flaw:
- When a shop had stock in **bottles**, the system was converting and deducting from **boxes**
- This caused inaccurate inventory tracking
- Returns must deduct from the **exact unit** the shop physically holds

## Solution Overview

### Key Principle
**Deduct from what the shop actually has, then convert when returning to store.**

### Implementation Components

#### 1. New Database Table: `shop_stock_by_unit`
Tracks shop inventory by specific measurement units.

**Structure:**
```sql
- id
- product_id (foreign key)
- measurement_unit_id (foreign key)
- quantity (decimal)
- created_at, updated_at
```

**Example Data:**
```
Product: Coca-Cola
- Unit: Bottle → Quantity: 12
- Unit: Box → Quantity: 3
```

#### 2. New Model: `ShopStockByUnit`
Location: `app/Models/ShopStockByUnit.php`

**Key Methods:**
- `getAvailableQuantity($productId, $unitId)` - Check available stock
- `addStock($productId, $unitId, $quantity)` - Increment stock
- `deductStock($productId, $unitId, $quantity)` - Decrement stock
- `hasSufficientStock($productId, $unitId, $quantity)` - Validate availability
- `convertToSalesUnit($productId, $unitId, $quantity)` - Convert to smallest unit
- `getQuantityBreakdown($productId, $quantityInSalesUnit)` - Break down into hierarchy

#### 3. Updated Controllers

**PurchaseRequestNoteController** (PRN Creation - Store → Shop)
- When transferring stock to shop, records the specific unit transferred
- Uses `ShopStockByUnit::addStock()` to track unit-specific inventory

**StockTransferReturnController** (Return - Shop → Store)
- Validates stock availability in the **specific unit** requested
- Deducts from `ShopStockByUnit` for the exact unit
- Converts to sales units for total tracking
- Breaks down quantity when saving to store (e.g., 12 bottles → 1 bundle + 2 bottles)

#### 4. API Endpoint
**Route:** `POST /stock-transfer-returns/available-quantity`

**Request:**
```json
{
  "product_id": 1,
  "measurement_unit_id": 3
}
```

**Response:**
```json
{
  "available_quantity": 12
}
```

#### 5. UI Enhancement
**StockTransferReturnCreateModal.vue**
- Fetches available quantity when product and unit are selected
- Displays below quantity input: "Available: 12 Bottles"
- Updates dynamically when unit changes

## Process Flow

### Stock Transfer (Store → Shop)
1. PRN is created with specific unit (e.g., 5 boxes)
2. Store quantity is deducted
3. Shop quantity is incremented in sales units (total tracking)
4. **NEW:** `ShopStockByUnit` records 5 boxes for that product

### Stock Return (Shop → Store)
1. User selects product and unit (e.g., 12 bottles)
2. **NEW:** System checks `ShopStockByUnit` for available bottles
3. **NEW:** System displays "Available: 12 Bottles"
4. On submit:
   - Deducts 12 from `ShopStockByUnit` (bottles)
   - Converts 12 bottles to sales units for total tracking
   - Deducts from `shop_quantity_in_sales_unit`
   - **NEW:** Breaks down into hierarchy for store:
     - If 1 bundle = 10 bottles
     - Store receives: 1 bundle + 2 bottles
   - Updates `store_quantity_in_purchase_unit` and `store_quantity_in_transfer_unit`

## Example Scenario

**Setup:**
- Product: Coca-Cola
- Hierarchy: 1 Box = 5 Bundles, 1 Bundle = 10 Bottles
- Shop has: 25 bottles (tracked in sales units and by-unit table)

**Transfer:**
- Shop originally received: 2 boxes + 5 bottles
- `ShopStockByUnit` records:
  - Box: 2
  - Bottle: 5

**Return:**
- User wants to return 12 bottles
- System checks: Do we have 12 bottles in `ShopStockByUnit`? ✅ Yes (we have 5 + converted 2 boxes = 25 total)
- **But wait!** User specifically selected "Bottle" unit
- System checks bottle-specific inventory: Only 5 bottles tracked separately
- If shop holds them as bottles, available = 5 ❌
- If shop holds them as boxes, user must return in boxes ✅

**Correct Flow:**
- Shop has 25 bottles in `shop_quantity_in_sales_unit`
- Shop received them as: 2 bundles (20 bottles) + 5 bottles
- `ShopStockByUnit`:
  - Bundle: 2 (= 20 bottles)
  - Bottle: 5
- To return 12 bottles:
  - Option 1: Return 1 bundle (10 bottles) → available ✅
  - Option 2: Return 5 bottles → available ✅
  - Option 3: User needs to return in the unit they have

## Database Migration

**File:** `2026_01_22_000001_create_shop_stock_by_unit_table.php`

**Run:**
```bash
php artisan migrate
```

**Seed existing data:**
```bash
php artisan db:seed --class=ShopStockByUnitSeeder
```

## Testing Checklist

- [ ] Create PRN (Stock Transfer to Shop)
  - [ ] Verify `shop_stock_by_unit` records created
  - [ ] Check correct unit is recorded
  
- [ ] Create Stock Transfer Return
  - [ ] Available quantity displays correctly
  - [ ] Available quantity updates when unit changes
  - [ ] Validation blocks insufficient stock
  - [ ] Stock deducted from correct unit
  - [ ] Store receives properly broken-down quantity
  
- [ ] Update Stock Transfer Return
  - [ ] Old stock restored to correct unit
  - [ ] New stock deducted from correct unit
  
- [ ] Delete Stock Transfer Return
  - [ ] Stock restored to correct unit
  - [ ] Store quantities adjusted correctly

## Impact Analysis

### Modified Files
1. `database/migrations/2026_01_22_000001_create_shop_stock_by_unit_table.php` ✨ NEW
2. `app/Models/ShopStockByUnit.php` ✨ NEW
3. `database/seeders/ShopStockByUnitSeeder.php` ✨ NEW
4. `app/Http/Controllers/PurchaseRequestNoteController.php` 🔧 MODIFIED
5. `app/Http/Controllers/StockTransferReturnController.php` 🔧 MODIFIED
6. `routes/web.php` 🔧 MODIFIED
7. `resources/js/Pages/StockTransferReturns/Components/StockTransferReturnCreateModal.vue` 🔧 MODIFIED

### Unchanged Processes
✅ Sales process
✅ Purchase process
✅ GRN (Goods Received Note)
✅ GRN Returns
✅ Store inventory management
✅ Product movements tracking

## Future Enhancements
- Add unit conversion UI helper
- Bulk return with mixed units
- Return history by unit
- Unit-specific low stock alerts for shop
