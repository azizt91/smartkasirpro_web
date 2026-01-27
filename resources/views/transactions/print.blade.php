<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi #{{ $transaction->transaction_code }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 10px;
            width: 58mm; /* Standard thermal paper width */
            background-color: #fff;
        }
        @media print {
            body {
                width: 100%;
                margin: 0;
                padding: 0;
            }
            @page {
                margin: 0;
                size: auto;
            }
            .no-print {
                display: none !important;
            }
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .border-top { border-top: 1px dashed black; margin: 5px 0; }
        .border-bottom { border-bottom: 1px dashed black; margin: 5px 0; }
        .flex { display: flex; justify-content: space-between; }
        .mb-1 { margin-bottom: 2px; }
        .store-name { font-size: 16px; font-weight: bold; margin-bottom: 5px; }
        
        /* Action Buttons */
        .actions {
            margin-bottom: 20px;
            text-align: center;
            padding: 10px;
            background: #f3f4f6;
            border-radius: 8px;
        }
        .btn {
            background-color: #4f46e5;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-family: sans-serif;
            font-weight: bold;
            border: none;
            cursor: pointer;
            margin: 0 5px;
            display: inline-flex;
            align-items: center;
        }
        .btn-bluetooth { background-color: #2563eb; }
        .btn-print { background-color: #4b5563; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="actions no-print">
        <button onclick="printBluetooth()" class="btn btn-bluetooth">
            🖨️ Print Bluetooth
        </button>
        <button onclick="window.print()" class="btn btn-print">
            📄 Print Browser
        </button>
    </div>

    <div class="text-center">
        <div class="store-name">{{ $storeSettings->store_name }}</div>
        <div>{{ $storeSettings->store_address }}</div>
        <div>Telp: {{ $storeSettings->store_phone }}</div>
    </div>

    <div class="border-top"></div>

    <div>
        <div class="flex">
            <span>No</span>
            <span>: {{ $transaction->transaction_code }}</span>
        </div>
        <div class="flex">
            <span>Tgl</span>
            <span>: {{ $transaction->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="flex">
            <span>Kasir</span>
            <span>: {{ $transaction->user->name ?? '-' }}</span>
        </div>
        @if($transaction->customer_name && $transaction->customer_name !== 'Umum')
        <div class="flex">
            <span>Cust</span>
            <span>: {{ $transaction->customer_name }}</span>
        </div>
        @endif
    </div>

    <div class="border-top"></div>

    <div>
        @foreach($transaction->items as $item)
            <div class="mb-1">
                <div>{{ $item->product->name }}</div>
                <div class="flex">
                    <span>&nbsp;&nbsp;{{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}</span>
                    <span>{{ number_format($item->quantity * $item->price, 0, ',', '.') }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="border-top"></div>

    <div class="text-right">
        <div class="flex font-bold">
            <span>Total:</span>
            <span>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
        </div>
        <div class="flex">
            <span>Metode:</span>
            <span style="text-transform: capitalize;">{{ $transaction->payment_method }}</span>
        </div>
        <!-- Check POS logic: Discount and Tax display -->
        @if($transaction->discount > 0)
        <div class="flex">
            <span>Diskon:</span>
            <span>-Rp {{ number_format($transaction->discount, 0, ',', '.') }}</span>
        </div>
        @endif
        @if($transaction->tax > 0)
        <div class="flex">
            <span>Pajak:</span>
            <span>Rp {{ number_format($transaction->tax, 0, ',', '.') }}</span>
        </div>
        @endif
        
        @if($transaction->payment_method !== 'utang')
        <div class="flex">
            <span>Bayar:</span>
            <span>Rp {{ number_format($transaction->amount_paid, 0, ',', '.') }}</span>
        </div>
        <div class="flex">
            <span>Kembali:</span>
            <span>Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
        </div>
        @endif
    </div>

    @if($transaction->payment_method === 'utang')
    <div class="border-top"></div>
    <div class="text-center font-bold" style="background: #f0f0f0; border: 1px dashed black; padding: 5px;">
        ⚠️ BELUM DIBAYAR - PIUTANG
    </div>
    @endif

    <div class="border-top"></div>

    <div class="text-center" style="margin-top: 10px;">
        {!! nl2br(e($storeSettings->store_description)) !!}
    </div>

    <script>
        // Data Transaksi & Settings untuk JS
        const transaction = @json($transaction);
        const storeSettings = @json($storeSettings);
        const authUser = { name: "{{ $transaction->user->name ?? '-' }}" }; // Use transaction user as cashier

        // [FIX] Use simpler formatting to avoid encoding issues (ta characters)
        function formatRupiah(number) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
        }

        async function printBluetooth() {
            try {
                if (!navigator.bluetooth) {
                     alert('Web Bluetooth tidak didukung di browser ini. Gunakan Chrome di Android/Desktop.');
                     return;
                }
                
                const device = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb']
                });

                const server = await device.gatt.connect();
                const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
                const characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');
                
                const receiptData = generateThermalReceiptData();
                
                // [FIX] Chunking data to avoid MTU limit (512 bytes)
                const chunkSize = 100; // Safe chunk size
                for (let i = 0; i < receiptData.byteLength; i += chunkSize) {
                    const chunk = receiptData.slice(i, i + chunkSize);
                    await characteristic.writeValue(chunk);
                    // Add small delay to ensure device processes chunk
                    await new Promise(resolve => setTimeout(resolve, 50)); 
                }
                
                alert('Struk berhasil dikirim ke printer!');
            } catch (error) {
                console.error('Bluetooth error:', error);
                alert('Gagal mencetak via Bluetooth: ' + error.message);
            }
        }

        function generateThermalReceiptData() {
            const tx = transaction;
            const now = new Date(tx.created_at); // Use transaction date
            const ESC = '\x1B', GS = '\x1D';
            const isUtang = tx.payment_method === 'utang';
            const paymentMethodLabels = {
                'cash': 'Tunai',
                'utang': 'UTANG',
                'card': 'Kartu',
                'ewallet': 'E-Wallet',
                'transfer': 'Transfer'
            };
            
            let receipt = '';
            receipt += ESC + '@'; // Initialize
            receipt += ESC + 'a' + '\x01'; // Center
            receipt += ESC + '!' + '\x18'; // Double height & width
            receipt += `${storeSettings.store_name}\n`;
            receipt += ESC + '!' + '\x00'; // Normal
            receipt += `${storeSettings.store_address}\n`;
            receipt += `Telp: ${storeSettings.store_phone}\n`;
            receipt += '================================\n';
            
            receipt += ESC + 'a' + '\x00'; // Left align
            receipt += `No: ${tx.transaction_code}\n`;
            // Format Date manually or use toLocaleString logic ensuring consistency
            const dateStr = new Date(tx.created_at).toLocaleDateString('id-ID');
            const timeStr = new Date(tx.created_at).toLocaleTimeString('id-ID');

            receipt += `Tgl: ${dateStr} ${timeStr}\n`;
            receipt += `Kasir: ${authUser.name}\n`;
            
            if (tx.customer_name && tx.customer_name !== 'Umum') {
                receipt += `Customer: ${tx.customer_name}\n`;
            }
            
            receipt += '================================\n';
            
            tx.items.forEach(item => {
                const productName = item.product ? item.product.name : 'Item Terhapus';
                const price = parseFloat(item.price);
                const qty = parseInt(item.quantity);
                const sub = parseFloat(item.price) * qty; // Calculate purely based on item data first
                 // OR use stored subtotal if available in pivot
                
                receipt += `${productName}\n`;
                receipt += `  ${qty} x ${formatRupiah(price)} = ${formatRupiah(sub)}\n`;
            });
            
            receipt += '================================\n';
            receipt += ESC + 'a' + '\x02'; // Right align
            
            receipt += `Subtotal: ${formatRupiah(tx.subtotal)}\n`;
            if (parseFloat(tx.discount) > 0) {
                receipt += `Diskon: -${formatRupiah(tx.discount)}\n`;
            }
            if (parseFloat(tx.tax) > 0) {
                receipt += `Pajak: ${formatRupiah(tx.tax)}\n`;
            }
            
            receipt += `Total: ${formatRupiah(tx.total_amount)}\n`;
            receipt += `Metode: ${paymentMethodLabels[tx.payment_method] || tx.payment_method}\n`;
            
            if (!isUtang) {
                receipt += `Bayar: ${formatRupiah(tx.amount_paid)}\n`;
                receipt += `Kembali: ${formatRupiah(tx.change_amount)}\n`;
            }
            
            receipt += ESC + 'a' + '\x01'; // Center
            if (isUtang) {
                receipt += '--------------------------------\n';
                receipt += ESC + '!' + '\x08'; // Bold
                receipt += '** BELUM DIBAYAR - PIUTANG **\n';
                receipt += ESC + '!' + '\x00'; // Normal
            }
            receipt += '================================\n';
            
            // Use store description from settings
            if (storeSettings.store_description) {
                receipt += storeSettings.store_description + '\n\n\n';
            } else {
                receipt += 'Terima kasih!\n\n\n';
            }
            
            receipt += GS + 'V' + '\x41' + '\x03'; // Cut
            
            return new TextEncoder().encode(receipt);
        }
    </script>
</body>
</html>
