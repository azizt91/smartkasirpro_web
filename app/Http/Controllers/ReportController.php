<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Routing\Controller as BaseController;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesReportExport;
use App\Exports\ProductReportExport;
use App\Exports\StockReportExport;

class ReportController extends BaseController
{
    private function checkAdminAccess()
    {
        if (!auth()->check()) {
            abort(401, 'Silakan login terlebih dahulu.');
        }
        
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Akses ditolak. Hanya admin yang dapat mengakses laporan.');
        }
    }

    public function index()
    {
        $this->checkAdminAccess();
        
        // Get summary statistics
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisYear = Carbon::now()->startOfYear();

        $stats = [
            'today_sales' => Transaction::where('status', 'completed')->where('payment_method', '!=', 'utang')->whereDate('created_at', $today)->sum('total_amount'),
            'today_transactions' => Transaction::where('status', 'completed')->whereDate('created_at', $today)->count(),
            'month_sales' => Transaction::where('status', 'completed')->where('payment_method', '!=', 'utang')->where('created_at', '>=', $thisMonth)->sum('total_amount'),
            'month_transactions' => Transaction::where('status', 'completed')->where('created_at', '>=', $thisMonth)->count(),
            'year_sales' => Transaction::where('status', 'completed')->where('payment_method', '!=', 'utang')->where('created_at', '>=', $thisYear)->sum('total_amount'),
            'year_transactions' => Transaction::where('status', 'completed')->where('created_at', '>=', $thisYear)->count(),
            'total_products' => Product::count(),
            'low_stock_products' => Product::where('type', '!=', 'jasa')->whereRaw('stock <= minimum_stock')->count(),
            'total_categories' => Category::count(),
            'total_users' => User::count(),
        ];

        // Recent transactions
        $recentTransactions = Transaction::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Top selling products this month
        $topProducts = Product::select([
                'products.id',
                'products.name',
                'products.barcode',
                'products.category_id',
                'products.selling_price',
                'products.image'
            ])
            ->selectRaw('SUM(transaction_items.quantity) as total_sold')
            ->selectRaw('SUM(transaction_items.subtotal) as total_revenue')
            ->with('category')
            ->join('transaction_items', 'products.id', '=', 'transaction_items.product_id')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.created_at', '>=', $thisMonth)
            ->groupBy([
                'products.id',
                'products.name',
                'products.barcode',
                'products.category_id',
                'products.selling_price',
                'products.image'
            ])
            ->orderBy('total_sold', 'desc')
            ->take(10)
            ->get();

        // Low stock products
        $lowStockProducts = Product::with('category')
            ->where('type', '!=', 'jasa')
            ->whereRaw('stock <= minimum_stock')
            ->orderBy('stock', 'asc')
            ->take(10)
            ->get();

        return view('reports.index', compact('stats', 'recentTransactions', 'topProducts', 'lowStockProducts'));
    }

    public function sales(Request $request)
    {
        $this->checkAdminAccess();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $format = $request->get('format', 'view');

        $transactionsQuery = Transaction::with(['user', 'items.product'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('created_at', 'desc');

        $expensesQuery = \App\Models\Expense::with('user')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc');

        $purchasesQuery = \App\Models\Purchase::with(['supplier', 'user', 'items.product'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc');

        // Calculate Totals (Before Pagination)
        // Use a separate query for totals to only include completed transactions
        $activeTransactionsQuery = $transactionsQuery->clone()->where('status', 'completed');

        $totalSales = $activeTransactionsQuery->sum('total_amount');
        $totalReceivables = $activeTransactionsQuery->clone()->where('payment_method', 'utang')->sum('total_amount');
        $totalReceived = $totalSales - $totalReceivables;
        
        $totalExpenses = $expensesQuery->sum('amount');
        $totalPurchases = $purchasesQuery->sum('total_amount');
        
        $netIncome = $totalSales - ($totalExpenses + $totalPurchases);

        $activeCount = $activeTransactionsQuery->count();

        $summary = [
            'total_transactions' => $transactionsQuery->count(), // Total Logged Transactions
            'total_amount' => $totalSales,
            'total_received' => $totalReceived,
            'total_receivables' => $totalReceivables,
            'total_discount' => $activeTransactionsQuery->sum('discount'),
            'total_points_discount' => $activeTransactionsQuery->sum('points_discount_amount'),
            'total_tax' => $activeTransactionsQuery->sum('tax'),
            'average_transaction' => $activeCount > 0 ? $totalSales / $activeCount : 0,
            'total_expenses' => $totalExpenses,
            'total_purchases' => $totalPurchases,
            'net_income' => $netIncome,
        ];

        if ($format === 'pdf') {
            // honest get() for export
            $transactions = $transactionsQuery->get();
            $expenses = $expensesQuery->get();
            $purchases = $purchasesQuery->get();

            $pdf = Pdf::loadView('reports.sales-pdf', compact('transactions', 'expenses', 'purchases', 'summary', 'startDate', 'endDate'));
            return $pdf->download('laporan-laba-rugi-' . $startDate . '-to-' . $endDate . '.pdf');
        }

        if ($format === 'excel') {
            // honest get() for export
            $transactions = $transactionsQuery->get();
            $expenses = $expensesQuery->get();
            $purchases = $purchasesQuery->get();

            return Excel::download(new SalesReportExport($transactions, $expenses, $purchases, $summary, $startDate, $endDate), 
                'laporan-laba-rugi-' . $startDate . '-to-' . $endDate . '.xlsx');
        }

        // Pagination for Web View
        $transactions = $transactionsQuery->paginate(10, ['*'], 'trans_page');
        $expenses = $expensesQuery->paginate(10, ['*'], 'exp_page');
        $purchases = $purchasesQuery->paginate(10, ['*'], 'purch_page');

        return view('reports.sales', compact('transactions', 'expenses', 'purchases', 'summary', 'startDate', 'endDate'));
    }

    public function products(Request $request)
    {
        $this->checkAdminAccess();
        
        $category = $request->get('category');
        $format = $request->get('format', 'view');

        $query = Product::with('category');
        
        if ($category) {
            $query->where('category_id', $category);
        }

        $products = $query->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        $summary = [
            'total_products' => $products->count(),
            'total_stock_value' => $products->sum(function($product) {
                return $product->stock * $product->purchase_price;
            }),
            'total_selling_value' => $products->sum(function($product) {
                return $product->stock * $product->selling_price;
            }),
            'low_stock_count' => $products->filter(function($product) {
                return $product->type !== 'jasa' && $product->stock <= $product->minimum_stock;
            })->count(),
        ];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.products-pdf', compact('products', 'summary', 'category'));
            return $pdf->download('laporan-produk-' . date('Y-m-d') . '.pdf');
        }

        if ($format === 'excel') {
            return Excel::download(new ProductReportExport($products, $summary), 
                'laporan-produk-' . date('Y-m-d') . '.xlsx');
        }

        return view('reports.products', compact('products', 'categories', 'summary', 'category'));
    }

    public function stock(Request $request)
    {
        $this->checkAdminAccess();
        
        $status = $request->get('status', 'all'); // all, low, out
        $format = $request->get('format', 'view');

        $query = Product::with('category');

        switch ($status) {
            case 'low':
                $query->where('type', '!=', 'jasa')->whereRaw('stock <= minimum_stock AND stock > 0');
                break;
            case 'out':
                $query->where('type', '!=', 'jasa')->where('stock', 0);
                break;
            default:
                // all products
                break;
        }

        $products = $query->orderBy('stock', 'asc')->get();

        $summary = [
            'total_products' => Product::count(),
            'low_stock_products' => Product::where('type', '!=', 'jasa')->whereRaw('stock <= minimum_stock AND stock > 0')->count(),
            'out_of_stock_products' => Product::where('type', '!=', 'jasa')->where('stock', 0)->count(),
            'normal_stock_products' => Product::whereRaw('stock > minimum_stock')->count(),
        ];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.stock-pdf', compact('products', 'summary', 'status'));
            return $pdf->download('laporan-stok-' . date('Y-m-d') . '.pdf');
        }

        if ($format === 'excel') {
            return Excel::download(new StockReportExport($products, $summary), 
                'laporan-stok-' . date('Y-m-d') . '.xlsx');
        }

        return view('reports.stock', compact('products', 'summary', 'status'));
    }

    /**
     * Display receivables (piutang) report - transactions with payment_method = 'utang'
     */
    public function receivables(Request $request)
    {
        $this->checkAdminAccess();
        
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Transaction::with(['user', 'items.product'])
            ->where('payment_method', 'utang')
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        $summary = [
            'total_receivables' => $query->sum('total_amount'),
            'total_transactions' => $query->count(),
        ];

        $transactions = $query->paginate(10);

        return view('reports.receivables', compact('transactions', 'summary', 'startDate', 'endDate'));
    }

    /**
     * Mark a receivable transaction as paid (change payment_method from 'utang' to 'cash')
     */
    public function markAsPaid(Transaction $transaction)
    {
        $this->checkAdminAccess();

        if ($transaction->payment_method !== 'utang') {
            return back()->with('error', 'Transaksi ini bukan piutang.');
        }

        $transaction->update([
            'payment_method' => 'cash',
        ]);

        return back()->with('success', 'Piutang berhasil ditandai sebagai lunas.');
    }

    /**
     * Display commissions report
     */
    public function commissions(Request $request)
    {
        $this->checkAdminAccess();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $employeeId = $request->get('employee_id');
        $format = $request->get('format', 'view');

        $query = TransactionItem::with(['transaction', 'employee', 'product'])
            ->whereHas('transaction', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                  ->where('status', 'completed');
            })
            ->whereNotNull('employee_id')
            ->orderBy('created_at', 'desc');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $summary = [
            'total_commission' => $query->sum('commission_amount'),
            'total_services' => $query->count(),
            'unpaid_commission' => (clone $query)->whereNull('settlement_id')->sum('commission_amount'),
        ];

        if ($format === 'pdf') {
            $items = $query->get();
            $pdf = Pdf::loadView('reports.commissions-pdf', compact('items', 'summary', 'startDate', 'endDate', 'employeeId'));
            return $pdf->download('laporan-komisi-' . $startDate . '-to-' . $endDate . '.pdf');
        }

        if ($format === 'excel') {
            $items = $query->get();
            return Excel::download(new \App\Exports\CommissionReportExport($items, $summary, $startDate, $endDate), 
                'laporan-komisi-' . $startDate . '-to-' . $endDate . '.xlsx');
        }

        $items = $query->paginate(15);
        $employees = \App\Models\Employee::orderBy('name')->get();

        return view('reports.commissions', compact('items', 'summary', 'startDate', 'endDate', 'employees', 'employeeId'));
    }

    /**
     * Settle selected commission items (batch) via AJAX.
     */
    public function settleCommission(Request $request)
    {
        $this->checkAdminAccess();

        $request->validate([
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer|exists:transaction_items,id',
            'payment_source' => 'required|in:tunai,bank',
        ]);

        $itemIds = $request->input('item_ids');
        $paymentSource = $request->input('payment_source');

        // Get unpaid commission items only
        $unpaidItems = TransactionItem::whereIn('id', $itemIds)
            ->whereNull('settlement_id')
            ->where('commission_amount', '>', 0)
            ->with('employee')
            ->get();

        if ($unpaidItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada komisi pending yang valid dari item yang dipilih.',
            ], 422);
        }

        \DB::beginTransaction();
        try {
            $label = \App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai';
            $settledCount = 0;
            $totalAmount = 0;

            // Group items by employee
            $grouped = $unpaidItems->groupBy('employee_id');

            foreach ($grouped as $employeeId => $items) {
                $employeeTotal = $items->sum('commission_amount');
                $employee = $items->first()->employee;
                $employeeName = $employee ? $employee->name : ($label . ' #' . $employeeId);

                // 1. Create Settlement Record
                $settlement = \App\Models\CommissionSettlement::create([
                    'employee_id' => $employeeId,
                    'amount' => $employeeTotal,
                    'payment_date' => now()->format('Y-m-d'),
                    'payment_source' => $paymentSource,
                    'settled_by' => auth()->id(),
                    'reference_note' => "Pembayaran Komisi - {$employeeName} ({$items->count()} item)",
                ]);

                // 2. Update Transaction Items with settlement_id
                foreach ($items as $item) {
                    $item->update(['settlement_id' => $settlement->id]);
                }

                // 3. Create Expense Record
                \App\Models\Expense::create([
                    'name' => 'Gaji/Komisi',
                    'date' => now()->format('Y-m-d'),
                    'description' => "Pembayaran Komisi Jasa - {$employeeName} ({$items->count()} layanan)",
                    'amount' => $employeeTotal,
                    'user_id' => auth()->id(),
                ]);

                // 4. Audit Log
                \Log::info('[Komisi] Settlement created', [
                    'settlement_id' => $settlement->id,
                    'employee' => $employeeName,
                    'amount' => $employeeTotal,
                    'payment_source' => $paymentSource,
                    'items_count' => $items->count(),
                    'settled_by' => auth()->user()->name,
                ]);

                $settledCount += $items->count();
                $totalAmount += $employeeTotal;
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil mencairkan {$settledCount} komisi senilai Rp " . number_format($totalAmount, 0, ',', '.') . " via " . ucfirst($paymentSource) . ". Tercatat sebagai Pengeluaran.",
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('[Komisi] Settlement failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencairkan komisi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function ledger(Request $request)
    {
        $this->checkAdminAccess();

        $accounts = \App\Models\Account::orderBy('code')->get();
        $accountId = $request->get('account_id');
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        $ledgers = collect();
        $startingBalance = 0;
        $selectedAccount = null;

        if ($accountId) {
            $selectedAccount = \App\Models\Account::find($accountId);
            
            // Calculate Starting Balance before start_date
            $pastLedger = \App\Models\Ledger::where('account_id', $accountId)
                ->whereDate('date', '<', $startDate)
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $totalDebit = $pastLedger->total_debit ?? 0;
            $totalCredit = $pastLedger->total_credit ?? 0;

            $startingBalance = $selectedAccount->default_balance === 'debit' 
                ? ($totalDebit - $totalCredit) 
                : ($totalCredit - $totalDebit);

            // Get ledgers within date range
            $ledgers = \App\Models\Ledger::where('account_id', $accountId)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'asc')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();
        }

        return view('reports.ledger', compact('accounts', 'selectedAccount', 'ledgers', 'startingBalance', 'startDate', 'endDate'));
    }

    public function profitLoss(Request $request)
    {
        $this->checkAdminAccess();

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // Load all accounts to group by type
        $accounts = \App\Models\Account::all();
        
        // Sum ledgers mapped to each account in date range
        $sums = \App\Models\Ledger::selectRaw('account_id, SUM(debit) as debit, SUM(credit) as credit')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $revenues = [];
        $totalRevenue = 0;

        $cogs = [];
        $totalCogs = 0;

        $expenses = [];
        $totalExpense = 0;

        foreach ($accounts as $account) {
            $sum = $sums->get($account->id);
            $debit = $sum ? $sum->debit : 0;
            $credit = $sum ? $sum->credit : 0;

            if ($account->type === 'revenue') {
                $balance = $credit - $debit;
                if ($balance != 0) {
                    $revenues[] = ['name' => $account->name, 'balance' => $balance];
                    $totalRevenue += $balance;
                }
            } elseif ($account->type === 'expense') {
                $balance = $debit - $credit;
                if ($balance != 0) {
                    if (stripos($account->name, 'HPP') !== false || stripos($account->name, 'Harga Pokok') !== false) {
                        $cogs[] = ['name' => $account->name, 'balance' => $balance];
                        $totalCogs += $balance;
                    } else {
                        $expenses[] = ['name' => $account->name, 'balance' => $balance];
                        $totalExpense += $balance;
                    }
                }
            }
        }

        $grossProfit = $totalRevenue - $totalCogs;
        $netProfit = $grossProfit - $totalExpense;

        return view('reports.profit_loss', compact(
            'startDate', 'endDate',
            'revenues', 'totalRevenue',
            'cogs', 'totalCogs',
            'grossProfit',
            'expenses', 'totalExpense',
            'netProfit'
        ));
    }
}
