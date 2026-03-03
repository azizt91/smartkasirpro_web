@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between pb-6 border-b-2 border-gray-200">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">📖 Buku Besar (Ledger)</h1>
                <p class="text-gray-600 mt-1">Laporan rincian mutasi per akun keuangan.</p>
            </div>
        </div>

<div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6 mt-6">
    <form action="{{ route('reports.ledger') }}" method="GET">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <!-- Account Selection -->
            <div class="md:col-span-4">
                <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1">Pilih Akun</label>
                <select name="account_id" id="account_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    <option value="">-- Pilih Akun --</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->code }} - {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <!-- Start Date -->
            <div class="md:col-span-3">
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
            <div class="md:col-span-3">
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Tampilkan
                </button>
            </div>
        </div>
    </form>
</div>

@if($selectedAccount)
<div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
    <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex flex-col sm:flex-row justify-between items-center gap-3">
        <h2 class="text-lg font-bold text-gray-900">Buku Besar: {{ $selectedAccount->code }} - {{ $selectedAccount->name }}</h2>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $selectedAccount->default_balance == 'debit' ? 'bg-indigo-100 text-indigo-800' : 'bg-green-100 text-green-800' }}">
            Saldo Normal: {{ ucfirst($selectedAccount->default_balance) }}
        </span>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/10">Tanggal</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-[15%]">Referensi</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-[30%]">Keterangan</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-[15%]">Debit (Rp)</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-[15%]">Kredit (Rp)</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-[15%]">Saldo (Rp)</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr class="bg-gray-100">
                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">Saldo Awal (Per {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }})</td>
                        <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right {{ $startingBalance < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($startingBalance, 0, ',', '.') }}</td>
                    </tr>

                    @php
                        $runningBalance = $startingBalance;
                        $sumDebit = 0;
                        $sumCredit = 0;
                    @endphp

                    @forelse($ledgers as $ledger)
                        @php
                            $sumDebit += $ledger->debit;
                            $sumCredit += $ledger->credit;
                            
                            if ($selectedAccount->default_balance === 'debit') {
                                $runningBalance += ($ledger->debit - $ledger->credit);
                            } else {
                                $runningBalance += ($ledger->credit - $ledger->debit);
                            }
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($ledger->date)->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if(str_contains($ledger->reference_type, 'void'))
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 mb-1">VOID</span><br>
                                @endif
                                <span class="capitalize">{{ $ledger->reference_type }}</span> <br> 
                                <span class="text-xs text-gray-400 mt-1 block">ID: {{ $ledger->reference_id ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate">{{ $ledger->description }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $ledger->debit > 0 ? 'text-green-600 font-medium' : 'text-gray-500' }}">{{ $ledger->debit > 0 ? number_format($ledger->debit, 0, ',', '.') : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $ledger->credit > 0 ? 'text-red-600 font-medium' : 'text-gray-500' }}">{{ $ledger->credit > 0 ? number_format($ledger->credit, 0, ',', '.') : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right border-l border-gray-100 {{ $runningBalance < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($runningBalance, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Tidak ada mutasi pada periode ini
                            </td>
                        </tr>
                    @endforelse

                    <tr class="bg-indigo-50 border-t-2 border-indigo-100">
                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-900 text-right">Mutasi Periode Ini</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600 text-right">{{ number_format($sumDebit, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600 text-right">{{ number_format($sumCredit, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"></td>
                    </tr>
                    <tr class="bg-indigo-600 text-white">
                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right">Saldo Akhir (Per {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }})</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right {{ $runningBalance < 0 ? 'text-red-200' : 'text-white' }}">{{ number_format($runningBalance, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
    </div>
</div>
@endsection
