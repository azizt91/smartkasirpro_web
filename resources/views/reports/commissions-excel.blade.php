<table>
    <thead>
        <tr>
            <th colspan="5" style="font-weight: bold; font-size: 14px; text-align: center;">LAPORAN KOMISI {{ strtoupper(\App\Models\Setting::getStoreSettings()->employee_label ?? 'PEGAWAI') }} JASA - SMARTKASIR PRO</th>
        </tr>
        <tr>
            <th colspan="5" style="text-align: center;">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</th>
        </tr>
        <tr>
            <th colspan="5"></th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #f3f4f6; text-align: center; border: 1px solid #000;">Tgl / Waktu</th>
            <th style="font-weight: bold; background-color: #f3f4f6; text-align: center; border: 1px solid #000;">No. Transaksi</th>
            <th style="font-weight: bold; background-color: #f3f4f6; text-align: center; border: 1px solid #000;">{{ \App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai' }} Jasa</th>
            <th style="font-weight: bold; background-color: #f3f4f6; text-align: center; border: 1px solid #000;">Layanan (Qty)</th>
            <th style="font-weight: bold; background-color: #f3f4f6; text-align: center; border: 1px solid #000;">Komisi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
            <tr>
                <td style="border: 1px solid #000;">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                <td style="border: 1px solid #000;">{{ $item->transaction->transaction_code }}</td>
                <td style="border: 1px solid #000; font-weight: bold;">{{ $item->employee->name ?? '-' }}</td>
                <td style="border: 1px solid #000;">{{ $item->product_name }} (x{{ $item->quantity }})</td>
                <td style="border: 1px solid #000; font-weight: bold; color: #166534;" data-format="#,##0_-">
                    {{ $item->commission_amount }}
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" style="font-weight: bold; text-align: right; border: 1px solid #000;">TOTAL KOMISI</td>
            <td style="font-weight: bold; border: 1px solid #000; color: #166534;" data-format="#,##0_-">
                {{ $summary['total_commission'] }}
            </td>
        </tr>
        <tr>
            <td colspan="4" style="font-weight: bold; text-align: right; border: 1px solid #000;">TOTAL LAYANAN KERJA</td>
            <td style="font-weight: bold; border: 1px solid #000; text-align: left;">
                {{ $summary['total_services'] }} Layanan
            </td>
        </tr>
    </tfoot>
</table>
