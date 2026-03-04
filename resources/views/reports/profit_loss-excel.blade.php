<table>
    <thead>
        <tr>
            <th colspan="2" style="font-weight: bold; font-size: 14px; text-align: center;">LAPORAN LABA RUGI</th>
        </tr>
        <tr>
            <th colspan="2" style="text-align: center;">SmartKasir Pro System</th>
        </tr>
        <tr>
            <th colspan="2" style="text-align: center;">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</th>
        </tr>
        <tr>
            <th colspan="2"></th>
        </tr>
    </thead>
    <tbody>
        <!-- PENDAPATAN -->
        <tr>
            <td colspan="2" style="font-weight: bold; background-color: #f3f4f6;">PENDAPATAN</td>
        </tr>
        @forelse($revenues as $rev)
        <tr>
            <td>{{ $rev['name'] }}</td>
            <td style="text-align: right;">{{ $rev['balance'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="2" style="font-style: italic; color: #6b7280;">Tidak ada data pendapatan</td>
        </tr>
        @endforelse
        <tr>
            <td style="font-weight: bold;">Total Pendapatan</td>
            <td style="font-weight: bold; text-align: right;">{{ $totalRevenue }}</td>
        </tr>

        <tr>
            <td colspan="2"></td>
        </tr>

        <!-- HPP -->
        <tr>
            <td colspan="2" style="font-weight: bold; background-color: #f3f4f6;">HARGA POKOK PENJUALAN (HPP)</td>
        </tr>
        @forelse($cogs as $cog)
        <tr>
            <td>{{ $cog['name'] }}</td>
            <td style="text-align: right;">{{ $cog['balance'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="2" style="font-style: italic; color: #6b7280;">Tidak ada data HPP</td>
        </tr>
        @endforelse
        <tr>
            <td style="font-weight: bold;">Total HPP</td>
            <td style="font-weight: bold; text-align: right;">-{{ $totalCogs }}</td>
        </tr>

        <tr>
            <td colspan="2"></td>
        </tr>

        <!-- LABA KOTOR -->
        <tr>
            <td style="font-weight: bold; font-size: 12px; background-color: #e0e7ff;">LABA KOTOR</td>
            <td style="font-weight: bold; font-size: 12px; text-align: right; background-color: #e0e7ff;">{{ $grossProfit }}</td>
        </tr>

        <tr>
            <td colspan="2"></td>
        </tr>

        <!-- BEBAN OPERASIONAL -->
        <tr>
            <td colspan="2" style="font-weight: bold; background-color: #f3f4f6;">BEBAN OPERASIONAL</td>
        </tr>
        @forelse($expenses as $exp)
        <tr>
            <td>{{ $exp['name'] }}</td>
            <td style="text-align: right;">{{ $exp['balance'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="2" style="font-style: italic; color: #6b7280;">Tidak ada data beban</td>
        </tr>
        @endforelse
        <tr>
            <td style="font-weight: bold;">Total Beban Operasional</td>
            <td style="font-weight: bold; text-align: right;">-{{ $totalExpense }}</td>
        </tr>

        <tr>
            <td colspan="2"></td>
        </tr>

        <!-- LABA BERSIH -->
        <tr>
            <td style="font-weight: bold; font-size: 14px; {{ $netProfit < 0 ? 'background-color: #fecaca;' : 'background-color: #dcfce7;' }}">LABA / RUGI BERSIH</td>
            <td style="font-weight: bold; font-size: 14px; text-align: right; {{ $netProfit < 0 ? 'background-color: #fecaca;' : 'background-color: #dcfce7;' }}">{{ $netProfit }}</td>
        </tr>
    </tbody>
</table>
