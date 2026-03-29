<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\CompanyInformation;
use App\Models\ProductMovement;
use App\Models\MeasurementUnit;
use App\Models\ProductAvailableQuantity;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * GoodReceiveNoteController
 * 
 * Manages the receipt of goods from suppliers (GRN - Goods Received Note).
 * Handles the complete procurement workflow including:
 * - Recording received goods with quantities and pricing
 * - Inventory adjustment (incrementing stock upon receipt)
 * - Product movement tracking for audit trail
 * 
 * Business Logic:
 * - GRNs reference suppliers and process selected products directly
 * - Stock is incremented when goods are received (by issued_quantity)
 * - GRN numbers are auto-generated with format: GRN-YYYYMMDD-XXXX
 * - All operations wrapped in database transactions for consistency
 * 
 * @package App\Http\Controllers
 */
class GoodReceiveNoteController extends Controller
{
    private function roundToWhole(float $value): int
    {
        return (int) round($value, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Display a listing of all Goods Received Notes
     * 
     * Provides paginated list of GRNs with:
     * - Associated products with quantities and pricing
     * - Product measurement units
     * - Supplier information
    * - Available products for direct GRN entry
     * - Auto-generated GRN number for new entries
     * 
     * @return \Inertia\Response
     */
    public function index()
    {
        // Eager-load related data to optimize performance
        $goodsReceivedNotes = GoodsReceivedNote::with(['goods_received_note_products.product', 'goods_received_note_products.product.measurement_unit', 'supplier'])
            ->paginate(10);
        
        // Load active suppliers for the dropdown
        $suppliers = Supplier::where('status', '!=', 0)->get();

        // Load active products for item selection
        $products = Product::where('status', '!=', 0)
            ->with(['measurement_unit', 'purchaseUnit'])
            ->get();
        
         $currencySymbol  = CompanyInformation::first();
        
        // Load measurement units for display purposes
        $measurementUnits = MeasurementUnit::orderBy('name')->get();
        return Inertia::render('GoodsReceivedNotes/Index', [
            'goodsReceivedNotes' => $goodsReceivedNotes,
            'measurementUnits' => $measurementUnits,
            'suppliers' => $suppliers,
            'availableProducts' => $products,
            'grnNumber' => $this->generateGoodReceiveNoteNumber(),
            'currencySymbol' => $currencySymbol,
        ]);
    }


    /**
     * Store a newly created Goods Received Note
     * 
     * Process flow:
     * 1. Validates GRN data (unique GRN number, supplier, products, pricing)
     * 2. Creates GRN record with metadata
     * 3. For each received product:
     *    - Creates GRN product line item with pricing
     *    - Records product movement (Type 0: PURCHASE) for audit trail
     *    - Increments product store_quantity
     * 
     * All operations are atomic (wrapped in transaction).
     * 
     * @param Request $request - Contains GRN data and product array
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate incoming data with comprehensive rules
        $validated = $request->validate([
            'goods_received_note_no'   => 'required|string|unique:goods_received_notes,goods_received_note_no',
            'supplier_id'   => 'required|exists:suppliers,id',
            'supplier_due_date' => 'nullable|date',
            'goods_received_note_date'      => 'required|date',
            'batch_number'  => 'nullable|string',
            'subtotal'      => 'nullable|numeric|min:0',
            'discount'      => 'nullable|numeric|min:0',
            'discount_type' => 'required|in:amount,percentage',
            'tax_total'     => 'nullable|numeric|min:0',
            'remarks'       => 'nullable|string',

            'products'                      => 'required|array|min:1',
            'products.*.product_id'         => 'required|exists:products,id',
            'products.*.batch_number'       => 'nullable|string',
            'products.*.requested_quantity' => 'required|numeric|min:0.01',
            'products.*.issued_quantity'    => 'required|numeric|min:0.01',
            'products.*.purchase_price'     => 'required|numeric|min:0',
            'products.*.discount'           => 'nullable|numeric|min:0',
            'products.*.discount_percentage'=> 'nullable|numeric|min:0|max:100',
            'products.*.measurement_unit_id'=> 'nullable|exists:measurement_units,id',
            'products.*.unit'               => 'nullable|string',
            'products.*.total'              => 'nullable|numeric',
        ]);

        $productIds = array_map(
            static fn (array $product): int => (int) ($product['product_id'] ?? 0),
            $validated['products']
        );

        if (count($productIds) !== count(array_unique($productIds))) {
            return redirect()
                ->back()
                ->withErrors(['products' => 'Duplicate products are not allowed in a single GRN.'])
                ->withInput();
        }

        // Start transaction to ensure data consistency across all operations
        DB::beginTransaction();

        try {
            $rawDiscount = (float) ($validated['discount'] ?? 0);
            $discountType = $validated['discount_type'] ?? 'amount';
            $grnDiscountPercentage = $discountType === 'percentage' ? $rawDiscount : null;
            $grnTaxTotal = $this->roundToWhole((float) ($validated['tax_total'] ?? 0));
            $computedSubtotal = 0;

            // Create main GRN record with default approval_status 'pending'
            $grn = GoodsReceivedNote::create([
                'purchase_order_request_id'        => null,
                'goods_received_note_no'        => $validated['goods_received_note_no'],
                'batch_number'  => $validated['batch_number'] ?? null,
                'supplier_id'   => $validated['supplier_id'],
                'user_id'       => Auth::id(),
                'goods_received_note_date'      => $validated['goods_received_note_date'],
                'subtotal'      => 0,
                'discount'      => 0,
                'discount_type' => $discountType,
                'discount_percentage' => $grnDiscountPercentage,
                'tax_total'     => $grnTaxTotal,
                'remarks'       => $validated['remarks'] ?? null,
                'status'        => 1,
                'approval_status' => 'pending',
                'error'=> null,
            ]);

            if (!empty($validated['supplier_due_date'])) {
                Supplier::whereKey($validated['supplier_id'])->update([
                    'due_date' => $validated['supplier_due_date'],
                ]);
            }

 
            // Process each received product
            foreach ($validated['products'] as $product) {
                $productModel = Product::find($product['product_id']);
                if (!$productModel) {
                    throw new \Exception('Product not found for product ID: ' . $product['product_id']);
                }

                $requestedQty = (float) ($product['requested_quantity'] ?? 0);
                $issuedQty = (float) ($product['issued_quantity'] ?? 0);
                $purchasePrice = (float) ($product['purchase_price'] ?? 0);
                $lineSubTotal = $issuedQty * $purchasePrice;
                $itemDiscountPercentage = (float) ($product['discount_percentage'] ?? 0);
                $itemDiscountPercentage = max(0, min(100, $itemDiscountPercentage));
                $itemDiscountAmount = $this->roundToWhole(($lineSubTotal * $itemDiscountPercentage) / 100);

                // Calculate line total: (qty × price) - discount
                $lineTotal = $this->roundToWhole(
                    $lineSubTotal - $itemDiscountAmount
                );
                $computedSubtotal += $lineTotal;

                // Use GRN batch_number for all products in this GRN
                $batchNumberForProduct = $validated['batch_number'] ?? $product['batch_number'] ?? null;

                //  Business validation: issued qty cannot exceed requested qty
                if ($requestedQty > 0 && $issuedQty > $requestedQty) {
                    throw new \Exception(
                        'Issued quantity cannot be greater than requested quantity for product ID: ' 
                        . $product['product_id']
                    );
                    
                }

                // Save received product line (store quantity in `quantity` column)
                GoodsReceivedNoteProduct::create([
                    'goods_received_note_id' => $grn->id,
                    'product_id'           => $product['product_id'],
                    // Store received amount in `quantity` using issued_quantity from frontend
                    'quantity'              => (int) $issuedQty,
                    'purchase_price'        => $purchasePrice,
                    'discount'              => $itemDiscountAmount,
                    'discount_percentage'   => $itemDiscountPercentage,
                    'total'                 => $lineTotal,
                    'batch_number'          => $batchNumberForProduct,
                   
                ]);

                // Save batch number to ProductAvailableQuantity table if batch_number is provided
                if (!empty($batchNumberForProduct)) {
                    $issuedQtyForBatch = (int) $issuedQty;
                    
                    $existingRecord = ProductAvailableQuantity::where('product_id', $product['product_id'])
                        ->where('batch_number', $batchNumberForProduct)
                        ->first();

                    if ($existingRecord) {
                        // Update existing batch record - add to available quantity in purchase units only
                        $existingRecord->increment('available_quantity', $issuedQtyForBatch);
                    } else {
                        // Create new batch record with GRN ID
                        // Only store available_quantity in purchase units
                        // quantity_in_transfer_unit and quantity_in_sales_unit will be calculated when items transfer
                        ProductAvailableQuantity::create([
                            'product_id'              => $product['product_id'],
                            'batch_number'           => $batchNumberForProduct,
                            'available_quantity'     => $issuedQtyForBatch,
                            'quantity_in_transfer_unit' => 0,
                            'quantity_in_sales_unit' => 0,
                            'unit_id'                => $product['measurement_unit_id'] ?? $productModel->purchase_unit_id ?? $productModel->measurement_unit_id,
                            'goods_received_note_id' => $grn->id,
                        ]);
                    }
                }

                // Record product movement (Type 0: PURCHASE - increases stock)
                // Creates audit trail for inventory tracking
                ProductMovement::recordMovement(
                    $product['product_id'],
                    ProductMovement::TYPE_PURCHASE,
                    (int) $issuedQty, // Positive for stock increase
                    $validated['goods_received_note_no']
                );

                // Increment storage stock quantity on the product by the received amount
                // Goods are received in purchase units only - don't pre-calculate transfer units
                if ($productModel) {
                    $receivedQty = (int) $issuedQty;
                    
                    // Update purchase unit quantity only (boxes)
                    $productModel->increment('store_quantity_in_purchase_unit', $receivedQty);
                    
                    // Don't auto-calculate transfer units - they will be created when boxes are broken down
                }
            }

            $grnDiscount = $discountType === 'percentage'
                ? $this->roundToWhole(($computedSubtotal * $rawDiscount) / 100)
                : $this->roundToWhole($rawDiscount);

            $grn->update([
                'subtotal' => $computedSubtotal,
                'discount' => $grnDiscount,
            ]);

            // Commit transaction - all operations succeeded
            DB::commit();

            return redirect()
                ->route('good-receive-notes.index')
                ->with('success', 'GRN created successfully!');

        } catch (\Throwable $e) {
            // Rollback all changes if any operation fails
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to create Good Receive Note: ' . $e->getMessage()])
                ->withInput();
        }
    }

    // public function edit(Grn $grn)
    // {
    //     $grn->load('grnProducts');
    //     $suppliers = Supplier::where('status', '!=', 0)->get();
    //     $purchaseOrders = Por::where('status', 'approved')->get();
    //     $products = Product::where('status', '!=', 0)->get();

    //     return Inertia::render('Grn/Edit', [
    //         'grn' => $grn,
    //         'suppliers' => $suppliers,
    //         'purchaseOrders' => $purchaseOrders,
    //         'availableProducts' => $products,
    //     ]);
    // }

    // public function update(Request $request, GoodsReceivedNote $goodsReceivedNote)
    // {
    //     $validated = $request->validate([
    //         'goods_received_note_no'        => 'required|unique:goods_received_notes,goods_received_note_no,' . $goodsReceivedNote->id,
    //         'supplier_id'   => 'required|exists:suppliers,id',
    //         'goods_received_note_date'      => 'required|date',
    //         'purchase_order_request_id'        => 'nullable|exists:purchase_order_requests,id',
    //         'discount'      => 'nullable|numeric|min:0',
    //         'tax_total'     => 'nullable|numeric|min:0',
    //         'remarks'       => 'nullable|string',
    //         'status'        => 'required|in:0,1,2',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         $goodsReceivedNote->update([
    //             'goods_received_note_no'        => $validated['goods_received_note_no'],
    //             'supplier_id'   => $validated['supplier_id'],
    //             'goods_received_note_date'      => $validated['goods_received_note_date'],
    //             'purchase_order_request_id'        => $validated['purchase_order_request_id'] ?? null,
    //             'discount'      => $validated['discount'] ?? 0,
    //             'tax_total'     => $validated['tax_total'] ?? 0,
    //             'remarks'       => $validated['remarks'] ?? null,
    //             'status'        => $validated['status'],
    //         ]);

    //         DB::commit();

    //         return redirect()->route('good-receive-notes.index')->with('success', 'Good Receive Note updated successfully');

    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         return redirect()
    //             ->back()
    //             ->withErrors(['error' => 'Failed to update Good Receive Note: ' . $e->getMessage()])
    //             ->withInput();
    //     }
    // }

    /**
     * Update the status of a Goods Received Note
     * 
     * Status values:
     * - 0: Inactive/Deleted
     * - 1: Active/Standard
     * - 2: Completed/Archived
     * 
     * @param Request $request - Contains 'status' field (0, 1, or 2)
     * @param GoodsReceivedNote $goodReceiveNote - The GRN to update (route model binding)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, GoodsReceivedNote $goodReceiveNote)
    {
        // Validate status is one of allowed values
        $validated = $request->validate([
            'status' => 'required|in:0,1,2',
        ]);

        // Update the GRN status
        $goodReceiveNote->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Status updated successfully');
    }

    /**
     * Approve a Goods Received Note (Admin only)
     */
    public function approve(GoodsReceivedNote $goodsReceivedNote)
    {
        $goodsReceivedNote->update(['approval_status' => 'approved']);

        return redirect()->back()->with('success', 'GRN approved successfully');
    }

    /**
     * Mark a Goods Received Note as inactive (soft delete)
     * 
     * Sets status to 0 to mark as deleted without removing data.
     * This preserves audit trail and historical records.
     * 
     * Note: This is a soft delete - the record remains in database
     * but is hidden from normal operations (status filter = 1).
     * 
     * @param GoodsReceivedNote $goodsReceivedNote - The GRN to delete (route model binding)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(GoodsReceivedNote $goodsReceivedNote)
    {
        // Mark as inactive rather than hard delete
        $goodsReceivedNote->update(['status' => 0]);

        return redirect()->back()->with('success', 'Good Receive Note marked as inactive successfully');
    }

    /**
     * Generate a unique Goods Received Note number
     * 
     * Format: GRN-YYYYMMDD-XXXX
     * - GRN: Static prefix
     * - YYYYMMDD: Date of creation (YEAR-MONTH-DAY)
     * - XXXX: Sequential number for the day (0001, 0002, etc.)
     * 
     * Examples:
     * - GRN-20251215-0001 (First GRN created on Dec 15, 2025)
     * - GRN-20251215-0002 (Second GRN created same day)
     * - GRN-20251216-0001 (First GRN created on Dec 16, 2025)
     * 
     * @return string Unique GRN number
     */
    private function generateGoodReceiveNoteNumber()
    {
        // Set prefix and current date
        $prefix = 'GRN';
        $date = date('Ymd');

        // Find last GRN created today to determine sequence
        $lastGrn = GoodsReceivedNote::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        // Extract sequence number from last GRN, or start at 1 if none found
        $sequence = 1;
        if ($lastGrn && !empty($lastGrn->goods_received_note_no)) {
            // Parse existing GRN number: GRN-YYYYMMDD-XXXX
            $parts = explode('-', $lastGrn->goods_received_note_no);
            if (count($parts) >= 3) {
                // Extract sequence from last part and increment
                $lastSeq = (int) $parts[2];
                $sequence = $lastSeq + 1;
            }
        }

        // Return formatted GRN number with zero-padded sequence
        return $prefix . '-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
   
}
