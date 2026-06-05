<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Display all settings as key => value array.
     */
    public function index()
    {
        $settingsCollection = Setting::all();

        $settings = $settingsCollection->pluck('value', 'key')->toArray();

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ])->with(['flash' => session('flash')]);
    }

    /**
     * Save / update settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings'              => 'required|array',
            'settings.shop_name'    => 'nullable|string|max:255',
            'settings.shop_address' => 'nullable|string',
            'settings.shop_phone'   => 'nullable|string|max:50',
            'settings.shop_email'   => 'nullable|email|max:255',
            'settings.currency'     => 'nullable|string|max:10',
            'settings.tax_rate'     => 'nullable|numeric|min:0|max:100',
            'settings.receipt_footer' => 'nullable|string',
            'settings.logo'         => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->back()->with('success', 'Settings saved successfully.');
    }
}
