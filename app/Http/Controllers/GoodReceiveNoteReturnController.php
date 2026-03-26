<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GoodsReceivedNoteReturn;
use App\Models\GoodsReceivedNoteReturnProduct;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\Product;
use App\Models\ProductMovement;
use App\Models\ProductAvailableQuantity;
use App\Models\MeasurementUnit;
use App\Models\CompanyInformation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * GoodReceiveNoteReturnController
 * 
 * Manages the return of goods to suppliers (BRN - Bill Return Note).
 * Handles the complete lifecycle of GRN returns including:
 * - Recording returned items with quantities and remarks
 * - Inventory adjustment (decrementing stock when goods are returned)
 * - Product movement tracking for audit trail
 * - Unit conversion for proper stock accounting
 * 
 * Business Logic:
 * - Returns reference an original GRN (Goods Received Note)
 * - Stock is decremented when returns are created
 * - Stock is restored when returns are deleted
 * - All operations are wrapped in database transactions
 * 
 * @package App\Http\Controllers
 */
class GoodReceiveNoteReturnController extends Controller
{
    /**
     * Display a listing of all GRN returns
     * 
     * Provides paginated list of returns with:
     * - User who processed the return
     * - Original GRN reference and its products
     * - Returned products with quantities
     * - Available GRNs for creating new returns (only active GRNs)
     * 
     * @return \Inertia\Response
     */
    public function index()
{
    // Eager-load all necessary relationships
    $returns = GoodsReceivedNoteReturn::with([
        'user',
        'goodsReceivedNote.goods_received_note_products.product.measurement_unit',
        'goodsReceivedNoteReturnProducts.product.measurement_unit'
    ])->latest()->paginate(20);
    
    // Eager-load GRN products for autofill on selection
    // Only show active GRNs (status != 0)
    $goodsReceivedNotes = GoodsReceivedNote::with([
        'goods_received_note_products.product.measurement_unit'
    ])
        ->where('status', '!=', 0)
        ->orderByDesc('id')
        ->get()
        ->toArray();
    
    // Get authenticated user for default assignment
    $user = auth()->user();
    
    $currencySymbol = CompanyInformation::first();
    
    // Load available products and measurement units
    // Only active products (status != 0) can be returned
    $availableProducts = Product::where('status', '!=', 0)
        ->with([
            'measurement_unit',
            'purchaseUnit',
            'transferUnit',
            'salesUnit'
        ])
        ->orderBy('name')
        ->get();
    
    // Get all measurement units
    $measurementUnits = MeasurementUnit::orderBy('name')->get()->toArray();
  

    return Inertia::render('GoodsReceivedNoteReturns/Index', [
        'returns' => $returns,
        'goodsReceivedNotes' => $goodsReceivedNotes,
        // Alias for frontend prop name `grns`
        'grns' => $goodsReceivedNotes,
        'user' => $user,
        'availableProducts' => $availableProducts,
        'measurementUnits' => $measurementUnits,
        'currencySymbol' => $currencySymbol,
    ]);
}
    /**
     * Show the form for creating a new GRN return
     * 
     * Provides necessary data for return creation:
     * - Available GRNs with their products (for autofill)
     * - All products (for manual selection)
     * - Measurement units (for display)
     * - Current user (for default assignment)
     * 
     * @return \Inertia\Response
     */
    public function create()
    {
        // Include goods_received_note_products so frontend can autofill products without extra routes
        // Serialize to plain array for predictable client-side shape
        // Only show active GRNs (status != 0)
        $goodsReceivedNotes = GoodsReceivedNote::with([
            'goods_received_note_products.product.measurement_unit',
            'goods_received_note_products.product.purchaseUnit',
            'goods_received_note_products.product.transferUnit',
            'goods_received_note_products.product.salesUnit'
        ])
            ->where('status', '!=', 0)
            ->orderByDesc('id')
            ->get()
            ->toArray();
        
        // Load all products for manual product selection with unit relationships
        $products = Product::with([
            'purchaseUnit',
            'transferUnit',
            'salesUnit'
        ])->orderBy('name')->get();
        
        // Load measurement units for display purposes
        $measurementUnits = MeasurementUnit::orderBy('name')->get()->toArray();
        
        // Get authenticated user for default assignment
        $user = auth()->user();
        return Inertia::render('goodsReceivedNoteReturns/Create',[ 
        'goodsReceivedNotes' => $goodsReceivedNotes,
        'products' => $products,
        'measurementUnits' => $measurementUnits,
        'user' => $user,
        ]);
    }

