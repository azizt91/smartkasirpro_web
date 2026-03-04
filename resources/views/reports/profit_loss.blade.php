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

<div class="space-y-6">
    <!-- Filter and Export Actions -->
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6 mb-6 mt-6">
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
            
            <!-- Filter Form -->
            <form action="{{ route('reports.profit_loss') }}" method="GET" class="flex-1 flex flex-col sm:flex-row gap-4 items-end">
                <div class="w-full sm:w-1/3">
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ $startDate }}" required>
                </div>
                <div class="w-full sm:w-1/3">
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                    <input type="date" name="end_date" id="end_date" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ $endDate }}" required>
                </div>
                <div class="w-full sm:w-auto">
                    <button type="submit" class="w-full sm:w-auto flex justify-center py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors z-10 relative cursor-pointer">
                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                </div>
            </form>

            <!-- Export Buttons -->
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('reports.profit_loss', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md shadow-sm transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download PDF
                </a>
                <a href="{{ route('reports.profit_loss', array_merge(request()->query(), ['format' => 'excel'])) }}" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md shadow-sm transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download Excel
                </a>
            </div>
            
        </div>
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
