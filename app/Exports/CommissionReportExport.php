<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithTitle;

class CommissionReportExport implements FromView, WithTitle
{
    protected $items;
    protected $summary;
    protected $startDate;
    protected $endDate;

    public function __construct($items, $summary, $startDate, $endDate)
    {
        $this->items = $items;
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        return view('reports.commissions-excel', [
            'items' => $this->items,
            'summary' => $this->summary,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    public function title(): string
    {
        return 'Laporan Komisi';
    }
}
