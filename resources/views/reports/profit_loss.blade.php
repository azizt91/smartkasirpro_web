@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between pb-6 border-b-2 border-gray-200">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">📈 Laporan Laba Rugi</h1>
                <p class="text-gray-600 mt-1">Statistik pendapatan dan beban operasional toko.</p>
            </div>
        </div>

<div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6 mt-6">
    <form action="{{ route('reports.profit_loss') }}" method="GET">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <!-- Start Date -->
            <div class="md:col-span-5">
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <input type="date" name="start_date" id="start_date" class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ $startDate }}" required>
                </div>
            </div>
            
            <!-- End Date -->
            <div class="md:col-span-5">
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <input type="date" name="end_date" id="end_date" class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ $endDate }}" required>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="md:col-span-2">
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Buat Laporan
                </button>
            </div>
        </div>
    </form>
</div>

<div class="flex justify-center mt-8">
    <div class="w-full lg:w-2/3">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-5 border-b border-gray-200 text-center">
                <h2 class="text-xl font-bold text-indigo-700 tracking-wide uppercase">Laporan Laba Rugi</h2>
                <p class="text-sm text-gray-500 mt-1">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
            </div>
            <div class="p-0">
                <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- PENDAPATAN -->
                        <tr class="bg-gray-100"><td colspan="2" class="px-6 py-3 text-left text-sm font-bold text-gray-900 uppercase tracking-wider">Pendapatan</td></tr>
                        @forelse($revenues as $rev)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700 pl-10">{{ $rev['name'] }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">Rp {{ number_format($rev['balance'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500 italic">Tidak ada data pendapatan</td></tr>
                        @endforelse
                        <tr class="bg-green-50 border-t border-green-100">
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-green-800 text-right">Total Pendapatan</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-green-700 text-right">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
                        </tr>

                        <!-- HARGA POKOK PENJUALAN -->
                        <tr class="bg-gray-100 border-t-2 border-gray-200"><td colspan="2" class="px-6 py-3 text-left text-sm font-bold text-gray-900 uppercase tracking-wider">Harga Pokok Penjualan (HPP)</td></tr>
                        @forelse($cogs as $cog)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700 pl-10">{{ $cog['name'] }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">Rp {{ number_format($cog['balance'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500 italic">Tidak ada data HPP</td></tr>
                        @endforelse
                        <tr class="bg-red-50 border-t border-red-100">
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-red-800 text-right">Total HPP</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-red-700 text-right">(Rp {{ number_format($totalCogs, 0, ',', '.') }})</td>
                        </tr>

                        <!-- LABA KOTOR -->
                        <tr class="bg-indigo-50 border-y-2 border-indigo-200">
                            <td class="px-6 py-4 whitespace-nowrap text-base font-bold text-indigo-900 text-right">LABA KOTOR</td>
                            <td class="px-6 py-4 whitespace-nowrap text-base font-bold text-right {{ $grossProfit < 0 ? 'text-red-600' : 'text-indigo-700' }}">
                                Rp {{ number_format($grossProfit, 0, ',', '.') }}
                            </td>
                        </tr>

                        <!-- BEBAN OPERASIONAL -->
                        <tr class="bg-gray-100 border-t-2 border-gray-200"><td colspan="2" class="px-6 py-3 text-left text-sm font-bold text-gray-900 uppercase tracking-wider">Beban Operasional</td></tr>
                        @forelse($expenses as $exp)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700 pl-10">{{ $exp['name'] }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">Rp {{ number_format($exp['balance'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500 italic">Tidak ada data beban</td></tr>
                        @endforelse
                        <tr class="bg-red-50 border-t border-red-100">
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-red-800 text-right">Total Beban Operasional</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-red-700 text-right">(Rp {{ number_format($totalExpense, 0, ',', '.') }})</td>
                        </tr>

                        <!-- LABA BERSIH -->
                        <tr class="{{ $netProfit < 0 ? 'bg-red-600' : 'bg-green-600' }} text-white">
                            <td class="px-6 py-5 whitespace-nowrap text-lg font-bold text-right">LABA / RUGI BERSIH</td>
                            <td class="px-6 py-5 whitespace-nowrap text-xl font-black text-right tracking-tight">Rp {{ number_format($netProfit, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>
@endsection
