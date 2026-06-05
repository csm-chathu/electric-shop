<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters'   => $request->only(['search']),
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Suppliers/Create')
            ->with(['flash' => session('flash')]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'email'   => 'nullable|email|max:255|unique:suppliers,email',
            'address' => 'nullable|string',
            'active'  => 'boolean',
        ]);

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $supplier = Supplier::withCount('purchases')->findOrFail($id);

        return Inertia::render('Suppliers/Show', [
            'supplier' => $supplier,
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $supplier = Supplier::findOrFail($id);

        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier,
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'email'   => 'nullable|email|max:255|unique:suppliers,email,' . $supplier->id,
            'address' => 'nullable|string',
            'active'  => 'boolean',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }
}
