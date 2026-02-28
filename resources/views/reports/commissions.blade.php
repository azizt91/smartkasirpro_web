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
                    <h1 class="text-3xl font-bold text-gray-900">👨‍🔧 Laporan Komisi {{ \App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai' }}</h1>
                    <p class="text-gray-600 mt-1">Daftar transaksi layanan jasa dan komisi</p>
                </div>
            </div>
            
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            
            <!-- Filter Form -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('reports.commissions') }}" class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-end gap-4">
                        <div class="w-full sm:flex-1 min-w-48">
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" 
                                   class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="w-full sm:flex-1 min-w-48">
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                            <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" 
                                   class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="w-full sm:flex-1 min-w-48">
                            <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1">Pilih {{ \App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai' }}</label>
                            <select name="employee_id" id="employee_id" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Semua {{ \App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai' }}</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2 w-full sm:w-auto">
                            <button type="submit" 
                                    class="w-full justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center">
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
                            <a href="{{ route('reports.commissions', array_merge(request()->query(), ['format' => 'pdf'])) }}" 
                               class="inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Export PDF
                            </a>
                            <a href="{{ route('reports.commissions', array_merge(request()->query(), ['format' => 'excel'])) }}" 
                               class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Export Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Total Layanan -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-5 border-l-4 border-indigo-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Layanan (Qty)</dt>
                                <dd class="text-2xl font-bold text-gray-900">{{ number_format($summary['total_services'], 0, ',', '.') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Total Komisi -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-5 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Komisi</dt>
                                <dd class="text-2xl font-bold text-green-600">Rp {{ number_format($summary['total_commission'], 0, ',', '.') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions List -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Detail Penghasilan Komisi</h3>
                    
                    <!-- Desktop Table -->
                    <div class="overflow-x-auto hidden md:block">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-center w-10">
                                        <input type="checkbox" id="select-all-checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" title="Pilih Semua Pending">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl / Waktu</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Transaksi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ \App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai' }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Layanan</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Komisi</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($items as $item)
                                    <tr class="hover:bg-gray-50 {{ !$item->settlement_id ? 'commission-row-pending' : '' }}">
                                        <td class="px-4 py-4 text-center">
                                            @if(!$item->settlement_id)
                                                <input type="checkbox" class="commission-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                       data-item-id="{{ $item->id }}"
                                                       data-amount="{{ $item->commission_amount }}"
                                                       data-employee="{{ $item->employee->name ?? '-' }}">
                                            @else
                                                <span class="text-gray-300">✓</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $item->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $item->transaction->transaction_code }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">
                                            {{ $item->employee->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $item->product_name }} (x{{ $item->quantity }})
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600 text-right">
                                            Rp {{ number_format($item->commission_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if($item->settlement_id)
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Dibayar
                                                </span>
                                            @else
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data komisi</h3>
                                            <p class="mt-1 text-sm text-gray-500">Belum ada layanan jasa yang dikerjakan pada periode ini.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="md:hidden space-y-4">
                        @forelse($items as $item)
                            <div class="bg-white rounded-xl shadow border border-gray-200 p-4 {{ !$item->settlement_id ? 'commission-row-pending' : '' }}">
                                <div class="flex items-start gap-3">
                                    @if(!$item->settlement_id)
                                        <input type="checkbox" class="commission-checkbox mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                               data-item-id="{{ $item->id }}"
                                               data-amount="{{ $item->commission_amount }}"
                                               data-employee="{{ $item->employee->name ?? '-' }}">
                                    @endif
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start mb-2">
                                            <div class="font-bold text-gray-900">{{ $item->employee->name ?? '-' }}</div>
                                            <div class="text-sm font-bold text-green-600">Rp {{ number_format($item->commission_amount, 0, ',', '.') }}</div>
                                        </div>
                                        <div class="text-sm text-gray-600 mb-2">
                                            {{ $item->product_name }} (x{{ $item->quantity }})
                                        </div>
                                        <div class="mb-3 text-right">
                                            @if($item->settlement_id)
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Dibayar</span>
                                            @else
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                            @endif
                                        </div>
                                        <div class="flex justify-between text-xs text-gray-500 pt-2 border-t border-gray-100">
                                            <span>{{ $item->transaction->transaction_code }}</span>
                                            <span>{{ $item->created_at->format('d M Y, H:i') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-sm text-gray-500">Belum ada komisi.</p>
                            </div>
                        @endforelse
                    </div>

                </div>
                <!-- Pagination -->
                <div class="px-6 pb-6 mt-4">
                    {{ $items->appends(request()->query())->links() }}
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Floating Action Bar -->
<div id="floating-action-bar" class="fixed bottom-0 left-0 right-0 bg-white border-t-2 border-indigo-500 shadow-2xl py-4 px-6 z-50" style="display:none;">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                    <span id="selected-count" class="text-sm font-bold text-indigo-700">0</span>
                </div>
                <span class="text-sm text-gray-600">komisi dipilih</span>
            </div>
            <div class="h-6 w-px bg-gray-300 hidden sm:block"></div>
            <div class="text-lg font-bold text-green-600">
                Rp <span id="selected-total">0</span>
            </div>
        </div>
        <button id="btn-settle" onclick="openSettleModal()" 
                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            💰 Bayar Komisi Terpilih
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCb = document.getElementById('select-all-checkbox');
    const floatingBar = document.getElementById('floating-action-bar');
    const checkboxes = document.querySelectorAll('.commission-checkbox');

    // Select All
    if (selectAllCb) {
        selectAllCb.addEventListener('change', function() {
            checkboxes.forEach(cb => { cb.checked = this.checked; });
            updateFloatingBar();
        });
    }

    // Individual checkbox
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            // Update select-all state
            const allChecked = document.querySelectorAll('.commission-checkbox:checked').length === checkboxes.length;
            if (selectAllCb) selectAllCb.checked = allChecked;
            updateFloatingBar();
        });
    });

    function updateFloatingBar() {
        const checked = document.querySelectorAll('.commission-checkbox:checked');
        const count = checked.length;
        let total = 0;
        checked.forEach(cb => { total += parseFloat(cb.dataset.amount) || 0; });

        document.getElementById('selected-count').textContent = count;
        document.getElementById('selected-total').textContent = new Intl.NumberFormat('id-ID').format(total);

        if (count > 0) {
            floatingBar.style.display = 'block';
        } else {
            floatingBar.style.display = 'none';
        }
    }
});

function openSettleModal() {
    const checked = document.querySelectorAll('.commission-checkbox:checked');
    let total = 0;
    checked.forEach(cb => { total += parseFloat(cb.dataset.amount) || 0; });
    const count = checked.length;

    Swal.fire({
        html: `
            <div class="text-left py-2">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Konfirmasi Pembayaran Komisi</h3>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 mb-5 border">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-600">Jumlah Item:</span>
                        <span class="font-bold text-gray-900">${count} komisi</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                        <span class="text-sm text-gray-600">Total Dibayar:</span>
                        <span class="text-xl font-bold text-green-600">Rp ${new Intl.NumberFormat('id-ID').format(total)}</span>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sumber Dana</label>
                    <select id="swal-payment-source" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 py-3 text-sm">
                        <option value="tunai">💵 Tunai (Cash)</option>
                        <option value="bank">🏦 Bank (Transfer)</option>
                    </select>
                </div>
                <p class="text-xs text-gray-400 mt-3">* Akan otomatis tercatat sebagai Pengeluaran (Beban Operasional) di kategori Gaji/Komisi.</p>
            </div>
        `,
        showConfirmButton: true,
        confirmButtonText: '✅ Konfirmasi Bayar',
        confirmButtonColor: '#4F46E5',
        showCancelButton: true,
        cancelButtonText: 'Batal',
        cancelButtonColor: '#9CA3AF',
        width: 440,
        preConfirm: () => {
            return document.getElementById('swal-payment-source').value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            processSettlement(result.value);
        }
    });
}

async function processSettlement(paymentSource) {
    const checked = document.querySelectorAll('.commission-checkbox:checked');
    const itemIds = Array.from(checked).map(cb => parseInt(cb.dataset.itemId));

    Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const response = await fetch('{{ route("reports.commissions.settle") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                item_ids: itemIds,
                payment_source: paymentSource,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil! ✅',
                text: data.message,
                confirmButtonColor: '#10B981',
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Terjadi kesalahan.', confirmButtonColor: '#EF4444' });
        }
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menghubungi server.', confirmButtonColor: '#EF4444' });
    }
}
</script>
@endsection
