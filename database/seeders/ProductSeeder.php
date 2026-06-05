<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Categories ───────────────────────────────────────────────────────────
        $cats = [
            ['name' => 'Beverages',     'name_si' => 'පාන වර්ග',      'slug' => 'beverages'],
            ['name' => 'Rice & Grains', 'name_si' => 'හාල් හා ධාන්‍ය', 'slug' => 'rice-grains'],
            ['name' => 'Spices & Dry',  'name_si' => 'කරල් හා කුළු',  'slug' => 'spices-dry'],
            ['name' => 'Dairy & Eggs',  'name_si' => 'කිරි හා බිත්තර','slug' => 'dairy-eggs'],
            ['name' => 'Snacks',        'name_si' => 'කෑම් වර්ග',     'slug' => 'snacks'],
            ['name' => 'Oils & Ghee',   'name_si' => 'තෙල් හා ගී',    'slug' => 'oils-ghee'],
            ['name' => 'Personal Care', 'name_si' => 'පෞද්ගලික',      'slug' => 'personal-care'],
            ['name' => 'Cleaning',      'name_si' => 'පිරිසිදු කිරීම', 'slug' => 'cleaning'],
        ];

        foreach ($cats as $c) {
            Category::firstOrCreate(['slug' => $c['slug']], $c);
        }

        $bev   = Category::where('slug', 'beverages')->first();
        $rice  = Category::where('slug', 'rice-grains')->first();
        $spice = Category::where('slug', 'spices-dry')->first();
        $dairy = Category::where('slug', 'dairy-eggs')->first();
        $snack = Category::where('slug', 'snacks')->first();
        $oil   = Category::where('slug', 'oils-ghee')->first();
        $pers  = Category::where('slug', 'personal-care')->first();
        $clean = Category::where('slug', 'cleaning')->first();

        // ─── Products ─────────────────────────────────────────────────────────────
        $products = [
            // Beverages
            ['category_id' => $bev->id,   'name' => 'Coca Cola 330ml',       'name_si' => 'කොකා කෝලා 330ml',       'barcode' => '5449000000996', 'cost_price' => 80,   'selling_price' => 95,   'wholesale_price' => 85,   'stock_qty' => 50,  'unit' => 'pcs'],
            ['category_id' => $bev->id,   'name' => 'Sprite 330ml',           'name_si' => 'ස්ප්‍රයිට් 330ml',      'barcode' => '5000112563574', 'cost_price' => 80,   'selling_price' => 95,   'wholesale_price' => 85,   'stock_qty' => 40,  'unit' => 'pcs'],
            ['category_id' => $bev->id,   'name' => 'Fanta Orange 330ml',     'name_si' => 'ෆැන්ටා 330ml',          'barcode' => '5449000054227', 'cost_price' => 80,   'selling_price' => 95,   'wholesale_price' => 85,   'stock_qty' => 35,  'unit' => 'pcs'],
            ['category_id' => $bev->id,   'name' => 'Water 500ml',            'name_si' => 'වතුර 500ml',            'barcode' => '4902430731225', 'cost_price' => 25,   'selling_price' => 35,   'wholesale_price' => 28,   'stock_qty' => 100, 'unit' => 'pcs'],
            ['category_id' => $bev->id,   'name' => 'Milo 200ml',             'name_si' => 'මයිලෝ 200ml',           'barcode' => '9556001301013', 'cost_price' => 55,   'selling_price' => 70,   'wholesale_price' => 60,   'stock_qty' => 48,  'unit' => 'pcs'],
            ['category_id' => $bev->id,   'name' => 'Tea 200g',               'name_si' => 'තේ 200g',               'barcode' => '5060002100017', 'cost_price' => 280,  'selling_price' => 340,  'wholesale_price' => 300,  'stock_qty' => 30,  'unit' => 'pcs'],

            // Rice & Grains
            ['category_id' => $rice->id,  'name' => 'Samba Rice',             'name_si' => 'සම්බා හාල්',            'barcode' => 'LK-RICE-001',   'cost_price' => 115,  'selling_price' => 135,  'wholesale_price' => 120,  'stock_qty' => 100, 'unit' => 'kg'],
            ['category_id' => $rice->id,  'name' => 'Keeri Samba Rice',       'name_si' => 'කීරි සම්බා හාල්',      'barcode' => 'LK-RICE-002',   'cost_price' => 130,  'selling_price' => 155,  'wholesale_price' => 138,  'stock_qty' => 80,  'unit' => 'kg'],
            ['category_id' => $rice->id,  'name' => 'Nadu Rice',              'name_si' => 'නාඩු හාල්',             'barcode' => 'LK-RICE-003',   'cost_price' => 100,  'selling_price' => 120,  'wholesale_price' => 105,  'stock_qty' => 60,  'unit' => 'kg'],
            ['category_id' => $rice->id,  'name' => 'Dhal (Red Lentils)',     'name_si' => 'පරිප්පු',               'barcode' => 'LK-DHAL-001',   'cost_price' => 220,  'selling_price' => 260,  'wholesale_price' => 235,  'stock_qty' => 50,  'unit' => 'kg'],
            ['category_id' => $rice->id,  'name' => 'Flour (Wheat)',          'name_si' => 'පිටි',                  'barcode' => 'LK-FLOUR-001',  'cost_price' => 145,  'selling_price' => 170,  'wholesale_price' => 152,  'stock_qty' => 40,  'unit' => 'kg'],

            // Spices & Dry goods
            ['category_id' => $spice->id, 'name' => 'Sugar',                  'name_si' => 'සීනි',                  'barcode' => 'LK-SUGAR-001',  'cost_price' => 185,  'selling_price' => 210,  'wholesale_price' => 192,  'stock_qty' => 80,  'unit' => 'kg'],
            ['category_id' => $spice->id, 'name' => 'Salt',                   'name_si' => 'ලුණු',                  'barcode' => 'LK-SALT-001',   'cost_price' => 60,   'selling_price' => 75,   'wholesale_price' => 63,   'stock_qty' => 50,  'unit' => 'kg'],
            ['category_id' => $spice->id, 'name' => 'Chilli Powder',          'name_si' => 'මිරිස් කුඩු',          'barcode' => 'LK-CHILLI-001', 'cost_price' => 580,  'selling_price' => 680,  'wholesale_price' => 610,  'stock_qty' => 20,  'unit' => 'kg'],
            ['category_id' => $spice->id, 'name' => 'Turmeric Powder',        'name_si' => 'කහ කුඩු',              'barcode' => 'LK-TURM-001',   'cost_price' => 680,  'selling_price' => 800,  'wholesale_price' => 715,  'stock_qty' => 15,  'unit' => 'kg'],
            ['category_id' => $spice->id, 'name' => 'Coriander Powder',       'name_si' => 'කෝතමල්ලි කුඩු',       'barcode' => 'LK-COR-001',    'cost_price' => 420,  'selling_price' => 500,  'wholesale_price' => 440,  'stock_qty' => 15,  'unit' => 'kg'],
            ['category_id' => $spice->id, 'name' => 'Pepper',                 'name_si' => 'ගම්මිරිස්',            'barcode' => 'LK-PEP-001',    'cost_price' => 1800, 'selling_price' => 2100, 'wholesale_price' => 1900, 'stock_qty' => 10,  'unit' => 'kg'],
            ['category_id' => $spice->id, 'name' => 'Cloves',                 'name_si' => 'කරාබු නැටි',           'barcode' => 'LK-CLOVE-001',  'cost_price' => 3200, 'selling_price' => 3800, 'wholesale_price' => 3350, 'stock_qty' => 5,   'unit' => 'kg'],

            // Dairy & Eggs
            ['category_id' => $dairy->id, 'name' => 'Fresh Milk 1L',          'name_si' => 'නැවුම් කිරි 1L',       'barcode' => 'LK-MILK-001',   'cost_price' => 190,  'selling_price' => 220,  'wholesale_price' => 200,  'stock_qty' => 30,  'unit' => 'pcs'],
            ['category_id' => $dairy->id, 'name' => 'Yogurt 200g',            'name_si' => 'යෝගට් 200g',           'barcode' => 'LK-YOGI-001',   'cost_price' => 60,   'selling_price' => 80,   'wholesale_price' => 68,   'stock_qty' => 24,  'unit' => 'pcs'],
            ['category_id' => $dairy->id, 'name' => 'Eggs (per piece)',        'name_si' => 'බිත්තර (1)',            'barcode' => 'LK-EGG-001',    'cost_price' => 25,   'selling_price' => 32,   'wholesale_price' => 27,   'stock_qty' => 120, 'unit' => 'pcs'],
            ['category_id' => $dairy->id, 'name' => 'Butter 100g',            'name_si' => 'බටර් 100g',            'barcode' => 'LK-BUTT-001',   'cost_price' => 180,  'selling_price' => 215,  'wholesale_price' => 192,  'stock_qty' => 20,  'unit' => 'pcs'],

            // Snacks
            ['category_id' => $snack->id, 'name' => 'Cream Crackers 200g',    'name_si' => 'ක්‍රීම් ක්‍රැකර්ස් 200g', 'barcode' => 'LK-CRACK-001', 'cost_price' => 120, 'selling_price' => 145,  'wholesale_price' => 128,  'stock_qty' => 30,  'unit' => 'pcs'],
            ['category_id' => $snack->id, 'name' => 'Marie Biscuits 200g',    'name_si' => 'මාරි බිස්කට් 200g',   'barcode' => 'LK-BISK-001',   'cost_price' => 95,   'selling_price' => 115,  'wholesale_price' => 100,  'stock_qty' => 35,  'unit' => 'pcs'],
            ['category_id' => $snack->id, 'name' => 'Potato Chips 50g',       'name_si' => 'අල චිප්ස් 50g',        'barcode' => 'LK-CHIP-001',   'cost_price' => 55,   'selling_price' => 70,   'wholesale_price' => 60,   'stock_qty' => 40,  'unit' => 'pcs'],
            ['category_id' => $snack->id, 'name' => 'Bread Loaf',             'name_si' => 'පාන් ලොෆ්',             'barcode' => 'LK-BREAD-001',  'cost_price' => 95,   'selling_price' => 115,  'wholesale_price' => 100,  'stock_qty' => 20,  'unit' => 'pcs'],

            // Oils & Ghee
            ['category_id' => $oil->id,   'name' => 'Coconut Oil 750ml',      'name_si' => 'පොල් තෙල් 750ml',      'barcode' => 'LK-COCOIL-001', 'cost_price' => 420,  'selling_price' => 490,  'wholesale_price' => 445,  'stock_qty' => 25,  'unit' => 'pcs'],
            ['category_id' => $oil->id,   'name' => 'Sunflower Oil 1L',       'name_si' => 'සූරියකාන්ත තෙල් 1L',  'barcode' => 'LK-SUNOIL-001', 'cost_price' => 490,  'selling_price' => 570,  'wholesale_price' => 515,  'stock_qty' => 20,  'unit' => 'pcs'],
            ['category_id' => $oil->id,   'name' => 'Ghee 200g',              'name_si' => 'ගී 200g',               'barcode' => 'LK-GHEE-001',   'cost_price' => 280,  'selling_price' => 330,  'wholesale_price' => 295,  'stock_qty' => 15,  'unit' => 'pcs'],

            // Personal Care
            ['category_id' => $pers->id,  'name' => 'Shampoo 90ml',           'name_si' => 'ෂැම්පූ 90ml',          'barcode' => 'LK-SHAMP-001',  'cost_price' => 95,   'selling_price' => 120,  'wholesale_price' => 103,  'stock_qty' => 30,  'unit' => 'pcs'],
            ['category_id' => $pers->id,  'name' => 'Soap Bar 100g',          'name_si' => 'සබන් 100g',             'barcode' => 'LK-SOAP-001',   'cost_price' => 70,   'selling_price' => 90,   'wholesale_price' => 76,   'stock_qty' => 48,  'unit' => 'pcs'],
            ['category_id' => $pers->id,  'name' => 'Toothpaste 70g',         'name_si' => 'දන්ත ක්‍රීම් 70g',     'barcode' => 'LK-TOOTH-001',  'cost_price' => 130,  'selling_price' => 160,  'wholesale_price' => 140,  'stock_qty' => 25,  'unit' => 'pcs'],
            ['category_id' => $pers->id,  'name' => 'Sanitary Napkin 8pcs',   'name_si' => 'සනිටරි 8pcs',          'barcode' => 'LK-SANIT-001',  'cost_price' => 120,  'selling_price' => 150,  'wholesale_price' => 128,  'stock_qty' => 30,  'unit' => 'pcs'],

            // Cleaning
            ['category_id' => $clean->id, 'name' => 'Washing Powder 1kg',     'name_si' => 'රෙදි සෝදන කුඩු 1kg',  'barcode' => 'LK-WASH-001',   'cost_price' => 280,  'selling_price' => 330,  'wholesale_price' => 295,  'stock_qty' => 20,  'unit' => 'pcs'],
            ['category_id' => $clean->id, 'name' => 'Dish Wash Liquid 400ml', 'name_si' => 'බඳුන් සෝදන 400ml',   'barcode' => 'LK-DISH-001',   'cost_price' => 140,  'selling_price' => 175,  'wholesale_price' => 150,  'stock_qty' => 24,  'unit' => 'pcs'],
            ['category_id' => $clean->id, 'name' => 'Floor Cleaner 500ml',    'name_si' => 'බිම් සෝදන 500ml',     'barcode' => 'LK-FLOOR-001',  'cost_price' => 160,  'selling_price' => 200,  'wholesale_price' => 170,  'stock_qty' => 18,  'unit' => 'pcs'],
        ];

        foreach ($products as $prod) {
            $prod = array_merge(['alert_qty' => 5, 'active' => true], $prod);
            Product::firstOrCreate(['barcode' => $prod['barcode']], $prod);
        }

        // ─── Suppliers ────────────────────────────────────────────────────────────
        $suppliers = [
            ['name' => 'ABC Distributors',  'phone' => '0112345678', 'email' => 'abc@example.com'],
            ['name' => 'Lanka Wholesale',   'phone' => '0113456789', 'email' => 'lanka@example.com'],
            ['name' => 'Colombo Imports',   'phone' => '0114567890', 'email' => 'colombo@example.com'],
        ];
        foreach ($suppliers as $s) {
            Supplier::firstOrCreate(['name' => $s['name']], $s);
        }

        // ─── Customers ────────────────────────────────────────────────────────────
        $customers = [
            ['name' => 'Kamal Perera',   'phone' => '0771234567', 'credit_limit' => 5000],
            ['name' => 'Sunil Fernando', 'phone' => '0772345678', 'credit_limit' => 3000],
            ['name' => 'Nimal Silva',    'phone' => '0773456789', 'credit_limit' => 2000],
        ];
        foreach ($customers as $c) {
            Customer::firstOrCreate(['phone' => $c['phone']], $c);
        }
    }
}
