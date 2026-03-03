<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Pesanan Berhasil - {{ $settings->store_name }}</title>
    
    <!-- Fonts & Tailwind -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 max-w-md w-full text-center">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Pesanan Diterima!</h1>
        <p class="text-gray-500 mb-6">Terima kasih Kak <strong>{{ $transaction->customer_name }}</strong>, pesanan Anda sedang kami siapkan.</p>
        
        <div class="bg-gray-50 rounded-xl p-4 text-left mb-6 border border-gray-100">
            <div class="flex justify-between items-center mb-3">
                <span class="text-sm text-gray-500">Kode Pesanan</span>
                <span class="font-mono font-bold text-indigo-600">{{ $transaction->transaction_code }}</span>
            </div>
            <div class="flex justify-between items-center mb-3">
                <span class="text-sm text-gray-500">Meja</span>
                <span class="font-bold text-gray-900">{{ $transaction->table->nama_meja }}</span>
            </div>
            <div class="flex justify-between items-center mb-3">
                <span class="text-sm text-gray-500">Status Pembayaran</span>
                @if($transaction->payment_status === 'paid')
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold">LUNAS</span>
                @elseif($transaction->payment_status === 'unpaid' && $transaction->payment_method === 'cash')
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-bold">BAYAR DI KASIR</span>
                @else
                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-bold">BELUM LUNAS</span>
                @endif
            </div>
            
            <div class="border-t border-gray-200 my-3"></div>
            
            <div class="flex justify-between items-end">
                <span class="text-sm font-medium text-gray-900">Total Pembayaran</span>
                <span class="text-lg font-black text-gray-900">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        @if($transaction->payment_status === 'unpaid' && $transaction->pg_pay_url)
            <div class="mb-6 p-4 bg-yellow-50 rounded-xl border border-yellow-200">
                <p class="text-sm text-yellow-800 font-medium mb-3">Selesaikan pembayaran online Anda untuk mempercepat proses pesanan.</p>
                <a href="{{ $transaction->pg_pay_url }}" class="block w-full text-center bg-yellow-400 hover:bg-yellow-500 text-yellow-900 font-bold py-2 px-4 rounded-lg transition-colors">
                    Lanjut Bayar Online
                </a>
            </div>
        @endif

        <a href="{{ route('public.order', ['hash' => $transaction->table->hash_slug]) }}" class="block w-full text-center bg-indigo-50 border border-indigo-100 hover:bg-indigo-100 text-indigo-700 font-medium py-3 px-4 rounded-xl transition-colors">
            Kembali ke Menu
        </a>
    </div>

</body>
</html>
