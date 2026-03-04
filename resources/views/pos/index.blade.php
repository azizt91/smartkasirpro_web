@extends('layouts.app')

@section('scripts')
@php
    $defaultSettings = [
        'store_name' => 'Toko Anda',
        'store_address' => 'Alamat Toko Anda',
        'store_phone' => 'No. Telepon Anda',
        'tax_rate' => 0
    ];
    $settings = $storeSettings ?? $defaultSettings;
@endphp
<script>
    const STORE_SETTINGS = @json($settings);
    const AUTH_USER = @json(auth()->user() ? ['name' => auth()->user()->name] : ['name' => 'Kasir']);
</script>
@endsection

@section('content')
<div class="h-[calc(100vh-4rem)] bg-slate-50 overflow-y-auto" id="pos-container" x-data="posApp()">
    <div class="p-4 sm:p-6 lg:p-8 min-h-screen">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🛒 Point of Sale</h1>
                    <p class="text-gray-600 mt-1">Scan, add products, and process transactions</p>
                </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-3">
                @if(data_get($settings, 'business_mode', 'retail') === 'resto')
                <button @click="showPendingOrders = true" class="relative inline-flex items-center px-4 py-2 bg-amber-50 text-amber-600 rounded-lg text-sm font-medium hover:bg-amber-100 transition-colors border border-amber-200 gap-2">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        <span>Pesanan Masuk</span>
                    </div>
                    <span x-cloak x-show="pendingOrdersCount > 0" class="flex items-center justify-center w-5 h-5 p-0 text-[10px] sm:text-xs font-bold text-white bg-red-500 rounded-full shadow-sm" x-text="pendingOrdersCount"></span>
                </button>
                @endif
                <a href="{{ route('pos.shift.close') }}" class="inline-flex items-center px-4 py-2 bg-red-50 text-red-600 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors border border-red-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Tutup Kasir
                </a>
                <div class="flex items-center px-3 py-2 bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                    <span class="text-sm font-medium text-gray-700">Online</span>
                </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        {{-- Products Section (Left Side) --}}
        <div class="md:col-span-2 lg:col-span-3 space-y-6">
            {{-- Search Area --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1 relative">
                        <input type="text" id="product-search" 
                               placeholder="Search products by name or barcode..." 
                               class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200" 
                               autocomplete="off">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <button onclick="openScannerModal()" 
                            class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:from-indigo-600 hover:to-purple-700 flex items-center justify-center gap-2 transition-all duration-200 shadow-sm hover:shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="hidden sm:inline">Scan</span>
                    </button>
                </div>
                <div id="search-results" class="mt-4 space-y-2 max-h-60 overflow-y-auto scrollbar-thin"></div>
            </div>

            {{-- Category Tabs --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="border-b border-gray-200">
                    <nav id="category-tabs-container" class="-mb-px flex space-x-1 overflow-x-auto scrollbar-thin px-6 py-4"></nav>
                </div>
            </div>

            {{-- Products Grid --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Products</h3>
                        <p class="text-sm text-gray-500">Click on a product to add it to cart</p>
                    </div>

                </div>
                
                <div id="products-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 gap-4 min-h-[400px]">
                    {{-- Products will be loaded here by JavaScript --}}
                </div>

                {{-- Pagination --}}
                <div class="flex flex-col sm:flex-row justify-between items-center mt-8 pt-6 border-t border-gray-200 gap-4">
                    <div class="flex items-center space-x-4">
                        <span id="pagination-info" class="text-sm text-gray-500"></span>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">per page</span>
                            <select id="per-page-select" 
                                    onchange="changePerPage(this.value)" 
                                    class="border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="10" selected>10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div id="pagination-buttons" class="flex space-x-1"></div>
                </div>
            </div>
        </div>

        {{-- Cart Section (Right Side) --}}
        <div class="md:col-span-1 lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-900">Shopping Cart</h3>
                    <button onclick="clearCart()" 
                            class="bg-red-50 hover:bg-red-100 text-red-600 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 border border-red-200">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Clear
                    </button>
                </div>
                
                <div id="cart-items-container" class="space-y-3 mb-6 max-h-80 overflow-y-auto scrollbar-thin">
                    {{-- Cart items will be populated here --}}
                    <!-- Order Masuk & Kitchen (Resto Mode) -->
                @if(data_get($settings, 'business_mode', 'retail') === 'resto')
                <div class="flex items-center gap-2">
                    <button @click="showPendingOrders = true" class="relative bg-amber-500 hover:bg-amber-600 text-white font-medium px-4 py-2 rounded-lg flex items-center transition-colors shadow-sm">
                        <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        Order Masuk
                        <span x-show="pendingOrdersCount > 0" class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow border-2 border-white animate-bounce" x-text="pendingOrdersCount"></span>
                    </button>

                    <a href="{{ route('pos.kitchen') }}" target="_blank" class="bg-gray-800 hover:bg-gray-700 text-white font-medium px-4 py-2 rounded-lg flex items-center transition-colors shadow-sm" title="Buka Kitchen View">
                        🍳 Dapur
                    </a>
                </div>
                @endif
                
                <!-- Category Filters (Scrollable) -->
                </div>
                
                <div class="border-t border-gray-200 pt-6 space-y-3">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Diskon (Rp)</span>
                            <input type="number" id="discount-amount" value="0" min="0" 
                                   class="w-24 text-right text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 py-1"
                                   oninput="updateCartTotals()">
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Pajak (%)</span>
                            <input type="number" id="tax-rate" value="{{ $storeSettings->tax_rate ?? 0 }}" min="0" max="100" step="0.1"
                                   class="w-24 text-right text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 py-1"
                                   oninput="updateCartTotals()">
                        </div>
                    </div>
                    <div class="border-t border-gray-100 pt-3">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Subtotal</span>
                            <span id="subtotal" class="font-medium">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-sm text-red-500 mb-1">
                            <span>Diskon</span>
                            <span id="display-discount">- Rp 0</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Pajak</span>
                            <span id="display-tax">+ Rp 0</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t border-gray-200">
                            <span>Total</span>
                            <span id="total">Rp 0</span>
                        </div>
                    </div>
                </div>
                
                <button id="checkout-button" 
                        onclick="openPaymentModal()" 
                        class="w-full mt-6 bg-gradient-to-r from-indigo-500 to-purple-600 text-white py-4 rounded-xl hover:from-indigo-600 hover:to-purple-700 font-semibold flex items-center justify-center gap-2 disabled:from-gray-400 disabled:to-gray-400 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md" 
                        disabled>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 1.5M7 13l1.5 1.5M17 21a2 2 0 100-4 2 2 0 000 4zM9 21a2 2 0 100-4 2 2 0 000 4z"></path>
                    </svg>
                    Checkout
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ================================= MODALS ================================= --}}

{{-- Modal untuk Barcode Scanner --}}
<div id="scanner-modal" class="fixed inset-0 bg-black bg-opacity-75 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-lg font-semibold text-center">Arahkan Kamera ke Barcode</h3>
        </div>
        <div class="p-4 bg-gray-900">
            <div id="reader" style="width: 100%; border-radius: 0.5rem; overflow: hidden;"></div>
        </div>
        <div class="p-4 bg-gray-50 border-t">
            <button onclick="closeScannerModal()" class="w-full py-3 text-center font-bold text-white bg-red-500 hover:bg-red-600 rounded-lg transition">
                Tutup
            </button>
        </div>
    </div>
</div>

{{-- Modal Pilihan Varian --}}
<div id="variant-modal" class="fixed inset-0 bg-black bg-opacity-75 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-auto overflow-hidden flex flex-col" style="max-height: 90vh;">
        <div class="flex justify-between items-center p-4 border-b bg-gray-50 flex-shrink-0">
            <h3 class="text-lg font-bold text-gray-900" id="variant-modal-title">Pilih Varian</h3>
            <button onclick="closeVariantModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-4 space-y-2" id="variant-modal-body" style="overflow-y: auto; flex: 1; min-height: 0;">
            <!-- Varian items rendered here -->
        </div>
        <div class="p-4 bg-gray-50 border-t flex-shrink-0">
            <button onclick="closeVariantModal()" class="w-full py-3 text-center font-bold text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
                Tutup
            </button>
        </div>
    </div>
</div>

{{-- Modal Pilih Pegawai (Untuk Jasa) --}}
<div id="employee-modal" class="fixed inset-0 bg-black bg-opacity-75 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-auto overflow-hidden flex flex-col">
        <div class="flex justify-between items-center p-4 border-b bg-indigo-50 flex-shrink-0">
            <h3 class="text-lg font-bold text-indigo-900">Pilih {{ \App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai' }} (Jasa)</h3>
            <button onclick="closeEmployeeModal()" class="text-indigo-400 hover:text-indigo-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-4 space-y-3">
            <p class="text-sm text-gray-600 mb-2">Pilih {{ strtolower(\App\Models\Setting::getStoreSettings()->employee_label ?? 'pegawai') }} yang akan mengerjakan layanan <strong id="employee-modal-item-name"></strong>:</p>
            <select id="selected-employee-id" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">-- Tidak dikaitkan (Opsional) --</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="p-4 bg-gray-50 border-t flex justify-between gap-3 flex-shrink-0">
            <button onclick="closeEmployeeModal()" class="w-1/2 py-2 text-center font-semibold text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition">Batal</button>
            <button onclick="confirmEmployeeSelection()" class="w-1/2 py-2 text-center font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">Lanjut</button>
        </div>
    </div>
</div>

{{-- Modal Pembayaran --}}
<div id="payment-modal" class="fixed inset-0 bg-black bg-opacity-75 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-auto overflow-hidden flex flex-col" style="max-height: 90vh;">

        <div class="grid grid-cols-2 flex-shrink-0">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-4 text-white">
                <p class="text-sm font-medium opacity-80">Total Belanja</p>
                <p class="text-3xl font-bold tracking-tight" id="modal-total">Rp 0</p>
            </div>
            <div class="bg-gradient-to-br from-yellow-500 to-orange-500 p-4 text-white">
                <p class="text-sm font-medium opacity-80">Kembalian</p>
                <p class="text-3xl font-bold tracking-tight" id="modal-change">Rp 0</p>
            </div>
        </div>

        <div class="p-6 space-y-5 overflow-y-auto" style="flex: 1; min-height: 0;">
            <div class="grid grid-cols-2 gap-4">
                <div x-data="{
                    open: false,
                    search: '',
                    selected: 'Umum',
                    customers: {{ $customers->map(fn($c) => ['name' => $c->name, 'phone' => $c->phone ?? '-', 'points' => $c->points])->toJson() }},
                    get filteredCustomers() {
                        if (this.search === '') return this.customers;
                        return this.customers.filter(c => 
                            c.name.toLowerCase().includes(this.search.toLowerCase()) || 
                            (c.phone && c.phone.includes(this.search))
                        );
                    },
                    selectCustomer(customer) {
                        if (typeof customer === 'string') {
                            this.selected = customer;
                            if(document.getElementById('customer-points')) document.getElementById('customer-points').value = 0;
                        } else {
                            this.selected = customer.name;
                            if(document.getElementById('customer-points')) document.getElementById('customer-points').value = customer.points || 0;
                        }
                        if (typeof updatePointUI === 'function') updatePointUI();
                        this.open = false;
                        this.search = '';
                    },
                    openAddCustomerModal() {
                        this.open = false;
                        document.getElementById('add-customer-modal').classList.remove('hidden');
                        setTimeout(() => document.getElementById('new-customer-name').focus(), 100);
                    }
                }" 
                x-init="window.addEventListener('customer-added', (e) => {
                    customers.unshift(e.detail);
                    selectCustomer(e.detail.name);
                });
                window.addEventListener('select-customer', (e) => {
                    selectCustomer(e.detail);
                });"
                class="relative z-50">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Nama Customer</label>
                    
                    <!-- Hidden Input for Form Submission/JS Reading -->
                    <input type="hidden" id="customer-name" :value="selected">

                    <!-- Dropdown Trigger -->
                    <button type="button" 
                            @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                            class="w-full px-4 py-2 bg-gray-100 border border-transparent rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-left flex justify-between items-center">
                        <span x-text="selected"></span>
                        <svg class="w-4 h-4 text-gray-500 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>

                    <!-- Dropdown Content -->
                    <div x-show="open" 
                         @click.away="open = false" 
                         style="display: none;"
                         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-hidden flex flex-col">
                        
                        <!-- Search Input -->
                        <div class="p-2 border-b border-gray-100">
                            <input x-ref="searchInput" 
                                   x-model="search" 
                                   type="text" 
                                   placeholder="Cari nama / No. HP..." 
                                   class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>

                        <!-- List Items -->
                        <ul class="overflow-y-auto flex-1">
                            <!-- Button: Tambah Pelanggan -->
                            <li @click="openAddCustomerModal()" 
                                class="px-4 py-3 hover:bg-green-50 cursor-pointer text-sm border-b border-gray-100 flex items-center text-green-600 font-semibold transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Tambah Pelanggan Baru
                            </li>

                            <!-- Option: Umum -->
                            <li @click="selectCustomer('Umum')" 
                                class="px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm border-b border-gray-50 flex justify-between items-center"
                                :class="{'bg-blue-50 text-blue-700': selected === 'Umum'}">
                                <span class="font-medium">Umum</span>
                            </li>

                            <template x-for="customer in filteredCustomers" :key="customer.name">
                                <li @click="selectCustomer(customer)" 
                                    class="px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm border-b border-gray-50 flex justify-between items-center"
                                    :class="{'bg-blue-50 text-blue-700': selected === customer.name}">
                                    <div class="flex flex-col">
                                        <span class="font-medium" x-text="customer.name"></span>
                                        <span class="text-xs text-gray-400" x-text="customer.phone"></span>
                                    </div>
                                    @if(data_get($settings, 'enable_loyalty_points', true))
                                    <span class="text-xs font-bold text-yellow-600 bg-yellow-100 px-2 rounded-full" x-text="'⭐ ' + (customer.points || 0)"></span>
                                    @endif
                                </li>
                            </template>
                            
                            <!-- No Results -->
                            <div x-show="filteredCustomers.length === 0 && search !== ''" class="px-4 py-3 text-sm text-gray-500 text-center">
                                Tidak ditemukan
                            </div>
                        </ul>
                    </div>
                </div>
                 
                 <input type="hidden" id="customer-points" value="0">
                 @if(data_get($settings, 'enable_loyalty_points', true))
                 <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Gunakan Poin</label>
                    <div class="flex items-center gap-2">
                        <input type="number" id="use-points" min="0" value="0"
                               class="w-full px-4 py-2 bg-gray-100 border-transparent rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-sm text-right font-bold"
                               oninput="updateModalPayment()">
                        <span class="text-xs text-gray-500 whitespace-nowrap" id="max-points-label">0 pts</span>
                    </div>
                    <div class="text-xs text-blue-600 mt-1 font-medium" id="point-discount-preview">- Rp 0</div>
                </div>
                 @else
                 <input type="hidden" id="use-points" value="0">
                 @endif
                 
                 @if(data_get($settings, 'business_mode', 'retail') === 'resto')
                 <div>
                    <label for="table-id" class="block text-xs font-medium text-gray-500 mb-1">Meja (Opsional)</label>
                    <select id="table-id"
                            class="w-full px-4 py-2 bg-gray-100 border-transparent rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-sm">
                        <option value="">-- Pilih Meja / Bawa Pulang --</option>
                        @foreach($tables ?? [] as $table)
                            <option value="{{ $table->id }}">{{ $table->nama_meja }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                 <div>
                    <label for="payment-method" class="block text-xs font-medium text-gray-500 mb-1">Metode Pembayaran</label>
                    <select id="payment-method"
                            class="w-full px-4 py-2 bg-gray-100 border-transparent rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                        <option value="">Pilih metode...</option>
                        <option value="cash">💵 Tunai</option>
                        @if(data_get($settings, 'business_mode', 'retail') !== 'resto')
                        <option value="utang">📝 Utang</option>
                        @endif
                        <option value="card">💳 Kartu</option>
                        <option value="ewallet">📱 E-Wallet</option>
                        <option value="transfer">🏦 Transfer Bank</option>
                        <option value="qris">📲 QRIS</option>
                    </select>
                </div>

                <!-- Channel Selector Placeholder -->
                <div id="channel-selector-container" class="hidden mt-3">
                    <label for="payment-channel" class="block text-xs font-medium text-gray-500 mb-1">Pilih Provider</label>
                    <select id="payment-channel" class="w-full px-4 py-2 bg-gray-100 border-transparent rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-sm">
                        <!-- Channels rendered here -->
                    </select>
                </div>
            </div>
            
            @if(auth()->user()->hasPermission('can_backdate_sales'))
            <div>
                <label for="transaction-date" class="block text-xs font-medium text-gray-500 mb-1">Tanggal Transaksi (Backdate)</label>
                <input type="datetime-local" id="transaction-date" 
                       class="w-full px-4 py-2 bg-gray-100 border-transparent rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-sm">
            </div>
            @endif

            <div>
                <label for="amount-paid" class="block text-xs font-medium text-gray-500 mb-1">Nominal Bayar</label>
                <div class="relative">
                     <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">Rp</span>
                     <input type="number" id="amount-paid" placeholder="0"
                           class="w-full pl-9 pr-4 py-2 bg-gray-100 border-transparent rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-lg font-semibold"
                           oninput="updateModalPayment()">
                </div>
            </div>

            <div>
                <label for="transaction-note" class="block text-xs font-medium text-gray-500 mb-1">Catatan (Opsional)</label>
                <textarea id="transaction-note" rows="2" placeholder="Contoh: Titipan..." class="w-full px-4 py-2 bg-gray-100 border-transparent rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-sm"></textarea>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                <button type="button" onclick="setQuickAmount(50000)" class="px-2 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 hover:scale-105 transition transform">50rb</button>
                <button type="button" onclick="setQuickAmount(100000)" class="px-2 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 hover:scale-105 transition transform">100rb</button>
                <button type="button" onclick="setQuickAmount(150000)" class="px-2 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 hover:scale-105 transition transform">150rb</button>
                <button type="button" onclick="setQuickAmount(200000)" class="px-2 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 hover:scale-105 transition transform">200rb</button>
                <button type="button" onclick="setQuickAmount(500000)" class="px-2 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 hover:scale-105 transition transform">500rb</button>
                <button type="button" onclick="setQuickAmount(1000000)" class="px-2 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 hover:scale-105 transition transform">1jt</button>
            </div>
        </div>

        <div class="grid grid-cols-2 flex-shrink-0">
            <button onclick="closePaymentModal()"
                    class="py-4 text-center font-bold text-white bg-gradient-to-br from-pink-500 to-red-500 hover:from-pink-600 hover:to-red-600 transition">
                Batal
            </button>
            <button id="btn-bayar" onclick="processTransaction()"
                    class="py-4 text-center font-bold text-white bg-gradient-to-br from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 transition">
                Bayar
            </button>
        </div>
    </div>
</div>

{{-- Modal Tambah Pelanggan --}}
<div id="add-customer-modal" class="fixed inset-0 bg-black bg-opacity-75 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-auto overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Tambah Pelanggan Baru</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" id="new-customer-name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor HP (Opsional)</label>
                    <input type="text" id="new-customer-phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-3 mt-6">
                <button onclick="closeAddCustomerModal()" class="py-2 text-center font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Batal
                </button>
                <button onclick="saveNewCustomer()" class="py-2 text-center font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition flex items-center justify-center">
                    <span id="btn-save-customer-text">Simpan</span>
                    <svg id="btn-save-customer-loading" class="animate-spin ml-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Sukses --}}
<div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="text-center">
            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4"><svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Pembayaran Berhasil!</h3>
            <p class="text-gray-600 mb-6">Transaksi telah berhasil diproses</p>
            <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-gray-600">No. Transaksi</div><div class="font-medium" id="transaction-id"></div>
                    <div class="text-gray-600">Total Bayar</div><div class="font-medium" id="success-total"></div>
                    <div class="text-gray-600">Kembalian</div><div class="font-medium text-green-600" id="success-change"></div>
                </div>
            </div>
            <div class="space-y-3">
                <div class="text-sm text-gray-600 mb-3">Cetak Struk?</div>
                <div class="grid grid-cols-3 gap-2">
                    <button onclick="doPrintUSB()" class="flex flex-col items-center gap-1 py-3 px-2 rounded-lg bg-indigo-50 hover:bg-indigo-100 border-2 border-indigo-200 hover:border-indigo-400 transition-all text-indigo-700 font-medium text-sm" title="Printer USB">
                        <span class="text-xl">🔌</span> USB
                    </button>
                    <button onclick="doPrintBluetooth()" class="flex flex-col items-center gap-1 py-3 px-2 rounded-lg bg-blue-50 hover:bg-blue-100 border-2 border-blue-200 hover:border-blue-400 transition-all text-blue-700 font-medium text-sm" title="Printer Bluetooth">
                        <span class="text-xl">📶</span> Bluetooth
                    </button>
                    <button onclick="doPrintBrowser()" class="flex flex-col items-center gap-1 py-3 px-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 border-2 border-emerald-200 hover:border-emerald-400 transition-all text-emerald-700 font-medium text-sm" title="Print Browser">
                        <span class="text-xl">📄</span> Browser
                    </button>
                </div>
                <button onclick="closeSuccessModal()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-600 py-2.5 rounded-lg font-medium text-sm transition-colors">✕ Lewati</button>
                <div class="text-xs text-gray-500 text-center">Modal akan tertutup dalam <span id="countdown-timer" class="font-medium text-blue-600">10</span> detik</div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        /* Sembunyikan semua elemen di halaman secara default saat mencetak */
        body * {
            visibility: hidden;
        }

        /* Tampilkan HANYA area cetak dan semua isinya */
        #print-area, #print-area * {
            visibility: visible;
        }

        /* Posisikan area cetak di sudut kiri atas halaman cetak agar rapi */
        #print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            display: block; /* <-- [TAMBAHKAN BARIS INI] Paksa elemen untuk tampil kembali */
        }
        /* Posisikan area cetak di sudut kiri atas halaman cetak agar rapi */
        #print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            display: block; /* <-- [TAMBAHKAN BARIS INI] Paksa elemen untuk tampil kembali */
        }
    }
    
    /* Hide spin buttons for number inputs */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>

