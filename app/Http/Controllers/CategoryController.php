<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Category::withCount('products');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters'    => $request->only(['search']),
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Categories/Create')
            ->with(['flash' => session('flash')]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255|unique:categories,name',
            'name_si' => 'nullable|string|max:255',
            'active'  => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Ensure slug uniqueness
        $baseSlug = $validated['slug'];
        $count    = 1;
        while (Category::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $count++;
        }

        Category::create($validated);

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::withCount('products')->findOrFail($id);

        return Inertia::render('Categories/Show', [
            'category' => $category,
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $category = Category::findOrFail($id);

        return Inertia::render('Categories/Edit', [
            'category' => $category,
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name'    => 'required|string|max:255|unique:categories,name,' . $category->id,
            'name_si' => 'nullable|string|max:255',
            'active'  => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Ensure slug uniqueness excluding current record
        $baseSlug = $validated['slug'];
        $count    = 1;
        while (Category::where('slug', $validated['slug'])->where('id', '!=', $category->id)->exists()) {
            $validated['slug'] = $baseSlug . '-' . $count++;
        }

        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}
