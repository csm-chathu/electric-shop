<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        $todaySales = Sale::whereDate('created_at', $today)
            ->where('status', '!=', 'held')
            ->sum('total');

        $monthSales = Sale::whereBetween('created_at', [$monthStart, Carbon::now()])
            ->where('status', '!=', 'held')
            ->sum('total');

        $totalProducts = Product::count();

        $lowStockCount = Product::whereColumn('stock_qty', '<=', 'alert_qty')->count();

        $recentSales = Sale::with('user')
            ->where('status', '!=', 'held')
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'todaySales'    => $todaySales,
            'monthSales'    => $monthSales,
            'totalProducts' => $totalProducts,
            'lowStockCount' => $lowStockCount,
            'recentSales'   => $recentSales,
        ])->with(['flash' => session('flash')]);
    }
}
