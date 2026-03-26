<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\ProductReleaseNote;
use App\Models\ProductReleaseNoteProduct;
use App\Models\ShopStockByUnit;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\ProductTransferRequest;
use App\Models\User;
use App\Models\CompanyInformation;
use App\Models\ProductMovement;
use App\Models\ProductAvailableQuantity;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\MeasurementUnit;

class PurchaseRequestNoteController extends Controller
{
    public function index()
    {
          $productReleaseNotes  = ProductReleaseNote::with(['product_release_note_products.product', 'product_release_note_products.product.measurement_unit', 'product_release_note_products.unit', 'user', 'product_transfer_request'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        $suppliers = Supplier::where('status', '!=', 0)->get();
        $products = Product::with([
            'purchaseUnit',
            'salesUnit',
            'transferUnit'
        ])->get();
        // Exclude completed PTRs from dropdown
        $productTransferRequests = ProductTransferRequest::with(['product_transfer_request_products.product', 'product_transfer_request_products.product.measurement_unit'])
            ->where('status', '!=', 'completed')
            ->get();
        $users = User::all();
        $measurementUnits = MeasurementUnit::orderBy('name')->get();
               $currencySymbol  = CompanyInformation::first();

    
 
        return Inertia::render('ProductReleaseNotes/Index', [
            'productReleaseNotes' => $productReleaseNotes,
            'suppliers' => $suppliers,
            'availableProducts' => $products,
            'productTransferRequests' => $productTransferRequests,
            'users' => $users,
            'measurementUnits' => $measurementUnits,
            'currencySymbol' => $currencySymbol,
        ]);
    }

 public function store(Request $request)
{
    // Validate request
    $validated = $request->validate([
        'product_transfer_request_id' => 'required|exists:product_transfer_requests,id',
        'user_id' => 'nullable|exists:users,id',
        'release_date' => 'required|date',
        'status' => 'required|in:0,1',
        'remark' => 'nullable|string',

        'products' => 'required|array|min:1',
        'products.*.product_id' => 'nullable|exists:products,id',
        'products.*.quantity' => 'required|numeric|min:0.01',
        'products.*.unit_price' => 'required|numeric|min:0',
        'products.*.total' => 'required|numeric|min:0',
        'products.*.unit_id' => 'nullable|exists:measurement_units,id',
    ]);

    DB::beginTransaction();

    try {
        // Check if PTR is already completed (PRN already generated via auto-approval)
        $existingPtr = ProductTransferRequest::find($validated['product_transfer_request_id']);
        if ($existingPtr && $existingPtr->status === 'completed') {
            return redirect()
                ->back()
                ->withErrors(['error' => 'This PTR has already been processed. A PRN was auto-generated.'])
                ->withInput();
        }

        // Create PRN Header
        $productReleaseNote = ProductReleaseNote::create([
            'product_transfer_request_id' => $validated['product_transfer_request_id'],
            'user_id' => $validated['user_id'] ?? auth()->id(),
            'release_date' => $validated['release_date'],
            'status' => $validated['status'],
            'remark' => $validated['remark'] ?? null,
        ]);

        // Get PTR to check the unit used for each product
        $ptr = ProductTransferRequest::with('product_transfer_request_products')->find($validated['product_transfer_request_id']);

        // Create PRN Products
        foreach ($validated['products'] as $product) {
            // Skip products with null product_id
            if (empty($product['product_id'])) {
                continue;
            }

            ProductReleaseNoteProduct::create([
                'product_release_note_id' => $productReleaseNote->id,
                'product_id' => $product['product_id'],
                'unit_id' => $product['unit_id'] ?? null,
                'quantity' => $product['quantity'],
                'unit_price' => $product['unit_price'],
                'total' => $product['total'],
            ]);

            // Record product movement
            ProductMovement::recordMovement(
                $product['product_id'],
                ProductMovement::TYPE_PURCHASE_RETURN,
                -$product['quantity'],
                'ProductReleaseNote-' . $productReleaseNote->id
            );

            // Deduct quantity from product_available_quantities using FIFO
            $quantityToDeduct = (float)$product['quantity'];
            $availableBatches = ProductAvailableQuantity::where('product_id', $product['product_id'])
                ->orderBy('created_at', 'asc') // FIFO: oldest first
                ->get();

            foreach ($availableBatches as $batch) {
                if ($quantityToDeduct <= 0) break;

                if ($batch->available_quantity >= $quantityToDeduct) {
                    // This batch has enough quantity
                    $batch->decrement('available_quantity', $quantityToDeduct);
                    $quantityToDeduct = 0;
                } else {
                    // Consume entire batch and delete the record
                    $quantityToDeduct -= $batch->available_quantity;
                    $batch->delete(); // Delete batch record when quantity becomes 0
                }
            }

            // Update product stock when PRN is created (actual goods release)
            $productModel = Product::find($product['product_id']);
            
            if ($productModel) {
                $quantity = (float)$product['quantity'];
                $unitId = $product['unit_id'] ?? null;
                
                $purchaseToTransferRate = $productModel->purchase_to_transfer_rate ?? 1;
                $transferToSalesRate = $productModel->transfer_to_sales_rate ?? 1;
                
                // Convert requested quantity to bundles (transfer units)
                $quantityInBundles = $quantity;
                if ($unitId == $productModel->purchase_unit_id) {
                    // Quantity is in boxes -> convert to bundles
                    $quantityInBundles = $quantity * $purchaseToTransferRate;
                } elseif ($unitId == $productModel->sales_unit_id) {
                    // Quantity is in bottles -> convert to bundles
                    $quantityInBundles = $transferToSalesRate > 0 ? $quantity / $transferToSalesRate : 0;
                }
                // If unit_id is transfer_unit_id, quantity is already in bundles
                
                // Get current store quantities using raw DB values
                $dbRecord = DB::table('products')->where('id', $product['product_id'])->first();
                $currentBoxes = (float)($dbRecord->store_quantity_in_purchase_unit ?? 0);
                $currentLooseBundles = (float)($dbRecord->store_quantity_in_transfer_unit ?? 0);
                
                // Total available bundles = (boxes * bundles_per_box) + loose bundles
                $totalAvailableBundles = ($currentBoxes * $purchaseToTransferRate) + $currentLooseBundles;
                
                if ($totalAvailableBundles < $quantityInBundles) {
                    throw new \Exception("Insufficient store quantity for product: {$productModel->name}");
                }
                
                // Deduct bundles, breaking boxes as needed
                $remainingBundles = $totalAvailableBundles - $quantityInBundles;
                
                // Calculate new boxes and loose bundles
                $newBoxes = floor($remainingBundles / $purchaseToTransferRate);
                $newLooseBundles = $remainingBundles - ($newBoxes * $purchaseToTransferRate);
                
                // Update store quantities using direct DB queries
                $purchaseDifference = $currentBoxes - $newBoxes;
                $looseDifference = $currentLooseBundles - $newLooseBundles;
                
                if ($purchaseDifference != 0) {
                    if ($purchaseDifference > 0) {
                        DB::table('products')
                            ->where('id', $product['product_id'])
                            ->decrement('store_quantity_in_purchase_unit', $purchaseDifference);
                    } else {
                        DB::table('products')
                            ->where('id', $product['product_id'])
                            ->increment('store_quantity_in_purchase_unit', abs($purchaseDifference));
                    }
                }
                
                if ($looseDifference != 0) {
                    if ($looseDifference > 0) {
                        DB::table('products')
                            ->where('id', $product['product_id'])
                            ->decrement('store_quantity_in_transfer_unit', $looseDifference);
                    } else {
                        DB::table('products')
                            ->where('id', $product['product_id'])
                            ->increment('store_quantity_in_transfer_unit', abs($looseDifference));
                    }
                }
                
                // Convert to sales units for shop and update
                $quantityInSalesUnits = $quantityInBundles * $transferToSalesRate;
                DB::table('products')
                    ->where('id', $product['product_id'])
                    ->increment('shop_quantity_in_sales_unit', $quantityInSalesUnits);
                
                // Track shop stock by specific unit for accurate returns
                // Record the quantity in the unit that was actually transferred
                $transferredUnitId = $product['unit_id'] ?? $productModel->transfer_unit_id;
                $quantityInTransferredUnit = $quantity; // Original quantity in the requested unit
                
                ShopStockByUnit::addStock(
                    $product['product_id'],
                    $transferredUnitId,
                    $quantityInTransferredUnit
                );
            }
        }

        // Update the related PTR status to 'completed'
        $productTransferRequest = ProductTransferRequest::find($validated['product_transfer_request_id']);
        if ($productTransferRequest) {
            $productTransferRequest->update(['status' => 'completed']);
        }

        DB::commit();

        return redirect()
            ->route('product-release-notes.index')
            ->with('success', 'PRN created successfully!');

    } catch (\Throwable $e) {
        DB::rollBack();

        return redirect()
            ->back()
            ->withErrors(['error' => 'Failed to create PRN: ' . $e->getMessage()])
            ->withInput();
    }
}

    
    public function update(Request $request, ProductReleaseNote $productReleaseNote)
    {
        $validated = $request->validate([
            'product_transfer_request_id'   => 'required|exists:product_transfer_requests,id',
            'user_id'                       => 'nullable|exists:users,id',
            'release_date'                  => 'required|date',
            'status'                        => 'required|in:0,1',
            'remark'                        => 'nullable|string',

            'products'                      => 'required|array|min:1',
            'products.*.product_id'         => 'required|exists:products,id',
            'products.*.quantity'           => 'required|numeric|min:0.01',
            'products.*.unit_price'         => 'required|numeric|min:0',
            'products.*.total'              => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $productReleaseNote->update([
                'product_transfer_request_id' => $validated['product_transfer_request_id'],
                'user_id'       => $validated['user_id'] ?? auth()->id(),
                'release_date'  => $validated['release_date'],
                'status'        => $validated['status'],
                'remark'        => $validated['remark'] ?? null,
            ]);

            // Get old products before deletion to reverse movements
            $oldProducts = ProductReleaseNoteProduct::where('product_release_note_id', $productReleaseNote->id)->get();
            
            // Reverse old product movements
            foreach ($oldProducts as $oldProduct) {
                ProductMovement::recordMovement(
                    $oldProduct->product_id,
                    ProductMovement::TYPE_PURCHASE_RETURN,
                    $oldProduct->quantity, // Positive to reverse the negative
                    'ProductReleaseNote-' . $productReleaseNote->id . '-REVERSED'
                );
                // reverse stock adjustments made when original PRN was created
                $productModel = Product::find($oldProduct->product_id);
                if ($productModel) {
                    $quantity = is_numeric($oldProduct->quantity) ? (float)$oldProduct->quantity : floatval($oldProduct->quantity);
                    $unitId = $oldProduct->unit_id ?? null;
                    $purchaseToTransfer = is_numeric($productModel->purchase_to_transfer_rate) && $productModel->purchase_to_transfer_rate > 0 ? (float)$productModel->purchase_to_transfer_rate : 1.0;
                    $transferToSales = is_numeric($productModel->transfer_to_sales_rate) && $productModel->transfer_to_sales_rate > 0 ? (float)$productModel->transfer_to_sales_rate : 1.0;
                    
                    // Convert to bundles based on unit
                    $quantityInBundles = $quantity;
                    if ($unitId == $productModel->purchase_unit_id) {
                        $quantityInBundles = $quantity * $purchaseToTransfer;
                    } elseif ($unitId == $productModel->sales_unit_id) {
                        $quantityInBundles = $transferToSales > 0 ? $quantity / $transferToSales : 0;
                    }
                    
                    $quantityInSalesUnits = $quantityInBundles * $transferToSales;
                    
                    // Read current values from DB
                    $dbRecord = DB::table('products')->where('id', $oldProduct->product_id)->first();
                    $currentBoxes = (float)($dbRecord->store_quantity_in_purchase_unit ?? 0);
                    $currentLooseBundles = (float)($dbRecord->store_quantity_in_transfer_unit ?? 0);
                    
                    // Restore the bundles to store
                    $totalBundles = ($currentBoxes * $purchaseToTransfer) + $currentLooseBundles + $quantityInBundles;
                    $newBoxes = floor($totalBundles / $purchaseToTransfer);
                    $newLooseBundles = $totalBundles - ($newBoxes * $purchaseToTransfer);
                    
                    $boxesDifference = $newBoxes - $currentBoxes;
                    $looseDifference = $newLooseBundles - $currentLooseBundles;
                    
                    if ($boxesDifference != 0) {
                        if ($boxesDifference > 0) {
                            DB::table('products')
                                ->where('id', $oldProduct->product_id)
                                ->increment('store_quantity_in_purchase_unit', $boxesDifference);
                        } else {
                            DB::table('products')
                                ->where('id', $oldProduct->product_id)
                                ->decrement('store_quantity_in_purchase_unit', abs($boxesDifference));
                        }
                    }
                    
                    if ($looseDifference != 0) {
                        if ($looseDifference > 0) {
                            DB::table('products')
                                ->where('id', $oldProduct->product_id)
                                ->increment('store_quantity_in_transfer_unit', $looseDifference);
                        } else {
                            DB::table('products')
                                ->where('id', $oldProduct->product_id)
                                ->decrement('store_quantity_in_transfer_unit', abs($looseDifference));
                        }
                    }
                    
                    // Remove from shop
                    DB::table('products')
                        ->where('id', $oldProduct->product_id)
                        ->decrement('shop_quantity_in_sales_unit', $quantityInSalesUnits);
                }
            }

            ProductReleaseNoteProduct::where('product_release_note_id', $productReleaseNote->id)->delete();
            foreach ($validated['products'] as $productData) {
                ProductReleaseNoteProduct::create([
                    'product_release_note_id'     => $productReleaseNote->id,
                    'product_id' => $productData['product_id'],
                    'quantity'   => $productData['quantity'],
                    'unit_price' => $productData['unit_price'],
                    'total'      => $productData['total'],
                    'unit_id'    => $productData['unit_id'] ?? null,
                ]);

                // Record new product movement
                ProductMovement::recordMovement(
                    $productData['product_id'],
                    ProductMovement::TYPE_PURCHASE_RETURN,
                    -$productData['quantity'], // Negative for stock reduction
                    'PRN-' . $productReleaseNote->id
                );
                // Update product stock values for new entries
                $productModel = Product::find($productData['product_id']);
                if ($productModel) {
                    $quantity = is_numeric($productData['quantity']) ? (float)$productData['quantity'] : floatval($productData['quantity']);
                    $unitId = $productData['unit_id'] ?? null;
                    $purchaseToTransfer = is_numeric($productModel->purchase_to_transfer_rate) && $productModel->purchase_to_transfer_rate > 0 ? (float)$productModel->purchase_to_transfer_rate : 1.0;
                    $transferToSales = is_numeric($productModel->transfer_to_sales_rate) && $productModel->transfer_to_sales_rate > 0 ? (float)$productModel->transfer_to_sales_rate : 1.0;
                    
                    // Convert to bundles based on unit
                    $quantityInBundles = $quantity;
                    if ($unitId == $productModel->purchase_unit_id) {
                        $quantityInBundles = $quantity * $purchaseToTransfer;
                    } elseif ($unitId == $productModel->sales_unit_id) {
                        $quantityInBundles = $transferToSales > 0 ? $quantity / $transferToSales : 0;
                    }
                    
                    $quantityInSalesUnits = $quantityInBundles * $transferToSales;
                    
                    // Read current values from DB
                    $dbRecord = DB::table('products')->where('id', $productData['product_id'])->first();
                    $currentBoxes = (float)($dbRecord->store_quantity_in_purchase_unit ?? 0);
                    $currentLooseBundles = (float)($dbRecord->store_quantity_in_transfer_unit ?? 0);
                    
                    // Deduct from store
                    $totalBundles = ($currentBoxes * $purchaseToTransfer) + $currentLooseBundles - $quantityInBundles;
                    $newBoxes = floor($totalBundles / $purchaseToTransfer);
                    $newLooseBundles = $totalBundles - ($newBoxes * $purchaseToTransfer);
                    
                    $boxesDifference = $currentBoxes - $newBoxes;
                    $looseDifference = $currentLooseBundles - $newLooseBundles;
                    
                    if ($boxesDifference != 0) {
                        if ($boxesDifference > 0) {
                            DB::table('products')
                                ->where('id', $productData['product_id'])
                                ->decrement('store_quantity_in_purchase_unit', $boxesDifference);
                        } else {
                            DB::table('products')
                                ->where('id', $productData['product_id'])
                                ->increment('store_quantity_in_purchase_unit', abs($boxesDifference));
                        }
                    }
                    
                    if ($looseDifference != 0) {
                        if ($looseDifference > 0) {
                            DB::table('products')
                                ->where('id', $productData['product_id'])
                                ->decrement('store_quantity_in_transfer_unit', $looseDifference);
                        } else {
                            DB::table('products')
                                ->where('id', $productData['product_id'])
                                ->increment('store_quantity_in_transfer_unit', abs($looseDifference));
                        }
                    }
                    
                    // Add to shop
                    DB::table('products')
                        ->where('id', $productData['product_id'])
                        ->increment('shop_quantity_in_sales_unit', $quantityInSalesUnits);
                }
            }

            DB::commit();

            return redirect()->route('product-release-notes.index')->with('success', 'PRN updated successfully');

        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to update PRN: ' . $e->getMessage()])
                ->withInput();
        }
    }

    

    public function destroy(ProductReleaseNote $productReleaseNote)
    {
        DB::beginTransaction();

        try {
            // Before deleting, restore stock values for related products
            $existing = ProductReleaseNoteProduct::where('product_release_note_id', $productReleaseNote->id)->get();
            foreach ($existing as $ex) {
                $product = Product::find($ex->product_id);
                if ($product) {
                    $quantity = is_numeric($ex->quantity) ? (float)$ex->quantity : floatval($ex->quantity);
                    $unitId = $ex->unit_id ?? null;
                    $purchaseToTransfer = is_numeric($product->purchase_to_transfer_rate) && $product->purchase_to_transfer_rate > 0 ? (float)$product->purchase_to_transfer_rate : 1.0;
                    $transferToSales = is_numeric($product->transfer_to_sales_rate) && $product->transfer_to_sales_rate > 0 ? (float)$product->transfer_to_sales_rate : 1.0;
                    
                    // Convert to bundles based on unit
                    $quantityInBundles = $quantity;
                    if ($unitId == $product->purchase_unit_id) {
                        $quantityInBundles = $quantity * $purchaseToTransfer;
                    } elseif ($unitId == $product->sales_unit_id) {
                        $quantityInBundles = $transferToSales > 0 ? $quantity / $transferToSales : 0;
                    }
                    
                    $quantityInSalesUnits = $quantityInBundles * $transferToSales;
                    
                    // Read current values from DB
                    $dbRecord = DB::table('products')->where('id', $ex->product_id)->first();
                    $currentBoxes = (float)($dbRecord->store_quantity_in_purchase_unit ?? 0);
                    $currentLooseBundles = (float)($dbRecord->store_quantity_in_transfer_unit ?? 0);
                    
                    // Restore: increment store quantity, decrement shop quantity
                    $totalBundles = ($currentBoxes * $purchaseToTransfer) + $currentLooseBundles + $quantityInBundles;
                    $newBoxes = floor($totalBundles / $purchaseToTransfer);
                    $newLooseBundles = $totalBundles - ($newBoxes * $purchaseToTransfer);
                    
                    $boxesDifference = $newBoxes - $currentBoxes;
                    $looseDifference = $newLooseBundles - $currentLooseBundles;
                    
                    if ($boxesDifference != 0) {
                        if ($boxesDifference > 0) {
                            DB::table('products')
                                ->where('id', $ex->product_id)
                                ->increment('store_quantity_in_purchase_unit', $boxesDifference);
                        } else {
                            DB::table('products')
                                ->where('id', $ex->product_id)
                                ->decrement('store_quantity_in_purchase_unit', abs($boxesDifference));
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
                    
                    // Remove from shop
                    DB::table('products')
                        ->where('id', $ex->product_id)
                        ->decrement('shop_quantity_in_sales_unit', $quantityInSalesUnits);
                }
            }
            // Delete related products
            ProductReleaseNoteProduct::where('product_release_note_id', $productReleaseNote->id)->delete();
            
            // Delete the PRN
            $productReleaseNote->delete();

            DB::commit();

            return redirect()->back()->with('success', 'PRN deleted successfully');

        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to delete PRN: ' . $e->getMessage()]);
        }
    }
}
