<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAvailableQuantity;
use App\Models\ProductMovement;
use App\Models\StockEntry;
use App\Models\StockEntryProduct;
use App\Models\Supplier;
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
                ->with('success', 'Stock entry saved successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(StockEntry $stockEntry)
    {
        $stockEntry->delete();

        return redirect()->route('stock-entries.index')
            ->with('success', 'Stock entry deleted.');
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

