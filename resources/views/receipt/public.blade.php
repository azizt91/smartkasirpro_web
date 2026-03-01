<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Digital — {{ $transaction->transaction_code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .receipt-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            max-width: 420px;
            width: 100%;
            overflow: hidden;
        }
        .receipt-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            padding: 24px;
            text-align: center;
        }
        .receipt-header h1 { font-size: 20px; font-weight: 700; }
        .receipt-header p { font-size: 12px; opacity: 0.85; margin-top: 4px; }
        .receipt-status {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 12px;
        }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .receipt-body { padding: 24px; }
        .receipt-info { margin-bottom: 20px; }
        .receipt-info .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            color: #6b7280;
        }
        .receipt-info .row .label { font-weight: 500; }
        .receipt-info .row .value { font-weight: 600; color: #1f2937; text-align: right; }
        .divider {
            border: none;
            border-top: 1px dashed #e5e7eb;
            margin: 16px 0;
        }
        .items-table { width: 100%; }
        .items-table th {
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-bottom: 8px;
        }
        .items-table td {
            font-size: 13px;
            padding: 6px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .items-table td:last-child { text-align: right; font-weight: 600; }
        .items-table th:last-child { text-align: right; }
        .total-section {
            background: #f9fafb;
            border-radius: 10px;
            padding: 16px;
            margin-top: 16px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 13px;
            color: #6b7280;
        }
        .total-row.grand {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            padding-top: 8px;
            border-top: 2px solid #e5e7eb;
            margin-top: 8px;
        }
        .receipt-footer {
            text-align: center;
            padding: 20px 24px;
            background: #f9fafb;
            border-top: 1px solid #f3f4f6;
        }
        .receipt-footer p {
            font-size: 12px;
            color: #9ca3af;
        }
        .receipt-footer .store-name {
            font-weight: 700;
            color: #4f46e5;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div class="receipt-header">
            <h1>{{ $settings->store_name ?? 'Smart Kasir Pro' }}</h1>
            <p>{{ $settings->store_address ?? '' }}</p>
            @php
                $statusClass = match($transaction->status) {
                    'completed' => 'status-completed',
                    'pending' => 'status-pending',
                    default => 'status-cancelled',
                };
                $statusText = match($transaction->status) {
                    'completed' => '✅ Lunas',
                    'pending' => '⏳ Menunggu Pembayaran',
                    default => '❌ Dibatalkan',
                };
            @endphp
            <span class="receipt-status {{ $statusClass }}">{{ $statusText }}</span>
        </div>

        <div class="receipt-body">
            <div class="receipt-info">
                <div class="row">
                    <span class="label">No. Transaksi</span>
                    <span class="value">{{ $transaction->transaction_code }}</span>
                </div>
                <div class="row">
                    <span class="label">Tanggal</span>
                    <span class="value">{{ $transaction->created_at->format('d M Y H:i') }}</span>
                </div>
                <div class="row">
                    <span class="label">Kasir</span>
                    <span class="value">{{ $transaction->user->name ?? '-' }}</span>
                </div>
                <div class="row">
                    <span class="label">Pelanggan</span>
                    <span class="value">{{ $transaction->customer_name ?? 'Umum' }}</span>
                </div>
                <div class="row">
                    <span class="label">Pembayaran</span>
                    <span class="value">{{ strtoupper($transaction->payment_method) }}</span>
                </div>
            </div>

            <hr class="divider">

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaction->items as $item)
                    <tr>
                        <td>{{ $item->product_name ?? ($item->product->name ?? 'Item') }}</td>
                        <td>{{ $item->quantity }} × Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format((float)$transaction->subtotal, 0, ',', '.') }}</span>
                </div>
                @if((float)$transaction->discount > 0)
                <div class="total-row">
                    <span>Diskon</span>
                    <span>-Rp {{ number_format((float)$transaction->discount, 0, ',', '.') }}</span>
                </div>
                @endif
                @if((float)$transaction->tax > 0)
                <div class="total-row">
                    <span>Pajak</span>
                    <span>Rp {{ number_format((float)$transaction->tax, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="total-row grand">
                    <span>Total</span>
                    <span>Rp {{ number_format((float)$transaction->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="receipt-footer">
            <p class="store-name">{{ $settings->store_name ?? 'Smart Kasir Pro' }}</p>
            <p>{{ $settings->store_description ?? 'Terima kasih telah berbelanja!' }}</p>
            <p style="margin-top: 8px; font-size: 11px;">{{ $settings->store_phone ?? '' }}</p>
        </div>
    </div>
</body>
</html>
