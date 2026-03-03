<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Menu - {{ $settings->store_name }}</title>
    
    <!-- Fonts & Tailwind -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body x-data="orderApp()" class="pb-24">

    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <div class="px-4 py-3 flex items-center gap-3">
            @if($settings->store_logo)
                <img src="{{ asset('storage/' . $settings->store_logo) }}" alt="Logo" class="h-10 w-10 object-contain rounded">
            @else
                <div class="h-10 w-10 bg-indigo-100 text-indigo-600 rounded flex items-center justify-center font-bold text-xl">
                    {{ substr($settings->store_name, 0, 1) }}
                </div>
            @endif
            <div>
                <h1 class="font-bold text-gray-900 leading-tight">{{ $settings->store_name }}</h1>
                <p class="text-xs text-indigo-600 font-medium whitespace-nowrap overflow-hidden text-ellipsis max-w-[200px] sm:max-w-xs">
                    📍 Meja: {{ $table->nama_meja }}
                </p>
            </div>
        </div>

        <!-- Categories Slider -->
        <div class="border-t border-gray-100 bg-white px-4 py-2 overflow-x-auto hide-scroll whitespace-nowrap flex gap-2">
            <button @click="setCategory('all')" 
                    :class="activeCategory === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600'" 
                    class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors">Semua</button>
            @foreach($categories as $category)
            <button @click="setCategory({{ $category->id }})" 
                    :class="activeCategory === {{ $category->id }} ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600'" 
                    class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors">{{ $category->name }}</button>
            @endforeach
        </div>
    </header>

    <!-- Product List -->
    <main class="p-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
        <template x-for="product in products" :key="product.id">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full relative">
                <div class="aspect-square bg-gray-50 flex items-center justify-center p-2">
                    <template x-if="product.image">
                        <img :src="product.image" :alt="product.name" class="w-full h-full object-contain rounded-lg">
                    </template>
                    <template x-if="!product.image">
                        <span class="text-3xl">🍽️</span>
                    </template>
                </div>
                <div class="p-3 flex-1 flex flex-col">
                    <h3 class="text-sm font-medium text-gray-900 line-clamp-2 leading-tight flex-1" x-text="product.name"></h3>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-sm font-bold text-teal-600" x-text="formatMoney(product.price)"></span>
                        <div class="flex items-center gap-2">
                            <!-- Show add/min buttons if in cart -->
                            <template x-if="getCartQty(product.id) > 0">
                                <div class="flex items-center bg-gray-100 rounded-lg">
                                    <button @click="updateCart(product, -1)" class="w-7 h-7 flex items-center justify-center text-gray-600 hover:text-indigo-600 focus:outline-none focus:bg-gray-200 rounded-l-lg">-</button>
                                    <span class="w-6 text-center text-xs font-bold text-gray-900" x-text="getCartQty(product.id)"></span>
                                    <button @click="updateCart(product, 1)" class="w-7 h-7 flex items-center justify-center text-gray-600 hover:text-indigo-600 focus:outline-none focus:bg-gray-200 rounded-r-lg">+</button>
                                </div>
                            </template>
                            <!-- Show Add button if not in cart -->
                            <template x-if="getCartQty(product.id) === 0">
                                <button @click="updateCart(product, 1)" class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border border-indigo-100 flex items-center justify-center transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Loading State -->
        <div x-show="loading" class="col-span-2 text-center py-10" style="display:none;">
            <svg class="animate-spin h-8 w-8 text-indigo-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500">Memuat menu...</p>
        </div>
        
        <!-- Empty State -->
        <div x-show="!loading && products.length === 0" class="col-span-2 text-center py-10" style="display:none;">
            <span class="text-4xl block mb-2">🍽️</span>
            <p class="text-gray-500">Kategori ini kosong atau stok habis.</p>
        </div>
    </main>

    <!-- Floating View Cart Button -->
    <div x-show="cart.length > 0" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-20 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" class="fixed bottom-4 left-0 w-full px-4 z-40" style="display:none;">
        <button @click="checkoutModal = true" class="w-full bg-indigo-600 text-white rounded-xl shadow-lg p-4 flex items-center justify-between hover:bg-indigo-700 transition">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full" x-text="totalItems"></span>
                </div>
                <div class="text-left">
                    <p class="text-xs text-indigo-200">Total Pesanan</p>
                    <p class="font-bold text-sm" x-text="formatMoney(cartTotal)"></p>
                </div>
            </div>
            <div class="font-medium text-sm flex items-center">
                Lanjut Pesan 
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </div>
        </button>
    </div>

    <!-- Checkout Modal -->
    <div x-show="checkoutModal" style="display:none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="checkoutModal" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="checkoutModal = false"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="checkoutModal" x-transition.scale class="inline-block align-bottom bg-white rounded-t-2xl sm:rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full sm:max-w-lg pb-safe">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-100 sticky top-0 bg-white z-10 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Konfirmasi Pesanan</h3>
                    <button @click="checkoutModal = false" class="text-gray-400 hover:text-gray-500 rounded-full p-1 border border-transparent focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="max-h-[60vh] overflow-y-auto p-4 space-y-4">
                    <!-- Form Customer -->
                    <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100">
                        <p class="text-xs font-semibold text-indigo-800 uppercase tracking-wider mb-2">Informasi Pemesan</p>
                        <div class="space-y-3">
                            <div>
                                <input type="text" x-model="customerName" placeholder="Nama Anda*" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <input type="tel" x-model="customerPhone" placeholder="Nomor WhatsApp (Cth: 0812...)" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="text-[10px] text-gray-500 mt-1">Digunakan untuk mengirim bukti pesanan (struk digital).</p>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Summary -->
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Ringkasan Pesanan</p>
                        <ul class="divide-y divide-gray-100">
                            <template x-for="item in cart" :key="item.id">
                                <li class="py-2 flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900" x-text="item.name"></p>
                                        <p class="text-xs text-gray-500"><span x-text="item.qty"></span> x <span x-text="formatMoney(item.price)"></span></p>
                                    </div>
                                    <p class="text-sm font-bold text-gray-900" x-text="formatMoney(item.price * item.qty)"></p>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Pilih Pembayaran</p>
                        <select x-model="paymentMethod" @change="paymentChannel = ''" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50 mb-3">
                            <option value="cash">🏦 Bayar Kasir Nanti</option>
                            @if($settings->pg_active !== 'none')
                            <option value="qris">📲 QRIS</option>
                            <option value="transfer">💳 Transfer Bank (Virtual Account)</option>
                            <option value="ewallet">📱 E-Wallet</option>
                            @endif
                        </select>
                        
                        <!-- Dynamic Channels for Transfer -->
                        <div x-show="paymentMethod === 'transfer'" x-transition class="bg-blue-50/50 p-3 rounded-lg border border-blue-100 mb-3" style="display:none;">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Bank</label>
                            <select x-model="paymentChannel" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 py-1.5">
                                <option value="">-- Pilih Bank --</option>
                                @foreach($transferChannels ?? [] as $channel)
                                    <option value="{{ $channel['code'] }}">{{ $channel['name'] }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-gray-500 mt-1">Sistem akan membuat Virtual Account khusus untuk pembayaran ini.</p>
                        </div>

                        <!-- Dynamic Channels for E-Wallet -->
                        <div x-show="paymentMethod === 'ewallet'" x-transition class="bg-blue-50/50 p-3 rounded-lg border border-blue-100 mb-3" style="display:none;">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Pilih E-Wallet</label>
                            <select x-model="paymentChannel" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 py-1.5">
                                <option value="">-- Pilih E-Wallet --</option>
                                @foreach($ewalletChannels ?? [] as $channel)
                                    <option value="{{ $channel['code'] }}">{{ $channel['name'] }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-gray-500 mt-1">Anda akan diarahkan ke aplikasi E-Wallet terkait.</p>
                        </div>
                        
                        <p class="text-[10px] text-gray-500">Pilihan metode pembayaran online tersedia jika diaktifkan oleh restoran.</p>
                    </div>
                </div>

                <!-- Footer Summary -->
                <div class="bg-gray-50 px-4 py-4 sm:px-6 border-t border-gray-200">
                    <div class="flex justify-between items-end mb-4">
                        <div class="text-xs text-gray-500">
                            <p>Subtotal: <span x-text="formatMoney(cartTotal)"></span></p>
                            <p>Pajak/Tax: <span x-text="formatMoney(cartTotal * (parseFloat({{ $settings->tax_rate ?? 0 }}) / 100))"></span></p>
                        </div>
                        <div class="text-right">
                            <span class="block text-xs font-medium text-gray-500">Total Pembayaran</span>
                            <span class="block text-lg font-black text-gray-900" x-text="formatMoney(cartTotal + (cartTotal * (parseFloat({{ $settings->tax_rate ?? 0 }}) / 100)))"></span>
                        </div>
                    </div>

                    <button @click="submitOrder" :disabled="isSubmitting" class="w-full inline-flex justify-center rounded-xl border border-transparent px-4 py-3 bg-indigo-600 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isSubmitting">Kirim ke Dapur <span x-text="'('+totalItems+' item)'"></span></span>
                        <span x-show="isSubmitting" class="flex items-center">
                            Memproses...
                        </span>
                    </button>
                    <p x-show="errorMessage" class="text-xs text-red-500 text-center mt-2 font-medium" x-text="errorMessage"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function orderApp() {
            return {
                products: [],
                allProducts: [],
                categories: [],
                cart: [],
                activeCategory: 'all',
                loading: true,
                checkoutModal: false,
                isSubmitting: false,
                customerName: '',
                customerPhone: '',
                paymentMethod: 'cash',
                paymentChannel: '',
                errorMessage: '',
                hash: '{{ $table->hash_slug }}',

                init() {
                    this.fetchProducts();
                },

                fetchProducts() {
                    this.loading = true;
                    // Simple fetch
                    fetch(`/api/order/products?category_id=${this.activeCategory}`)
                        .then(res => res.json())
                        .then(data => {
                            this.products = data;
                            if(this.activeCategory === 'all') this.allProducts = [...data];
                            this.loading = false;
                        });
                },

                setCategory(id) {
                    this.activeCategory = id;
                    if (id === 'all') {
                        this.products = [...this.allProducts];
                    } else {
                        this.products = this.allProducts.filter(p => true); // Normally filter here, but we re-fetch to ensure fresh stock if needed or rely on local. We just fetch for simplicity.
                        this.fetchProducts();
                    }
                },

                getCartQty(productId) {
                    const item = this.cart.find(i => i.id === productId);
                    return item ? item.qty : 0;
                },

                updateCart(product, change) {
                    const index = this.cart.findIndex(i => i.id === product.id);
                    if (index >= 0) {
                        this.cart[index].qty += change;
                        if (this.cart[index].qty <= 0) {
                            this.cart.splice(index, 1);
                        }
                    } else if (change > 0) {
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            price: product.price,
                            qty: 1
                        });
                    }
                },

                get totalItems() {
                    return this.cart.reduce((total, item) => total + item.qty, 0);
                },

                get cartTotal() {
                    return this.cart.reduce((total, item) => total + (item.price * item.qty), 0);
                },

                formatMoney(amount) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
                },

                async submitOrder() {
                    if(!this.customerName.trim()) {
                        this.errorMessage = 'Nama harus diisi!';
                        return;
                    }
                    if(this.cart.length === 0) {
                        this.errorMessage = 'Cart kosong!';
                        return;
                    }

                    if(this.paymentMethod === 'transfer' && !this.paymentChannel) {
                        this.errorMessage = 'Pilih Bank terlebih dahulu!';
                        return;
                    }
                    if(this.paymentMethod === 'ewallet' && !this.paymentChannel) {
                        this.errorMessage = 'Pilih E-Wallet terlebih dahulu!';
                        return;
                    }

                    this.isSubmitting = true;
                    this.errorMessage = '';

                    const payload = {
                        customer_name: this.customerName,
                        customer_phone: this.customerPhone,
                        payment_method: this.paymentMethod,
                        payment_channel: this.paymentChannel,
                        items: this.cart
                    };

                    try {
                        const response = await fetch(`/order/${this.hash}/submit`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await response.json();
                        
                        if (!response.ok) throw new Error(data.message || 'Gagal mengirim pesanan');

                        if (data.success) {
                            if (data.pay_url) {
                                window.location.href = data.pay_url;
                            } else {
                                window.location.href = data.redirect;
                            }
                        }
                    } catch (err) {
                        this.errorMessage = err.message;
                        this.isSubmitting = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
