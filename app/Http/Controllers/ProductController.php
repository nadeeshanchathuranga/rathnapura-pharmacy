<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Type;
use App\Models\MeasurementUnit;
use App\Models\CompanyInformation;
use App\Models\Discount;
use App\Models\Tax;
use App\Models\ActivityLog;
use App\Models\ProductAvailableQuantity;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\Division;
use App\Models\Supplier;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Generate unique barcode
     */
    private function generateBarcode()
    {
        do {
            // Generate 13 digit barcode (EAN-13 format)
            $barcode = '2' . str_pad(rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
        } while (Product::where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $productQuery = Product::select([
            'id',
            'name',
            'barcode',
            'brand_id',
            'category_id',
            'type_id',
            'discount_id',
            'tax_id',
            'purchase_price',
            'wholesale_price',
            'retail_price',
            'shop_quantity_in_sales_unit',
            'shop_low_stock_margin',
            'store_quantity_in_purchase_unit',
            'store_quantity_in_transfer_unit as loose_bundles',
            'store_quantity_in_sale_unit',
            'store_low_stock_margin',
            'purchase_unit_id',
            'sales_unit_id',
            'transfer_unit_id',
            'purchase_to_transfer_rate',
            'transfer_to_sales_rate',
            'return_product',
            'status',
            'image',
            'division_id',
            'created_at',
            'updated_at'
        ])->with([
            'brand',
            'category',
            'type',
            'discount',
            'tax',
            'purchaseUnit',
            'salesUnit',
            'transferUnit',
            'shopStockByUnit.measurementUnit',
            'division'
        ]);

        // Cashiers (role=2) only see their division's products
        if ($user->role === 2 && $user->division_id) {
            $productQuery->where('division_id', $user->division_id);
        } elseif ($request->filled('division_filter') && in_array($user->role, [0, 1])) {
            // Admin/Backoffice may filter by division
            $productQuery->where('division_id', $request->division_filter);
        }

        $products = $productQuery->orderBy('id', 'desc')->paginate(10);

        // Load available quantities for each product from product_available_quantities table in FIFO order
        $availableQuantities = ProductAvailableQuantity::select('product_id', 'batch_number', 'available_quantity', 'unit_id', 'created_at')
            ->orderBy('created_at', 'asc') // FIFO: oldest batches first
            ->get()
            ->groupBy('product_id');

        // Add current batch (oldest) and total to each product
        $products->getCollection()->transform(function ($product) use ($availableQuantities) {
            $batches = $availableQuantities->get($product->id, collect());
            $product->available_quantities = $batches;
            
            // Get only the current batch (oldest/first one being consumed)
            $product->current_batch = $batches->first();
            
            // Calculate total store quantity from product_available_quantities
            $product->store_quantity_from_batches = $batches->sum('available_quantity');
            return $product;
        });

        $currencySymbol = CompanyInformation::first();
        $divisions = Division::active()->get(['id', 'name', 'slug']);

        // Data needed by the edit modal
        $brands    = Brand::where('status', '!=', 0)->orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $types     = Type::where('status', '!=', 0)->orderBy('name')->get(['id', 'name']);
        $units     = MeasurementUnit::where('status', '!=', 0)->orderBy('name')->get(['id', 'name', 'symbol']);
        $discounts = Discount::where('status', '!=', 0)->orderBy('name')->get(['id', 'name', 'value', 'type']);
        $taxes     = Tax::where('status', '!=', 0)->orderBy('name')->get(['id', 'name', 'percentage']);
        $suppliers = Supplier::where('status', '!=', 0)->orderBy('name')->get(['id', 'name']);
        $customers = Customer::where('status', '!=', 0)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Products/Index', [
            'products'      => $products,
            'currencySymbol'=> $currencySymbol,
            'divisions'     => $divisions,
            'divisionFilter'=> $request->division_filter,
            'brands'        => $brands,
            'categories'    => $categories,
            'types'         => $types,
            'measurementUnits' => $units,
            'discounts'     => $discounts,
            'taxes'         => $taxes,
            'suppliers'     => $suppliers,
            'customers'     => $customers,
        ]);
    }

    /**
     * Update product info (no stock fields).
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'barcode'                  => 'nullable|string|max:100|unique:products,barcode,' . $product->id,
            'brand_id'                 => 'nullable|exists:brands,id',
            'category_id'              => 'nullable|exists:categories,id',
            'type_id'                  => 'nullable|exists:types,id',
            'discount_id'              => 'nullable|exists:discounts,id',
            'tax_id'                   => 'nullable|exists:taxes,id',
            'purchase_price'           => 'nullable|numeric|min:0',
            'wholesale_price'          => 'nullable|numeric|min:0',
            'retail_price'             => 'nullable|numeric|min:0',
            'return_product'           => 'nullable|boolean',
            'purchase_unit_id'         => 'nullable|exists:measurement_units,id',
            'sales_unit_id'            => 'nullable|exists:measurement_units,id',
            'transfer_unit_id'         => 'nullable|exists:measurement_units,id',
            'purchase_to_transfer_rate'=> 'nullable|numeric|min:0',
            'transfer_to_sales_rate'   => 'nullable|numeric|min:0',
            'store_low_stock_margin'   => 'nullable|numeric|min:0',
            'shop_low_stock_margin'    => 'nullable|numeric|min:0',
            'status'                   => 'nullable|integer|in:0,1',
            'division_id'              => 'nullable|exists:divisions,id',
            'image'                    => 'nullable|image|max:2048',
        ]);

        // Remove purchase_price and stock-affecting fields - they should not be updated via this endpoint
        unset($validated['purchase_price'], $validated['shop_quantity_in_sales_unit'], $validated['store_quantity_in_purchase_unit']);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        } else {
            unset($validated['image']);
        }

        $product->update($validated);

        return back()->with('success', 'Product updated successfully.');
    }

    /**
     * Log activity to activity_logs table
     */
    public function logActivity(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string',
            'module' => 'required|string',
            'details' => 'required',
        ]);

        // Accept details as string or array
        $details = $validated['details'];
        if (is_array($details) || is_object($details)) {
            $details = json_encode($details);
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $validated['action'],
            'module' => $validated['module'],
            'details' => $details,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get purchase price using FIFO (First In First Out) method
     * Returns the per-piece unit cost from the oldest GRN that has available quantity for the product
     * 
     * @param int $productId - The product ID
     * @return float|null - The FIFO unit cost price or null if no stock available
     */
    public function getFifoPurchasePrice($productId)
    {
        $grnProduct = GoodsReceivedNoteProduct::with(['grn', 'product:id,purchase_to_transfer_rate,transfer_to_sales_rate'])
            ->where('product_id', $productId)
            ->whereHas('grn', function ($query) {
                $query->where('status', '!=', 0); // Only active GRNs
            })
            ->orderBy('created_at', 'asc') // Oldest first
            ->first();

        if (!$grnProduct || !$grnProduct->product) {
            return null;
        }

        return $this->calculateUnitCostPrice($grnProduct, $grnProduct->product);
    }

    /**
     * Get purchase price by batch number
     * Returns the per-piece unit cost for a specific product-batch combination
     * 
     * @param int $productId - The product ID
     * @param string $batchNumber - The batch number (e.g., BATCH-20260120-5432)
     * @return float|null - The unit cost price for that batch or null if not found
     */
    public function getPurchasePriceByBatch($productId, $batchNumber)
    {
        $grnProduct = GoodsReceivedNoteProduct::with(['grn', 'product:id,purchase_to_transfer_rate,transfer_to_sales_rate'])
            ->where('product_id', $productId)
            ->where('batch_number', $batchNumber)
            ->whereHas('grn', function ($query) {
                $query->where('status', '!=', 0); // Only active GRNs
            })
            ->first();

        if (!$grnProduct || !$grnProduct->product) {
            return null;
        }

        return $this->calculateUnitCostPrice($grnProduct, $grnProduct->product);
    }

    /**
     * Calculate per-piece unit cost from a GRN line using:
     * unit_cost = total_cost / total_units
     */
    private function calculateUnitCostPrice(GoodsReceivedNoteProduct $grnProduct, Product $product): ?float
    {
        $quantity = (float) ($grnProduct->quantity ?? 0);
        if ($quantity <= 0) {
            return null;
        }

        $purchaseToTransferRate = (float) ($product->purchase_to_transfer_rate ?? 1);
        $transferToSalesRate = (float) ($product->transfer_to_sales_rate ?? 1);
        $purchaseToTransferRate = $purchaseToTransferRate > 0 ? $purchaseToTransferRate : 1.0;
        $transferToSalesRate = $transferToSalesRate > 0 ? $transferToSalesRate : 1.0;

        $totalUnits = $quantity * $purchaseToTransferRate * $transferToSalesRate;
        if ($totalUnits <= 0) {
            return null;
        }

        $lineTotal = is_numeric($grnProduct->total) ? (float) $grnProduct->total : 0.0;
        if ($lineTotal <= 0) {
            $lineTotal = ((float) ($grnProduct->purchase_price ?? 0)) * $quantity;
        }

        return round($lineTotal / $totalUnits, 2);
    }

    /**
     * API endpoint to get pricing info by batch number
     * Used by frontend to auto-populate purchase price field based on selected batch
     */
    public function getPricingInfoByBatch(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_number' => 'required|string',
        ]);

        $purchasePrice = $this->getPurchasePriceByBatch($validated['product_id'], $validated['batch_number']);

        if (!$purchasePrice) {
            return response()->json([
                'success' => false,
                'message' => 'No unit cost price found for this product-batch combination',
                'purchase_price' => null,
                'unit_cost_price' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'purchase_price' => $purchasePrice,
            'unit_cost_price' => $purchasePrice,
            'batch_number' => $validated['batch_number'],
            'source' => 'Batch Tracking',
            'message' => 'Unit cost price fetched from batch record',
        ]);
    }

    /**
     * API endpoint to get FIFO purchase price for a product
     * Used by frontend to auto-populate purchase price field
     */
    public function getFifoPricingInfo($productId)
    {
        $fifoPurchasePrice = $this->getFifoPurchasePrice($productId);

        return response()->json([
            'purchase_price' => $fifoPurchasePrice,
            'unit_cost_price' => $fifoPurchasePrice,
            'source' => 'FIFO',
            'message' => $fifoPurchasePrice ? 'Unit cost price fetched from oldest GRN' : 'No stock available in GRN',
        ]);
    }
}
