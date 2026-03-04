<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProfitLossReportExport implements FromView, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $revenues;
    protected $totalRevenue;
    protected $cogs;
    protected $totalCogs;
    protected $grossProfit;
    protected $expenses;
    protected $totalExpense;
    protected $netProfit;

    public function __construct(
        $startDate, $endDate,
        $revenues, $totalRevenue,
        $cogs, $totalCogs,
        $grossProfit,
        $expenses, $totalExpense,
        $netProfit
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->revenues = $revenues;
        $this->totalRevenue = $totalRevenue;
        $this->cogs = $cogs;
        $this->totalCogs = $totalCogs;
        $this->grossProfit = $grossProfit;
        $this->expenses = $expenses;
        $this->totalExpense = $totalExpense;
        $this->netProfit = $netProfit;
    }

    public function view(): View
    {
        return view('reports.profit_loss-excel', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'revenues' => $this->revenues,
            'totalRevenue' => $this->totalRevenue,
            'cogs' => $this->cogs,
            'totalCogs' => $this->totalCogs,
            'grossProfit' => $this->grossProfit,
            'expenses' => $this->expenses,
            'totalExpense' => $this->totalExpense,
            'netProfit' => $this->netProfit,
        ]);
    }

    public function title(): string
    {
        return 'Laba Rugi';
    }
}
