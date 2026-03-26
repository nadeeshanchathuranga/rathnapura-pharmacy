<?php

namespace App\Http\Controllers;

use App\Models\MeasurementUnit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Inertia\Inertia;

class MeasurementUnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         
            $measurementUnits = MeasurementUnit::orderBy('status', 'desc')
    ->orderBy('id', 'desc')
    ->paginate(10);



        return Inertia::render('MeasurementUnits/Index', [
            'measurementUnits' => $measurementUnits,
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
        'name'   => 'required|string|max:255|unique:measurement_units,name',
        'symbol' => 'required|string|max:50',
        'status' => 'required|in:0,1',
    ], [
        'name.unique' => 'A measurement unit with this name already exists.',
    ]);

    try {
        $unit = MeasurementUnit::create($validated);
    } catch (QueryException $e) {
        if ($e->getCode() === '23000') {
            return back()->withErrors(['name' => 'A measurement unit with this name already exists.'])->withInput();
        }
        throw $e;
    }

    return back()
        ->with('success', 'Measurement Unit created successfully')
        ->with('newUnit', $unit);  
}

    /**
     * Display the specified resource.
     */
    public function show(MeasurementUnit $measurementUnit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MeasurementUnit $measurementUnit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MeasurementUnit $measurementUnit)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255', Rule::unique('measurement_units','name')->ignore($measurementUnit->id)],
            'symbol' => 'required|string|max:50',
            'status' => 'required|in:0,1',
        ], [
            'name.unique' => 'A measurement unit with this name already exists.',
        ]);

        try {
            $measurementUnit->update($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['name' => 'A measurement unit with this name already exists.'])->withInput();
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Measurement Unit updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MeasurementUnit $measurementUnit)
    {
        // Set status to inactive before soft deleting
        $measurementUnit->status = 0;
        $measurementUnit->save();
        
        // Soft delete
        $measurementUnit->delete();

        return redirect()->back()->with('success', 'Measurement Unit deleted successfully');
    }
}
