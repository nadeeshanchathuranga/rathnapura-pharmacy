<?php

namespace App\Http\Controllers;

use App\Models\StockTransferReturn;
use App\Models\StockTransferReturnProduct;
use App\Models\Product;
use App\Models\User;
use App\Models\ProductMovement;
use App\Models\MeasurementUnit;
use App\Models\ShopStockByUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockTransferReturnController extends Controller
{
    public function index()
    {
        $stockTransferReturns = StockTransferReturn::with(['user', 'products.product.measurement_unit', 'products.measurementUnit'])
            ->latest()
            ->paginate(10);

        $products = Product::with([
                'purchaseUnit', 
                'salesUnit', 
                'transferUnit',
                'shopStockByUnit.measurementUnit'
            ])
            ->where('status', '!=', 0)
            ->get()
            ->map(function ($product) {
                // Get only units that exist in shop stock with quantity > 0
                $shopUnits = $product->shopStockByUnit
                    ->where('quantity', '>', 0)
                    ->map(function($stock) {
                        return [
                            'id' => $stock->measurement_unit_id,
                            'name' => $stock->measurementUnit->name ?? '',
                            'symbol' => $stock->measurementUnit->symbol ?? '',
                            'available_quantity' => $stock->quantity
                        ];
                    })
                    ->values();
                
                // If no shop stock units, fall back to all product units (for backward compatibility)
                if ($shopUnits->isEmpty()) {
                    $units = collect([
                        $product->purchaseUnit,
                        $product->salesUnit,
                        $product->transferUnit
                    ])->filter()->unique('id')->values();
                    
                    $product->measurement_units = $units;
                } else {
                    $product->measurement_units = $shopUnits;
                }
                
                return $product;
            });

        $measurementUnits = MeasurementUnit::where('status', '!=', 0)->get();
        $users = User::all();
        $returnNo = $this->generateReturnNumber();

        return Inertia::render('StockTransferReturns/Index', [
            'stockTransferReturns' => $stockTransferReturns,
            'products' => $products,
            'measurementUnits' => $measurementUnits,
            'users' => $users,
            'returnNo' => $returnNo
        ]);
    }

    /**
     * Get available quantity for a specific product and unit in shop
     */
    public function getAvailableQuantity(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'measurement_unit_id' => 'required|exists:measurement_units,id',
        ]);


        $quantity = ShopStockByUnit::getAvailableQuantity(
            $validated['product_id'],
            $validated['measurement_unit_id']
        );

        return response()->json([
            'available_quantity' => $quantity
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'return_no' => 'required|string|unique:stock_transfer_returns,return_no',
            'return_date' => 'required|date',
            'reason' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.measurement_unit_id' => 'required|exists:measurement_units,id',
            'products.*.stock_transfer_quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $user_id = auth()->id();

            // Validate stock for all products first - check shop_quantity_in_sales_unit in products table
            foreach ($validated['products'] as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $requestedQty = $productData['stock_transfer_quantity'];
                // For simplicity, always check shop_quantity_in_sales_unit
                if ($product->shop_quantity_in_sales_unit < $requestedQty) {
                    DB::rollBack();
                    return back()->withErrors([
                        'products' => "Insufficient stock for {$product->name}. Available: {$product->shop_quantity_in_sales_unit} units"
                    ]);
                }
            }

            // Create header record
            $stockTransferReturn = StockTransferReturn::create([
                'return_no' => $validated['return_no'],
                'user_id' => $user_id,
                'reason' => $validated['reason'] ?? null,
                'return_date' => $validated['return_date'],
                'status' => 'completed',
            ]);

            // Create detail records and update stock
// Create detail records and update stock
foreach ($validated['products'] as $productData) {
    // Create product line item
    StockTransferReturnProduct::create([
        'stock_transfer_return_id' => $stockTransferReturn->id,
        'product_id' => $productData['product_id'],
        'measurement_unit_id' => $productData['measurement_unit_id'],
        'stock_transfer_quantity' => $productData['stock_transfer_quantity'],
    ]);

    $product = Product::findOrFail($productData['product_id']);
    $unitId = $productData['measurement_unit_id'];
    $returnQty = $productData['stock_transfer_quantity'];

    // Get conversion rates
    $salesPerBundle = $product->transfer_to_sales_rate ?? 1; // bottles per bundle
    $bundlesPerBox = $product->purchase_to_transfer_rate ?? 1; // bundles per box
    $salesPerBox = $salesPerBundle * $bundlesPerBox; // bottles per box

    // Convert return quantity to bottles based on selected unit
    $returnInBottles = 0;
    if ($unitId == $product->sales_unit_id) {
        // Return in bottles
        $returnInBottles = $returnQty;
    } elseif ($unitId == $product->transfer_unit_id) {
        // Return in bundles
        $returnInBottles = $returnQty * $salesPerBundle;
    } elseif ($unitId == $product->purchase_unit_id) {
        // Return in boxes
        $returnInBottles = $returnQty * $salesPerBox;
    }

    // 1. Deduct from shop (in bottles)
    $product->decrement('shop_quantity_in_sales_unit', $returnInBottles);

    // 2. Add to store with proper conversion
    // Calculate how many boxes and loose bundles to add
    $totalBottlesToAdd = $returnInBottles;
    $boxesToAdd = floor($totalBottlesToAdd / $salesPerBox);
    $remainingAfterBox = $totalBottlesToAdd % $salesPerBox;
    $bundlesToAdd = floor($remainingAfterBox / $salesPerBundle);
    $bottlesToAdd = $remainingAfterBox % $salesPerBundle;
    
    // Note: We only track boxes and bundles in store, not individual bottles
    if ($boxesToAdd > 0) {
        $product->increment('store_quantity_in_purchase_unit', $boxesToAdd);
    }
    if ($bundlesToAdd > 0) {
        $product->increment('store_quantity_in_transfer_unit', $bundlesToAdd);
    }
    if ($bottlesToAdd > 0) {
        $product->increment('store_quantity_in_sale_unit', $bottlesToAdd);
    }
        // If there are loose bottles, we can either ignore or handle them as needed    
    // Any remaining bottles (less than a bundle) would stay in shop as loose bottles
    // If you want to track them in store as loose bottles, you'd need another field

    // Record movement
    ProductMovement::record(
        $productData['product_id'],
        ProductMovement::TYPE_STOCK_TRANSFER_RETURN,
        $returnInBottles,
        'StockTransferReturn-' . $stockTransferReturn->id
    );
}

            DB::commit();

            return redirect()->route('stock-transfer-returns.index')
                ->with('success', 'Stock Transfer Return created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create return: ' . $e->getMessage()]);
        }
    }

    public function destroy(StockTransferReturn $stockTransferReturn)
    {
        DB::beginTransaction();

        try {
            // Reverse stock movement for all products
            foreach ($stockTransferReturn->products as $returnProduct) {
                $product = Product::findOrFail($returnProduct->product_id);
                $unitId = $returnProduct->measurement_unit_id;
                $quantityInSpecificUnit = $returnProduct->stock_transfer_quantity;
                
                // Add back to shop stock by specific unit
                ShopStockByUnit::addStock(
                    $returnProduct->product_id,
                    $unitId,
                    $quantityInSpecificUnit
                );
                
                // Convert to sales units for total tracking
                $quantityInSalesUnits = ShopStockByUnit::convertToSalesUnit(
                    $returnProduct->product_id,
                    $unitId,
                    $quantityInSpecificUnit
                );
                
                // Reverse: increment shop total
                $product->increment('shop_quantity_in_sales_unit', $quantityInSalesUnits);
                
                // Calculate how much to deduct from store
                $purchaseToTransferRate = $product->purchase_to_transfer_rate ?? 1;
                $transferToSalesRate = $product->transfer_to_sales_rate ?? 1;
                
                $quantityInBundles = $transferToSalesRate > 0 ? $quantityInSalesUnits / $transferToSalesRate : 0;
                $purchaseUnitsToDeduct = floor($quantityInBundles / $purchaseToTransferRate);
                $looseBundlesToDeduct = $quantityInBundles - ($purchaseUnitsToDeduct * $purchaseToTransferRate);
                
                // Deduct from store
                $product->decrement('store_quantity_in_purchase_unit', $purchaseUnitsToDeduct);
                $product->decrement('store_quantity_in_transfer_unit', $looseBundlesToDeduct);
            }

            // Delete will cascade to products table
            $stockTransferReturn->delete();

            DB::commit();

            return redirect()->route('stock-transfer-returns.index')
                ->with('success', 'Stock Transfer Return deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete return: ' . $e->getMessage()]);
        }
    }

    private function generateReturnNumber()
    {
        $prefix = 'STR';
        $date = date('Ymd');
        $lastReturn = StockTransferReturn::whereDate('created_at', today())
            ->latest()
            ->first();

        $sequence = $lastReturn ? (int)substr($lastReturn->return_no, -4) + 1 : 1;

        return $prefix . '-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function updateStatus(Request $request, StockTransferReturn $stockTransferReturn)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,completed',
        ]);

        $stockTransferReturn->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Status updated successfully');
    }

    public function update(Request $request, StockTransferReturn $stockTransferReturn)
    {
        $validated = $request->validate([
            'return_date' => 'required|date',
            'reason' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.measurement_unit_id' => 'required|exists:measurement_units,id',
            'products.*.stock_transfer_quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            // Reverse old stock movements
            foreach ($stockTransferReturn->products as $oldProduct) {
                $unitId = $oldProduct->measurement_unit_id;
                $quantity = $oldProduct->stock_transfer_quantity;
                
                // Add back to shop by specific unit
                ShopStockByUnit::addStock($oldProduct->product_id, $unitId, $quantity);
                
                // Convert and update totals
                $quantityInSalesUnits = ShopStockByUnit::convertToSalesUnit(
                    $oldProduct->product_id,
                    $unitId,
                    $quantity
                );
                
                $product = Product::findOrFail($oldProduct->product_id);
                $product->increment('shop_quantity_in_sales_unit', $quantityInSalesUnits);
                
                // Deduct from store
                $purchaseToTransferRate = $product->purchase_to_transfer_rate ?? 1;
                $transferToSalesRate = $product->transfer_to_sales_rate ?? 1;
                $quantityInBundles = $transferToSalesRate > 0 ? $quantityInSalesUnits / $transferToSalesRate : 0;
                $purchaseUnits = floor($quantityInBundles / $purchaseToTransferRate);
                $looseBundles = $quantityInBundles - ($purchaseUnits * $purchaseToTransferRate);
                
                $product->decrement('store_quantity_in_purchase_unit', $purchaseUnits);
                $product->decrement('store_quantity_in_transfer_unit', $looseBundles);
            }

            // Delete old products
            $stockTransferReturn->products()->delete();

            // Validate new products stock by specific unit
            foreach ($validated['products'] as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $unitId = $productData['measurement_unit_id'];
                $requestedQty = $productData['stock_transfer_quantity'];
                
                if (!ShopStockByUnit::hasSufficientStock($productData['product_id'], $unitId, $requestedQty)) {
                    $available = ShopStockByUnit::getAvailableQuantity($productData['product_id'], $unitId);
                    $unit = MeasurementUnit::find($unitId);
                    $unitName = $unit ? $unit->name : 'units';
                    
                    DB::rollBack();
                    return back()->withErrors([
                        'products' => "Insufficient stock for {$product->name}. Available: {$available} {$unitName}"
                    ]);
                }
            }

            // Update header
            $stockTransferReturn->update([
                'return_date' => $validated['return_date'],
                'reason' => $validated['reason'] ?? null,
            ]);

            // Create new products and update stock
            foreach ($validated['products'] as $productData) {
                StockTransferReturnProduct::create([
                    'stock_transfer_return_id' => $stockTransferReturn->id,
                    'product_id' => $productData['product_id'],
                    'measurement_unit_id' => $productData['measurement_unit_id'],
                    'stock_transfer_quantity' => $productData['stock_transfer_quantity'],
                ]);

                // Deduct from specific unit
                $unitId = $productData['measurement_unit_id'];
                $quantityInSpecificUnit = $productData['stock_transfer_quantity'];
                
                ShopStockByUnit::deductStock(
                    $productData['product_id'],
                    $unitId,
                    $quantityInSpecificUnit
                );
                
                // Convert and update totals
                $quantityInSalesUnits = ShopStockByUnit::convertToSalesUnit(
                    $productData['product_id'],
                    $unitId,
                    $quantityInSpecificUnit
                );
                
                $product = Product::findOrFail($productData['product_id']);
                $product->decrement('shop_quantity_in_sales_unit', $quantityInSalesUnits);
                
                // Add to store with breakdown
                $purchaseToTransferRate = $product->purchase_to_transfer_rate ?? 1;
                $transferToSalesRate = $product->transfer_to_sales_rate ?? 1;
                $quantityInBundles = $transferToSalesRate > 0 ? $quantityInSalesUnits / $transferToSalesRate : 0;
                $newPurchaseUnits = floor($quantityInBundles / $purchaseToTransferRate);
                $newLooseBundles = $quantityInBundles - ($newPurchaseUnits * $purchaseToTransferRate);
                
                $product->increment('store_quantity_in_purchase_unit', $newPurchaseUnits);
                $product->increment('store_quantity_in_transfer_unit', $newLooseBundles);

                ProductMovement::record(
                    $productData['product_id'],
                    ProductMovement::TYPE_STOCK_TRANSFER_RETURN,
                    $quantityInSalesUnits,
                    'StockTransferReturn-' . $stockTransferReturn->id
                );
            }

            DB::commit();

            return redirect()->route('stock-transfer-returns.index')
                ->with('success', 'Stock Transfer Return updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update return: ' . $e->getMessage()]);
        }
    }
}
