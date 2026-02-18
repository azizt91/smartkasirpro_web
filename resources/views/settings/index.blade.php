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
                                <label for="store_phone" class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon</label>
                                <input type="text" id="store_phone" name="store_phone" value="{{ old('store_phone', $settings->store_phone) }}"
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="(021) 123-4567">
                                @error('store_phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
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

                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-3 mb-6">Branding Toko</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                            <div class="md:col-span-1">
                                <p class="block text-sm font-medium text-gray-700 mb-2">Logo Saat Ini</p>
                                <div class="aspect-square w-32 h-32 bg-gray-100 rounded-lg border flex items-center justify-center">
                                    @if($settings->store_logo && Storage::disk('public')->exists($settings->store_logo))
                                        <img src="{{ Storage::url($settings->store_logo) }}" alt="Logo Toko" class="w-full h-full object-contain p-2 rounded-lg">
                                    @else
                                        <div class="text-center text-gray-500 text-xs p-2">
                                            <svg class="w-8 h-8 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <p class="mt-1">Belum ada logo</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label for="store_logo" class="block text-sm font-medium text-gray-700 mb-2">Upload Logo Baru <span class="text-gray-500">(Opsional)</span></label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600">
                                            <label for="store_logo_input" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                <span>Upload file</span>
                                                <input id="store_logo_input" name="store_logo" type="file" class="sr-only">
                                            </label>
                                            <p class="pl-1">atau tarik dan lepas</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, GIF hingga 2MB</p>
                                    </div>
                                </div>
                                @error('store_logo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
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
</script>
@endsection



