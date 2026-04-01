<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderProduct;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\CompanyInformation;
use App\Models\MeasurementUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

/**
 * PurchaseOrdersController
 *
 * Manages formal Purchase Orders sent to suppliers.
 * Provides the complete PO lifecycle:
 * - Creating POs with product line items
 * - Approving / completing / cancelling POs
 * - Viewing and listing POs
 * - Soft-delete with restoration
 *
 * Workflow: PO (pending) → approved → GRN created referencing the PO → completed
 *
 * @package App\Http\Controllers
 */
class PurchaseOrdersController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function roundToWhole(float $value): int
    {
        return (int) round($value, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Generate a unique Purchase Order number in format: PO-YYYYMMDD-XXXX
     */
    private function generatePurchaseOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "PO-{$date}-";

        $last = PurchaseOrder::withTrashed()
            ->where('order_number', 'like', $prefix . '%')
            ->orderByDesc('order_number')
            ->value('order_number');

        if ($last) {
            $lastSeq = (int) substr($last, strlen($prefix));
            $next = $lastSeq + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // ─────────────────────────────────────────────────────────────
    // index
    // ─────────────────────────────────────────────────────────────

    /**
     * Display paginated list of Purchase Orders.
     */
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with([
            'supplier',
            'user',
            'products.product',
            'products.measurementUnit',
        ])
            ->where('status', '!=', 'cancelled')
            ->orWhereNull('status')
            ->latest()
            ->paginate(10);

        // Re-query to include all statuses in paginated result (fix override)
        $purchaseOrders = PurchaseOrder::with([
            'supplier',
            'user',
            'products.product',
            'products.measurementUnit',
        ])
            ->latest()
            ->paginate(10);

        $suppliers        = Supplier::where('status', '!=', 0)->get();
        $availableProducts = Product::where('status', '!=', 0)
            ->with(['measurement_unit', 'purchaseUnit'])
            ->get();
        $measurementUnits = MeasurementUnit::orderBy('name')->get();
        $currencySymbol   = CompanyInformation::first();

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders'   => $purchaseOrders,
            'suppliers'        => $suppliers,
            'availableProducts'=> $availableProducts,
            'measurementUnits' => $measurementUnits,
            'poNumber'         => $this->generatePurchaseOrderNumber(),
            'currencySymbol'   => $currencySymbol,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // store
    // ─────────────────────────────────────────────────────────────

    /**
     * Store a newly created Purchase Order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_number'                       => 'required|string|unique:purchase_orders,order_number',
            'order_date'                         => 'required|date',
            'supplier_id'                        => 'required|exists:suppliers,id',
            'discount'                           => 'nullable|numeric|min:0',
            'discount_type'                      => 'required|in:amount,percentage',
            'tax_total'                          => 'nullable|numeric|min:0',
            'remarks'                            => 'nullable|string',
            'products'                           => 'required|array|min:1',
            'products.*.product_id'              => 'required|exists:products,id',
            'products.*.quantity'                => 'required|numeric|min:0.01',
            'products.*.purchase_price'          => 'required|numeric|min:0',
            'products.*.discount_percentage'     => 'nullable|numeric|min:0|max:100',
            'products.*.measurement_unit_id'     => 'nullable|exists:measurement_units,id',
        ]);

        $productIds = array_column($validated['products'], 'product_id');
        if (count($productIds) !== count(array_unique($productIds))) {
            return back()
                ->withErrors(['products' => 'Duplicate products are not allowed in a single Purchase Order.'])
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $rawDiscount       = (float) ($validated['discount'] ?? 0);
            $discountType      = $validated['discount_type'];
            $discountPct       = $discountType === 'percentage' ? $rawDiscount : null;
            $taxTotal          = $this->roundToWhole((float) ($validated['tax_total'] ?? 0));
            $computedSubtotal  = 0;

            $po = PurchaseOrder::create([
                'order_number'        => $validated['order_number'],
                'order_date'          => $validated['order_date'],
                'supplier_id'         => $validated['supplier_id'],
                'user_id'             => Auth::id(),
                'subtotal'            => 0,
                'discount'            => 0,
                'discount_type'       => $discountType,
                'discount_percentage' => $discountPct,
                'tax_total'           => $taxTotal,
                'total_amount'        => 0,
                'status'              => 'pending',
                'remarks'             => $validated['remarks'] ?? null,
            ]);

            foreach ($validated['products'] as $item) {
                $qty               = (float) ($item['quantity'] ?? 0);
                $price             = (float) ($item['purchase_price'] ?? 0);
                $discountPctItem   = (float) ($item['discount_percentage'] ?? 0);
                $discountPctItem   = max(0, min(100, $discountPctItem));
                $lineSubtotal      = $qty * $price;
                $discountAmount    = $this->roundToWhole(($lineSubtotal * $discountPctItem) / 100);
                $lineTotal         = $this->roundToWhole($lineSubtotal - $discountAmount);
                $computedSubtotal += $lineTotal;

                PurchaseOrderProduct::create([
                    'purchase_order_id'   => $po->id,
                    'product_id'          => $item['product_id'],
                    'measurement_unit_id' => $item['measurement_unit_id'] ?? null,
                    'quantity'            => $qty,
                    'purchase_price'      => $price,
                    'discount_percentage' => $discountPctItem,
                    'discount'            => $discountAmount,
                    'total'               => $lineTotal,
                ]);
            }

            $headerDiscount = $discountType === 'percentage'
                ? $this->roundToWhole(($computedSubtotal * $rawDiscount) / 100)
                : $this->roundToWhole($rawDiscount);

            $grandTotal = $computedSubtotal - $headerDiscount + $taxTotal;

            $po->update([
                'subtotal'     => $computedSubtotal,
                'discount'     => $headerDiscount,
                'total_amount' => $grandTotal,
            ]);

            DB::commit();

            return redirect()
                ->route('purchase-orders.index')
                ->with('success', 'Purchase Order created successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withErrors(['error' => 'Failed to create Purchase Order: ' . $e->getMessage()])
                ->withInput();
        }
    }

    // ─────────────────────────────────────────────────────────────
    // updateStatus
    // ─────────────────────────────────────────────────────────────

    /**
     * Update the status of a Purchase Order.
     * Allowed transitions: pending→approved, approved→completed, any→cancelled
     */
    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,completed,cancelled',
        ]);

        DB::beginTransaction();
        try {
            $old = $purchaseOrder->getAttribute('status');
            $purchaseOrder->update(['status' => $request->status]);
            DB::commit();

            return back()->with('success', 'Status updated from ' . ucfirst($old) . ' to ' . ucfirst($request->status) . '.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update status: ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // destroy (soft delete)
    // ─────────────────────────────────────────────────────────────

    /**
     * Soft-delete a Purchase Order (only when status is pending).
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $status = strtolower((string) ($purchaseOrder->getAttribute('status') ?? ''));

        if ($status !== 'pending') {
            return back()->withErrors([
                'error' => 'Only PENDING purchase orders can be deleted. Current status: ' . ucfirst($status),
            ]);
        }

        DB::beginTransaction();
        try {
            $purchaseOrder->update(['status' => 'cancelled']);
            $purchaseOrder->delete();
            DB::commit();

            return back()->with('success', 'Purchase Order cancelled and deleted.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete Purchase Order: ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // restore
    // ─────────────────────────────────────────────────────────────

    /**
     * Restore a soft-deleted Purchase Order.
     */
    public function restore($id)
    {
        DB::beginTransaction();
        try {
            $po = PurchaseOrder::withTrashed()->findOrFail($id);
            $po->restore();
            $po->update(['status' => 'pending']);
            DB::commit();

            return back()->with('success', 'Purchase Order restored successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to restore Purchase Order: ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // AJAX – get PO details for GRN pre-fill
    // ─────────────────────────────────────────────────────────────

    /**
     * Return PO details as JSON for use in GRN create modal (product pre-fill).
     */
    public function details($id)
    {
        $po = PurchaseOrder::with(['products.product.measurement_unit', 'products.measurementUnit', 'supplier'])
            ->findOrFail($id);

        return response()->json($po);
    }

    // ─────────────────────────────────────────────────────────────
    // PDF Export
    // ─────────────────────────────────────────────────────────────

    /**
     * Download a PDF for a single Purchase Order.
     */
    public function exportPdf($id)
    {
        if (!class_exists(Pdf::class)) {
            return back()->with('error', 'PDF export is not available. Please install barryvdh/laravel-dompdf.');
        }

        $purchaseOrder = PurchaseOrder::with([
            'supplier',
            'user',
            'products.product',
            'products.measurementUnit',
        ])->findOrFail($id);

        $companyInfo = CompanyInformation::first();
        $currency = $companyInfo->currency_symbol ?? ($companyInfo->currency ?? '');

        $pdf = Pdf::loadView('reports.Components.purchase-order-pdf', [
            'purchaseOrder' => $purchaseOrder,
            'currency'      => $currency,
        ]);

        return $pdf->download('purchase-order-' . $purchaseOrder->order_number . '.pdf');
    }
}
