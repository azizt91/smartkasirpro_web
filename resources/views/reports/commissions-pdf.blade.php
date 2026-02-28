<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Komisi {{ \App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .label {
            font-weight: bold;
            color: #666;
            font-size: 11px;
        }
        .summary-item .value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 11px;
        }
        td {
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN KOMISI {{ strtoupper(\App\Models\Setting::getStoreSettings()->employee_label ?? 'PEGAWAI') }} JASA</h1>
        <p><strong>SmartKasir Pro System</strong></p>
        <p>Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        @if($employeeId)
            <p>{{ \App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai' }}: {{ \App\Models\Employee::find($employeeId)->name }}</p>
        @else
            <p>{{ \App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai' }}: Semua {{ \App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai' }}</p>
        @endif
        <p>Dicetak pada: {{ now()->format('d M Y, H:i') }} WIB</p>
    </div>

    <div class="summary">
        <h3 style="margin-top: 0; text-align: center;">RINGKASAN KOMISI</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">TOTAL LAYANAN KERJA</div>
                <div class="value">{{ number_format($summary['total_services'], 0, ',', '.') }} Layanan</div>
            </div>
            <div class="summary-item">
                <div class="label">TOTAL KOMISI TERKUMPUL</div>
                <div class="value" style="color: #2e7d32;">Rp {{ number_format($summary['total_commission'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <h3>DETAIL KOMISI</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 20%;">Tgl / Waktu</th>
                <th style="width: 20%;">No. Transaksi</th>
                <th style="width: 20%;">{{ \App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai' }} Jasa</th>
                <th style="width: 20%;">Layanan (Qty)</th>
                <th style="width: 15%;" class="text-right">Total Komisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $item->transaction->transaction_code }}</td>
                    <td><strong>{{ $item->employee->name ?? '-' }}</strong></td>
                    <td>{{ $item->product_name }} (x{{ $item->quantity }})</td>
                    <td class="text-right" style="color: #2e7d32; font-weight: bold;">Rp {{ number_format($item->commission_amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px;">Tidak ada data komisi pada periode ini</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f8f9fa; font-weight: bold;">
                <td colspan="5" class="text-center">TOTAL KOMISI</td>
                <td class="text-right" style="color: #2e7d32;">Rp {{ number_format($summary['total_commission'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Laporan ini digenerate secara otomatis oleh sistem SmartKasir Pro</p>
        <p>© {{ date('Y') }} SmartKasir Pro System - All Rights Reserved</p>
    </div>
</body>
</html>
