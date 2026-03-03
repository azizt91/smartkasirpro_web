<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kitchen View - {{ $settings->store_name }}</title>
    
    <!-- Fonts & Tailwind -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body x-data="kitchenApp()" class="h-screen flex flex-col overflow-hidden">

    <!-- Header -->
    <header class="bg-gray-900 text-white px-6 py-4 flex items-center justify-between shadow-md z-10">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-black tracking-tight text-white flex items-center gap-2">
                🍳 Kitchen View
            </h1>
            <span class="bg-gray-800 text-gray-300 px-3 py-1 rounded-full text-sm font-medium border border-gray-700">
                Auto-refresh <span x-text="countdown"></span>s
            </span>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right">
                <p class="font-bold text-lg leading-tight" x-text="currentTime"></p>
                <p class="text-gray-400 text-xs flex justify-end items-center gap-2">
                    <span class="w-2 h-2 rounded-full" :class="isConnected ? 'bg-green-500' : 'bg-red-500'"></span>
                    <span x-text="isConnected ? 'Terhubung' : 'Terputus'"></span>
                </p>
            </div>
            <a href="{{ route('dashboard') }}" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors border border-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 overflow-x-auto hide-scroll p-6 bg-slate-900">
        <div class="flex gap-6 h-full items-start" style="min-width: max-content;">
            
            <template x-if="orders.length === 0">
                <div class="w-full mt-20 flex flex-col items-center justify-center text-center">
                    <span class="text-6xl mb-4">🍻</span>
                    <h2 class="text-2xl font-bold text-gray-400">Belum ada antrean pesanan</h2>
                    <p class="text-gray-500">Istirahat dulu sejenak!</p>
                </div>
            </template>

            <template x-for="(order, index) in orders" :key="order.id">
                <div class="w-80 bg-white rounded-xl shadow-xl flex flex-col overflow-hidden flex-shrink-0 border-2" 
                     :class="order.order_status === 'pending' ? 'border-red-400' : 'border-amber-400'">
                    
                    <!-- Card Header -->
                    <div class="px-5 py-4" :class="order.order_status === 'pending' ? 'bg-red-50' : 'bg-amber-50'">
                        <div class="flex justify-between items-start mb-2">
                            <span class="bg-white px-2.5 py-1 rounded shadow-sm text-sm font-bold border"
                                  :class="order.order_status === 'pending' ? 'text-red-700 border-red-200' : 'text-amber-700 border-amber-200'"
                                  x-text="'#' + order.transaction_code.substring(4)"></span>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full text-white" 
                                  :class="order.order_status === 'pending' ? 'bg-red-500' : 'bg-amber-500 animate-pulse'"
                                  x-text="order.order_status === 'pending' ? 'ANTREAN BARU' : 'SEDANG DIMASAK'"></span>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900" x-text="order.table_name"></h3>
                        <p class="text-sm font-medium flex items-center gap-1" :class="order.order_status === 'pending' ? 'text-red-600' : 'text-amber-600'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span x-text="order.time_ago"></span>
                        </p>
                    </div>

                    <!-- Order Items -->
                    <div class="p-5 flex-1 overflow-y-auto bg-white">
                        <ul class="space-y-4">
                            <template x-for="item in order.items">
                                <li class="flex items-start gap-3 pb-3 border-b border-gray-100 last:border-0 last:pb-0">
                                    <span class="text-lg font-black bg-gray-100 min-w-[32px] h-8 flex items-center justify-center rounded text-gray-700" x-text="item.qty"></span>
                                    <div>
                                        <p class="text-base font-bold text-gray-900 leading-tight" x-text="item.name"></p>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Card Actions -->
                    <div class="p-4 bg-gray-50 border-t border-gray-100">
                        <template x-if="order.order_status === 'pending'">
                            <div class="flex gap-2">
                                <button @click="updateStatus(order.transaction_code, 'processing')" tabindex="-1"
                                        class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg shadow-md transition-colors flex items-center justify-center gap-2 text-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"></path></svg>
                                    MASAK
                                </button>
                            </div>
                        </template>
                        <template x-if="order.order_status === 'processing'">
                            <div class="flex gap-2">
                                <button @click="updateStatus(order.transaction_code, 'completed')" tabindex="-1"
                                        class="w-full py-3 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg shadow-md transition-colors flex items-center justify-center gap-2 text-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    SELESAI
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            
        </div>
    </main>

    <script>
        function kitchenApp() {
            return {
                orders: [],
                countdown: 10,
                interval: null,
                timerInterval: null,
                currentTime: '',
                isConnected: true,

                init() {
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                    
                    this.fetchOrders();
                    
                    this.timerInterval = setInterval(() => {
                        this.countdown--;
                        if (this.countdown <= 0) {
                            this.fetchOrders();
                            this.countdown = 10;
                        }
                    }, 1000);
                },

                updateTime() {
                    const now = new Date();
                    this.currentTime = now.toLocaleTimeString('id-ID', { hour12: false });
                },

                async fetchOrders() {
                    try {
                        const res = await fetch('{{ route('pos.api.orders.kitchen') }}');
                        if (!res.ok) throw new Error('Network response was not ok');
                        this.orders = await res.json();
                        this.isConnected = true;
                        
                        // Check if any order is new (you could add sound here)
                    } catch (error) {
                        console.error('Failed to fetch orders:', error);
                        this.isConnected = false;
                    }
                },

                async updateStatus(code, status) {
                    try {
                        const res = await fetch(`/pos/api/orders/${code}/status`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ status: status })
                        });
                        
                        if (res.ok) {
                            // Optimistic update
                            if (status === 'completed' || status === 'cancelled') {
                                this.orders = this.orders.filter(o => o.transaction_code !== code);
                            } else {
                                const idx = this.orders.findIndex(o => o.transaction_code === code);
                                if (idx > -1) this.orders[idx].order_status = status;
                            }
                        }
                    } catch (error) {
                        alert('Gagal mengupdate status pesanan!');
                    }
                }
            }
        }
    </script>
</body>
</html>
