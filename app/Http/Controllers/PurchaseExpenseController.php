<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Models\CompanyInformation;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::with([
            'user',
            'supplier:id,name,due_date',
        ])
            ->orderBy('expense_date', 'desc')
            ->paginate(10);

        $suppliers = Supplier::where('status', 1)
            
            ->orderBy('name')
            ->get();
               $currencySymbol  = CompanyInformation::first();

        return Inertia::render('PurchaseExpenses/Index', [
            'expenses' => $expenses,
            'suppliers' => $suppliers,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_type' => 'required|integer|in:0,1,2,3',
            'card_type' => 'nullable|in:visa,mastercard',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reference' => 'required_if:payment_type,1,3|nullable|string|max:255',
            'remark' => 'nullable|string',
        ]);

        if ((int) ($validated['payment_type'] ?? -1) === 1 && empty($validated['card_type'])) {
            return back()
                ->withErrors(['card_type' => 'Card type is required for card payments.'])
                ->withInput();
        }

        if ((int) ($validated['payment_type'] ?? -1) !== 1) {
            $validated['card_type'] = null;
        }

        // Ensure a title exists to satisfy DB constraint
        $validated['title'] = $validated['title'] ?? 'Purchase Expense';
        $validated['user_id'] = Auth::id();

        Expense::create($validated);

        return redirect()->route('purchase-expenses.index')
            ->with('success', 'Purchase Expense created successfully.');
    }

    public function update(Request $request, Expense $purchaseExpense)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_type' => 'required|integer|in:0,1,2,3',
            'card_type' => 'nullable|in:visa,mastercard',
            'reference' => 'required_if:payment_type,1,3|nullable|string|max:255',
            'remark' => 'nullable|string',
        ]);

        if ((int) ($validated['payment_type'] ?? -1) === 1 && empty($validated['card_type'])) {
            return back()
                ->withErrors(['card_type' => 'Card type is required for card payments.'])
                ->withInput();
        }

        if ((int) ($validated['payment_type'] ?? -1) !== 1) {
            $validated['card_type'] = null;
        }

        $validated['title'] = $validated['title'] ?? $purchaseExpense->title ?? 'Purchase Expense';

        $purchaseExpense->update($validated);

        return redirect()->route('purchase-expenses.index')
            ->with('success', 'Purchase Expense updated successfully.');
    }

    public function destroy(Expense $purchaseExpense)
    {
        $purchaseExpense->delete();

        return redirect()->route('purchase-expenses.index')
            ->with('success', 'Purchase Expense deleted successfully.');
    }

    public function getSupplierData(Request $request)
    {
        $supplierId = $request->input('supplier_id');
        $supplier = Supplier::select('id', 'name', 'due_date')->find($supplierId);

        $latestGrn = DB::table('goods_received_notes')
            ->where('supplier_id', $supplierId)
            ->orderByDesc('goods_received_note_date')
            ->orderByDesc('id')
            ->first(['id', 'goods_received_note_no', 'goods_received_note_date']);

        // Calculate total amount from GRN products
        $totalAmount = DB::table('goods_received_notes_products')
            ->join('goods_received_notes', 'goods_received_notes_products.goods_received_note_id', '=', 'goods_received_notes.id')
            ->where('goods_received_notes.supplier_id', $supplierId)
            ->sum(DB::raw('CAST(goods_received_notes_products.total as DECIMAL(15,2))'));

        // Convert to float
        $totalAmount = (float) ($totalAmount ?? 0);

        // Calculate paid amount from expenses for this supplier
        $paid = DB::table('purchase_expenses')
            ->where('supplier_id', $supplierId)
            ->sum(DB::raw('CAST(amount as DECIMAL(15,2))'));

        $paid = (float) ($paid ?? 0);

        // Calculate balance
        $balance = $totalAmount - $paid;

        return response()->json([
            'supplier_id' => $supplier?->id,
            'supplier_name' => $supplier?->name,
            'supplier_due_date' => optional($supplier?->due_date)->format('Y-m-d'),
            'transaction_due_date' => optional($supplier?->due_date)->format('Y-m-d'),
            'grn_id' => $latestGrn?->id,
            'grn_no' => $latestGrn?->goods_received_note_no,
            'grn_date' => $latestGrn?->goods_received_note_date,
            'total_amount' => number_format($totalAmount, 2, '.', ''),
            'paid' => number_format($paid, 2, '.', ''),
            'balance' => number_format($balance, 2, '.', ''),
        ]);
    }
}
