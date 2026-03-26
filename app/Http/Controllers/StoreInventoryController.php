<?php

namespace App\Http\Controllers;

use App\Models\StoreInventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StoreInventoryController extends Controller
{
    /**
     * Display a listing of store inventory transactions.
     */
    public function index(Request $request)
    {
        // // Get inventory type (shop or store) - default to shop
         $inventoryType = $request->input('inventory_type', 'shop');

        // Get all products for filter dropdown with their units and quantities
        // Note: shop_quantity_in_purchase_unit is a computed attribute, not a DB column
        $products = Product::with(['purchaseUnit', 'brand', 'category','salesUnit','transferUnit'])
            ->orderBy('name')
            ->get();
        return Inertia::render('StoreInventory/Index', [
            // 'inventoryRecords' => $inventoryRecords,
            'products' => $products,
            'inventoryType' => $inventoryType,
            'filters' => $request->only(['product_id', 'transaction_type', 'date_from', 'date_to', 'status', 'inventory_type']),
        ]);
    }

    /**
     * Display the specified inventory record.
     */
    public function show($id)
    {
        $inventory = StoreInventory::with(['product.purchaseUnit','product.transferUnit','product.salesUnit', 'user'])
            ->findOrFail($id);

        return response()->json($inventory);
    }

    /**
     * Update the specified inventory record.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:500',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $inventory = StoreInventory::findOrFail($id);
        $inventory->update($validated);

        return redirect()->route('store-inventory.index')
            ->with('success', 'Inventory record updated successfully.');
    }

    /**
     * Remove the specified inventory record (soft delete).
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $inventory = StoreInventory::findOrFail($id);
            
            // Reverse the inventory change
            $product = Product::findOrFail($inventory->product_id);
            $newQuantity = $product->store_quantity_in_purchase_unit - $inventory->quantity_change;
            
            if ($newQuantity < 0) {
                return back()->withErrors(['error' => 'Cannot delete: would result in negative inventory.']);
            }

            $product->update([
                'store_quantity_in_purchase_unit' => $newQuantity
            ]);

            $inventory->delete();

            DB::commit();

            return redirect()->route('store-inventory.index')
                ->with('success', 'Inventory record deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete inventory record: ' . $e->getMessage()]);
        }
    }

    /**
     * Get current store quantities for all products.
     */
    public function getCurrentQuantities()
    {
        $products = Product::with(['purchaseUnit', 'brand', 'category'])
            ->where('store_quantity_in_purchase_unit', '>', 0)
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'brand' => $product->brand?->name,
                    'category' => $product->category?->name,
                    'store_quantity' => $product->store_quantity_in_purchase_unit,
                    'purchase_unit' => $product->purchaseUnit?->symbol,
                    'reorder_level' => $product->store_low_stock_margin,
                    'is_low_stock' => $product->store_quantity_in_purchase_unit <= $product->store_low_stock_margin,
                ];
            });

        return response()->json($products);
    }

    /**
     * Log inventory change for GRN Return (Store Inventory)
     * Call this when a GRN return is processed
     */
    public static function logGrnReturn($product_id, $quantity_change, $grn_return_id)
    {
        try {
            $product = Product::findOrFail($product_id);
            $quantityBefore = $product->store_quantity_in_purchase_unit;
            $quantityAfter = $quantityBefore + $quantity_change;

            StoreInventory::create([
                'product_id' => $product_id,
                'user_id' => Auth::id(),
                'reference_no' => StoreInventory::generateReferenceNo(),
                'transaction_type' => 'return',
                'quantity_before' => $quantityBefore,
                'quantity_change' => $quantity_change,
                'quantity_after' => $quantityAfter,
                'measurement_unit' => $product->purchase_unit_id,
                'remarks' => "GRN Return #$grn_return_id",
                'transaction_date' => now()->toDateString(),
                'status' => 'completed',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log GRN return to Store Inventory: ' . $e->getMessage());
        }
    }

    /**
     * Log inventory change for Sales Return (Shop Inventory)
     * Call this when a sales return is processed
     */
    public static function logSalesReturn($product_id, $quantity_change, $sales_return_id)
    {
        try {
            $product = Product::findOrFail($product_id);
            // For shop inventory, we calculate based on sales unit then convert to purchase unit
            $shop_purchase_qty_before = $product->shop_quantity_in_purchase_unit ?? 0;
            
            // Convert quantity change from sales units to purchase units
            $rate = $product->transfer_to_sales_rate ?? 1;
            $quantity_change_purchase = $rate > 0 ? $quantity_change / $rate : 0;
            
            $quantityAfter = $shop_purchase_qty_before + $quantity_change_purchase;

            StoreInventory::create([
                'product_id' => $product_id,
                'user_id' => Auth::id(),
                'reference_no' => StoreInventory::generateReferenceNo(),
                'transaction_type' => 'return',
                'quantity_before' => $shop_purchase_qty_before,
                'quantity_change' => $quantity_change_purchase,
                'quantity_after' => $quantityAfter,
                'measurement_unit' => $product->purchase_unit_id,
                'remarks' => "Sales Return #$sales_return_id",
                'transaction_date' => now()->toDateString(),
                'status' => 'completed',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log Sales return to Shop Inventory: ' . $e->getMessage());
        }
    }

    /**
     * Log inventory change for Sales (Shop Inventory)
     * Call this when a sale is completed
     */
    public static function logSale($product_id, $quantity_sold, $sales_id)
    {
        try {
            $product = Product::findOrFail($product_id);
            $shop_purchase_qty_before = $product->shop_quantity_in_purchase_unit ?? 0;
            
            // Convert quantity sold from sales units to purchase units
            $rate = $product->transfer_to_sales_rate ?? 1;
            $quantity_change_purchase = $rate > 0 ? -($quantity_sold / $rate) : 0; // Negative because it's sold
            
            $quantityAfter = $shop_purchase_qty_before + $quantity_change_purchase;

            StoreInventory::create([
                'product_id' => $product_id,
                'user_id' => Auth::id(),
                'reference_no' => StoreInventory::generateReferenceNo(),
                'transaction_type' => 'transfer_out', // Sold/transferred out
                'quantity_before' => $shop_purchase_qty_before,
                'quantity_change' => $quantity_change_purchase,
                'quantity_after' => $quantityAfter,
                'measurement_unit' => $product->purchase_unit_id,
                'remarks' => "Sale #$sales_id",
                'transaction_date' => now()->toDateString(),
                'status' => 'completed',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log Sale to Shop Inventory: ' . $e->getMessage());
        }
    }
}

