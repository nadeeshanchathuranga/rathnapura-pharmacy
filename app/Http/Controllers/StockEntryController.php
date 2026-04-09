<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAvailableQuantity;
use App\Models\ProductMovement;
use App\Models\StockEntry;
use App\Models\StockEntryProduct;
use App\Models\Supplier;
use App\Models\CompanyInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StockEntryController extends Controller
{
    public function index()
    {
        $entries = StockEntry::with(['supplier', 'user', 'products.product'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $suppliers = Supplier::where('status', '!=', 0)->orderBy('name')->get(['id', 'name']);

        $categories = Category::where('status', '!=', 0)->orderBy('name')->get(['id', 'name']);

        $products = Product::where('status', '!=', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'barcode', 'shop_quantity_in_sales_unit', 'purchase_price',
                   'retail_price', 'purchase_to_transfer_rate', 'transfer_to_sales_rate']);

        return Inertia::render('StockEntries/Index', [
            'entries'          => $entries,
            'suppliers'        => $suppliers,
            'categories'       => $categories,
            'availableProducts'=> $products,
            'entryNumber'      => $this->generateEntryNumber(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entry_number'                  => 'required|string|unique:stock_entries,entry_number',
            'invoice_number'                => 'nullable|string|max:100',
            'supplier_id'                   => 'nullable|exists:suppliers,id',
            'entry_type'                    => 'required|in:addition,deduction',
            'entry_date'                    => 'required|date',
            'remarks'                       => 'nullable|string|max:500',
            'products'                      => 'required|array|min:1',
            'products.*.product_id'         => 'nullable|integer',
            'products.*.is_new'             => 'nullable|boolean',
            'products.*.new_name'           => 'nullable|string|max:255',
            'products.*.new_barcode'        => 'nullable|string|max:100',
            'products.*.new_category_id'    => 'nullable|exists:categories,id',
            'products.*.new_division_id'    => 'nullable|exists:divisions,id',
            'products.*.new_retail_price'   => 'nullable|numeric|min:0',
            'products.*.purchase_price'     => 'nullable|numeric|min:0',
            'products.*.quantity'           => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            $entry = StockEntry::create([
                'entry_number' => $validated['entry_number'],
                'invoice_number' => $validated['invoice_number'] ?? null,
                'supplier_id'  => $validated['supplier_id'] ?? null,
                'user_id'      => Auth::id(),
                'entry_type'   => $validated['entry_type'],  
                'entry_date'   => $validated['entry_date'],
                'remarks'      => $validated['remarks'] ?? null,
            ]);

            foreach ($validated['products'] as $index => $line) {
                $isNew = (bool) ($line['is_new'] ?? false);

                if ($entry->entry_type === 'deduction') {
                    if (empty($line['product_id'])) {
                        throw new \Exception('Product ID is required for deductions.');
                    }
                    $product = Product::lockForUpdate()->find($line['product_id']);
                    if (!$product) {
                        throw new \Exception('Product not found: ' . $line['product_id']);
                    }
                    $qty = (float) $line['quantity'];
                    if ($product->shop_quantity_in_sales_unit < $qty) {
                        throw new \Exception(
                            "Insufficient stock for \"{$product->name}\". " .
                            "Available: {$product->shop_quantity_in_sales_unit}, Requested: {$qty}"
                        );
                    }
                    $product->decrement('shop_quantity_in_sales_unit', $qty);
                    $this->deductBatchesFifo($product, $qty);
                    StockEntryProduct::create([
                        'stock_entry_id'  => $entry->id,
                        'product_id'      => $product->id,
                        'quantity'        => $qty,
                        'purchase_price'  => null,
                        'is_opening_stock'=> false,
                        'notes'           => null,
                    ]);
                    ProductMovement::recordMovement(
                        $product->id,
                        ProductMovement::TYPE_STOCK_DEDUCTION,
                        -$qty,
                        $validated['entry_number']
                    );
                    continue;
                }

                // ─── ADDITION ─────────────────────────────────────────────
                if ($isNew) {
                    if (empty($line['new_name'])) {
                        throw new \Exception('Product name is required for new products.');
                    }

                    $divisionId = $line['new_division_id'] ?? null;
                    if (empty($divisionId)) {
                        throw ValidationException::withMessages([
                            "products.{$index}.new_division_id" => 'Division is required for new products.',
                        ]);
                    }

                    if ($user && method_exists($user, 'hasDivision') && $user->hasDivision() && !$user->isAdmin() && !$user->isBackoffice()) {
                        if ((int) $divisionId !== (int) $user->division_id) {
                            throw ValidationException::withMessages([
                                "products.{$index}.new_division_id" => 'You can only create products in your assigned division.',
                            ]);
                        }
                    }

                    $barcode = !empty($line['new_barcode']) ? trim($line['new_barcode']) : null;
                    if (!$barcode) {
                        $barcode = $this->generateBarcode();
                    }

                    $product = Product::create([
                        'name'                        => trim($line['new_name']),
                        'barcode'                     => $barcode,
                        'category_id'                 => $line['new_category_id'] ?? null,
                        'purchase_price'              => !empty($line['purchase_price']) ? (float) $line['purchase_price'] : null,
                        'retail_price'                => !empty($line['new_retail_price']) ? (float) $line['new_retail_price'] : null,
                        'division_id'                 => (int) $divisionId,
                        'shop_quantity_in_sales_unit' => 0,
                        'status'                      => 1,
                        'purchase_to_transfer_rate'   => 1,
                        'transfer_to_sales_rate'      => 1,
                    ]);
                } else {
                    if (empty($line['product_id'])) {
                        throw new \Exception('Product ID is required.');
                    }

                    $product = Product::lockForUpdate()->find($line['product_id']);
                    if (!$product) {
                        throw new \Exception('Product not found: ' . $line['product_id']);
                    }
                }

                $qty = (float) $line['quantity'];  // addition qty

                StockEntryProduct::create([
                    'stock_entry_id' => $entry->id,
                    'product_id'     => $product->id,
                    'quantity'       => $qty,
                    'purchase_price' => !empty($line['purchase_price']) ? (float) $line['purchase_price'] : null,
                    'is_opening_stock'=> false,
                    'notes'          => null,
                ]);

                // Update shop stock
                $product->increment('shop_quantity_in_sales_unit', $qty);

                // Update purchase price if provided
                if (!empty($line['purchase_price'])) {
                    $product->update(['purchase_price' => (float) $line['purchase_price']]);
                }

                // Add to FIFO batch (stored in purchase units)
                $purchaseQty = $this->convertSalesToPurchaseUnits($product, $qty);

                ProductAvailableQuantity::create([
                    'product_id'                => $product->id,
                    'batch_number'              => $validated['entry_number'],
                    'available_quantity'        => $purchaseQty > 0 ? $purchaseQty : $qty,
                    'quantity_in_transfer_unit' => 0,
                    'quantity_in_sales_unit'    => $qty,
                    'unit_id'                   => $product->sales_unit_id ?? $product->purchase_unit_id ?? null,
                    'goods_received_note_id'    => null,
                ]);

                ProductMovement::recordMovement(
                    $product->id,
                    ProductMovement::TYPE_STOCK_ADDITION,
                    $qty,
                    $validated['entry_number']
                );
            }

            DB::commit();

            return redirect()->route('stock-entries.index')
                ->with('success', 'Stock entry saved successfully.')
                ->with('print_stock_entry_id', $entry->id);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function update(Request $request, StockEntry $stockEntry)
    {
        $validated = $request->validate([
            'invoice_number'            => 'nullable|string|max:100',
            'supplier_id'               => 'nullable|exists:suppliers,id',
            'entry_date'                => 'required|date',
            'remarks'                   => 'nullable|string|max:500',
            'products'                  => 'required|array|min:1',
            'products.*.product_id'     => 'required|integer|exists:products,id',
            'products.*.quantity'       => 'required|numeric|min:0.01',
            'products.*.purchase_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Map existing product quantities by product_id
            $existingQtys = $stockEntry->products()
                ->get()
                ->keyBy('product_id')
                ->map(fn($p) => (float) $p->quantity);

            // Map new product quantities by product_id (sum if duplicate)
            $newQtys = collect($validated['products'])
                ->groupBy('product_id')
                ->map(fn($rows) => $rows->sum(fn($r) => (float) $r['quantity']));

            $allIds = $existingQtys->keys()->merge($newQtys->keys())->unique();

            foreach ($allIds as $pid) {
                $oldQty = $existingQtys->get($pid, 0.0);
                $newQty = $newQtys->get($pid, 0.0);
                $diff   = $newQty - $oldQty;

                if (abs($diff) < 0.0001) {
                    continue;
                }

                $product = Product::lockForUpdate()->find($pid);
                if (!$product) {
                    continue;
                }

                if ($stockEntry->entry_type === 'addition') {
                    if ($diff > 0) {
                        // Adding more stock
                        $product->increment('shop_quantity_in_sales_unit', $diff);
                        $purchaseQty = $this->convertSalesToPurchaseUnits($product, $diff);

                        $batch = ProductAvailableQuantity::where('product_id', $pid)
                            ->where('batch_number', $stockEntry->entry_number)
                            ->first();
                        if ($batch) {
                            $batch->increment('available_quantity', $purchaseQty > 0 ? $purchaseQty : $diff);
                        } else {
                            ProductAvailableQuantity::create([
                                'product_id'                => $pid,
                                'batch_number'              => $stockEntry->entry_number,
                                'available_quantity'        => $purchaseQty > 0 ? $purchaseQty : $diff,
                                'quantity_in_transfer_unit' => 0,
                                'quantity_in_sales_unit'    => $diff,
                                'unit_id'                   => $product->sales_unit_id ?? $product->purchase_unit_id ?? null,
                                'goods_received_note_id'    => null,
                            ]);
                        }

                        ProductMovement::recordMovement(
                            $pid, ProductMovement::TYPE_STOCK_ADDITION, $diff,
                            $stockEntry->entry_number . ' (edit)'
                        );
                    } else {
                        // Reducing stock
                        $removeQty = abs($diff);
                        if ($product->shop_quantity_in_sales_unit < $removeQty) {
                            throw new \Exception(
                                "Cannot reduce quantity for \"{$product->name}\". Current stock: {$product->shop_quantity_in_sales_unit}, Reduce by: {$removeQty}"
                            );
                        }
                        $product->decrement('shop_quantity_in_sales_unit', $removeQty);
                        $this->deductBatchesFifo($product, $removeQty);

                        ProductMovement::recordMovement(
                            $pid, ProductMovement::TYPE_STOCK_ADDITION, -$removeQty,
                            $stockEntry->entry_number . ' (edit)'
                        );
                    }
                } else {
                    // entry_type === 'deduction'
                    if ($diff > 0) {
                        // More deduction
                        if ($product->shop_quantity_in_sales_unit < $diff) {
                            throw new \Exception(
                                "Insufficient stock for \"{$product->name}\". Available: {$product->shop_quantity_in_sales_unit}, Extra: {$diff}"
                            );
                        }
                        $product->decrement('shop_quantity_in_sales_unit', $diff);
                        $this->deductBatchesFifo($product, $diff);

                        ProductMovement::recordMovement(
                            $pid, ProductMovement::TYPE_STOCK_DEDUCTION, -$diff,
                            $stockEntry->entry_number . ' (edit)'
                        );
                    } else {
                        // Less deduction — restore stock
                        $restoreQty  = abs($diff);
                        $product->increment('shop_quantity_in_sales_unit', $restoreQty);
                        $purchaseRestore = $this->convertSalesToPurchaseUnits($product, $restoreQty);

                        $latestBatch = ProductAvailableQuantity::where('product_id', $pid)
                            ->orderBy('created_at', 'desc')
                            ->lockForUpdate()
                            ->first();
                        if ($latestBatch) {
                            $latestBatch->increment('available_quantity', $purchaseRestore > 0 ? $purchaseRestore : $restoreQty);
                        } else {
                            ProductAvailableQuantity::create([
                                'product_id'                => $pid,
                                'batch_number'              => null,
                                'available_quantity'        => $purchaseRestore > 0 ? $purchaseRestore : $restoreQty,
                                'quantity_in_transfer_unit' => 0,
                                'quantity_in_sales_unit'    => $restoreQty,
                                'unit_id'                   => $product->sales_unit_id ?? $product->purchase_unit_id ?? null,
                                'goods_received_note_id'    => null,
                            ]);
                        }

                        ProductMovement::recordMovement(
                            $pid, ProductMovement::TYPE_STOCK_DEDUCTION, $restoreQty,
                            $stockEntry->entry_number . ' (edit)'
                        );
                    }
                }
            }

            // Update header fields (entry_type and entry_number are immutable)
            $stockEntry->update([
                'invoice_number' => $validated['invoice_number'] ?? null,
                'supplier_id'    => $validated['supplier_id'] ?? null,
                'entry_date'     => $validated['entry_date'],
                'remarks'        => $validated['remarks'] ?? null,
            ]);

            // Replace product lines
            $stockEntry->products()->delete();
            foreach ($validated['products'] as $line) {
                StockEntryProduct::create([
                    'stock_entry_id'   => $stockEntry->id,
                    'product_id'       => (int) $line['product_id'],
                    'quantity'         => (float) $line['quantity'],
                    'purchase_price'   => !empty($line['purchase_price']) ? (float) $line['purchase_price'] : null,
                    'is_opening_stock' => false,
                    'notes'            => null,
                ]);

                if ($stockEntry->entry_type === 'addition' && !empty($line['purchase_price'])) {
                    Product::where('id', $line['product_id'])
                        ->update(['purchase_price' => (float) $line['purchase_price']]);
                }
            }

            DB::commit();

            return redirect()->route('stock-entries.index')
                ->with('success', 'Stock entry updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(StockEntry $stockEntry)
    {
        $stockEntry->load('products.product');

        DB::beginTransaction();
        try {
            foreach ($stockEntry->products as $line) {
                $product = $line->product;
                if (!$product) {
                    continue;
                }

                $qty = (float) $line->quantity;

                if ($stockEntry->entry_type === 'addition') {
                    // Reverse an addition: remove stock that was added
                    if ($product->shop_quantity_in_sales_unit < $qty) {
                        throw new \Exception(
                            "Cannot delete entry: insufficient stock to reverse \"{$product->name}\". " .
                            "Available: {$product->shop_quantity_in_sales_unit}, Need to remove: {$qty}"
                        );
                    }
                    $product->decrement('shop_quantity_in_sales_unit', $qty);
                    $this->deductBatchesFifo($product, $qty);

                    ProductMovement::recordMovement(
                        $product->id,
                        ProductMovement::TYPE_STOCK_ADDITION,
                        -$qty,
                        $stockEntry->entry_number . ' (deleted)'
                    );
                } else {
                    // Reverse a deduction: restore stock that was removed
                    $product->increment('shop_quantity_in_sales_unit', $qty);
                    $purchaseQty = $this->convertSalesToPurchaseUnits($product, $qty);

                    $latestBatch = ProductAvailableQuantity::where('product_id', $product->id)
                        ->orderBy('created_at', 'desc')
                        ->lockForUpdate()
                        ->first();

                    if ($latestBatch) {
                        $latestBatch->increment('available_quantity', $purchaseQty > 0 ? $purchaseQty : $qty);
                    } else {
                        ProductAvailableQuantity::create([
                            'product_id'                => $product->id,
                            'batch_number'              => null,
                            'available_quantity'        => $purchaseQty > 0 ? $purchaseQty : $qty,
                            'quantity_in_transfer_unit' => 0,
                            'quantity_in_sales_unit'    => $qty,
                            'unit_id'                   => $product->sales_unit_id ?? $product->purchase_unit_id ?? null,
                            'goods_received_note_id'    => null,
                        ]);
                    }

                    ProductMovement::recordMovement(
                        $product->id,
                        ProductMovement::TYPE_STOCK_DEDUCTION,
                        $qty,
                        $stockEntry->entry_number . ' (deleted)'
                    );
                }
            }

            $stockEntry->delete();

            DB::commit();

            return redirect()->route('stock-entries.index')
                ->with('success', 'Stock entry deleted and stock quantities reversed.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function printInvoice(StockEntry $stockEntry)
    {
        $stockEntry->load(['supplier', 'user', 'products.product']);
        $companyInformation = CompanyInformation::first();

        return view('stock-entries.invoice', [
            'stockEntry' => $stockEntry,
            'companyInformation' => $companyInformation,
            'currency' => $companyInformation?->currency ?? 'Rs.',
        ]);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private function generateBarcode(): string
    {
        do {
            $barcode = '2' . str_pad(rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
        } while (Product::where('barcode', $barcode)->exists());
        return $barcode;
    }

    private function deductBatchesFifo(Product $product, float $salesQty): void
    {
        $purchaseQty = $this->convertSalesToPurchaseUnits($product, $salesQty);
        if ($purchaseQty <= 0) return;

        $remaining = $purchaseQty;
        $batches = ProductAvailableQuantity::where('product_id', $product->id)
            ->where('available_quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $deduct = min((float) $batch->available_quantity, $remaining);
            $batch->decrement('available_quantity', $deduct);
            $remaining -= $deduct;
            $batch->refresh();
            if ((float) $batch->available_quantity <= 0) {
                $batch->delete();
            }
        }
    }

    private function generateEntryNumber(): string
    {
        $prefix = 'SE-' . now()->format('Ymd') . '-';
        $last = StockEntry::where('entry_number', 'like', $prefix . '%')
            ->orderByDesc('entry_number')
            ->value('entry_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function convertSalesToPurchaseUnits(Product $product, float $salesQty): float
    {
        $rate = ((float) ($product->purchase_to_transfer_rate ?? 1))
              * ((float) ($product->transfer_to_sales_rate ?? 1));

        return $rate > 0 ? $salesQty / $rate : $salesQty;
    }
}

