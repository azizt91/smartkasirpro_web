<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Show the settings form
     */
    public function index()
    {
        $settings = Setting::getStoreSettings();

        return view('settings.index', compact('settings'));
    }

    /**
     * Update the settings
     */
    public function update(Request $request)
    {

        $request->validate([
            'store_name' => 'required|string|max:255',
            'business_mode' => 'required|in:retail,resto',
            'store_address' => 'nullable|string|max:500',
            'store_phone' => 'nullable|string|max:50',
            'store_description' => 'nullable|string|max:1000',
            'store_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'point_earning_rate' => 'required|integer|min:1',
            'point_exchange_rate' => 'required|integer|min:1',
            'employee_label' => 'nullable|string|max:20',
            'enable_loyalty_points' => 'nullable|boolean',
            // Payment Gateway
            'pg_active' => 'nullable|in:none,tripay,duitku,midtrans',
            'pg_mode' => 'nullable|in:sandbox,production',
            'pg_fee_bearer' => 'nullable|in:merchant,customer',
            'tripay_api_key' => 'nullable|string|max:500',
            'tripay_private_key' => 'nullable|string|max:500',
            'tripay_merchant_code' => 'nullable|string|max:100',
            'duitku_merchant_code' => 'nullable|string|max:100',
            'duitku_api_key' => 'nullable|string|max:500',
            'midtrans_client_key' => 'nullable|string|max:500',
            'midtrans_server_key' => 'nullable|string|max:500',
            'midtrans_merchant_id' => 'nullable|string|max:100',
            // WhatsApp Notification (Fonnte)
            'fonnte_token' => 'nullable|string|max:500',
            'enable_wa_notification' => 'nullable|boolean',
        ]);

        $data = $request->only([
            'store_name', 'business_mode', 'store_address', 'store_phone', 'store_description',
            'tax_rate', 'point_earning_rate', 'point_exchange_rate', 'employee_label',
            // Payment Gateway
            'pg_active', 'pg_mode', 'pg_fee_bearer',
            'tripay_api_key', 'tripay_private_key', 'tripay_merchant_code',
            'duitku_merchant_code', 'duitku_api_key',
            'midtrans_client_key', 'midtrans_server_key', 'midtrans_merchant_id',
            // WhatsApp
            'fonnte_token',
        ]);
        $data['enable_loyalty_points'] = $request->has('enable_loyalty_points');
        $data['enable_wa_notification'] = $request->has('enable_wa_notification');
        $data['pg_active'] = $data['pg_active'] ?? 'none';

        // Handle logo upload
        if ($request->hasFile('store_logo')) {
            $settings = Setting::getStoreSettings();

            // Delete old logo if exists
            if ($settings->store_logo && Storage::disk('public')->exists($settings->store_logo)) {
                Storage::disk('public')->delete($settings->store_logo);
            }

            // Store new logo
            $logoPath = $request->file('store_logo')->store('logos', 'public');
            $data['store_logo'] = $logoPath;
        }

        Setting::updateStoreSettings($data);

        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan berhasil disimpan');
    }

    /**
     * Test WhatsApp connection via Fonnte API.
     */
    public function testWhatsapp(Request $request)
    {
        $request->validate(['fonnte_token' => 'required|string']);

        $service = new WhatsappService($request->fonnte_token);
        $result = $service->testConnection();

        return response()->json($result);
    }
}
