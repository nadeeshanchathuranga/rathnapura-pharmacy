<?php

namespace App\Http\Controllers;

use App\Models\SmtpSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmtpSettingController extends Controller
{
    /**
     * Display the SMTP settings page
     */
    public function index()
    {
        $smtpSetting = SmtpSetting::first();
        
        return Inertia::render('Settings/SmtpSetting', [
            'smtpSetting' => $smtpSetting
        ]);
    }

    /**
     * Store or update SMTP settings
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mail_mailer' => 'required|string|max:50',
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_username' => 'required|string|max:255',
            'mail_password' => 'required|string|max:255',
            'mail_encryption' => 'nullable|string|in:ssl,tls',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ]);

        $smtpSetting = SmtpSetting::first();

        if ($smtpSetting) {
            $smtpSetting->update($validated);
        } else {
            SmtpSetting::create($validated);
        }

        return redirect()->route('settings.smtp')
            ->with('success', 'SMTP settings saved successfully.');
    }
}
