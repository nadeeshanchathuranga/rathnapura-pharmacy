<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       $categories = Category::with('parent')
    ->orderBy('id', 'desc')
    ->paginate(10);


        return Inertia::render('Categories/Index', [
            'categories' => $categories,
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
            'name' => ['required','string','max:255','unique:categories,name'],
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:0,1',
        ], [
            'name.unique' => 'A category with this name already exists.',
        ]);

        try {
            $category = Category::create($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['name' => 'A category with this name already exists.'])->withInput();
            }
            throw $e;
        }

        return back()
            ->with('success', 'Category created successfully.')
            ->with('newCategory', $category);
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
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255', Rule::unique('categories','name')->ignore($category->id)],
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:0,1',
        ], [
            'name.unique' => 'A category with this name already exists.',
        ]);

        try {
            $category->update($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['name' => 'A category with this name already exists.'])->withInput();
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        // Set status to inactive before soft deleting
        $category->status = 0;
        $category->save();

        // Soft delete
        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully');
    }
}
