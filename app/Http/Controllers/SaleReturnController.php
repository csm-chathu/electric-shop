<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleReturnController extends Controller
{
    public function create(Sale $sale)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'cashier']), 403);
        $sale->load(['items.product', 'user', 'customer']);

        return Inertia::render('Sales/Return', [
            'sale'     => $sale,
            'settings' => Setting::all()->pluck('value', 'key')->toArray(),
        ]);
    }

    public function store(Request $request, Sale $sale)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'cashier']), 403);
        $request->validate([
            'items'        => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|integer',
            'items.*.qty'          => 'required|numeric|min:0.001',
            'items.*.product_name' => 'required|string',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.total'        => 'required|numeric|min:0',
            'items.*.product_id'   => 'nullable|integer',
            'reason'       => 'nullable|string|max:500',
        ]);

        $items = collect($request->items)->filter(fn($i) => ($i['qty'] ?? 0) > 0)->values();

        if ($items->isEmpty()) {
            return back()->with('error', 'Select at least one item to return.');
        }

        $total = $items->sum('total');

        DB::transaction(function () use ($sale, $items, $total, $request) {
            $returnNo = 'RTN-' . strtoupper(substr(md5(uniqid()), 0, 8));

            SaleReturn::create([
                'sale_id'   => $sale->id,
                'user_id'   => auth()->id(),
                'return_no' => $returnNo,
                'reason'    => $request->reason,
                'total'     => $total,
                'items'     => $items->toArray(),
            ]);

            // Restore stock for items that have a product_id
            foreach ($items as $item) {
                if (!empty($item['product_id'])) {
                    Product::where('id', $item['product_id'])
                        ->increment('stock_qty', $item['qty']);
                }
            }
        });

        return redirect()->route('sales.show', $sale->id)
            ->with('success', 'Return processed successfully.');
    }
}
