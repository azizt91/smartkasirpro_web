@extends('layouts.app')

@section('header')
    <div class="flex flex-col sm:flex-row justify-between items-center space-y-2 sm:space-y-0">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Log Aktivitas (Audit Logs)') }}
        </h2>
    </div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Filter Card -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 text-gray-900 border-b border-gray-200">
                <form action="{{ route('reports.audits') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
                    <div class="w-full sm:w-1/3">
                        <label for="action" class="block text-sm font-medium text-gray-700 mb-1">Aksi</label>
                        <select name="action" id="action" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Semua Aksi</option>
                            <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Dibuat (Created)</option>
                            <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Diperbarui (Updated)</option>
                            <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Dihapus (Deleted)</option>
                        </select>
                    </div>
                    <div class="w-full sm:w-1/3">
                        <label for="model_type" class="block text-sm font-medium text-gray-700 mb-1">Tipe Data (Model)</label>
                        <select name="model_type" id="model_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Semua Tipe</option>
                            <option value="Product" {{ request('model_type') == 'Product' ? 'selected' : '' }}>Produk / Jasa</option>
                            <option value="Transaction" {{ request('model_type') == 'Transaction' ? 'selected' : '' }}>Transaksi</option>
                            <option value="CommissionSettlement" {{ request('model_type') == 'CommissionSettlement' ? 'selected' : '' }}>Pelunasan Komisi</option>
                        </select>
                    </div>
                    <div class="w-full sm:w-1/3 flex items-end">
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Terapkan Filter
                        </button>
                        <a href="{{ route('reports.audits') }}" class="ml-3 mt-4 sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi & Waktu</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Oleh User</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Terdampak</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Perubahan Nilai (JSON)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($logs as $log)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500 align-top">
                                    @if($log->action == 'created')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 border border-green-200 uppercase">Input</span>
                                    @elseif($log->action == 'updated')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200 uppercase">Update</span>
                                    @elseif($log->action == 'deleted')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 border border-red-200 uppercase">Hapus</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200 uppercase">{{ $log->action }}</span>
                                    @endif
                                    <br>
                                    <span class="text-xs mt-1 block">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 border-l border-gray-50 align-top">
                                    <div class="font-bold">{{ $log->user ? $log->user->name : 'Sistem / Guest' }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $log->ip_address }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 border-l border-gray-50 align-top">
                                    <div class="font-medium font-mono text-purple-700 bg-purple-50 px-1 inline-block rounded">
                                        {{ str_replace('App\\Models\\', '', $log->model_type) }} #{{ $log->model_id }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 border-l border-gray-50 align-top max-w-lg">
                                    @if($log->action === 'updated')
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <div class="bg-red-50 p-2 rounded border border-red-100 overflow-x-auto max-h-40 overflow-y-auto w-full">
                                                <strong class="text-red-700 block mb-1 sticky top-0 bg-red-50">Data Lama:</strong>
                                                <pre class="font-mono text-[10px] text-gray-700 whitespace-pre-wrap">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                            <div class="bg-green-50 p-2 rounded border border-green-100 overflow-x-auto max-h-40 overflow-y-auto w-full">
                                                <strong class="text-green-700 block mb-1 sticky top-0 bg-green-50">Data Baru:</strong>
                                                <pre class="font-mono text-[10px] text-gray-700 whitespace-pre-wrap">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        </div>
                                    @elseif($log->action === 'deleted')
                                        <div class="bg-red-50 p-2 rounded border border-red-100 overflow-x-auto max-h-40 overflow-y-auto text-xs w-full max-w-sm sm:max-w-md md:max-w-full">
                                            <strong class="text-red-700 block mb-1 sticky top-0 bg-red-50">Data Dihapus:</strong>
                                            <pre class="font-mono text-[10px] text-gray-700 whitespace-pre-wrap">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
                                    @elseif($log->action === 'created')
                                        <div class="bg-green-50 p-2 rounded border border-green-100 overflow-x-auto max-h-40 overflow-y-auto text-xs w-full max-w-sm sm:max-w-md md:max-w-full">
                                            <strong class="text-green-700 block mb-1 sticky top-0 bg-green-50">Data Ditambahkan:</strong>
                                            <pre class="font-mono text-[10px] text-gray-700 whitespace-pre-wrap">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada log aktivitas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">
                    {{ $logs->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
