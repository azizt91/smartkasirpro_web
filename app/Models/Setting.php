<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'store_name',
        'business_mode',
        'store_address',
        'store_phone',
        'store_logo',
        'store_description',
        'tax_rate',
        'point_earning_rate',
        'point_exchange_rate',
        'employee_label',
        'enable_loyalty_points',
        // Payment Gateway
        'pg_active',
        'pg_mode',
        'pg_fee_bearer',
        'tripay_api_key',
        'tripay_private_key',
        'tripay_merchant_code',
        'duitku_merchant_code',
        'duitku_api_key',
        'midtrans_client_key',
        'midtrans_server_key',
        'midtrans_merchant_id',
        // WhatsApp Notification (Fonnte)
        'fonnte_token',
        'enable_wa_notification',
    ];

    /**
     * Get the store settings (singleton pattern)
     */
    public static function getStoreSettings()
    {
        $settings = self::first();

        if (!$settings) {
            $settings = self::create([
                'store_name' => 'SmartKasir Pro',
                'store_address' => 'Jl. Contoh No. 123',
                'store_phone' => '(021) 1234567',
                'store_description' => 'Sistem Point of Sale modern untuk mengelola bisnis minimarket Anda dengan mudah dan efisien',
                'enable_loyalty_points' => true
            ]);
        }

        return $settings;
    }

    /**
     * Update store settings
     */
    public static function updateStoreSettings($data)
    {
        $settings = self::getStoreSettings();
        $settings->update($data);
        return $settings;
    }
}
