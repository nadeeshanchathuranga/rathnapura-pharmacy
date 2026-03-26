<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;


class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $suppliers = Supplier::orderBy('id', 'desc')
            ->paginate(10);

        // Calculate balance for each supplier
        $suppliers->getCollection()->transform(function ($supplier) {
            // Get total amount from goods_received_notes_products for this supplier
            $totalAmount = DB::table('goods_received_notes_products')
                ->join('goods_received_notes', 'goods_received_notes_products.goods_received_note_id', '=', 'goods_received_notes.id')
                ->where('goods_received_notes.supplier_id', $supplier->id)
                ->sum(DB::raw('CAST(goods_received_notes_products.total as DECIMAL(15,2))'));

            $totalAmount = (float) ($totalAmount ?? 0);

            // Get paid amount from purchase_expenses for this supplier
            $paid = DB::table('purchase_expenses')
                ->where('supplier_id', $supplier->id)
                ->sum(DB::raw('CAST(amount as DECIMAL(15,2))'));

            $paid = (float) ($paid ?? 0);

            // Calculate balance
            $supplier->total_amount = $totalAmount;
            $supplier->paid_amount = $paid;
            $supplier->due_payment = $totalAmount - $paid;
            return $supplier;
        });

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'nullable|email:rfc,dns|max:255',   // Better email check
            'phone_number'     => 'nullable|digits:10',
            'address'   => 'nullable|string',
            'status'    => 'required|in:0,1',
        ]);

        Supplier::create($validated);

        return redirect()->back()->with('success', 'Supplier created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
             'name'      => 'required|string|max:255',
            'email'     => 'nullable|email:rfc,dns|max:255',   // Better email check
            'phone_number'     => 'nullable|digits:10', // phone_number pattern
            'address'   => 'nullable|string',
            'status'    => 'required|in:0,1',
        ]);

        $supplier->update($validated);

        return redirect()->back()->with('success', 'Supplier updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->update(['status' => 0]);

        return redirect()->back()->with('success', 'Supplier deleted successfully');
    }
}
