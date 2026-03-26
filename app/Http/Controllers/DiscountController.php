<?php

namespace App\Http\Controllers;
use App\Models\CompanyInformation;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Inertia\Inertia;

class DiscountController extends Controller
{
    public function index()
    {
        $discounts = Discount::orderBy('status', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);
     $currencySymbol  = CompanyInformation::first();


        return Inertia::render('Discounts/Index', [
            'discounts' => $discounts,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255','unique:discounts,name'],
            'type' => 'required|in:0,1',
            'value' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:0,1',
        ], [
            'name.unique' => 'A discount with this name already exists.',
        ]);

        try {
            $newDiscount = Discount::create($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['name' => 'A discount with this name already exists.'])->withInput();
            }
            throw $e;
        }

        // If this is an Inertia request (from QuickAddModal), return the new discount in props
        if ($request->wantsJson()) {
            return back()->with('newDiscount', $newDiscount)->with('success', 'Discount created successfully');
        }

        return redirect()->back()->with('success', 'Discount created successfully');
    }

    public function update(Request $request, Discount $discount)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255', Rule::unique('discounts','name')->ignore($discount->id)],
            'type' => 'required|in:0,1',
            'value' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:0,1',
        ], [
            'name.unique' => 'A discount with this name already exists.',
        ]);

        try {
            $discount->update($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['name' => 'A discount with this name already exists.'])->withInput();
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Discount updated successfully');
    }

    public function destroy(Discount $discount)
    {
        $discount->update(['status' => 0]);

        return redirect()->back()->with('success', 'Discount deleted successfully');
    }
}