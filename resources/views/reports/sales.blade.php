@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-4">
            <div class="flex items-center space-x-4">
                <a href="{{ route('reports.index') }}" class="text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">📊 Laporan Penjualan</h1>
                    <p class="text-gray-600 mt-1">Analisis transaksi dan pendapatan</p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            
            <!-- Filter Form -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('reports.sales') }}" class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-end gap-4">
                        <div class="w-full sm:flex-1 min-w-48">
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" 
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="w-full sm:flex-1 min-w-48">
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                            <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" 
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="flex gap-2 w-full sm:w-auto">
                            <button type="submit" 
                                    class="w-full justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Export Buttons -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-4">
                        <h3 class="text-lg font-semibold text-gray-900">Export Laporan</h3>
                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                            <a href="{{ route('reports.sales', array_merge(request()->query(), ['format' => 'pdf'])) }}" 
                               class="inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download PDF
                            </a>
                            <a href="{{ route('reports.sales', array_merge(request()->query(), ['format' => 'excel'])) }}" 
                               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download Excel
                            </a>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600">
                        Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    </p>
                </div>
            </div>

            <!-- Transactions Cards -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Detail Transaksi</h3>
                    <div class="md:hidden space-y-4">
                        @forelse($transactions as $transaction)
                            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $transaction->transaction_code }}</p>
                                        <p class="text-sm text-gray-500">{{ $transaction->created_at->format('d M Y, H:i') }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if($transaction->payment_method === 'cash') bg-green-100 text-green-800
                                        @elseif($transaction->payment_method === 'card') bg-blue-100 text-blue-800
                                        @elseif($transaction->payment_method === 'ewallet') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($transaction->payment_method) }}
                                    </span>
                                </div>

                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="text-sm text-gray-500">Total</p>
                                        <p class="text-lg font-bold text-green-600">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="text-sm space-y-1 text-gray-600">
                                        <div class="flex justify-between"><p>Subtotal:</p> <p>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</p></div>
                                        <div class="flex justify-between"><p>Diskon:</p> <p class="text-orange-600">- Rp {{ number_format($transaction->discount, 0, ',', '.') }}</p></div>
                                        <div class="flex justify-between"><p>Pajak:</p> <p>+ Rp {{ number_format($transaction->tax, 0, ',', '.') }}</p></div>
                                        <div class="flex justify-between border-t mt-2 pt-2"><p>Kasir:</p> <p class="font-medium">{{ $transaction->user->name }}</p></div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada transaksi</h3>
                                <p class="mt-1 text-sm text-gray-500">Tidak ada transaksi pada periode yang dipilih.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Desktop Table -->
                    <div class="overflow-x-auto hidden md:block">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Transaksi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kasir</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diskon</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pajak</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pembayaran</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($transactions as $transaction)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $transaction->transaction_code }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transaction->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transaction->user->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-orange-600">
                                            Rp {{ number_format($transaction->discount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-600">
                                            Rp {{ number_format($transaction->tax, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                            Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($transaction->payment_method === 'cash') bg-green-100 text-green-800
                                                @elseif($transaction->payment_method === 'card') bg-blue-100 text-blue-800
                                                @elseif($transaction->payment_method === 'ewallet') bg-purple-100 text-purple-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($transaction->payment_method) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada transaksi</h3>
                                            <p class="mt-1 text-sm text-gray-500">Tidak ada transaksi pada periode yang dipilih.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="md:hidden space-y-4">
                        @forelse($transactions as $transaction)
                            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $transaction->transaction_code }}</p>
                                        <p class="text-sm text-gray-500">{{ $transaction->created_at->format('d M Y, H:i') }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if($transaction->payment_method === 'cash') bg-green-100 text-green-800
                                        @elseif($transaction->payment_method === 'card') bg-blue-100 text-blue-800
                                        @elseif($transaction->payment_method === 'ewallet') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($transaction->payment_method) }}
                                    </span>
                                </div>

                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="text-sm text-gray-500">Total</p>
                                        <p class="text-lg font-bold text-green-600">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="text-sm space-y-1 text-gray-600">
                                        <div class="flex justify-between"><p>Subtotal:</p> <p>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</p></div>
                                        <div class="flex justify-between"><p>Diskon:</p> <p class="text-orange-600">- Rp {{ number_format($transaction->discount, 0, ',', '.') }}</p></div>
                                        <div class="flex justify-between"><p>Pajak:</p> <p>+ Rp {{ number_format($transaction->tax, 0, ',', '.') }}</p></div>
                                        <div class="flex justify-between border-t mt-2 pt-2"><p>Kasir:</p> <p class="font-medium">{{ $transaction->user->name }}</p></div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada transaksi</h3>
                                <p class="mt-1 text-sm text-gray-500">Tidak ada transaksi pada periode yang dipilih.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Mobile Cards -->
                    <div class="md:hidden space-y-4">
                        @forelse($transactions as $transaction)
                            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $transaction->transaction_code }}</p>
                                        <p class="text-sm text-gray-500">{{ $transaction->created_at->format('d M Y, H:i') }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if($transaction->payment_method === 'cash') bg-green-100 text-green-800
                                        @elseif($transaction->payment_method === 'card') bg-blue-100 text-blue-800
                                        @elseif($transaction->payment_method === 'ewallet') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($transaction->payment_method) }}
                                    </span>
                                </div>

                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="text-sm text-gray-500">Total</p>
                                        <p class="text-lg font-bold text-green-600">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="text-sm space-y-1 text-gray-600">
                                        <div class="flex justify-between"><p>Subtotal:</p> <p>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</p></div>
                                        <div class="flex justify-between"><p>Diskon:</p> <p class="text-orange-600">- Rp {{ number_format($transaction->discount, 0, ',', '.') }}</p></div>
                                        <div class="flex justify-between"><p>Pajak:</p> <p>+ Rp {{ number_format($transaction->tax, 0, ',', '.') }}</p></div>
                                        <div class="flex justify-between border-t mt-2 pt-2"><p>Kasir:</p> <p class="font-medium">{{ $transaction->user->name }}</p></div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada transaksi</h3>
                                <p class="mt-1 text-sm text-gray-500">Tidak ada transaksi pada periode yang dipilih.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
