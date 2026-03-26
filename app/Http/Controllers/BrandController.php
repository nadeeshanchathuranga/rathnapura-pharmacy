<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Inertia\Inertia;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       $brands = Brand::orderBy('status', 'desc')
    ->orderBy('id', 'desc')
    ->paginate(10);

  
  
        return Inertia::render('Brands/Index', [
            'brands' => $brands
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
        'name'   => 'required|string|max:255|unique:brands,name',
        'status' => 'required|boolean',
    ], [
        'name.unique' => 'A brand with this name already exists.',
    ]);

    try {
        $brand = Brand::create($validated);
    } catch (QueryException $e) {
        if ($e->getCode() === '23000') {
            return back()->withErrors(['name' => 'A brand with this name already exists.'])->withInput();
        }
        throw $e;
    }

    return back()
        ->with('success', 'Brand created successfully.')
        ->with('newBrand', $brand);
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $brand->id,
            'status' => 'required|boolean',
        ], [
            'name.unique' => 'A brand with this name already exists.',
        ]);

        try {
            $brand->update($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['name' => 'A brand with this name already exists.'])->withInput();
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Brand updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        // Set status to inactive before soft deleting
        $brand->status = 0;
        $brand->save();
        
        // Soft delete
        $brand->delete();

        return redirect()->back()->with('success', 'Brand deleted successfully');
    }
}
