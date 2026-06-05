<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products   = $query->latest()->paginate(20)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return Inertia::render('Products/Index', [
            'products'   => $products,
            'categories' => $categories,
            'filters'    => $request->only(['search', 'category_id']),
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return Inertia::render('Products/Create', [
            'categories' => $categories,
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'name_si'       => 'nullable|string|max:255',
            'category_id'   => 'nullable|exists:categories,id',
            'barcode'       => 'nullable|string|max:100|unique:products,barcode',
            'sku'           => 'nullable|string|max:100|unique:products,sku',
            'description'   => 'nullable|string',
            'cost_price'      => 'nullable|numeric|min:0',
            'selling_price'   => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'stock_qty'       => 'nullable|numeric|min:0',
            'alert_qty'       => 'nullable|numeric|min:0',
            'unit'            => 'nullable|string|max:50',
            'active'          => 'boolean',
        ]);

        if (empty($validated['barcode'])) {
            $validated['barcode'] = strtoupper(Str::slug($validated['name'], '') . '-' . strtoupper(Str::random(6)));
        }

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category')->findOrFail($id);

        return Inertia::render('Products/Show', [
            'product' => $product,
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product    = Product::findOrFail($id);
        $categories = Category::orderBy('name')->get();

        return Inertia::render('Products/Edit', [
            'product'    => $product,
            'categories' => $categories,
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'name_si'       => 'nullable|string|max:255',
            'category_id'   => 'nullable|exists:categories,id',
            'barcode'       => 'nullable|string|max:100|unique:products,barcode,' . $product->id,
            'sku'           => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'description'   => 'nullable|string',
            'cost_price'      => 'nullable|numeric|min:0',
            'selling_price'   => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'stock_qty'       => 'nullable|numeric|min:0',
            'alert_qty'       => 'nullable|numeric|min:0',
            'unit'            => 'nullable|string|max:50',
            'active'          => 'boolean',
        ]);

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    public function search(Request $request)
    {
        $search = $request->get('q', '');
        $products = Product::where('active', true)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('name_si', 'like', "%{$search}%");
            })
            ->select('id', 'name', 'name_si', 'barcode', 'selling_price', 'wholesale_price', 'stock_qty', 'unit')
            ->limit(20)
            ->get();

        return response()->json($products);
    }
}
