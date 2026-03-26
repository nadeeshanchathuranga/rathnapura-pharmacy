<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Inertia\Inertia;

class TypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
    $types = Type::orderBy('status', 'desc')
    ->orderBy('id', 'desc')
    ->paginate(10);

        return Inertia::render('Types/Index', [
            'types' => $types,
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
            'name' => ['required','string','max:255','unique:types,name'],
            'status' => 'required|in:0,1',
        ], [
            'name.unique' => 'A type with this name already exists.',
        ]);

        try {
            $type = Type::create($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['name' => 'A type with this name already exists.'])->withInput();
            }
            throw $e;
        }

        return back()
            ->with('success', 'Type created successfully.')
            ->with('newType', $type);
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
    public function update(Request $request, Type $type)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255', Rule::unique('types','name')->ignore($type->id)],
            'status' => 'required|in:0,1',
        ], [
            'name.unique' => 'A type with this name already exists.',
        ]);

        try {
            $type->update($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['name' => 'A type with this name already exists.'])->withInput();
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Type updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Type $type)
    {
        $type->update(['status' => 0]);

        return redirect()->back()->with('success', 'Type deleted successfully');
    }
}
