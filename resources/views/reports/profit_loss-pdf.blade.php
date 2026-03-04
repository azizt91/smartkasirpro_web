<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi</title>
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
            font-size: 11px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .bg-gray {
            background-color: #f1f5f9;
        }
        .bg-indigo {
            background-color: #eef2ff;
        }
        .bg-green {
            background-color: #16a34a;
            color: #fff;
        }
        .bg-red {
            background-color: #dc2626;
            color: #fff;
        }
        .font-bold {
            font-weight: bold;
        }
        .pl-4 {
            padding-left: 20px;
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
        <h1>LAPORAN LABA RUGI</h1>
        <p><strong>SmartKasir Pro System</strong></p>
        <p>Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        <p>Dicetak pada: {{ now()->format('d M Y, H:i') }} WIB</p>
    </div>

    <table>
        <tbody>
            <!-- PENDAPATAN -->
            <tr class="bg-gray">
                <td colspan="2" class="font-bold">PENDAPATAN</td>
            </tr>
            @forelse($revenues as $rev)
            <tr>
                <td class="pl-4">{{ $rev['name'] }}</td>
                <td class="text-right">Rp {{ number_format($rev['balance'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="2" class="text-center" style="color:#666; font-style:italic;">Tidak ada data pendapatan</td>
            </tr>
            @endforelse
            <tr>
                <td class="font-bold text-right" style="color: #16a34a;">Total Pendapatan</td>
                <td class="font-bold text-right" style="color: #16a34a;">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
            </tr>

            <!-- HPP -->
            <tr class="bg-gray">
                <td colspan="2" class="font-bold">HARGA POKOK PENJUALAN (HPP)</td>
            </tr>
            @forelse($cogs as $cog)
            <tr>
                <td class="pl-4">{{ $cog['name'] }}</td>
                <td class="text-right">Rp {{ number_format($cog['balance'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="2" class="text-center" style="color:#666; font-style:italic;">Tidak ada data HPP</td>
            </tr>
            @endforelse
            <tr>
                <td class="font-bold text-right" style="color: #dc2626;">Total HPP</td>
                <td class="font-bold text-right" style="color: #dc2626;">(Rp {{ number_format($totalCogs, 0, ',', '.') }})</td>
            </tr>

            <!-- LABA KOTOR -->
            <tr class="bg-indigo">
                <td class="font-bold text-right" style="font-size: 13px;">LABA KOTOR</td>
                <td class="font-bold text-right" style="font-size: 13px; color: {{ $grossProfit < 0 ? '#dc2626' : '#4338ca' }};">
                    Rp {{ number_format($grossProfit, 0, ',', '.') }}
                </td>
            </tr>

            <!-- BEBAN OPERASIONAL -->
            <tr class="bg-gray">
                <td colspan="2" class="font-bold">BEBAN OPERASIONAL</td>
            </tr>
            @forelse($expenses as $exp)
            <tr>
                <td class="pl-4">{{ $exp['name'] }}</td>
                <td class="text-right">Rp {{ number_format($exp['balance'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="2" class="text-center" style="color:#666; font-style:italic;">Tidak ada data beban</td>
            </tr>
            @endforelse
            <tr>
                <td class="font-bold text-right" style="color: #dc2626;">Total Beban Operasional</td>
                <td class="font-bold text-right" style="color: #dc2626;">(Rp {{ number_format($totalExpense, 0, ',', '.') }})</td>
            </tr>

            <!-- LABA BERSIH -->
            <tr class="{{ $netProfit < 0 ? 'bg-red' : 'bg-green' }}">
                <td class="font-bold text-right" style="font-size: 14px; padding: 12px;">LABA / RUGI BERSIH</td>
                <td class="font-bold text-right" style="font-size: 16px; padding: 12px;">Rp {{ number_format($netProfit, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Laporan ini digenerate secara otomatis oleh sistem SmartKasir Pro</p>
        <p>© {{ date('Y') }} SmartKasir Pro System - All Rights Reserved</p>
    </div>
</body>
</html>
