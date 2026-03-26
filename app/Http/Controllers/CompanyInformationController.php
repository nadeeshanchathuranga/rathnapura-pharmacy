<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\CompanyInformation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class CompanyInformationController extends Controller
{
    public function index()
    {
        $companyInfo = CompanyInformation::first();
       $currencies = Currency::all();


        return Inertia::render('Settings/CompanyInformation', [
            'companyInfo' => $companyInfo,
            'currencies' => $currencies,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            // Enforce exactly 10 digits for phone
            'phone' => 'nullable|digits:10',
            // Enhanced email validation
            'email' => [
                'nullable',
                'string',
                'email:rfc,dns',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
            // Enhanced website URL validation
            'website' => [
                'nullable',
                'string',
                'max:2048',
                'regex:/^https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(\/.*)?$/'
            ],
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'currency' => 'required|string|max:10',
        ], [
            'email.email' => 'Please enter a valid email address.',
            'email.regex' => 'Email format is invalid. Please use a standard email format.',
            'website.regex' => 'Website URL must be a valid HTTP or HTTPS URL (e.g., https://example.com).',
            'website.max' => 'Website URL must be less than 2048 characters.',
            'phone.digits' => 'Phone number must be exactly 10 digits.',
        ]);

        // Additional custom validation for website URL
        if (!empty($validated['website'])) {
            // Ensure URL starts with http:// or https://
            if (!preg_match('/^https?:\/\//', $validated['website'])) {
                return back()->withErrors([
                    'website' => 'Website URL must start with http:// or https://'
                ]);
            }

            // Validate URL structure using PHP's filter_var
            if (!filter_var($validated['website'], FILTER_VALIDATE_URL)) {
                return back()->withErrors([
                    'website' => 'Please enter a valid website URL.'
                ]);
            }

            // Parse URL to check components
            $parsedUrl = parse_url($validated['website']);
            if (!isset($parsedUrl['host']) || strlen($parsedUrl['host']) < 3) {
                return back()->withErrors([
                    'website' => 'Website URL must have a valid domain name.'
                ]);
            }
        }

        // Check if company info exists
        $companyInfo = CompanyInformation::first();

        // Handle logo upload only if a new file is provided
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            $validated['logo'] = $logoPath;

            // Delete old logo if new one is uploaded
            if ($companyInfo && $companyInfo->logo) {
                Storage::disk('public')->delete($companyInfo->logo);
            }
        } else {
            // Remove logo from validated data if no new file is uploaded
            // This prevents the logo field from being set to null
            unset($validated['logo']);
        }

        if ($companyInfo) {
            $companyInfo->update($validated);
        } else {
            CompanyInformation::create($validated);
        }

        return redirect()->route('settings.company')
            ->with('success', 'Company information saved successfully.');
    }
}
