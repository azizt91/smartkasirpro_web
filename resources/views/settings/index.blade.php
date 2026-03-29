@php
use Illuminate\Support\Facades\Storage;
@endphp
@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="pb-6 border-b-2 border-gray-200 mb-8">
            <h1 class="text-3xl font-bold text-gray-900">⚙️ Pengaturan Toko</h1>
            <p class="text-gray-600 mt-1">Kelola informasi umum dan branding toko Anda.</p>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="p-6 sm:p-8 space-y-8">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-3 mb-6">Informasi Utama</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="store_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Toko <span class="text-red-500">*</span></label>
                                <input type="text" id="store_name" name="store_name" value="{{ old('store_name', $settings->store_name) }}" required
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Contoh: Toko Barokah Jaya">
                                @error('store_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="business_mode" class="block text-sm font-medium text-gray-700 mb-1">Mode Bisnis <span class="text-red-500">*</span></label>
                                <select id="business_mode" name="business_mode" required
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                    <option value="retail" {{ old('business_mode', $settings->business_mode ?? 'retail') == 'retail' ? 'selected' : '' }}>🛒 Retail (Minimarket, Toko Kelontong, dll)</option>
                                    <option value="resto" {{ old('business_mode', $settings->business_mode ?? 'retail') == 'resto' ? 'selected' : '' }}>🍽️ Cafe / Resto (Dengan Fitur QR Meja)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Catatan: Mode Resto akan menyembunyikan piutang/utang di POS dan mengaktifkan opsi Manajemen Meja.</p>
                                @error('business_mode')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="store_phone" class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon</label>
                                <input type="text" id="store_phone" name="store_phone" value="{{ old('store_phone', $settings->store_phone) }}"
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="(021) 123-4567">
                                @error('store_phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="employee_label" class="block text-sm font-medium text-gray-700 mb-1">Label Sebutan Pegawai <span class="text-gray-500">(Maks 20 huruf)</span></label>
                                <input type="text" id="employee_label" name="employee_label" value="{{ old('employee_label', $settings->employee_label ?? 'Pegawai') }}" maxlength="20"
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Mekanik untuk Bengkel, Kapster untuk Barber, Terapis untuk Spa">
                                <p class="mt-1 text-xs text-gray-500">Label ini akan mengubah semua penamaan fitur jasa, struk, dan laporannya (Default: Pegawai).</p>
                                @error('employee_label')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="store_address" class="block text-sm font-medium text-gray-700 mb-1">Alamat Toko</label>
                                <textarea id="store_address" name="store_address" rows="3"
                                          class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                          placeholder="Jl. Pahlawan No. 123, Kota Bahagia">{{ old('store_address', $settings->store_address) }}</textarea>
                                @error('store_address')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="store_description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi/Footer Struk <span class="text-gray-500">(Opsional)</span></label>
                                <textarea id="store_description" name="store_description" rows="3"
                                          class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                          placeholder="Contoh: Terima kasih telah berbelanja! Barang yang sudah dibeli tidak dapat dikembalikan.">{{ old('store_description', $settings->store_description) }}</textarea>
                                @error('store_description')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            <div class="md:col-span-1">
                                <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1">Pajak Default (%)</label>
                                <div class="relative">
                                    <input type="number" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', $settings->tax_rate ?? 0) }}" step="0.1" min="0" max="100"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 pr-8"
                                           placeholder="0">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">%</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Nilai ini akan menjadi default pajak di halaman kasir.</p>
                                @error('tax_rate')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div x-data="{ 
                        logoPreview: null,
                        fileName: null,
                        handleFileUpload(event) {
                            const file = event.target.files[0];
                            if (file) {
                                this.fileName = file.name;
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    this.logoPreview = e.target.result;
                                };
                                reader.readAsDataURL(file);
                            }
                        }
                    }">
                        <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-3 mb-6">Branding Toko</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                            <div class="md:col-span-1">
                                <p class="block text-sm font-medium text-gray-700 mb-2">Logo Saat Ini</p>
                                <div class="aspect-square w-32 h-32 bg-gray-100 rounded-lg border flex items-center justify-center overflow-hidden">
                                    <template x-if="logoPreview">
                                        <img :src="logoPreview" alt="Logo Preview" class="w-full h-full object-contain p-2 rounded-lg">
                                    </template>
                                    <template x-if="!logoPreview">
                                        <div class="w-full h-full flex items-center justify-center">
                                            @if($settings->store_logo && Storage::disk('public')->exists($settings->store_logo))
                                                <img src="{{ Storage::url($settings->store_logo) }}" alt="Logo Toko" class="w-full h-full object-contain p-2 rounded-lg">
                                            @else
                                                <div class="text-center text-gray-500 text-xs p-2">
                                                    <svg class="w-8 h-8 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    <p class="mt-1">Belum ada logo</p>
                                                </div>
                                            @endif
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label for="store_logo" class="block text-sm font-medium text-gray-700 mb-2">Upload Logo Baru <span class="text-gray-500">(Opsional)</span></label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md transition-colors hover:border-indigo-400"
                                     :class="fileName ? 'border-indigo-500 bg-indigo-50' : ''">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <label for="store_logo_input" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500 px-2">
                                                <span>Upload file</span>
                                                <input id="store_logo_input" name="store_logo" type="file" class="sr-only" accept="image/png, image/jpeg, image/gif" @change="handleFileUpload($event)">
                                            </label>
                                        </div>
                                        <p class="text-xs text-gray-500" x-text="fileName ? 'File terpilih: ' + fileName : 'PNG, JPG, GIF hingga 2MB'"></p>
                                    </div>
                                </div>
                                @error('store_logo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-3 mb-6">Program Loyalitas (Poin)</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                            <div class="md:col-span-1">
                                <label for="point_earning_rate" class="block text-sm font-medium text-gray-700 mb-1">Nilai Belanja per 1 Poin (Rp) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                    </div>
                                    <input type="number" id="point_earning_rate" name="point_earning_rate" value="{{ old('point_earning_rate', $settings->point_earning_rate ?? 10000) }}" min="1" required
                                           class="w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="10000">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Contoh: 10000 (Pelanggan mendapat 1 poin setiap belanja kelipatan Rp 10.000)</p>
                                @error('point_earning_rate')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            <div class="md:col-span-1">
                                <label for="point_exchange_rate" class="block text-sm font-medium text-gray-700 mb-1">Nilai Tukar 1 Poin (Rp) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                    </div>
                                    <input type="number" id="point_exchange_rate" name="point_exchange_rate" value="{{ old('point_exchange_rate', $settings->point_exchange_rate ?? 100) }}" min="1" required
                                           class="w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="100">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Contoh: 100 (1 Poin dapat ditukar diskon senilai Rp 100)</p>
                                @error('point_exchange_rate')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            <div class="md:col-span-2 mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900">Aktifkan Fitur Poin</h3>
                                    <p class="text-xs text-gray-500 mt-1">Matikan fitur ini jika toko tidak memerlukan sistem poin loyalitas.</p>
                                </div>
                                
                                {{-- Menggunakan Alpine.js untuk kepastian Toggle Rendering UI --}}
                                <div x-data="{ enabled: {{ old('enable_loyalty_points', $settings->enable_loyalty_points) ? 'true' : 'false' }} }" class="flex items-center">
                                    <input type="hidden" name="enable_loyalty_points" :value="enabled ? '1' : '0'">
                                    
                                    <button 
                                        type="button" 
                                        @click="enabled = !enabled"
                                        :class="enabled ? 'bg-indigo-600' : 'bg-gray-200'"
                                        class="relative inline-flex h-7 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" 
                                        role="switch" 
                                        :aria-checked="enabled">
                                        <span class="sr-only">Use loyalty points</span>
                                        <span 
                                            :class="enabled ? 'translate-x-7' : 'translate-x-0'"
                                            class="pointer-events-none relative inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============================================= --}}
                    {{-- PAYMENT GATEWAY SECTION --}}
                    {{-- ============================================= --}}
                    <div x-data="{ pgActive: '{{ old('pg_active', $settings->pg_active ?? 'none') }}' }">
                        <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-3 mb-6">
                            <span class="inline-flex items-center gap-2">💳 Payment Gateway</span>
                        </h2>

                        <p class="text-sm text-gray-500 mb-6">
                            Integrasikan pembayaran digital (QRIS, Transfer Bank, E-Wallet) ke POS Anda. Pilih salah satu provider aktif.
                        </p>

                        {{-- Mode & Fee Bearer --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mode Gateway</label>
                                <select name="pg_mode" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="sandbox" {{ old('pg_mode', $settings->pg_mode ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>🧪 Sandbox (Testing)</option>
                                    <option value="production" {{ old('pg_mode', $settings->pg_mode ?? 'sandbox') == 'production' ? 'selected' : '' }}>🚀 Production (Live)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Biaya Admin Dibebankan Ke</label>
                                <select name="pg_fee_bearer" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="customer" {{ old('pg_fee_bearer', $settings->pg_fee_bearer ?? 'customer') == 'customer' ? 'selected' : '' }}>👤 Pelanggan</option>
                                    <option value="merchant" {{ old('pg_fee_bearer', $settings->pg_fee_bearer ?? 'customer') == 'merchant' ? 'selected' : '' }}>🏪 Toko (Merchant)</option>
                                </select>
                            </div>
                        </div>

                        {{-- Provider Selection Cards --}}
                        <div class="space-y-4">
                            {{-- NONE --}}
                            <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer transition-all"
                                   :class="pgActive === 'none' ? 'border-gray-400 bg-gray-50 ring-1 ring-gray-400' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="pg_active" value="none" x-model="pgActive"
                                       class="text-gray-600 focus:ring-gray-500">
                                <div>
                                    <span class="font-medium text-gray-700">Nonaktif</span>
                                    <p class="text-xs text-gray-500">Tidak menggunakan payment gateway digital</p>
                                </div>
                            </label>

                            {{-- TRIPAY --}}
                            <div class="border rounded-xl transition-all"
                                 :class="pgActive === 'tripay' ? 'border-blue-400 bg-blue-50/30 ring-1 ring-blue-400' : 'border-gray-200 hover:border-gray-300'">
                                <label class="flex items-center gap-3 p-4 cursor-pointer">
                                    <input type="radio" name="pg_active" value="tripay" x-model="pgActive"
                                           class="text-blue-600 focus:ring-blue-500">
                                    <div class="flex-1">
                                        <span class="font-semibold text-gray-900">Tripay</span>
                                        <p class="text-xs text-gray-500">QRIS, Virtual Account, E-Wallet — <a href="https://tripay.co.id" target="_blank" class="text-blue-600 underline">tripay.co.id</a></p>
                                    </div>
                                </label>
                                <div x-show="pgActive === 'tripay'" x-transition class="px-4 pb-4 pt-1 border-t border-blue-100 space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">API Key</label>
                                        <input type="text" name="tripay_api_key" value="{{ old('tripay_api_key', $settings->tripay_api_key) }}"
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono"
                                               placeholder="DEV-xxx...">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Private Key</label>
                                        <input type="password" name="tripay_private_key" value="{{ old('tripay_private_key', $settings->tripay_private_key) }}"
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono"
                                               placeholder="xxx...">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Merchant Code</label>
                                        <input type="text" name="tripay_merchant_code" value="{{ old('tripay_merchant_code', $settings->tripay_merchant_code) }}"
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono"
                                               placeholder="T12345">
                                    </div>
                                </div>
                            </div>

                            {{-- DUITKU --}}
                            <div class="border rounded-xl transition-all"
                                 :class="pgActive === 'duitku' ? 'border-green-400 bg-green-50/30 ring-1 ring-green-400' : 'border-gray-200 hover:border-gray-300'">
                                <label class="flex items-center gap-3 p-4 cursor-pointer">
                                    <input type="radio" name="pg_active" value="duitku" x-model="pgActive"
                                           class="text-green-600 focus:ring-green-500">
                                    <div class="flex-1">
                                        <span class="font-semibold text-gray-900">Duitku</span>
                                        <p class="text-xs text-gray-500">QRIS, Virtual Account, E-Wallet — <a href="https://duitku.com" target="_blank" class="text-green-600 underline">duitku.com</a></p>
                                    </div>
                                </label>
                                <div x-show="pgActive === 'duitku'" x-transition class="px-4 pb-4 pt-1 border-t border-green-100 space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Merchant Code</label>
                                        <input type="text" name="duitku_merchant_code" value="{{ old('duitku_merchant_code', $settings->duitku_merchant_code) }}"
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm font-mono"
                                               placeholder="DXXXX">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">API Key</label>
                                        <input type="password" name="duitku_api_key" value="{{ old('duitku_api_key', $settings->duitku_api_key) }}"
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm font-mono"
                                               placeholder="xxx...">
                                    </div>
                                </div>
                            </div>

                            {{-- MIDTRANS --}}
                            <div class="border rounded-xl transition-all"
                                 :class="pgActive === 'midtrans' ? 'border-yellow-400 bg-yellow-50/30 ring-1 ring-yellow-400' : 'border-gray-200 hover:border-gray-300'">
                                <label class="flex items-center gap-3 p-4 cursor-pointer">
                                    <input type="radio" name="pg_active" value="midtrans" x-model="pgActive"
                                           class="text-yellow-600 focus:ring-yellow-500">
                                    <div class="flex-1">
                                        <span class="font-semibold text-gray-900">Midtrans</span>
                                        <p class="text-xs text-gray-500">GoPay, ShopeePay, VA — <a href="https://midtrans.com" target="_blank" class="text-yellow-600 underline">midtrans.com</a></p>
                                    </div>
                                </label>
                                <div x-show="pgActive === 'midtrans'" x-transition class="px-4 pb-4 pt-1 border-t border-yellow-100 space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Client Key</label>
                                        <input type="text" name="midtrans_client_key" value="{{ old('midtrans_client_key', $settings->midtrans_client_key) }}"
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 text-sm font-mono"
                                               placeholder="SB-Mid-client-xxx">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Server Key</label>
                                        <input type="password" name="midtrans_server_key" value="{{ old('midtrans_server_key', $settings->midtrans_server_key) }}"
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 text-sm font-mono"
                                               placeholder="SB-Mid-server-xxx">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Merchant ID</label>
                                        <input type="text" name="midtrans_merchant_id" value="{{ old('midtrans_merchant_id', $settings->midtrans_merchant_id) }}"
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 text-sm font-mono"
                                               placeholder="G123456789">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Webhook Info Box --}}
                        <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                            <p class="text-xs text-amber-800">
                                <strong>ℹ️ Callback URL:</strong> Salin URL berikut ke dashboard provider Anda:<br>
                                <code class="text-xs bg-amber-100 px-1 rounded">{{ url('/payment/callback/tripay') }}</code> (Tripay)<br>
                                <code class="text-xs bg-amber-100 px-1 rounded">{{ url('/payment/callback/duitku') }}</code> (Duitku)<br>
                                <code class="text-xs bg-amber-100 px-1 rounded">{{ url('/payment/callback/midtrans') }}</code> (Midtrans)
                            </p>
                        </div>
                    </div>

                    {{-- ============================================= --}}
                    {{-- WHATSAPP GATEWAY (FONNTE) SECTION --}}
                    {{-- ============================================= --}}
                    <div x-data="{ 
                        waEnabled: {{ old('enable_wa_notification', $settings->enable_wa_notification ?? false) ? 'true' : 'false' }},
                        testLoading: false,
                        testResult: null 
                    }">
                        <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-3 mb-6">
                            <span class="inline-flex items-center gap-2">💬 WhatsApp Gateway (Fonnte)</span>
                        </h2>

                        <p class="text-sm text-gray-500 mb-6">
                            Kirim notifikasi otomatis ke WhatsApp pelanggan saat transaksi digital pending dan setelah pembayaran berhasil.
                            Daftar akun di <a href="https://fonnte.com" target="_blank" class="text-green-600 underline font-medium">fonnte.com</a>.
                        </p>

                        {{-- Toggle Enable --}}
                        <div class="mb-6 flex items-center justify-between p-4 bg-green-50/50 border border-green-100 rounded-xl">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Aktifkan Notifikasi WhatsApp</h3>
                                <p class="text-xs text-gray-500 mt-1">Pesan WA otomatis dikirim saat transaksi Payment Gateway pending & berhasil.</p>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="enable_wa_notification" :value="waEnabled ? '1' : '0'">
                                <button 
                                    type="button" 
                                    @click="waEnabled = !waEnabled"
                                    :class="waEnabled ? 'bg-green-600' : 'bg-gray-200'"
                                    class="relative inline-flex h-7 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2" 
                                    role="switch" 
                                    :aria-checked="waEnabled">
                                    <span class="sr-only">Enable WA Notification</span>
                                    <span 
                                        :class="waEnabled ? 'translate-x-7' : 'translate-x-0'"
                                        class="pointer-events-none relative inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                    </span>
                                </button>
                            </div>
                        </div>

                        {{-- Token Input --}}
                        <div x-show="waEnabled" x-transition class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fonnte API Token</label>
                                <input type="password" name="fonnte_token" id="fonnte_token"
                                       value="{{ old('fonnte_token', $settings->fonnte_token) }}"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm font-mono"
                                       placeholder="Masukkan token dari dashboard Fonnte...">
                                <p class="mt-1 text-xs text-gray-500">Dapatkan token di menu <strong>Device</strong> pada dashboard <a href="https://md.fonnte.com" target="_blank" class="text-green-600 underline">md.fonnte.com</a></p>
                                @error('fonnte_token')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            {{-- Test Connection Button --}}
                            <div class="flex items-center gap-3">
                                <button type="button"
                                        @click="testWaConnection()"
                                        :disabled="testLoading"
                                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-wait transition-all shadow-sm">
                                    <template x-if="testLoading">
                                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </template>
                                    <template x-if="!testLoading">
                                        <span>📡</span>
                                    </template>
                                    <span x-text="testLoading ? 'Mengecek...' : 'Test Koneksi WA'"></span>
                                </button>

                                <template x-if="testResult !== null">
                                    <span :class="testResult.success ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium" x-text="testResult.success ? '✅ ' + testResult.detail : '❌ ' + testResult.detail"></span>
                                </template>
                            </div>
                        </div>

                        {{-- Info Box --}}
                        <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg" x-show="waEnabled" x-transition>
                            <p class="text-xs text-green-800">
                                <strong>ℹ️ Catatan:</strong> Notifikasi WA hanya dikirim ke pelanggan yang terdaftar dengan nomor HP. 
                                Pelanggan berkategori "Umum" atau tanpa nomor HP akan otomatis dilewati (skip). 
                            </p>
                        </div>
                    </div>

                    
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-3 mb-6">Printer Struk</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            {{-- USB Printer Info Card --}}
                            <div class="md:col-span-2 bg-indigo-50 border border-indigo-100 rounded-xl p-5 flex flex-col sm:flex-row items-start gap-4">
                                <div class="p-3 bg-white rounded-lg shadow-sm text-2xl flex-shrink-0">
                                    🖨️
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-indigo-900 text-lg">Printer Thermal USB</h3>
                                    <p class="text-sm text-indigo-700 mt-1 leading-relaxed">
                                        Fitur ini membutuhkan browser <strong>Google Chrome</strong> atau <strong>Microsoft Edge</strong> di PC/Laptop. 
                                        Pastikan kabel USB printer thermal sudah terhubung dan printer dalam keadaan menyala.
                                    </p>
                                    <div class="mt-4 flex items-center gap-3 bg-white/50 px-3 py-2 rounded-lg w-fit border border-indigo-100">
                                        <div id="usb-status-indicator" class="w-3 h-3 rounded-full bg-gray-300 shadow-sm transition-all duration-300"></div>
                                        <span id="usb-status-text" class="text-sm font-semibold text-gray-600 transition-all duration-300">Menunggu status...</span>
                                    </div>
                                </div>
                            </div>

                            {{-- USB Actions --}}
                            <div class="md:col-span-1 bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="text-xl">🔌</span>
                                    <h3 class="font-semibold text-gray-900">Koneksi USB</h3>
                                </div>
                                <div class="space-y-3">
                                    <button type="button" onclick="testPrintUSB()" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:border-gray-300 transition-all flex items-center justify-center gap-2 group">
                                        <span class="group-hover:scale-110 transition-transform">📄</span> Test Print USB
                                    </button>
                                    <button type="button" onclick="ThermalPrinter.disconnectUSB(); checkUSBStatus();" class="w-full px-4 py-2.5 bg-white border border-red-200 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 hover:border-red-300 transition-all flex items-center justify-center gap-2 group">
                                        <span class="group-hover:scale-110 transition-transform">🚫</span> Putuskan Koneksi
                                    </button>
                                </div>
                                <p class="mt-3 text-xs text-gray-500 text-center">
                                    Klik "Test Print" untuk memicu permintaan izin akses USB.
                                </p>
                            </div>

                            {{-- Bluetooth Actions --}}
                            <div class="md:col-span-1 bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="text-xl">📶</span>
                                    <h3 class="font-semibold text-gray-900">Koneksi Bluetooth</h3>
                                </div>
                                <div class="space-y-3">
                                    <button type="button" onclick="testPrintBluetooth()" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 hover:border-gray-300 transition-all flex items-center justify-center gap-2 group">
                                        <span class="group-hover:scale-110 transition-transform">📄</span> Test Print Bluetooth
                                    </button>
                                </div>
                                <p class="mt-3 text-xs text-gray-500 text-center leading-relaxed">
                                    Hanya didukung di Android (Chrome/Edge) atau perangkat dengan Web Bluetooth API aktif.
                                </p>
                            </div>

                            {{-- Preferences --}}
                            <div class="md:col-span-2 mt-2">
                                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                                    <div class="flex items-start gap-3">
                                        <div class="p-2 bg-white rounded-lg border border-gray-200 text-gray-500">
                                            ⚙️
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900">Reset Preferensi Cetak</h4>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                Dialog "Pilih Metode Cetak" akan muncul kembali saat mencetak struk jika Anda mereset ini.
                                            </p>
                                        </div>
                                    </div>
                                    <button type="button" onclick="resetPrinterPref()" class="px-5 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 hover:shadow-sm transition-all whitespace-nowrap">
                                        Reset Default
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                    </div>
                </div>

                <div class="px-6 sm:px-8 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="w-full sm:w-auto flex items-center justify-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-md transition-transform transform hover:scale-105 duration-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>

        @if(session('success'))
            <div id="success-toast" class="fixed bottom-5 right-5 flex items-center w-full max-w-xs p-4 text-gray-500 bg-white rounded-lg shadow-lg" role="alert">
                <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                </div>
                <div class="ml-3 text-sm font-normal">{{ session('success') }}</div>
                <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8" onclick="document.getElementById('success-toast').style.display='none'">
                    <span class="sr-only">Close</span>
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
            </div>
            <script>
                setTimeout(() => {
                    const toast = document.getElementById('success-toast');
                    if(toast) toast.style.display = 'none';
                }, 5000);
            </script>
        @endif

    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/thermal-printer.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        checkUSBStatus();
        
        // Check WebUSB support
        if (!ThermalPrinter.isUSBSupported()) {
            document.getElementById('usb-status-text').textContent = 'Browser ini tidak mendukung WebUSB';
            document.getElementById('usb-status-text').className = 'text-sm font-medium text-red-500';
            document.getElementById('usb-status-indicator').className = 'w-3 h-3 rounded-full bg-red-500';
        }

        // Monitor USB connection changes (if supported)
        if (navigator.usb) {
            navigator.usb.addEventListener('connect', checkUSBStatus);
            navigator.usb.addEventListener('disconnect', checkUSBStatus);
        }
    });

    async function checkUSBStatus() {
        if (!ThermalPrinter.isUSBSupported()) return;

        const devices = await navigator.usb.getDevices();
        const printer = devices.find(d => d.productName && d.productName.toLowerCase().includes('print') || d.classCode === 7); // Simple heuristic
        
        const indicator = document.getElementById('usb-status-indicator');
        const text = document.getElementById('usb-status-text');

        if (devices.length > 0) {
            // Kita asumsikan ada device yang pernah dipair
            indicator.className = 'w-3 h-3 rounded-full bg-green-500 animate-pulse';
            text.textContent = 'Perangkat Terdeteksi (' + devices.length + ')';
            text.className = 'text-sm font-medium text-green-700';
        } else {
            indicator.className = 'w-3 h-3 rounded-full bg-gray-300';
            text.textContent = 'Belum ada perangkat terhubung';
            text.className = 'text-sm font-medium text-gray-600';
        }
    }

    async function testPrintUSB() {
        try {
            await ThermalPrinter.testPrintUSB();
            checkUSBStatus();
            alert('✅ Test Print USB Berhasil!');
        } catch (error) {
            console.error(error);
            alert('❌ Gagal: ' + error.message);
        }
    }

    async function testPrintBluetooth() {
        try {
            const ESC = '\x1B', GS = '\x1D';
            let data = ESC + '@' + 'TEST PRINT BLUETOOTH\n' + '================\n\n\n' + GS + 'V' + '\x41' + '\x03';
            await ThermalPrinter.printBluetooth(new TextEncoder().encode(data));
            alert('✅ Test Print Bluetooth Berhasil!');
        } catch (error) {
            console.error(error);
            alert('❌ Gagal: ' + error.message);
        }
    }

    function resetPrinterPref() {
        localStorage.removeItem('pos_printer_preference');
        alert('✅ Preferensi printer di-reset. Dialog pilih printer akan muncul lagi.');
    }

    // WhatsApp Test Connection
    async function testWaConnection() {
        const token = document.getElementById('fonnte_token').value;
        if (!token) {
            alert('Masukkan token Fonnte terlebih dahulu.');
            return;
        }

        // Get Alpine.js data scope
        const el = document.querySelector('[x-data*="waEnabled"]');
        const scope = Alpine.$data(el);
        scope.testLoading = true;
        scope.testResult = null;

        try {
            const response = await fetch('{{ route("settings.test-whatsapp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ fonnte_token: token }),
            });
            const data = await response.json();
            scope.testResult = data;
        } catch (error) {
            scope.testResult = { success: false, detail: 'Network error: ' + error.message };
        } finally {
            scope.testLoading = false;
        }
    }
</script>
@endsection