<div id="print-area" class="hidden"></div>

{{-- ================================= SCRIPT ================================= --}}
<script>
    // === STATE MANAGEMENT ===
    let cart = [];
    let searchTimeout = null;
    let currentPage = 1;
    let currentCategory = 'all';
    let currentType = ''; // unused, kept for loadProducts URL compatibility
    let perPage = 10;
    let countdownInterval = null;
    let currentProductsList = []; // New Global store for products
    window.currentTransaction = null;

    // === UTILITY FUNCTIONS ===
    const formatRupiah = (amount) => `Rp ${new Intl.NumberFormat('id-ID').format(amount)}`;

    // === INITIALIZATION ===
    document.addEventListener('DOMContentLoaded', function() {
        initPos();
    });

    function initPos() {
        console.log('Initializing POS system...');
        loadCategories();
        loadProducts();
        setupSearch();
        updateCartDisplay();
        updateCartDisplay();
        document.getElementById('product-search')?.focus();
    }
    
    // Add this function to handle direct quantity updates
    function updateItemQuantity(cartId, value) {
        const item = cart.find(item => item.cartId === cartId);
        if (item) {
            let newQty = parseInt(value);
            if (isNaN(newQty) || newQty < 1) newQty = 1;
            
            if (item.type === 'jasa' || newQty <= item.stock) {
                item.quantity = newQty;
                updateCartDisplay();
            } else {
                alert(`Stok tidak mencukupi! Stok tersedia: ${item.stock}`);
                // Reset input value
                updateCartDisplay();
            }
        }
    }

    // === API & DATA FETCHING ===
    async function fetchData(url) {
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error(`Error fetching from ${url}:`, error);
            return null;
        }
    }

    async function loadCategories() {
        const categories = await fetchData('/pos/categories');
        if (categories) renderCategoryTabs(categories);
    }

    async function loadProducts() {
        const searchInput = document.getElementById('product-search');
        const query = searchInput.value.trim();

        const url = `/pos/products/search?q=${encodeURIComponent(query)}&page=${currentPage}&category=${currentCategory}&type=${currentType}&per_page=${perPage}`;

        const data = await fetchData(url);

        if (data) {
            currentProductsList = data.data || []; // Store globally
            
            // Logika untuk barcode scanner otomatis (Hanya untuk Single Product)
            if (currentProductsList.length === 1 && query.length > 5 && !currentProductsList[0].is_group) {
                 // Check if it matches barcode logic (need barcode in response if we want strict match, but backend search handles it)
                 // Assuming single result from strict barcode search is what we want.
                 // WARNING: Backend returns Group. If barcode search matches a VARIANT, backend returns the GROUP containing it.
                 // We need to check if we can auto-add.
                 // Current logic: If scan -> Match -> Auto add.
                 // With Groups: If scan Variant B -> Backend returns Group containing B. 
                 // We don't know WHICH variant matched unless backend tells us.
                 // For now, let's DISABLE auto-add for Groups/Variants via simple search to avoid adding wrong item.
                 // OR: If the Group has only 1 product (Single), auto add.
                 
                if (!currentProductsList[0].is_group) {
                    const product = currentProductsList[0];
                    addToCart(product.id, product.name, product.selling_price, product.stock, product.image, product.type);
                    searchInput.value = '';
                    currentPage = 1; 
                    await loadProducts();
                    return;
                }
            }
            
            displayProductsGrid(currentProductsList);
            updatePagination(data);
        } else {
            document.getElementById('products-grid').innerHTML = '<p class="text-red-500 text-center col-span-full">Error memuat produk.</p>';
        }
    }

    // === RENDERING & DISPLAY LOGIC ===
    function renderCategoryTabs(categories) {
        const container = document.getElementById('category-tabs-container');
        if (!container) return;
        let tabsHTML = `<button onclick="filterByCategory('all', this)" class="category-tab px-6 py-4 text-sm font-medium text-white bg-teal-600 border-b-2 border-teal-600 whitespace-nowrap active">Semua</button>`;
        categories.forEach(category => {
            tabsHTML += `<button onclick="filterByCategory('${category.id}', this)" class="category-tab px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 whitespace-nowrap">${category.name}</button>`;
        });
        container.innerHTML = tabsHTML;
    }



    function displayProductsGrid(products) {
        const grid = document.getElementById('products-grid');
        if (!products || products.length === 0) {
            grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-8">Tidak ada produk ditemukan</div>';
            return;
        }
        
        grid.innerHTML = products.map((p, index) => {
            if (p.is_group) {
                // Render Group Card
                return `
                <div class="bg-white border rounded-lg p-3 hover:shadow-md transition-shadow cursor-pointer relative" onclick="openVariantModal(${index})">
                     <div class="absolute top-2 right-2 bg-purple-100 text-purple-700 text-xs font-bold px-2 py-1 rounded-full">Varian</div>
                    <div class="aspect-square bg-gray-100 rounded-lg mb-2 flex items-center justify-center overflow-hidden">
                        ${p.image ? `<img src="/storage/${p.image}" alt="${p.name}" class="w-full h-full object-cover">` : `<span class="text-2xl">📦</span>`}
                    </div>
                    <div class="text-sm font-medium text-gray-900 mb-1 line-clamp-2">${p.name}</div>
                    <div class="text-xs text-gray-500 mb-2">${p.type === 'jasa' ? 'Jasa/Servis' : 'Total Stok: ' + p.stock}</div>
                    <div class="text-sm font-bold text-teal-600">${typeof p.price_display === 'number' ? formatRupiah(p.price_display) : formatRupiah(p.selling_price)}</div>
                </div>`;
            } else {
                // Render Single Card
                return `
                <div class="bg-white border rounded-lg p-3 hover:shadow-md transition-shadow cursor-pointer" onclick="addToCart(${p.id}, '${p.name.replace(/'/g, "\\'")}', ${p.selling_price}, ${p.stock}, '${p.image || ''}', '${p.type || 'barang'}')">
                    <div class="aspect-square bg-gray-100 rounded-lg mb-2 flex items-center justify-center overflow-hidden">
                        ${p.image ? `<img src="/storage/${p.image}" alt="${p.name}" class="w-full h-full object-cover">` : `<span class="text-2xl">📦</span>`}
                    </div>
                    <div class="text-sm font-medium text-gray-900 mb-1 line-clamp-2">${p.name}</div>
                    <div class="text-xs text-gray-500 mb-2">${p.type === 'jasa' ? 'Jasa/Servis' : 'Stok: ' + p.stock}</div>
                    <div class="text-sm font-bold text-teal-600">${formatRupiah(p.selling_price)}</div>
                </div>`;
            }
        }).join('');
    }

    function updatePagination(data) {
        const infoEl = document.getElementById('pagination-info');
        const buttonsEl = document.getElementById('pagination-buttons');
        if (!infoEl || !buttonsEl || !data.total) {
            if(infoEl) infoEl.textContent = '';
            if(buttonsEl) buttonsEl.innerHTML = '';
            return;
        }
        infoEl.textContent = `Menampilkan ${data.from || 0} sampai ${data.to || 0} dari ${data.total} hasil`;
        let buttonsHTML = '';
        data.links.forEach(link => {
            if (link.url) {
                buttonsHTML += `<button onclick="changePage('${link.url}')" class="px-3 py-2 text-sm ${link.active ? 'bg-teal-600 text-white rounded' : 'text-gray-500 hover:text-gray-700'}">${link.label.replace('&laquo;', '‹').replace('&raquo;', '›')}</button>`;
            } else {
                buttonsHTML += `<span class="px-3 py-2 text-sm text-gray-400">${link.label.replace('&laquo;', '‹').replace('&raquo;', '›')}</span>`;
            }
        });
        buttonsEl.innerHTML = buttonsHTML;
    }

    // === SEARCH LOGIC ===
    function setupSearch() {
        const searchInput = document.getElementById('product-search');
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                loadProducts();
            }, 300);
        });
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                currentPage = 1;
                loadProducts();
            }
        });
    }

    // === VARIANT MODAL LOGIC ===
    function openVariantModal(index) {
        const group = currentProductsList[index];
        if (!group || !group.variants) return;

        const modalBody = document.getElementById('variant-modal-body');
        const modalTitle = document.getElementById('variant-modal-title');
        
        modalTitle.textContent = group.name;
        
        modalBody.innerHTML = group.variants.map(v => `
            <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 cursor-pointer ${v.stock <= 0 && v.type !== 'jasa' ? 'opacity-50 pointer-events-none' : ''}" 
                 onclick="${v.stock > 0 || v.type === 'jasa' ? `addToCart(${v.id}, '${v.full_name.replace(/'/g, "\\'")}', ${v.price}, ${v.stock}, '${v.image || group.image || ''}', '${v.type || 'barang'}'); closeVariantModal();` : ''}">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-200 rounded flex-shrink-0 mr-3 overflow-hidden">
                         ${v.image || group.image ? `<img src="/storage/${v.image || group.image}" class="w-full h-full object-cover">` : ''}
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">${v.name}</div>
                        <div class="text-sm text-gray-500">${v.type === 'jasa' ? 'Jasa/Servis' : 'Stok: ' + v.stock}</div>
                    </div>
                </div>
                <div class="font-bold text-teal-600">${formatRupiah(v.price)}</div>
            </div>
        `).join('');

        document.getElementById('variant-modal').classList.remove('hidden');
    }

    function closeVariantModal() {
        document.getElementById('variant-modal').classList.add('hidden');
    }

    // Variabel global untuk instance scanner
    let html5QrcodeScanner = null;

    // Fungsi untuk membuka modal dan memulai scanner
    function openScannerModal() {
        document.getElementById('scanner-modal').classList.remove('hidden');
        
        // Cek HTTPS atau Localhost
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
             Swal.fire({
                icon: 'warning',
                title: 'Koneksi Tidak Aman',
                text: 'Fitur kamera mungkin tidak berfungsi jika tidak menggunakan HTTPS.',
                timer: 3000
            });
        }

        startScanner();
    }

    function startScanner() {
        if (html5QrcodeScanner) {
            // Already running
            return;
        }

        html5QrcodeScanner = new Html5Qrcode("reader");
        
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            formatsToSupport: [
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.QR_CODE
            ]
        };
        
        // Attempt 1: Back Camera
        html5QrcodeScanner.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanError
        ).catch(err => {
            console.warn("Back camera failed, trying front...", err);
            // Attempt 2: Front/User Camera
            html5QrcodeScanner.start(
                { facingMode: "user" },
                config,
                onScanSuccess,
                onScanError
            ).catch(err2 => {
                console.error("Scanner failed:", err2);
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Membuka Kamera',
                    text: 'Pastikan izin kamera diberikan dan menggunakan HTTPS.'
                });
                closeScannerModal();
            });
        });
    }

    function onScanSuccess(decodedText, decodedResult) {
        // Play beep sound
        playBeep();
        
        // Stop scanner temporarily for UX
        html5QrcodeScanner.pause();

        // Process Scan
        console.log('Barcode ditemukan:', decodedText);
        
        const searchInput = document.getElementById('product-search');
        searchInput.value = decodedText;
        
        closeScannerModal();
        
        Swal.fire({
             toast: true,
             position: 'top-end',
             icon: 'success',
             title: 'Barcode Berhasil Discanned',
             timer: 1500,
             showConfirmButton: false
         });
         
        loadProducts(); 
    }

    function onScanError(error) {
        // Ignore scan errors during continuous scanning
    }

    function playBeep() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            oscillator.frequency.value = 1000;
            oscillator.type = 'sine';
            gainNode.gain.value = 0.3;
            oscillator.start();
            setTimeout(() => oscillator.stop(), 100);
        } catch (e) {
            console.log('Audio not supported', e);
        }
    }

    function closeScannerModal() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                html5QrcodeScanner = null;
                document.getElementById('scanner-modal').classList.add('hidden');
            }).catch(err => {
                console.error('Error stopping scanner:', err);
                html5QrcodeScanner = null;
                document.getElementById('scanner-modal').classList.add('hidden');
            });
        } else {
             document.getElementById('scanner-modal').classList.add('hidden');
        }
    }

    // === PAYMENT CHANNEL UI ===
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethodSelect = document.getElementById('payment-method');
        if (paymentMethodSelect) {
            paymentMethodSelect.addEventListener('change', async function() {
                const method = this.value;
                const channelContainer = document.getElementById('channel-selector-container');
                const channelInput = document.getElementById('payment-channel');
                
                channelInput.innerHTML = '';
                channelInput.value = '';
            
            if (DIGITAL_METHODS.includes(method)) {
                // Khusus QRIS: kita hide containernya dan auto-select provider pertama
                if (method === 'qris') {
                    channelContainer.classList.add('hidden');
                } else {
                    channelContainer.classList.remove('hidden');
                    channelInput.innerHTML = '<option value="">Memuat provider...</option>';
                }
                
                try {
                    const response = await fetch(`/pos/payment-channels?method=${method}`);
                    const res = await response.json();
                    
                    if (res.success && res.data.length > 0) {
                        if (method === 'qris') {
                            // Langsung otomatis assign QRIS channel pertama
                            channelInput.innerHTML = `<option value="${res.data[0].code}">${res.data[0].name}</option>`;
                            channelInput.value = res.data[0].code;
                        } else {
                            channelInput.innerHTML = '<option value="">-- Pilih Provider --</option>' + res.data.map(ch => `
                                <option value="${ch.code}">${ch.name}</option>
                            `).join('');
                        }
                    } else {
                        if (method !== 'qris') {
                            channelInput.innerHTML = '<option value="">Provider tidak tersedia / belum dikonfigurasi</option>';
                        } else {
                            channelInput.innerHTML = '<option value="">QRIS tidak dikonfigurasi</option>';
                        }
                    }
                } catch (e) {
                    if (method !== 'qris') {
                        channelInput.innerHTML = '<option value="">Gagal memuat provider</option>';
                    }
                }
            } else {
                channelContainer.classList.add('hidden');
            }
        });
        }
    });

    // === QUICK ADD CUSTOMER ===
    function closeAddCustomerModal() {
        document.getElementById('add-customer-modal').classList.add('hidden');
        document.getElementById('new-customer-name').value = '';
        document.getElementById('new-customer-phone').value = '';
    }

    async function saveNewCustomer() {
        const nameInput = document.getElementById('new-customer-name');
        const phoneInput = document.getElementById('new-customer-phone');
        const btnText = document.getElementById('btn-save-customer-text');
        const btnLoading = document.getElementById('btn-save-customer-loading');

        const name = nameInput.value.trim();
        const phone = phoneInput.value.trim();

        if (!name) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Nama pelanggan wajib diisi!' });
            return;
        }

        // Show loading
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');

        try {
            const response = await fetch("{{ route('customers.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name, phone })
            });

            const result = await response.json();

            if (result.success) {
                // Success
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Pelanggan berhasil ditambahkan!',
                    timer: 1500,
                    showConfirmButton: false
                });

                closeAddCustomerModal();

                // Add to Alpine.js data in the payment modal
                // We need access to the Alpine component scope. 
                // Since it's inside x-data, retrieving it externally is tricky.
                // Workaround: We dispatch a custom event or reload the page. 
                // BUT user wants NO RELOAD.
                // Let's try to push to the window global if we expose it, or dispatch event.
                
                // Better approach: Since Alpine creates a component scope, let's dispatch an event 
                // and listen for it inside the x-data.
                
                // Dispatch event with new customer data
                window.dispatchEvent(new CustomEvent('customer-added', { 
                    detail: { name: result.customer.name, phone: result.customer.phone || '-' } 
                }));

            } else {
                throw new Error(result.message || 'Gagal menyimpan data');
            }
        } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', title: 'Gagal', text: error.message || 'Terjadi kesalahan saat menyimpan.' });
        } finally {
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
        }
    }

    // === JASA MODAL & CART LOGIC ===
    let pendingJasaItem = null;

    function openEmployeeModal(itemData) {
        pendingJasaItem = itemData;
        document.getElementById('employee-modal-item-name').textContent = itemData.name;
        document.getElementById('selected-employee-id').value = ""; // reset
        document.getElementById('employee-modal').classList.remove('hidden');
    }

    function closeEmployeeModal() {
        pendingJasaItem = null;
        document.getElementById('employee-modal').classList.add('hidden');
    }

    function confirmEmployeeSelection() {
        if (!pendingJasaItem) return;
        const selector = document.getElementById('selected-employee-id');
        const empId = selector.value;
        const empName = empId ? selector.options[selector.selectedIndex].text : null;
        
        executeAddToCart(pendingJasaItem.id, pendingJasaItem.name, pendingJasaItem.price, pendingJasaItem.stock, pendingJasaItem.image, pendingJasaItem.type, empId, empName);
        closeEmployeeModal();
    }

    function addToCart(id, name, price, stock, image, type = 'barang') {
        if (type === 'jasa') {
            openEmployeeModal({id, name, price, stock, image, type});
            return;
        }
        executeAddToCart(id, name, price, stock, image, type, null, null);
    }

    function executeAddToCart(id, name, price, stock, image, type, employee_id, employee_name) {
        const cartId = type === 'jasa' ? id + '-' + (employee_id || 'none') : String(id);
        const existingItem = cart.find(item => item.cartId === cartId);
        
        if (existingItem) {
            if (type === 'jasa' || existingItem.quantity < stock) {
                existingItem.quantity++;
            } else {
                alert('Stok tidak mencukupi!');
            }
        } else {
            if (type === 'jasa' || stock > 0) {
                cart.push({ cartId, id, name, price, stock, image, type, employee_id, employee_name, quantity: 1 });
            } else {
                alert('Stok produk habis!');
            }
        }
        updateCartDisplay();
        document.getElementById('product-search').value = '';
        document.getElementById('product-search').focus();
    }

    function updateCartDisplay() {
        const container = document.getElementById('cart-items-container');
        const checkoutButton = document.getElementById('checkout-button');
        if (cart.length === 0) {
            container.innerHTML = `<div class="text-center text-gray-500 py-8"><svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 1.5M7 13l1.5 1.5M17 21a2 2 0 100-4 2 2 0 000 4zM9 21a2 2 0 100-4 2 2 0 000 4z"></path></svg>Keranjang kosong</div>`;
            checkoutButton.disabled = true;
        } else {
            container.innerHTML = cart.map(item => `
                <div class="flex items-center p-3 bg-gray-50 rounded-lg mb-3">
                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center mr-3 overflow-hidden flex-shrink-0">
                        ${item.image ? `<img src="/storage/${item.image}" alt="${item.name}" class="w-full h-full object-cover">` : `<span class="text-lg">📦</span>`}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 truncate">${item.name}</div>
                        ${item.employee_name ? `<div class="text-xs text-indigo-600 mb-1 font-medium">👨‍🔧 ${STORE_SETTINGS.employee_label || 'Pegawai'}: ${item.employee_name}</div>` : ''}
                        <div class="text-xs text-gray-500 mb-2">${formatRupiah(item.price)}</div>
                        <div class="flex items-center justify-between mt-2">
                            <div class="flex items-center space-x-1">
                                <button onclick="decreaseQuantity('${item.cartId}')" class="w-8 h-8 bg-gray-200 rounded text-lg font-bold hover:bg-gray-300 flex items-center justify-center text-gray-600">-</button>
                                <input type="number" 
                                       value="${item.quantity}" 
                                       min="1" 
                                       ${item.type !== 'jasa' ? `max="${item.stock}"` : ''}
                                       onchange="updateItemQuantity('${item.cartId}', this.value)"
                                       class="w-12 text-center text-sm border-gray-200 rounded focus:ring-indigo-500 focus:border-indigo-500 p-1 mx-1">
                                <button onclick="increaseQuantity('${item.cartId}')" class="w-8 h-8 bg-teal-600 text-white rounded text-lg font-bold hover:bg-teal-700 flex items-center justify-center">+</button>
                            </div>
                            <button onclick="removeFromCart('${item.cartId}')" class="w-6 h-6 bg-red-500 text-white rounded text-xs hover:bg-red-600">×</button>
                        </div>
                    </div>
                </div>
            `).join('');
            checkoutButton.disabled = false;
        }
        updateCartTotals();
    }

    function increaseQuantity(cartId) {
        const item = cart.find(item => item.cartId === cartId);
        if (item && (item.type === 'jasa' || item.quantity < item.stock)) {
            item.quantity++;
            updateCartDisplay();
        } else if (item) {
            alert('Stok tidak mencukupi!');
        }
    }

    function decreaseQuantity(cartId) {
        const item = cart.find(item => item.cartId === cartId);
        if (item && item.quantity > 1) {
            item.quantity--;
            updateCartDisplay();
        } else if (item) {
            removeFromCart(cartId);
        }
    }

    function removeFromCart(cartId) {
        cart = cart.filter(item => item.cartId !== cartId);
        updateCartDisplay();
    }

    function updateCartTotals() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        // Ambil nilai diskon dan pajak
        let discount = parseFloat(document.getElementById('discount-amount').value) || 0;
        let taxRate = parseFloat(document.getElementById('tax-rate').value) || 0;

        // Validasi diskon tidak boleh lebih besar dari subtotal
        if (discount > subtotal) {
            discount = subtotal;
            document.getElementById('discount-amount').value = subtotal;
        }

        // Hitung pajak dari subtotal setelah diskon (opsional: tergantung kebijakan toko, biasanya dari DPP)
        // Di sini kita asumsikan pajak dihitung dari (Subtotal - Diskon)
        const taxableAmount = Math.max(0, subtotal - discount);
        const tax = taxableAmount * (taxRate / 100);

        const total = Math.max(0, subtotal - discount + tax);

        document.getElementById('subtotal').textContent = formatRupiah(subtotal);
        document.getElementById('display-discount').textContent = `- ${formatRupiah(discount)}`;
        document.getElementById('display-tax').textContent = `+ ${formatRupiah(tax)}`;
        document.getElementById('total').textContent = formatRupiah(total);
        
        // Simpan total ke dataset agar mudah diambil
        document.getElementById('total').dataset.value = total;
    }

    function clearCart() {
        if (confirm('Anda yakin ingin mereset keranjang?')) {
            cart = [];
            updateCartDisplay();
        }
    }

    // === EVENT HANDLERS & FILTERS ===
    async function changePage(url) {
        if (!url) {
            return; // Hentikan jika URL tidak valid
        }

        // Ambil nomor halaman dari URL yang diklik
        const pageNumber = new URL(url).searchParams.get('page');

        if (!pageNumber) {
            return; // Hentikan jika tidak ada nomor halaman
        }

        // Update variabel halaman saat ini
        currentPage = pageNumber;

        // Panggil fungsi untuk memuat produk dari halaman yang baru
        await loadProducts();
    }

    function filterByCategory(category, element) {
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.classList.remove('active', 'text-white', 'bg-teal-600', 'border-teal-600');
            tab.classList.add('text-gray-500', 'border-transparent');
        });
        element.classList.add('active', 'text-white', 'bg-teal-600', 'border-teal-600');
        element.classList.remove('text-gray-500', 'border-transparent');
        currentCategory = category;
        currentPage = 1;
        loadProducts();
    }

    function changePerPage(newPerPage) {
        perPage = parseInt(newPerPage);
        currentPage = 1;
        loadProducts();
    }

    // === MODAL & TRANSACTION LOGIC ===
    function openPaymentModal() {
        if (cart.length === 0) return;
        
        // Ambil total yang sudah dihitung di updateCartTotals
        const total = parseFloat(document.getElementById('total').dataset.value) || 0;
        
        document.getElementById('modal-total').textContent = formatRupiah(total);
        document.getElementById('amount-paid').value = total;
        updateModalPayment();
        document.getElementById('payment-modal').classList.remove('hidden');
        document.getElementById('amount-paid').focus();
    }

    function closePaymentModal() {
        document.getElementById('payment-modal').classList.add('hidden');
    }

    function setQuickAmount(amount) {
        document.getElementById('amount-paid').value = amount;
        updateModalPayment();
    }

    function updatePointUI() {
        const points = parseInt(document.getElementById('customer-points').value) || 0;
        document.getElementById('max-points-label').textContent = `${points} pts`;
        document.getElementById('use-points').max = points;
        document.getElementById('use-points').value = 0;
        updateModalPayment();
    }

    function updateModalPayment() {
        const total = parseFloat(document.getElementById('total').dataset.value) || 0;
        
        let usePoints = parseInt(document.getElementById('use-points').value) || 0;
        const maxPoints = parseInt(document.getElementById('customer-points').value) || 0;
        if (usePoints > maxPoints) {
            usePoints = maxPoints;
            document.getElementById('use-points').value = usePoints;
        }

        const pointExchangeRate = STORE_SETTINGS.point_exchange_rate || 100;
        const pointDiscount = usePoints * pointExchangeRate;
        document.getElementById('point-discount-preview').textContent = `- Rp ${new Intl.NumberFormat('id-ID').format(pointDiscount)}`;
        
        const finalTotal = Math.max(0, total - pointDiscount);
        document.getElementById('modal-total').textContent = formatRupiah(finalTotal);

        const amount = parseFloat(document.getElementById('amount-paid').value) || 0;
        const change = Math.max(0, amount - finalTotal);
        document.getElementById('modal-change').textContent = formatRupiah(change);
    }

    let isProcessing = false; // Guard against double-submit

    // Metode digital yang diproses via Payment Gateway
    const DIGITAL_METHODS = ['qris', 'transfer', 'ewallet'];

    async function processTransaction() {
        if (isProcessing) return; // Prevent double-click
        isProcessing = true;

        const btnBayar = document.getElementById('btn-bayar');
        btnBayar.disabled = true;
        btnBayar.innerHTML = '<svg class="animate-spin h-5 w-5 mx-auto text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

        // Recalculate everything for safety
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        let discount = parseFloat(document.getElementById('discount-amount').value) || 0;
        let taxRate = parseFloat(document.getElementById('tax-rate').value) || 0;

        if (discount > subtotal) discount = subtotal;

        const taxableAmount = Math.max(0, subtotal - discount);
        const tax = taxableAmount * (taxRate / 100);
        const total = Math.max(0, subtotal - discount + tax);

        const paymentMethod = document.getElementById('payment-method').value;
        const paymentChannel = document.getElementById('payment-channel') ? document.getElementById('payment-channel').value : '';
        const isDigital = DIGITAL_METHODS.includes(paymentMethod);

        // Untuk digital payment, amount_paid = total (otomatis)
        const amountPaid = isDigital ? total : (parseFloat(document.getElementById('amount-paid').value) || 0);
        
        if (cart.length === 0) { resetProcessing(); return alert('Keranjang kosong!'); }
        if (!paymentMethod) { resetProcessing(); return alert('Pilih metode pembayaran!'); }
        if (isDigital && !paymentChannel) { resetProcessing(); return alert('Pilih provider pembayaran (E-Wallet/Bank/dll)!'); }
        if (!isDigital && amountPaid < total) { resetProcessing(); return alert('Jumlah bayar kurang!'); }

        const transactionData = {
            items: cart.map(item => ({ product_id: item.id, quantity: item.quantity, price: item.price, employee_id: item.employee_id })),
            payment_method: paymentMethod,
            payment_channel: paymentChannel,
            amount_paid: amountPaid,
            customer_name: document.getElementById('customer-name').value || 'Umum',
            table_id: document.getElementById('table-id') ? document.getElementById('table-id').value : null,
            discount: discount,
            tax: tax,
            subtotal: subtotal,
            note: document.getElementById('transaction-note').value,
            transaction_date: document.getElementById('transaction-date') ? document.getElementById('transaction-date').value : null,
            points_redeemed: parseInt(document.getElementById('use-points').value) || 0,
            pending_order_code: window.currentPendingOrderCode || null,
        };

        try {
            const response = await fetch('/pos/transaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(transactionData)
            });
            const result = await response.json();
            if (response.ok && result.success) {
                closePaymentModal();

                // Cek apakah ini pembayaran digital (ada data PG)
                if (result.payment) {
                    showPaymentPendingModal(result.transaction, result.payment);
                } else {
                    showSuccessModal(result.transaction);
                }
            } else {
                alert(result.message || 'Transaksi gagal!');
            }
        } catch (error) {
            console.error('Transaction error:', error);
            alert('Terjadi kesalahan saat memproses transaksi.');
        } finally {
            resetProcessing();
        }
    }

    // === PAYMENT GATEWAY PENDING MODAL ===
    let pgCheckInterval = null;

    function showPaymentPendingModal(transaction, payment) {
        window.currentTransaction = transaction;
        const method = transaction.payment_method;
        const provider = (payment.provider || '').toUpperCase();

        let contentHtml = '';

        if (method === 'qris') {
            // Tampilkan QR Code
            const qrUrl = payment.qr_url || payment.pay_url;
            contentHtml = `
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full mb-4">
                        📲 QRIS — ${provider}
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Scan QR Code di bawah ini dengan aplikasi e-wallet atau mobile banking Anda.</p>
                    ${qrUrl 
                        ? `<img src="${qrUrl}" alt="QR Code" class="mx-auto max-w-[250px] rounded-lg border-2 border-gray-200 shadow-md mb-4" onerror="this.outerHTML='<a href=\\'' + '${payment.pay_url}' + '\\'' + ' target=\\'_blank\\' class=\\'inline-block px-6 py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700\\'>Buka Halaman Pembayaran</a>'">`
                        : `<a href="${payment.pay_url}" target="_blank" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 mb-4">Buka Halaman Pembayaran</a>`
                    }
                    <p class="text-xs text-gray-400">Total: <strong>${formatRupiah(transaction.total_amount)}</strong></p>
                </div>
            `;
        } else if (method === 'transfer') {
            // Tampilkan Virtual Account / Link Bayar
            contentHtml = `
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full mb-4">
                        🏦 Transfer Bank — ${provider}
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Silakan transfer ke rekening berikut atau buka halaman pembayaran.</p>
                    <div class="bg-gray-50 border rounded-xl p-4 mb-4">
                        <p class="text-sm text-gray-500">Total Bayar</p>
                        <p class="text-2xl font-bold text-gray-900">${formatRupiah(transaction.total_amount)}</p>
                    </div>
                    <a href="${payment.pay_url}" target="_blank" class="inline-block w-full px-6 py-3 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 mb-2">Buka Halaman Pembayaran</a>
                </div>
            `;
        } else {
            // E-Wallet: Tampilkan link redirect
            contentHtml = `
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 bg-purple-100 text-purple-800 text-xs font-bold px-3 py-1 rounded-full mb-4">
                        📱 E-Wallet — ${provider}
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Pelanggan akan diarahkan ke aplikasi e-wallet untuk menyelesaikan pembayaran.</p>
                    <div class="bg-gray-50 border rounded-xl p-4 mb-4">
                        <p class="text-sm text-gray-500">Total Bayar</p>
                        <p class="text-2xl font-bold text-gray-900">${formatRupiah(transaction.total_amount)}</p>
                    </div>
                    <a href="${payment.pay_url}" target="_blank" class="inline-block w-full px-6 py-3 bg-purple-600 text-white rounded-lg font-bold hover:bg-purple-700 mb-2">Buka Pembayaran</a>
                </div>
            `;
        }

        const expiredAt = payment.expired_at ? new Date(payment.expired_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) : '-';

        Swal.fire({
            html: `
                <div class="py-2">
                    <div class="flex items-center justify-center gap-2 mb-2">
                        <div class="w-3 h-3 bg-yellow-400 rounded-full animate-pulse"></div>
                        <span class="text-sm font-bold text-yellow-600">Menunggu Pembayaran...</span>
                    </div>
                    <p class="text-xs text-gray-400 mb-5">Kode: ${transaction.transaction_code} · Batas: ${expiredAt}</p>
                    ${contentHtml}
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: '🔄 Cek Status Bayar',
            confirmButtonColor: '#3B82F6',
            showCancelButton: true,
            cancelButtonText: '⏳ Bayar Nanti',
            cancelButtonColor: '#9CA3AF',
            allowOutsideClick: false,
            allowEscapeKey: false,
            width: 420,
        }).then((result) => {
            if (result.isConfirmed) {
                checkPaymentStatus(transaction.transaction_code);
            } else {
                // Bayar Nanti — tutup dan reset
                Swal.fire({
                    icon: 'info',
                    title: 'Pembayaran Tertunda',
                    html: `<p class="text-sm">Transaksi <strong>${transaction.transaction_code}</strong> masih <span class="text-yellow-600 font-bold">pending</span>.</p><p class="text-xs text-gray-400 mt-2">Status akan otomatis berubah saat pelanggan membayar.</p>`,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3B82F6',
                });
                cart = [];
                updateCartDisplay();
                loadProducts();
            }
        });

        // Auto-poll setiap 10 detik
        if (pgCheckInterval) clearInterval(pgCheckInterval);
        pgCheckInterval = setInterval(() => {
            checkPaymentStatusSilent(transaction.transaction_code);
        }, 10000);
    }

    async function checkPaymentStatus(transactionCode) {
        Swal.fire({ title: 'Mengecek...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const res = await fetch(`/pos/transaction/${transactionCode}/status`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await res.json();

            if (data.status === 'completed') {
                if (pgCheckInterval) clearInterval(pgCheckInterval);
                Swal.fire({ icon: 'success', title: 'Pembayaran Berhasil! ✅', text: `Transaksi ${transactionCode} telah lunas.`, confirmButtonColor: '#10B981' });
                cart = [];
                updateCartDisplay();
                loadProducts();
            } else if (data.status === 'cancelled') {
                if (pgCheckInterval) clearInterval(pgCheckInterval);
                Swal.fire({ icon: 'error', title: 'Pembayaran Gagal', text: 'Transaksi ini telah dibatalkan atau expired.', confirmButtonColor: '#EF4444' });
                cart = [];
                updateCartDisplay();
                loadProducts();
            } else {
                Swal.fire({
                    icon: 'warning', title: 'Belum Dibayar',
                    html: `<p class="text-sm">Status masih <strong class="text-yellow-600">pending</strong>.</p><p class="text-xs text-gray-400 mt-2">Mohon tunggu pelanggan menyelesaikan pembayaran.</p>`,
                    confirmButtonText: '🔄 Cek Lagi',
                    confirmButtonColor: '#3B82F6',
                    showCancelButton: true,
                    cancelButtonText: '⏳ Bayar Nanti',
                    cancelButtonColor: '#9CA3AF',
                }).then((result) => {
                    if (result.isConfirmed) checkPaymentStatus(transactionCode);
                    else {
                        if (pgCheckInterval) clearInterval(pgCheckInterval);
                        cart = []; updateCartDisplay(); loadProducts();
                    }
                });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mengecek status pembayaran.' });
        }
    }

    async function checkPaymentStatusSilent(transactionCode) {
        try {
            const res = await fetch(`/pos/transaction/${transactionCode}/status`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await res.json();
            if (data.status === 'completed') {
                if (pgCheckInterval) clearInterval(pgCheckInterval);
                Swal.fire({ icon: 'success', title: 'Pembayaran Berhasil! ✅', text: `Transaksi ${transactionCode} telah lunas.`, confirmButtonColor: '#10B981' });
                cart = []; updateCartDisplay(); loadProducts();
            } else if (data.status === 'cancelled') {
                if (pgCheckInterval) clearInterval(pgCheckInterval);
                Swal.fire({ icon: 'error', title: 'Pembayaran Expired', text: 'Transaksi ini telah expired.', confirmButtonColor: '#EF4444' });
                cart = []; updateCartDisplay(); loadProducts();
            }
        } catch (e) { /* silent fail */ }
    }

    function resetProcessing() {
        isProcessing = false;
        const btnBayar = document.getElementById('btn-bayar');
        if (btnBayar) {
            btnBayar.disabled = false;
            btnBayar.innerHTML = 'Bayar';
        }
    }

    function showSuccessModal(transaction) {
        window.currentTransaction = transaction;
        document.getElementById('transaction-id').textContent = transaction.transaction_code;
        document.getElementById('success-total').textContent = formatRupiah(transaction.total_amount);
        document.getElementById('success-change').textContent = formatRupiah(transaction.change_amount);
        document.getElementById('success-modal').classList.remove('hidden');
        startCountdownTimer();
    }

    function closeSuccessModal() {
        if (countdownInterval) clearInterval(countdownInterval);
        document.getElementById('success-modal').classList.add('hidden');
        cart = [];
        updateCartDisplay();
        loadProducts();
    }

    function startCountdownTimer() {
        let timeLeft = 10;
        const countdownEl = document.getElementById('countdown-timer');
        countdownEl.textContent = timeLeft;
        if (countdownInterval) clearInterval(countdownInterval);
        countdownInterval = setInterval(() => {
            timeLeft--;
            countdownEl.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                closeSuccessModal();
            }
        }, 1000);
    }

    // === PRINTING LOGIC (via ThermalPrinter module) ===
    function _getReceiptData() {
        const tx = window.currentTransaction;
        if (!tx) { alert('Data transaksi tidak ditemukan!'); return null; }
        const receiptBytes = ThermalPrinter.generateReceipt(tx, STORE_SETTINGS, AUTH_USER.name);
        const receiptHTML = ThermalPrinter.generateReceiptHTML(tx, STORE_SETTINGS, AUTH_USER.name);
        return { receiptBytes, receiptHTML };
    }

    async function doPrintUSB() {
        const data = _getReceiptData();
        if (!data) return;
        try {
            await ThermalPrinter.printUSB(data.receiptBytes);
            ThermalPrinter.savePreference('usb');
        } catch (e) {
            console.error('USB print error:', e);
            Swal.fire({ icon: 'error', title: 'Gagal Print USB', text: e.message });
        }
    }

    async function doPrintBluetooth() {
        const data = _getReceiptData();
        if (!data) return;
        try {
            await ThermalPrinter.printBluetooth(data.receiptBytes);
            ThermalPrinter.savePreference('bluetooth');
        } catch (e) {
            console.error('Bluetooth print error:', e);
            Swal.fire({ icon: 'error', title: 'Gagal Print Bluetooth', text: e.message });
        }
    }

    function doPrintBrowser() {
        const data = _getReceiptData();
        if (!data) return;
        ThermalPrinter.printBrowser(data.receiptHTML);
        ThermalPrinter.savePreference('browser');
    }
</script>    <!-- RESTO MODE: PENDING ORDERS MODAL -->
    @if(data_get($settings, 'business_mode', 'retail') === 'resto')
    <div x-cloak x-show="showPendingOrders" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display:none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showPendingOrders" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showPendingOrders = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showPendingOrders" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b">
                    <div class="flex justify-between items-center bg-amber-50 p-4 rounded-lg border border-amber-200">
                        <div>
                            <h3 class="text-xl font-bold leading-6 text-amber-900" id="modal-title">🍽️ Antrean Pesanan Masuk</h3>
                            <p class="text-sm text-amber-700 mt-1">Daftar pesanan mandiri dari meja pelanggan.</p>
                        </div>
                        <button @click="showPendingOrders = false" class="text-amber-500 hover:text-amber-700">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    <div class="mt-4 max-h-[60vh] overflow-y-auto">
                        <template x-if="pendingOrders.length === 0">
                            <div class="text-center py-8 text-gray-500">
                                Belum ada pesanan masuk.
                            </div>
                        </template>
                        <div class="space-y-3">
                            <template x-for="order in pendingOrders" :key="order.id">
                                <div class="border border-gray-200 rounded-lg p-4 flex justify-between items-center hover:bg-gray-50 transition-colors cursor-pointer" @click="openPendingOrder(order)">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-bold text-lg text-gray-900" x-text="order.table_name"></span>
                                            <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded" x-text="order.transaction_code"></span>
                                            <template x-if="order.customer_name">
                                                <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded flex items-center gap-1 font-medium">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                    <span x-text="order.customer_name"></span>
                                                </span>
                                            </template>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-2 truncate max-w-sm" x-text="order.items_summary"></p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded" 
                                                  :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                                  x-text="order.payment_status === 'paid' ? 'SUDAH BAYAR (' + (order.payment_method || 'QRIS').toUpperCase() + ')' : 'BELUM LUNAS'"></span>
                                            
                                            <!-- KITCHEN STATUS -->
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded"
                                                  :class="{
                                                    'bg-orange-100 text-orange-700': order.order_status === 'pending',
                                                    'bg-blue-100 text-blue-700': order.order_status === 'processing',
                                                    'bg-green-100 text-green-700': order.order_status === 'completed'
                                                  }"
                                                  x-text="order.order_status === 'pending' ? 'DAPUR: MENUNGGU' : (order.order_status === 'processing' ? 'DAPUR: DIMASAK' : 'DAPUR: SELESAI')">
                                            </span>

                                            <span class="text-xs text-gray-400" x-text="order.time_ago"></span>
                                        </div>
                                    </div>
                                    <div class="text-right flex flex-col items-end">
                                        <span class="font-bold text-lg text-green-600 mb-2" x-text="formatRupiah(order.total_amount)"></span>
                                        <div class="flex gap-2">
                                            <button @click.stop="cancelPendingOrder(order)" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-3 py-2 rounded text-sm font-medium transition-colors">
                                                Batalkan
                                            </button>
                                            <button @click="openPendingOrder(order)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors shadow-sm">
                                                Buka & Proses
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    </div><!-- End of inner wrapper -->
</div><!-- End of pos-container -->

<!-- AlpineJS data for Resto Mode -->
<script>
    function posApp() {
        return {
            showPendingOrders: false,
            pendingOrders: [],
            
            get pendingOrdersCount() {
                return this.pendingOrders.length;
            },

            init() {
                @if(data_get($settings, 'business_mode', 'retail') === 'resto')
                this.fetchPendingOrders();
                setInterval(() => {
                    this.fetchPendingOrders();
                }, 10000); // Check every 10 seconds
                @endif
            },

            async fetchPendingOrders() {
                try {
                    const res = await fetch('{{ route('pos.api.orders.pending') }}');
                    if (res.ok) {
                        this.pendingOrders = await res.json();
                    }
                } catch (e) {
                    console.error('Gagal memuat antrean:', e);
                }
            },

            async cancelPendingOrder(order) {
                Swal.fire({
                    title: 'Tolak Pesanan?',
                    text: 'Apakah Anda yakin ingin membatalkan/menghapus pesanan ' + order.transaction_code + ' dari ' + order.table_name + '?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Tolak!',
                    cancelButtonText: 'Batal'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const response = await fetch(`/pos/api/orders/${order.transaction_code}/status`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({ status: 'cancelled' }) // Mengubah status menjadi cancelled
                            });

                            if (response.ok) {
                                this.pendingOrders = this.pendingOrders.filter(o => o.transaction_code !== order.transaction_code);
                                Swal.fire('Ditolak', 'Pesanan berhasil dibatalkan.', 'success');
                            } else {
                                Swal.fire('Gagal', 'Terjadi kesalahan saat menolak pesanan.', 'error');
                            }
                        } catch (e) {
                            console.error('Error cancelling order:', e);
                            Swal.fire('Gagal', 'Terjadi kesalahan pada sistem.', 'error');
                        }
                    }
                });
            },

            openPendingOrder(order) {
                // Confirm action via SweetAlert
                Swal.fire({
                    title: 'Proses Pesanan ' + order.table_name + '?',
                    text: "Order " + order.transaction_code + " senilai " + formatRupiah(order.total_amount) + " akan dimasukkan ke keranjang POS.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Pindahkan',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Close pending orders modal
                        this.showPendingOrders = false;
                        
                        // Dispatch event to update Alpine.js customer dropdown
                        window.dispatchEvent(new CustomEvent('select-customer', { 
                            detail: order.customer_name || 'Umum' 
                        }));
                        
                        // Set table picker
                        const tableEl = document.getElementById('table-id');
                        if (tableEl && order.table_name) {
                            // Find option by text matching table_name
                            Array.from(tableEl.options).forEach(opt => {
                                if (opt.text.includes(order.table_name)) {
                                    tableEl.value = opt.value;
                                }
                            });
                        }
                        
                        // Clear existing POS cart
                        clearCart();
                        
                        // Inject pending order items into the global POS cart 
                        // Note: addToCart comes from regular pos/index.blade.php javascript 
                        if (order.items && order.items.length > 0) {
                            order.items.forEach(item => {
                                // Add item multiple times based on qty or using custom add strategy
                                // Since addToCart only adds 1 qty or increments, we loop it
                                for (let i = 0; i < item.qty; i++) {
                                    addToCart(item.id, item.name, item.price, 999, item.image, item.type);
                                }
                            });
                        }
                        
                        // Set global tracking variable to indicate this is a pending order settlement
                        window.currentPendingOrderCode = order.transaction_code;
                        window.currentPendingOrderTable = order.table_name;
                        
                        Swal.fire({
                            title: 'Pesanan Dimuat',
                            text: 'Pilih metode pembayaran dan tekan Checkout untuk menyelesaikan.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            }
        }
    }
</script>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="{{ asset('js/thermal-printer.js') }}"></script>
@endsection
