<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Sale::with(['user', 'customer'])
            ->where('status', '!=', 'held');

        if ($request->filled('search')) {
            $query->where('invoice_no', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales'   => $sales,
            'filters' => $request->only(['search', 'date_from', 'date_to']),
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Show the form for creating a new resource (POS billing screen).
     */
    public function create()
    {
        $customers = Customer::where('active', true)->orderBy('name')->get();

        // Top 24 popular products by sales qty; fall back to newest if no sales yet
        $popularIds = SaleItem::select('product_id', DB::raw('SUM(qty) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(24)
            ->pluck('product_id');

        $popularProducts = Product::where('active', true)
            ->where('stock_qty', '>', 0)
            ->when($popularIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $popularIds)
                ->orderByRaw('FIELD(id, ' . $popularIds->implode(',') . ')')
            )
            ->when($popularIds->isEmpty(), fn ($q) => $q->orderByDesc('created_at'))
            ->limit(24)
            ->get(['id', 'name', 'name_si', 'barcode', 'selling_price', 'wholesale_price', 'stock_qty', 'unit']);

        return Inertia::render('Sales/Create', [
            'customers'       => $customers,
            'popularProducts' => $popularProducts,
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.qty'            => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.discount'       => 'nullable|numeric|min:0',
            'items.*.total'          => 'required|numeric|min:0',
            'payment_method'         => 'required|string',
            'subtotal'               => 'required|numeric|min:0',
            'discount'               => 'nullable|numeric|min:0',
            'tax'                    => 'nullable|numeric|min:0',
            'total'                  => 'required|numeric|min:0',
            'paid'                   => 'required|numeric|min:0',
            'customer_id'            => 'nullable|exists:customers,id',
            'note'                   => 'nullable|string',
        ]);

        $sale = DB::transaction(function () use ($request) {
            // Generate invoice number
            $date        = Carbon::now()->format('Ymd');
            $lastSale    = Sale::whereDate('created_at', Carbon::today())
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();
            $sequence    = $lastSale
                ? (intval(substr($lastSale->invoice_no, -4)) + 1)
                : 1;
            $invoiceNo   = 'INV-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            $balance = $request->total - $request->paid;

            $sale = Sale::create([
                'invoice_no'  => $invoiceNo,
                'user_id'     => Auth::id(),
                'customer_id' => $request->customer_id,
                'subtotal'    => $request->subtotal,
                'discount'    => $request->discount ?? 0,
                'tax'         => $request->tax ?? 0,
                'total'       => $request->total,
                'paid'        => $request->paid,
                'balance'     => $balance,
                'status'      => 'completed',
                'note'        => $request->note,
            ]);

            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'unit_price'   => $item['unit_price'],
                    'cost_price'   => $product->cost_price,
                    'qty'          => $item['qty'],
                    'discount'     => $item['discount'] ?? 0,
                    'total'        => $item['total'],
                ]);

                $stockBefore = $product->stock_qty;
                $product->decrement('stock_qty', $item['qty']);

                StockMovement::create([
                    'product_id'   => $product->id,
                    'user_id'      => Auth::id(),
                    'type'         => 'out',
                    'qty'          => $item['qty'],
                    'stock_before' => $stockBefore,
                    'stock_after'  => $stockBefore - $item['qty'],
                    'reference'    => $sale->invoice_no,
                    'note'         => 'Sale: ' . $sale->invoice_no,
                ]);
            }

            // Record payment
            Payment::create([
                'sale_id'   => $sale->id,
                'method'    => $request->payment_method,
                'amount'    => $request->paid,
                'reference' => null,
            ]);

            // Handle credit sales
            if ($request->payment_method === 'credit' && $request->customer_id) {
                $customer = Customer::findOrFail($request->customer_id);
                $customer->increment('credit_balance', $balance);
            }

            return $sale;
        });

        return redirect()->route('sales.show', $sale->id)
            ->with('success', 'විකුණුම සාර්ථකව සම්පූර්ණ විය.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sale = Sale::with([
            'items.product:id,name_si',
            'payments',
            'customer',
            'user',
        ])->findOrFail($id);

        $settings = \App\Models\Setting::all()->pluck('value', 'key');

        return Inertia::render('Sales/Show', [
            'sale'     => $sale,
            'settings' => $settings,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Sales are generally not edited in a POS system
        abort(403, 'Sales cannot be edited.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        abort(403, 'Sales cannot be updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sale = Sale::findOrFail($id);
        $sale->delete();

        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully.');
    }

    /**
     * Hold a sale bill.
     */
    public function holdBill(Request $request)
    {
        $request->validate([
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'    => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total'  => 'required|numeric|min:0',
            'subtotal'       => 'required|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'tax'            => 'nullable|numeric|min:0',
            'total'          => 'required|numeric|min:0',
            'customer_id'    => 'nullable|exists:customers,id',
            'note'           => 'nullable|string',
        ]);

        $sale = DB::transaction(function () use ($request) {
            $date      = Carbon::now()->format('Ymd');
            $lastSale  = Sale::whereDate('created_at', Carbon::today())
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();
            $sequence  = $lastSale
                ? (intval(substr($lastSale->invoice_no, -4)) + 1)
                : 1;
            $invoiceNo = 'HOLD-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'invoice_no'  => $invoiceNo,
                'user_id'     => Auth::id(),
                'customer_id' => $request->customer_id,
                'subtotal'    => $request->subtotal,
                'discount'    => $request->discount ?? 0,
                'tax'         => $request->tax ?? 0,
                'total'       => $request->total,
                'paid'        => 0,
                'balance'     => $request->total,
                'status'      => 'held',
                'note'        => $request->note,
            ]);

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'unit_price'   => $item['unit_price'],
                    'cost_price'   => $product->cost_price,
                    'qty'          => $item['qty'],
                    'discount'     => $item['discount'] ?? 0,
                    'total'        => $item['total'],
                ]);
            }

            return $sale;
        });

        return response()->json([
            'sale_id'    => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'message'    => 'Bill held successfully.',
        ]);
    }

    /**
     * Get all held bills for the current user.
     */
    public function getHeldBills()
    {
        $heldBills = Sale::with(['customer', 'items.product'])
            ->where('status', 'held')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json($heldBills);
    }
}
