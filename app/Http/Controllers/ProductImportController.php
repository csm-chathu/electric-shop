<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductImportController extends Controller
{
    // ── Download sample CSV ───────────────────────────────────────────────────
    public function sample()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="product-import-sample.csv"',
        ];

        $rows = [
            ['name', 'name_si', 'category', 'sku', 'unit', 'cost_price', 'selling_price', 'wholesale_price', 'stock_qty', 'alert_qty'],
            ['Red Lentils',    'රතු දාල්',      'Dhal & Pulses', 'DAL-RED-NEW', 'kg',  320, 380, 355, 50, 5],
            ['White Sugar',    'සුදු සීනි',     'Sugar & Salt',  'SUG-WHT-NEW', 'kg',  215, 245, 230, 100, 10],
            ['Coconut Oil',    'පොල් තෙල්',     'Oils & Fats',   'OIL-COC-NEW', 'L',   690, 780, 740, 30, 5],
            ['Wheat Flour',    'තිරිඟු පිටි',   'Flour',         'FLR-WHT-NEW', 'kg',  235, 270, 255, 80, 10],
            ['Chili Powder',   'මිරිස් කුඩු',   'Spices',        'SPC-CHI-NEW', 'g',   110, 130, 120, 60, 5],
            ['New Item',       'නව අයිතමය',    'Other',         'OTH-001',     'pcs', 100, 120, 110, 50, 5],
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
            fputs($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Import uploaded CSV ───────────────────────────────────────────────────
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $path   = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');

        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            return back()->with('error', 'Empty file.');
        }

        // Normalise header keys
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        // Build category name → id map (case-insensitive)
        $categories = Category::all()->keyBy(fn($c) => strtolower(trim($c->name)));

        $imported = 0;
        $skipped  = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) continue;

            $data = array_combine($header, array_pad($row, count($header), ''));

            $name = trim($data['name'] ?? '');
            if (!$name) continue;

            $catKey = strtolower(trim($data['category'] ?? ''));
            $cat    = $categories->get($catKey);

            if (!$cat) {
                // Auto-create the category
                $cat = Category::create([
                    'name'    => trim($data['category']),
                    'name_si' => trim($data['category']),
                    'slug'    => Str::slug(trim($data['category']) . '-' . uniqid()),
                    'active'  => true,
                ]);
                $categories->put($catKey, $cat);
            }

            $sku = trim($data['sku'] ?? '') ?: strtoupper(Str::slug($name)) . '-' . strtoupper(Str::random(4));

            // Skip duplicate SKU
            if (Product::where('sku', $sku)->exists()) {
                $skipped[] = $name . ' (duplicate SKU: ' . $sku . ')';
                continue;
            }

            Product::create([
                'category_id'     => $cat->id,
                'name'            => $name,
                'name_si'         => trim($data['name_si'] ?? $name),
                'sku'             => $sku,
                'unit'            => trim($data['unit']  ?? 'pcs'),
                'cost_price'      => (float) ($data['cost_price']      ?? 0),
                'selling_price'   => (float) ($data['selling_price']   ?? 0),
                'wholesale_price' => (float) ($data['wholesale_price'] ?? 0),
                'stock_qty'       => (float) ($data['stock_qty']       ?? 0),
                'alert_qty'       => (float) ($data['alert_qty']       ?? 5),
                'active'          => true,
            ]);

            $imported++;
        }

        fclose($handle);

        $msg = "Imported {$imported} product(s).";
        if ($skipped) {
            $msg .= ' Skipped: ' . implode(', ', $skipped);
        }

        return back()->with('success', $msg);
    }
}
