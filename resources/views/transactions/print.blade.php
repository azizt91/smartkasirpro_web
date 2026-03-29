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
            padding: 12px;
            background: #f3f4f6;
            border-radius: 8px;
        }
        .btn-group {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-family: sans-serif;
            font-weight: bold;
            font-size: 13px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-usb { background-color: #6366f1; }
        .btn-bluetooth { background-color: #2563eb; }
        .btn-print { background-color: #4b5563; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="actions no-print">
        <div class="btn-group">
            <button onclick="doPrintUSB()" class="btn btn-usb">
                🔌 USB
            </button>
            <button onclick="doPrintBluetooth()" class="btn btn-bluetooth">
                📶 Bluetooth
            </button>
            <button onclick="window.print()" class="btn btn-print">
                📄 Browser
            </button>
        </div>
    </div>

    <div class="text-center">
        @if(!empty($storeSettings->store_logo))
            <img src="{{ asset('storage/' . $storeSettings->store_logo) }}" alt="Logo" style="max-width: 100%; max-height: 80px; display: block; margin: 0 auto 5px auto;">
        @endif
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
                @if($item->employee)
                <div style="font-size: 10px; margin-left: 10px;">(Kapster: {{ $item->employee->name }})</div>
                @endif
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

    <script src="{{ asset('js/thermal-printer.js') }}"></script>
    <script>
        // Data Transaksi & Settings untuk JS
        const transaction = @json($transaction);
        const storeSettings = @json($storeSettings);
        const authUser = { name: "{{ $transaction->user->name ?? '-' }}" };

        async function doPrintUSB() {
            try {
                const receiptData = ThermalPrinter.generateReceipt(transaction, storeSettings, authUser.name);
                await ThermalPrinter.printUSB(receiptData);
                ThermalPrinter.savePreference('usb');
                alert('✅ Struk berhasil dikirim ke printer USB!');
            } catch (error) {
                console.error('USB print error:', error);
                alert('Gagal mencetak via USB: ' + error.message);
            }
        }

        async function doPrintBluetooth() {
            try {
                const receiptData = ThermalPrinter.generateReceipt(transaction, storeSettings, authUser.name);
                await ThermalPrinter.printBluetooth(receiptData);
                ThermalPrinter.savePreference('bluetooth');
                alert('✅ Struk berhasil dikirim ke printer Bluetooth!');
            } catch (error) {
                console.error('Bluetooth print error:', error);
                alert('Gagal mencetak via Bluetooth: ' + error.message);
            }
        }
    </script>
</body>
</html>