    /**
     * Load GRN details with available quantities from product_available_quantities table
     * 
     * API endpoint that returns:
     * - GRN header information
     * - Products received with unit relationships
     * - Available quantities from product_available_quantities table (via SUM aggregation)
     * 
     * @param int $id - The GRN ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function goodsReceivedNoteReturnDetails($id)
    {
        try {
            // Load the Goods Received Note with relationships
            $goodsReceivedNote = GoodsReceivedNote::with(['supplier', 'user'])
                ->findOrFail($id);

            // Get products from goods_received_note_products table with all unit relationships
            $goodsReceivedNoteProducts = GoodsReceivedNoteProduct::where('goods_received_note_id', $id)
                ->with([
                    'product',
                    'product.purchaseUnit',
                    'product.transferUnit',
                    'product.salesUnit'
                ])
                ->get()
                ->map(function($grnProduct) {
                    $product = $grnProduct->product;
                    
                    // Get available quantities from product_available_quantities table
                    // Filter by the specific GRN to get only quantities from this GRN
                    $availableQty = ProductAvailableQuantity::where('product_id', $product->id)
                        ->where('goods_received_note_id', $grnProduct->goods_received_note_id)
                        ->select(
                            DB::raw('COALESCE(SUM(available_quantity), 0) as total_purchase_units'),
                            DB::raw('COALESCE(SUM(quantity_in_transfer_unit), 0) as total_transfer_units'),
                            DB::raw('COALESCE(SUM(quantity_in_sales_unit), 0) as total_sales_units')
                        )
                        ->first();
                    
                    return [
                        'product_id' => $grnProduct->product_id,
                        'name' => $product->name ?? 'N/A',
                        'batch_number' => $grnProduct->batch_number ?? 'N/A',
                        'quantity' => $grnProduct->quantity ?? 0,
                        'requested_quantity' => $grnProduct->requested_quantity ?? 0,
                        'purchase_price' => (float)($grnProduct->purchase_price ?? 0),
                        'discount' => (float)($grnProduct->discount ?? 0),
                        'total' => (float)($grnProduct->total ?? 0),
                        'purchase_unit' => $product->purchaseUnit,
                        'transfer_unit' => $product->transferUnit,
                        'sales_unit' => $product->salesUnit,
                        'purchase_to_transfer_rate' => $product->purchase_to_transfer_rate ?? 1,
                        'transfer_to_sales_rate' => $product->transfer_to_sales_rate ?? 1,
                        // Available quantities from product_available_quantities table
                        'available_quantity_in_purchase_unit' => (float)($availableQty->total_purchase_units ?? 0),
                        'available_quantity_in_transfer_unit' => (float)($availableQty->total_transfer_units ?? 0),
                        'available_quantity_in_sales_unit' => (float)($availableQty->total_sales_units ?? 0),
                    ];
                });

            return response()->json([
                'goodsReceivedNote' => $goodsReceivedNote,
                'goodsReceivedNoteProducts' => $goodsReceivedNoteProducts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load GRN details',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Store a newly created GRN return
     * 
     * Process flow:
     * 1. Validates return data (GRN reference, products, quantities)
     * 2. Creates GRN return record
     * 3. Creates return product line items
     * 4. Records product movements for audit trail (Type 5: GRN_RETURN)
     * 5. Adjusts inventory:
     *    - Increments store_quantity (returns go back to store)
     *    - Applies unit conversion rates (purchase → transfer → sales)
     * 
     * All operations wrapped in transaction for data consistency.
     * 
     * @param Request $request - Contains goods_received_note_id, date, user_id, and products array
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate incoming data
        $validated = $request->validate([
            'goods_received_note_id' => 'required|exists:goods_received_notes,id',
            'date' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.qty' => 'required|numeric|min:1',
            'products.*.unit_id' => 'required|exists:measurement_units,id',
            'products.*.remarks' => 'nullable|string',
        ]);

        // Start database transaction to ensure data consistency
        DB::beginTransaction();
        try {
            // Create the main GRN return record
            $grnReturn = GoodsReceivedNoteReturn::create([
                'goods_received_note_id' => $validated['goods_received_note_id'],
                'date' => $validated['date'],
                'user_id' => $validated['user_id'],
            ]);

            // Track total return amount for GRN subtotal update
            $totalReturnAmount = 0;

            // Process each returned product
            foreach ($validated['products'] as $p) {
                // Clamp the return quantity to the available quantity in the GRN product
                $grnProduct = GoodsReceivedNoteProduct::where('goods_received_note_id', $validated['goods_received_note_id'])
                    ->where('product_id', $p['product_id'])
                    ->first();
                $currentQty = $grnProduct ? $grnProduct->quantity : 0;
                $clampedReturnQty = min($currentQty, $p['qty']);
                
                // Create return product line item with clamped quantity
                GoodsReceivedNoteReturnProduct::create([
                    'goods_received_note_return_id' => $grnReturn->id,
                    'product_id' => $p['product_id'],
                    'quantity' => $clampedReturnQty,
                    'measurement_unit_id' => $p['unit_id'],
                    'remarks' => $p['remarks'] ?? null,
                ]);
                
                // Record product movement for GRN return (Type 5: TYPE_GRN_RETURN)
                // This creates an audit trail for inventory tracking
                if ($clampedReturnQty > 0) {
                    ProductMovement::record($p['product_id'], ProductMovement::TYPE_GRN_RETURN, $clampedReturnQty, 'GRN Return #' . $grnReturn->id);
                    
                    if ($grnProduct) {
                        // Deduct returned quantity from the original GRN product quantity
                        $grnProduct->decrement('quantity', $clampedReturnQty);
                        
                        // Get purchase price from GRN product
                        $purchasePrice = (float)($grnProduct->purchase_price ?? 0);
                        
                        // Update store inventory based on unit type returned (use clamped quantity for all calculations)
                        $returnedQty = (float)$clampedReturnQty;
                        $selectedUnitId = (int)$p['unit_id'];
                        
                        // Get product with fresh data from DB for unit conversion rates
                        $prod = Product::find($p['product_id']);
                        if ($prod) {
                            $purchaseUnitId = (int)$prod->purchase_unit_id;
                            $transferUnitId = (int)$prod->transfer_unit_id;
                            $salesUnitId = (int)$prod->sales_unit_id;
                            
                            $purchaseToTransferRate = (float)$prod->purchase_to_transfer_rate ?: 1.0;
                            $transferToSalesRate = (float)$prod->transfer_to_sales_rate ?: 1.0;
                            
                            // Calculate return amount based on unit type
                            $returnAmount = 0;
                            if ($selectedUnitId == $purchaseUnitId) {
                                // Returned in purchase units
                                $returnAmount = $returnedQty * $purchasePrice;
                            } elseif ($selectedUnitId == $transferUnitId) {
                                // Returned in transfer units - convert to purchase unit price
                                $transferPrice = $purchasePrice / $purchaseToTransferRate;
                                $returnAmount = $returnedQty * $transferPrice;
                            } elseif ($selectedUnitId == $salesUnitId) {
                                // Returned in sales units - convert to purchase unit price
                                $salesPrice = $purchasePrice / ($purchaseToTransferRate * $transferToSalesRate);
                                $returnAmount = $returnedQty * $salesPrice;
                            }
                            
                            // Add to total return amount
                            $totalReturnAmount += $returnAmount;
                            
                            // Update the total in goods_received_note_products
                            $grnProduct->decrement('total', $returnAmount);
                            
                            // Read raw database values directly to avoid accessor calculations
                            $dbRecord = DB::table('products')->where('id', $p['product_id'])->first();
                            $currentPurchaseQty = (float)($dbRecord->store_quantity_in_purchase_unit ?? 0);
                            $currentLooseQty = (float)($dbRecord->store_quantity_in_transfer_unit ?? 0);
                            
                            if ($selectedUnitId == $purchaseUnitId) {
                                // Returned in purchase units - decrement directly
                                DB::table('products')
                                    ->where('id', $p['product_id'])
                                    ->decrement('store_quantity_in_purchase_unit', $returnedQty);
                                
                                // Update store_quantity_in_sale_unit (always 0 for purchase unit returns)
                                DB::table('products')
                                    ->where('id', $p['product_id'])
                                    ->update(['store_quantity_in_sale_unit' => 0]);

                            } elseif ($selectedUnitId == $transferUnitId) {
                                // Returned in transfer units - convert and redistribute
                                // Calculate total transfer units (bundles)
                                $totalTransferUnits = ($currentPurchaseQty * $purchaseToTransferRate) + $currentLooseQty;
                                
                                // Subtract returned quantity to get new total
                                $newTotalTransferUnits = $totalTransferUnits - $returnedQty;
                                
                                // Re-distribute into purchase units and loose bundles
                                $newPurchaseQty = floor($newTotalTransferUnits / $purchaseToTransferRate);
                                $newLooseQty = $newTotalTransferUnits - ($newPurchaseQty * $purchaseToTransferRate);  // Keep remaining bundles (not just full units)
                                
                                // Update using raw DB queries
                                $purchaseDifference = $currentPurchaseQty - $newPurchaseQty;
                                $looseDifference = $currentLooseQty - $newLooseQty;
                                
                                if ($purchaseDifference != 0) {
                                    if ($purchaseDifference > 0) {
                                        DB::table('products')
                                            ->where('id', $p['product_id'])
                                            ->decrement('store_quantity_in_purchase_unit', $purchaseDifference);
                                    } else {
                                        DB::table('products')
                                            ->where('id', $p['product_id'])
                                            ->increment('store_quantity_in_purchase_unit', abs($purchaseDifference));
                                    }
                                }
                                
                                if ($looseDifference != 0) {
                                    if ($looseDifference > 0) {
                                        DB::table('products')
                                            ->where('id', $p['product_id'])
                                            ->decrement('store_quantity_in_transfer_unit', $looseDifference);
                                    } else {
                                        DB::table('products')
                                            ->where('id', $p['product_id'])
                                            ->increment('store_quantity_in_transfer_unit', abs($looseDifference));
                                    }
                                }
                                
                                // ✓ Update store_quantity_in_sale_unit - store only loose bottles from fractional bundles
                                // Extract only the fractional part of loose bundles (e.g., 0.8 from 4.8)
                                // $fractionalBundles = fmod($newTotalTransferUnits, $purchaseToTransferRate);
                                // // Convert fractional bundles to bottles and floor to get only full bottles
                                // $looseBottlesOnly = floor($fractionalBundles * $transferToSalesRate);
                                // DB::table('products')
                                //     ->where('id', $p['product_id'])
                                //     ->update(['store_quantity_in_sale_unit' => round($looseBottlesOnly, 2)]);
                            } 
                            elseif ($selectedUnitId == $salesUnitId) {
                                // Returned in sales units - convert to transfer units first, then redistribute
                                $returnedInTransferUnits = $returnedQty / $transferToSalesRate;
                                
                                // Use the already-read values from above
                                // Calculate total transfer units
                                $totalTransferUnits = ($currentPurchaseQty * $purchaseToTransferRate) + $currentLooseQty;
                                
                                // Subtract returned quantity (converted to transfer units)
                                $newTotalTransferUnits = $totalTransferUnits - $returnedInTransferUnits;
                                $newTotalSalesUnits = $newTotalTransferUnits * $transferToSalesRate;
                                
                                // Re-distribute into purchase units and loose bundles
                                $newPurchaseQty = floor($newTotalTransferUnits / $purchaseToTransferRate);
                                $newLooseQty = $newTotalTransferUnits - ($newPurchaseQty * $purchaseToTransferRate);  // Keep remaining bundles
                                
                                // Update using raw DB queries
                                $purchaseDifference = $currentPurchaseQty - $newPurchaseQty;
                                $looseDifference = $currentLooseQty - $newLooseQty;
                                 
                                if ($purchaseDifference != 0) {
                                    if ($purchaseDifference > 0) {
                                        DB::table('products')
                                            ->where('id', $p['product_id'])
                                            ->decrement('store_quantity_in_purchase_unit', $purchaseDifference);
                                    } else {
                                        DB::table('products')
                                            ->where('id', $p['product_id'])
                                            ->increment('store_quantity_in_purchase_unit', abs($purchaseDifference));
                                    }
                                }
                                
                                if ($looseDifference != 0) {
                                    if ($looseDifference > 0) {
                                        DB::table('products')
                                            ->where('id', $p['product_id'])
                                            ->decrement('store_quantity_in_transfer_unit', $looseDifference);
                                    } else {
                                        DB::table('products')
                                            ->where('id', $p['product_id'])
                                            ->increment('store_quantity_in_transfer_unit', abs($looseDifference));
                                    }
                                }
                                
                                // ✓ Update store_quantity_in_sale_unit - store only loose bottles from fractional bundles
                                // Extract only the fractional part of loose bundles (e.g., 0.8 from 4.8)
                                $looseBottlesOnly = fmod($newTotalSalesUnits, $transferToSalesRate);
                                // Convert fractional bundles to bottles and floor to get only full bottles
                                // $looseBottlesOnly = floor($looseBottlesOnly * $transferToSalesRate);
                               

                                DB::table('products')
                                    ->where('id', $p['product_id'])
                                    ->update(['store_quantity_in_sale_unit' => round($looseBottlesOnly, 2)]);
                            }
                        }
                    }
                }

                // ========== DEDUCT FROM product_available_quantities TABLE ==========
                // Use FIFO approach to deduct the returned quantity from product_available_quantities
                // This keeps the batch-level inventory in sync with the aggregate product table
                
                $prod = Product::find($p['product_id']);
                if ($prod) {
                    $purchaseUnitId = (int)$prod->purchase_unit_id;
                    $transferUnitId = (int)$prod->transfer_unit_id;
                    $salesUnitId = (int)$prod->sales_unit_id;
                    $purchaseToTransferRate = (float)$prod->purchase_to_transfer_rate ?: 1.0;
                    $transferToSalesRate = (float)$prod->transfer_to_sales_rate ?: 1.0;
                    
                    // Step 1: Convert the returned quantity to bundles (transfer units)
                    $quantityInBundles = 0;
                    $quantityInSalesUnits = 0;
                    
                    if ($selectedUnitId == $purchaseUnitId) {
                        // Returned in purchase units - convert to bundles
                        $quantityInBundles = $clampedReturnQty * $purchaseToTransferRate;
                        $quantityInSalesUnits = 0;
                    } elseif ($selectedUnitId == $transferUnitId) {
                        // Returned in transfer units - already in bundles
                        $quantityInBundles = $clampedReturnQty;
                        $quantityInSalesUnits = 0;
                    } elseif ($selectedUnitId == $salesUnitId) {
                        // Returned in sales units - convert to bundles and sales units
                        $quantityInBundles = floor($clampedReturnQty / $transferToSalesRate);
                        $quantityInSalesUnits = $clampedReturnQty % $transferToSalesRate;
                    }
                    
                    // Step 2: Split bundles into boxes (purchase units) + remainder bundles
                    $boxesToDeduct = floor($quantityInBundles / $purchaseToTransferRate);
                    $bundlesToDeduct = $quantityInBundles % $purchaseToTransferRate;
                    
                    // Step 3: FIFO deduction from product_available_quantities table
                    // Fetch batches ordered by created_at (oldest first)
                    $batches = ProductAvailableQuantity::where('product_id', $p['product_id'])
                        ->orderBy('created_at', 'asc')
                        ->get();
                    
                    foreach ($batches as $batch) {
                        if ($boxesToDeduct <= 0 && $bundlesToDeduct <= 0 && $quantityInSalesUnits <= 0) {
                            break; // All quantities deducted
                        }
                        
                        // Step 3a: Deduct boxes from available_quantity
                        if ($boxesToDeduct > 0) {
                            $deductedBoxes = min($boxesToDeduct, $batch->available_quantity ?? 0);
                            $batch->available_quantity -= $deductedBoxes;
                            $boxesToDeduct -= $deductedBoxes;
                        }
                        
                        // Step 3b: Deduct bundles from quantity_in_transfer_unit
                        // If not enough bundles, convert boxes to bundles
                        if ($bundlesToDeduct > 0) {
                            // First, try to deduct from existing bundles
                            $deductedBundles = min($bundlesToDeduct, $batch->quantity_in_transfer_unit ?? 0);
                            $batch->quantity_in_transfer_unit -= $deductedBundles;
                            $bundlesToDeduct -= $deductedBundles;
                            
                            // If still need more bundles, convert boxes to bundles
                            if ($bundlesToDeduct > 0 && ($batch->available_quantity ?? 0) > 0) {
                                // How many boxes do we need to convert to get enough bundles?
                                $boxesNeeded = ceil($bundlesToDeduct / $purchaseToTransferRate);
                                $boxesAvailable = $batch->available_quantity ?? 0;
                                
                                if ($boxesAvailable >= $boxesNeeded) {
                                    // Convert boxes to bundles
                                    $convertedBundles = $boxesNeeded * $purchaseToTransferRate;
                                    $deductedFromConverted = min($bundlesToDeduct, $convertedBundles);
                                    
                                    // Update: deduct boxes, add remaining bundles
                                    $batch->available_quantity -= $boxesNeeded;
                                    $batch->quantity_in_transfer_unit += ($convertedBundles - $deductedFromConverted);
                                    $bundlesToDeduct -= $deductedFromConverted;
                                }
                            }
                        }
                        
                        // Step 3c: Deduct sales units from quantity_in_sales_unit
                        // If not enough sales units, convert bundles or boxes to sales units
                        if ($quantityInSalesUnits > 0) {
                            // First, try to deduct from existing sales units
                            $deductedSalesUnits = min($quantityInSalesUnits, $batch->quantity_in_sales_unit ?? 0);
                            $batch->quantity_in_sales_unit -= $deductedSalesUnits;
                            $quantityInSalesUnits -= $deductedSalesUnits;
                            
                            // If still need more sales units, convert bundles to sales units
                            if ($quantityInSalesUnits > 0 && ($batch->quantity_in_transfer_unit ?? 0) > 0) {
                                // How many bundles do we need to convert to get enough sales units?
                                $bundlesNeeded = ceil($quantityInSalesUnits / $transferToSalesRate);
                                $bundlesAvailable = $batch->quantity_in_transfer_unit ?? 0;
                                
                                if ($bundlesAvailable >= $bundlesNeeded) {
                                    // Convert bundles to sales units
                                    $convertedSalesUnits = $bundlesNeeded * $transferToSalesRate;
                                    $deductedFromConverted = min($quantityInSalesUnits, $convertedSalesUnits);
                                    
                                    // Update: deduct bundles, add remaining sales units
                                    $batch->quantity_in_transfer_unit -= $bundlesNeeded;
                                    $batch->quantity_in_sales_unit += ($convertedSalesUnits - $deductedFromConverted);
                                    $quantityInSalesUnits -= $deductedFromConverted;
                                }
                            }
                            
                            // If still need more sales units, convert boxes to bundles then to sales units
                            if ($quantityInSalesUnits > 0 && ($batch->available_quantity ?? 0) > 0) {
                                // How many sales units can we get from available boxes?
                                $salesUnitsPerBox = $purchaseToTransferRate * $transferToSalesRate;
                                $boxesNeeded = ceil($quantityInSalesUnits / $salesUnitsPerBox);
                                $boxesAvailable = $batch->available_quantity ?? 0;
                                
                                if ($boxesAvailable >= $boxesNeeded) {
                                    // Convert boxes to bundles then to sales units
                                    $convertedSalesUnits = $boxesNeeded * $salesUnitsPerBox;
                                    $deductedFromConverted = min($quantityInSalesUnits, $convertedSalesUnits);
                                    
                                    // Update: deduct boxes, convert remainder to bundles and sales units
                                    $batch->available_quantity -= $boxesNeeded;
                                    $remainingSalesUnits = $convertedSalesUnits - $deductedFromConverted;
                                    $remainingBundles = floor($remainingSalesUnits / $transferToSalesRate);
                                    $remainingSalesOnly = $remainingSalesUnits % $transferToSalesRate;
                                    
                                    $batch->quantity_in_transfer_unit += $remainingBundles;
                                    $batch->quantity_in_sales_unit += $remainingSalesOnly;
                                    $quantityInSalesUnits -= $deductedFromConverted;
                                }
                            }
                        }
                        
                        // Delete batch if all quantities are exhausted
                        if (($batch->available_quantity ?? 0) <= 0 && 
                            ($batch->quantity_in_transfer_unit ?? 0) <= 0 && 
                            ($batch->quantity_in_sales_unit ?? 0) <= 0) {
                            $batch->delete();
                        } else {
                            $batch->save();
                        }
                    }
                }
            }

            if ($totalReturnAmount > 0) {
                GoodsReceivedNote::where('id', $validated['goods_received_note_id'])
                    ->decrement('subtotal', $totalReturnAmount);
            }

            // Log to Store Inventory for each product returned
            foreach ($validated['products'] as $p) {
                if (!empty($p['product_id']) && !empty($p['qty'])) {
                    StoreInventoryController::logGrnReturn($p['product_id'], (float)$p['qty'], $grnReturn->id);
                }
            }

            DB::commit();
            return redirect()->route('good-receive-note-returns.index')->with('success', 'GRN return recorded.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified GRN return from storage
     * 
     * Deletion process:
     * 1. Restores stock levels (increments store_quantity)
     * 2. Removes product movement records (audit trail cleanup)
     * 3. Deletes return product line items
     * 4. Deletes the return record
     * 
     * Note: Stock restoration uses the same unit conversion logic
     * to ensure accurate inventory levels.
     * 
     * @param GoodsReceivedNoteReturn $grnReturn - The return to delete (route model binding)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(GoodsReceivedNoteReturn $grnReturn)
    {
        // Start database transaction for atomicity
        DB::beginTransaction();
        try {
            // Get return products before deletion for stock restoration
            $returnProducts = GoodsReceivedNoteReturnProduct::where('goods_received_note_return_id', $grnReturn->id)->get();
            
            // Track total return amount for GRN subtotal restoration
            $totalReturnAmount = 0;
            
            // Restore stock for related products and remove related product movements
            $existing = GoodsReceivedNoteReturnProduct::where('goods_received_note_return_id', $grnReturn->id)->get();
            // Restore stock for each returned product
            foreach ($existing as $ex) {
                // Restore the quantity in the original GRN product record
                $grnProduct = GoodsReceivedNoteProduct::where('goods_received_note_id', $grnReturn->goods_received_note_id)
                    ->where('product_id', $ex->product_id)
                    ->first();
                
                if ($grnProduct) {
                    $grnProduct->increment('quantity', $ex->quantity);
                    
                    $returnedQty = is_numeric($ex->quantity) ? (float)$ex->quantity : floatval($ex->quantity);
                    $selectedUnitId = (int)$ex->measurement_unit_id;
                    
                    // Get product with fresh data from DB
                    $prod = Product::find($ex->product_id);
                    if ($prod) {
                        // Get purchase price from GRN product
                        $purchasePrice = (float)($grnProduct->purchase_price ?? 0);
                        
                        $purchaseUnitId = (int)$prod->purchase_unit_id;
                        $transferUnitId = (int)$prod->transfer_unit_id;
                        $salesUnitId = (int)$prod->sales_unit_id;
                        
                        $purchaseToTransferRate = (float)$prod->purchase_to_transfer_rate ?: 1.0;
                        $transferToSalesRate = (float)$prod->transfer_to_sales_rate ?: 1.0;
                        
                        // Calculate return amount based on unit type
                        $returnAmount = 0;
                        if ($selectedUnitId == $purchaseUnitId) {
                            // Returned in purchase units
                            $returnAmount = $returnedQty * $purchasePrice;
                        } elseif ($selectedUnitId == $transferUnitId) {
                            // Returned in transfer units - convert to purchase unit price
                            $transferPrice = $purchasePrice / $purchaseToTransferRate;
                            $returnAmount = $returnedQty * $transferPrice;
                        } elseif ($selectedUnitId == $salesUnitId) {
                            // Returned in sales units - convert to purchase unit price
                            $salesPrice = $purchasePrice / ($purchaseToTransferRate * $transferToSalesRate);
                            $returnAmount = $returnedQty * $salesPrice;
                        }
                        
                        // Add to total return amount
                        $totalReturnAmount += $returnAmount;
                        
                        // Restore the total in goods_received_note_products
                        $grnProduct->increment('total', $returnAmount);
                        
                        // Read raw database values directly
                        $dbRecord = DB::table('products')->where('id', $ex->product_id)->first();
                        $currentPurchaseQty = (float)($dbRecord->store_quantity_in_purchase_unit ?? 0);
                        $currentLooseQty = (float)($dbRecord->store_quantity_in_transfer_unit ?? 0);
                        
                        if ($selectedUnitId == $purchaseUnitId) {
                            // Restored in purchase units - increment directly
                            DB::table('products')
                                ->where('id', $ex->product_id)
                                ->increment('store_quantity_in_purchase_unit', $returnedQty);
                        } elseif ($selectedUnitId == $transferUnitId) {
                            // Restored in transfer units - convert and redistribute
                            
                            // Calculate total transfer units
                            $totalTransferUnits = ($currentPurchaseQty * $purchaseToTransferRate) + $currentLooseQty;
                            
                            // Add returned quantity back
                            $newTotalTransferUnits = $totalTransferUnits + $returnedQty;
                            
                            // Re-distribute into purchase units and loose bundles
                            $newPurchaseQty = floor($newTotalTransferUnits / $purchaseToTransferRate);
                            $newLooseQty = $newTotalTransferUnits % $purchaseToTransferRate;
                            
                            // Update using raw DB queries
                            $purchaseDifference = $newPurchaseQty - $currentPurchaseQty;
                            $looseDifference = $newLooseQty - $currentLooseQty;
                            
                            if ($purchaseDifference != 0) {
                                if ($purchaseDifference > 0) {
                                    DB::table('products')
                                        ->where('id', $ex->product_id)
                                        ->increment('store_quantity_in_purchase_unit', $purchaseDifference);
                                } else {
                                    DB::table('products')
                                        ->where('id', $ex->product_id)
                                        ->decrement('store_quantity_in_purchase_unit', abs($purchaseDifference));
                                }
                            }
                            
                            if ($looseDifference != 0) {
                                if ($looseDifference > 0) {
                                    DB::table('products')
                                        ->where('id', $ex->product_id)
                                        ->increment('store_quantity_in_transfer_unit', $looseDifference);
                                } else {
                                    DB::table('products')
                                        ->where('id', $ex->product_id)
                                        ->decrement('store_quantity_in_transfer_unit', abs($looseDifference));
                                }
                            }
                        } elseif ($selectedUnitId == $salesUnitId) {
                            // Restored in sales units - convert to transfer units first, then redistribute
                            $returnedInTransferUnits = $returnedQty / $transferToSalesRate;
                            
                            // Use the already-read values from above
                            // Calculate total transfer units
                            $totalTransferUnits = ($currentPurchaseQty * $purchaseToTransferRate) + $currentLooseQty;
                            
                            // Add returned quantity back (converted to transfer units)
                            $newTotalTransferUnits = $totalTransferUnits + $returnedInTransferUnits;
                            
                            // Re-distribute into purchase units and loose bundles
                            $newPurchaseQty = floor($newTotalTransferUnits / $purchaseToTransferRate);
                            $newLooseQty = $newTotalTransferUnits % $purchaseToTransferRate;
                            
                            // Update using raw DB queries
                            $purchaseDifference = $newPurchaseQty - $currentPurchaseQty;
                            $looseDifference = $newLooseQty - $currentLooseQty;
                            
                            if ($purchaseDifference != 0) {
                                if ($purchaseDifference > 0) {
                                    DB::table('products')
                                        ->where('id', $ex->product_id)
                                        ->increment('store_quantity_in_purchase_unit', $purchaseDifference);
                                } else {
                                    DB::table('products')
                                        ->where('id', $ex->product_id)
                                        ->decrement('store_quantity_in_purchase_unit', abs($purchaseDifference));
                                }
                            }
                            
                            if ($looseDifference != 0) {
                                if ($looseDifference > 0) {
                                    DB::table('products')
                                        ->where('id', $ex->product_id)
                                        ->increment('store_quantity_in_transfer_unit', $looseDifference);
                                } else {
                                    DB::table('products')
                                        ->where('id', $ex->product_id)
                                        ->decrement('store_quantity_in_transfer_unit', abs($looseDifference));
                                }
                            }
                        }
                    }
                }
            }

            // Delete previous product movement records tied to this GRN return (by reference)
            // This cleans up the audit trail for this return
            ProductMovement::where('reference', 'GRN Return #' . $grnReturn->id)->delete();

            // Restore GRN subtotal by adding back the total return amount
            if ($totalReturnAmount > 0) {
                GoodsReceivedNote::where('id', $grnReturn->goods_received_note_id)
                    ->increment('subtotal', $totalReturnAmount);
            }

            // Delete related product line items
            GoodsReceivedNoteReturnProduct::where('goods_received_note_return_id', $grnReturn->id)->delete();

            // Delete the main return record
            $grnReturn->delete();

            // Commit transaction - all operations succeeded
            DB::commit();
            return redirect()->route('grn-returns.index')->with('success', 'GRN return deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed deleting GRN return: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
