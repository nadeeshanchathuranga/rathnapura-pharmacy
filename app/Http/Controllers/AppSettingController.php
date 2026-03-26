<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\Currency;

class AppSettingController extends Controller
{
    public function index()
    {
        $appSetting = AppSetting::first();
       $currencies = Currency::all();
        return Inertia::render('Settings/AppSetting', [
            'appSetting' => $appSetting,
            'currencies' => $currencies,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'app_icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:1024',
            'app_footer' => 'nullable|string|max:500',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'currency' => 'nullable|string|max:10'
        ]);

        $appSetting = AppSetting::first();

        // Handle app logo upload
        if ($request->hasFile('app_logo')) {
            // Delete old logo if exists
            if ($appSetting && $appSetting->app_logo) {
                Storage::disk('public')->delete($appSetting->app_logo);
            }
            $validated['app_logo'] = $request->file('app_logo')->store('app-assets', 'public');
        } else {
            // If no new logo is uploaded, don't override the existing one
            unset($validated['app_logo']);
        }

        // Handle app icon upload
        if ($request->hasFile('app_icon')) {
            // Delete old icon if exists
            if ($appSetting && $appSetting->app_icon) {
                Storage::disk('public')->delete($appSetting->app_icon);
            }
            $validated['app_icon'] = $request->file('app_icon')->store('app-assets', 'public');
        } else {
            // If no new icon is uploaded, don't override the existing one
            unset($validated['app_icon']);
        }

        if ($appSetting) {
            $appSetting->update($validated);
        } else {
            $appSetting = AppSetting::create($validated);
        }

        // Return the updated settings page with the latest appSetting
        return Inertia::render('Settings/AppSetting', [
            'appSetting' => $appSetting->fresh(),
            'currencies' => \App\Models\Currency::all(),
        ])->with('success', 'App settings saved successfully.');
    }
}
