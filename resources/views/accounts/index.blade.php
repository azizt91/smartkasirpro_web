@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between pb-6 border-b-2 border-gray-200">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">🗂️ Daftar Akun (Chart of Accounts)</h1>
                <p class="text-gray-600 mt-1">Kelola akun keuangan standar dan atur Saldo Awal (Initial Balance).</p>
            </div>
            <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row sm:items-center gap-3">
                <a href="{{ route('reports.ledger') }}" class="w-full sm:w-auto flex items-center justify-center px-4 py-2 bg-white text-gray-700 border border-gray-300 font-medium rounded-lg hover:bg-gray-50 shadow-sm transition duration-150">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Lihat Buku Besar
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="mt-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-md shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mt-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-md shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        @endif

        <div class="mt-8 bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Akun</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo Normal</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo Berjalan</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @php 
                        $typeLabels = [
                            'asset' => 'Aset (Harta)',
                            'liability' => 'Kewajiban (Hutang)',
                            'equity' => 'Ekuitas (Modal)',
                            'revenue' => 'Pendapatan',
                            'expense' => 'Beban / Pengeluaran'
                        ];
                        @endphp

                        @forelse($accounts as $acc)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{{ $acc->code }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ array_key_exists($acc->type, $typeLabels) ? $typeLabels[$acc->type] : $acc->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $acc->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ $acc->default_balance }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right {{ $acc->balance < 0 ? 'text-red-600' : 'text-green-600' }}">
                                Rp {{ number_format($acc->balance, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium border-l border-gray-100">
                                @if(!$acc->has_initial)
                                <button type="button" data-bs-toggle="modal" data-bs-target="#initialBalanceModal{{ $acc->id }}" class="inline-flex items-center px-3 py-1.5 border border-indigo-200 text-xs font-medium rounded text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                    Set Saldo Awal
                                </button>

                                <!-- Modal (Bootstrap implementation inside Tailwind layout) -->
                                <div class="modal fade" id="initialBalanceModal{{ $acc->id }}" tabindex="-1" aria-labelledby="modalLabel{{ $acc->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form action="{{ route('accounts.initialBalance', $acc->id) }}" method="POST" class="modal-content border-0 shadow-lg rounded-xl">
                                            @csrf
                                            <div class="modal-header border-b border-gray-200 bg-gray-50 rounded-t-xl px-4 py-3 pb-3">
                                                <h5 class="modal-title font-bold text-gray-900 text-base" id="modalLabel{{ $acc->id }}">Set Saldo Awal - {{ $acc->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-left px-5 py-4">
                                                <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-4 rounded text-sm text-blue-700">
                                                    Saldo awal hanya bisa diset sekali dan akan otomatis di-offset ke Modal Pemilik.
                                                </div>
                                                <div class="mb-2">
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nominal (Rp)</label>
                                                    <input type="text" name="amount" class="form-control currency-format w-full border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" id="amount_{{ $acc->id }}" placeholder="Cth: 100000" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-t border-gray-200 bg-gray-50 rounded-b-xl px-4 py-3">
                                                <button type="button" class="btn btn-light bg-white border border-gray-300 text-gray-700 hover:bg-gray-50" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary bg-indigo-600 hover:bg-indigo-700 border-transparent text-white focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Simpan Saldo</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @else
                                <span class="inline-flex items-center text-xs font-medium text-green-700 bg-green-50 px-2.5 py-1 rounded-full border border-green-200 shadow-sm">
                                    <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    Diset
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                <span class="block text-sm font-medium">Belum ada data akun</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setupCurrencyInput();
});

function setupCurrencyInput() {
    const inputs = document.querySelectorAll('.currency-format');
    inputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value !== '') {
                this.value = parseInt(value, 10).toLocaleString('id-ID');
            } else {
                this.value = '';
            }
        });
    });
}
</script>
@endsection
