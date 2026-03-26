<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderRequest;
use App\Models\PurchaseOrderRequestProduct;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderRequestsController extends Controller
{


    /**
     * Display a listing of PORs
     */
    public function index()
    {
        return redirect()->route('good-receive-notes.index');
    }

    /**
     * Show the form for creating a new POR
     */

    /**
     * Store a newly created POR
     */
   public function store(Request $request)
{
    $validated = $request->validate([
        'order_number' => 'required|string|unique:purchase_order_requests,order_number',
        'order_date' => 'required|date',
        'user_id' => 'required|exists:users,id',
        'products' => 'required|array|min:1',
        'products.*.product_id' => 'required|exists:products,id',
        'products.*.requested_quantity' => 'required|integer|min:1',
        'products.*.measurement_unit_id' => 'nullable|exists:measurement_units,id',
    ]);

    DB::beginTransaction();

    try {
        // Determine status based on role
        // Admin PORs are auto-approved, non-admin PORs are pending
        $currentUser = User::find(Auth::id());
        $userRole = (int) ($currentUser?->getAttribute('role') ?? -1);
        $status = ($userRole === 0) ? 'approved' : 'pending';

        $purchaseOrderRequest = PurchaseOrderRequest::create([
            'order_number' => $validated['order_number'],
            'order_date' => $validated['order_date'],
            'user_id' => $validated['user_id'],
            'total_amount' => 0,
            'status' => $status,
            'created_by' => Auth::id(),
        ]);

        foreach ($validated['products'] as $productData) {
            PurchaseOrderRequestProduct::create([
                'purchase_order_request_id' => $purchaseOrderRequest->getKey(),
                'product_id' => $productData['product_id'],
                'requested_quantity' => $productData['requested_quantity'],
                'measurement_unit_id' => $productData['measurement_unit_id'] ?? null,
            ]);
        }

        DB::commit();

        return redirect()
            ->route('purchase-order-requests.index')
            ->with('success', 'Purchase Order Request created successfully');

    } catch (\Exception $e) {
        DB::rollBack();

        return back()
            ->withErrors(['error' => 'Failed to create POR: ' . $e->getMessage()])
            ->withInput();
    }
}

    /**
     * Display the specified POR
     */

    /**
     * Update the status of the specified POR
     */
    public function updateStatus(Request $request, PurchaseOrderRequest $purchaseOrderRequest)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,completed,inactive'
        ]);

        DB::beginTransaction();

        try {
            $oldStatus = (string) ($purchaseOrderRequest->getAttribute('status') ?? 'pending');
            $purchaseOrderRequest->update(['status' => $request->status]);

            DB::commit();

            return back()->with('success', 'Status updated from ' . ucfirst($oldStatus) . ' to ' . ucfirst($request->status) . ' successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Failed to update status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete the specified POR
     */
   public function destroy($id)
{
    $purchaseOrderRequest = PurchaseOrderRequest::findOrFail($id);
    $currentStatus = strtolower((string) ($purchaseOrderRequest->getAttribute('status') ?? ''));

    // Allow delete only when status is ACTIVE
    if ($currentStatus !== 'active') {
        return back()->withErrors([
            'error' => 'Only ACTIVE Purchase Order Requests can be deleted.'
        ]);
    }

    DB::beginTransaction();

    try {
        // Mark inactive then soft delete
        $purchaseOrderRequest->setAttribute('status', 'inactive');
        $purchaseOrderRequest->save();

        $purchaseOrderRequest->delete();

        DB::commit();

        return back()->with('success', 'Purchase Order Request marked inactive and deleted');

    } catch (\Exception $e) {
        DB::rollBack();

        return back()->withErrors([
            'error' => 'Failed to delete POR: ' . $e->getMessage()
        ]);
    }
}

    /**
     * Restore a soft-deleted POR
     */
    public function restore($id)
    {
        DB::beginTransaction();

        try {
            $purchaseOrderRequest = PurchaseOrderRequest::withTrashed()->findOrFail($id);
            $purchaseOrderRequest->restore();

            DB::commit();

            return back()->with('success', 'Purchase Order Request restored successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Failed to restore POR: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update the specified POR
     */
    // public function update(Request $request, PurchaseOrderRequest $purchaseOrderRequest)
    // {
    //     // Only allow update if status is pending
    //     if ($purchaseOrderRequest->status !== 'pending') {
    //         return back()->withErrors([
    //             'error' => 'Only pending PORs can be updated. Current status: ' . ucfirst($purchaseOrderRequest->status)
    //         ]);
    //     }

    //     $validated = $request->validate([
    //         'order_date' => 'required|date',
    //         'user_id' => 'required|exists:users,id',
    //         'products' => 'required|array|min:1',
    //         'products.*.product_id' => 'required|exists:products,id',
    //         'products.*.requested_quantity' => 'required|integer|min:1',
    //         'products.*.measurement_unit_id' => 'required|exists:measurement_units,id'
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         // Update POR
    //         $purchaseOrderRequest->update([
    //             'order_date' => $validated['order_date'],
    //             'user_id' => $validated['user_id']
    //         ]);

    //         // Delete existing products
    //         PurchaseOrderRequestProduct::where('purchase_order_request_id', $purchaseOrderRequest->id)->delete();

    //         // Add new products
    //         foreach ($validated['products'] as $productData) {
    //             PurchaseOrderRequestProduct::create([
    //                 'purchase_order_request_id' => $purchaseOrderRequest->id,
    //                 'product_id' => $productData['product_id'],
    //                 'requested_quantity' => $productData['requested_quantity'],
    //                 'measurement_unit_id' => $productData['measurement_unit_id']
    //             ]);
    //         }

    //         DB::commit();

    //         return redirect()->route('purchase-order-requests.index')
    //             ->with('success', 'Purchase Order Request updated successfully');

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return back()->withErrors([
    //             'error' => 'Failed to update POR: ' . $e->getMessage()
    //         ]);
    //     }
    // }

    private function generateOrderNumber()
    {
        $prefix = 'POR';
        $date = date('Ymd');

        // Get the last order number with today's date pattern (including soft deleted)
        $lastPor = PurchaseOrderRequest::withTrashed()
            ->where('order_number', 'like', $prefix . '-' . $date . '-%')
            ->orderBy('order_number', 'desc')
            ->first();

        $sequence = $lastPor ? (int)substr($lastPor->order_number, -4) + 1 : 1;

        return $prefix . '-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }



 public function purchaseOrderDetails($id)
{
    try {
        // Load the Purchase Order
        $purchaseOrder = PurchaseOrderRequest::findOrFail($id);

        // Get products from por_products table, include measurement unit
        $purchaseOrderProducts = PurchaseOrderRequestProduct::where('purchase_order_request_id', $id)
            ->with(['product.purchaseUnit'])
            ->get()
            ->map(function($purchaseOrderProduct) use ($purchaseOrder) {
                // Sum quantities already received for this PO & product across GRNs
                $issued = GoodsReceivedNoteProduct::whereHas('grn', function($q) use ($purchaseOrder) {
                    $q->where('purchase_order_request_id', $purchaseOrder->id);
                })->where('product_id', $purchaseOrderProduct->product_id)->sum('quantity');

                $requested = $purchaseOrderProduct->requested_quantity ?? 0;
                $remaining = max(0, $requested - $issued);

                return [
                    'product_id' => $purchaseOrderProduct->product_id,
                    'name'       => $purchaseOrderProduct->product->name ?? 'N/A',
                    // Return the remaining requested quantity (requested - already issued)
                    'requested_quantity'   => $remaining,
                    'measurement_unit_id' => $purchaseOrderProduct->measurement_unit_id,
                    'measurement_unit'    => $purchaseOrderProduct->product->purchaseUnit->name ?? 'N/A',
                    //'price'      => $purchaseOrderProduct->product->purchase_price ?? 0,
                    'already_issued_quantity' => $issued,
                ];
            });


        return response()->json([
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrderProducts' => $purchaseOrderProducts
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to load PO details',
            'message' => $e->getMessage()
        ], 404);
    }
}


}
