<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Inertia\Inertia;

class TaxController extends Controller
{
    public function index()
    {
        $taxes = Tax::orderBy('status', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return Inertia::render('Taxes/Index', [
            'taxes' => $taxes,
        ]);
    }

    public function store(Request $request)
    {


        $validated = $request->validate([
            'name' => ['required','string','max:255','unique:taxes,name'],
            'percentage' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:0,1',
            'status' => 'required|in:0,1',
        ], [
            'name.unique' => 'A tax with this name already exists.',
        ]);
 
        try {
            $newTax = Tax::create($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['name' => 'A tax with this name already exists.'])->withInput();
            }
            throw $e;
        }

        // If this is an Inertia request (from QuickAddModal), return the new tax in props
        if ($request->wantsJson()) {
            return back()->with('newTax', $newTax)->with('success', 'Tax created successfully');
        }

        return redirect()->back()->with('success', 'Tax created successfully');
    }

    public function update(Request $request, Tax $tax)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255', Rule::unique('taxes','name')->ignore($tax->id)],
            'percentage' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:0,1',
            'status' => 'required|in:0,1',
        ], [
            'name.unique' => 'A tax with this name already exists.',
        ]);

        try {
            $tax->update($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['name' => 'A tax with this name already exists.'])->withInput();
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Tax updated successfully');
    }

    public function destroy(Tax $tax)
    {
        $tax->update(['status' => 0]);

        return redirect()->back()->with('success', 'Tax deleted successfully');
    }
}