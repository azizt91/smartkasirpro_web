@extends('layouts.app')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tutup Kasir (Cetak X-Report)') }}
        </h2>
        <a href="{{ route('pos.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
            &larr; Kembali ke Kasir
        </a>
    </div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row gap-6">
        
        <!-- Kolom Kiri: Laporan Shift (X-Report) -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full md:w-1/2">
            <div class="p-6 text-gray-900">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">Ringkasan Penjualan Shift</h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Waktu Buka:</span>
                        <span class="font-medium">{{ $shift->start_time->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total Transaksi:</span>
                        <span class="font-medium">{{ $totalTransactions }} struk</span>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <h4 class="font-semibold text-gray-700">Rincian Pembayaran Masuk</h4>
                    <div class="flex justify-between items-center bg-gray-50 p-2 rounded">
                        <span class="text-gray-600">Tunai (Cash)</span>
                        <span class="font-medium text-green-600">Rp {{ number_format($cashSales, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-gray-50 p-2 rounded">
                        <span class="text-gray-600">Kartu Debit</span>
                        <span class="font-medium">Rp {{ number_format($debitSales, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-gray-50 p-2 rounded">
                        <span class="text-gray-600">QRIS / E-Wallet</span>
                        <span class="font-medium">Rp {{ number_format($qrisSales, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-gray-50 p-2 rounded">
                        <span class="text-gray-600">Transfer Bank</span>
                        <span class="font-medium">Rp {{ number_format($transferSales, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center border-t pt-2 mt-2 font-bold text-lg">
                        <span>Total Pendapatan</span>
                        <span>Rp {{ number_format($totalSales, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="mt-8 text-center sm:text-left">
                    <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        🖨️ Cetak Ringkasan (X-Report)
                    </button>
                    <p class="text-xs text-gray-500 mt-2">Cetak laporan ini sebelum menyeimbangkan laci kasir.</p>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Form Tutup Kasir -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full md:w-1/2">
            <div class="p-6 text-gray-900">
                <h3 class="text-lg font-bold text-red-600 border-b pb-2 mb-4">Penutupan Kasir</h3>

                <form action="{{ route('pos.shift.update') }}" method="POST">
                    @csrf
                    
                    <!-- Hidden Expected -->
                    <input type="hidden" name="expected_cash" value="{{ $expectedCash }}">

                    <div class="mb-4 bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <p class="text-sm text-blue-800 font-medium">Modal Awal di Laci: Rp {{ number_format($shift->starting_cash, 0, ',', '.') }}</p>
                        <p class="text-sm text-blue-800 font-medium mt-1">Penjualan Tunai: Rp {{ number_format($cashSales, 0, ',', '.') }}</p>
                        <div class="border-t border-blue-200 my-2"></div>
                        <p class="text-lg text-blue-900 font-bold">Estimasi Uang Fisik Seharusnya:</p>
                        <p class="text-2xl text-blue-900 font-black">Rp {{ number_format($expectedCash, 0, ',', '.') }}</p>
                    </div>

                    <div class="mb-5">
                        <label for="actual_cash" class="block text-sm font-bold text-gray-700">Uang Fisik Aktual di Laci Saat Ini (Rp) <span class="text-red-500">*</span></label>
                        <p class="text-xs text-gray-500 mb-2">Hitung seluruh uang tunai yang ada di dalam laci kasir secara manual, lalu masukkan jumlahnya di bawah ini.</p>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <input type="number" name="actual_cash" id="actual_cash" required min="0" 
                                class="focus:ring-red-500 focus:border-red-500 block w-full pl-12 pr-12 sm:text-lg border-gray-300 rounded-md py-3 font-bold text-gray-900" 
                                placeholder="0">
                        </div>
                        @error('actual_cash')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Catatan Selisih (Opsional)</label>
                        <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Tulis catatan jika uang fisik tidak sesuai estimasi..."></textarea>
                    </div>

                    <div class="flex items-center">
                        <button type="submit" onclick="return confirm('Apakah Anda yakin hitungan sudah benar dan siap menutup kasir? Transaksi selama shift ini akan direkap permanently.')" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Akhiri & Tutup Shift
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<style type="text/css" media="print">
    body * {
        visibility: hidden;
    }
    .w-full.md\:w-1\/2:first-child, .w-full.md\:w-1\/2:first-child * {
        visibility: visible;
    }
    .w-full.md\:w-1\/2:first-child {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        border: none !important;
    }
    button {
        display: none !important;
    }
</style>
@endsection
