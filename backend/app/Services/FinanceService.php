<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use App\Models\User;
use App\Services\AnalyticsService;

class FinanceService
{
    private AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function dashboard(User $user): array
    {
        return [
            'success' => true,

            'summary' => $this->analyticsService->summary($user),

            'monthly' => $this->analyticsService->monthlySummary($user),

            'recent_transactions' => $this->recentTransactions($user),

            'expense_by_category' => $this->analyticsService->expenseByCategory($user),

            'income_by_category' => $this->analyticsService->incomeByCategory($user),
        ];
    }

    public function monthlyReport(User $user, int $month, int $year): array
    {
        $transactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->latest()
            ->get();

        $income = $transactions
            ->filter(fn($transaction) => $transaction->category->type === 'income')
            ->sum('amount');

        $expense = $transactions
            ->filter(fn($transaction) => $transaction->category->type === 'expense')
            ->sum('amount');

        return [
            'success' => true,
            'month' => Carbon::create()->month($month)->format('F'),
            'year' => $year,
            'income' => $income,
            'expense' => $expense,
            'savings' => max(0, $income - $expense),
            'deficit' => max(0, $expense - $income),
            'transactions' => $transactions,
        ];
    }

    public function weeklyReport(User $user): array
    {
        $start = Carbon::now()->startOfWeek();
        $end = Carbon::now()->endOfWeek();

        $transactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->whereBetween('transaction_date', [$start, $end])
            ->latest()
            ->get();

        $income = $transactions
            ->filter(fn ($transaction) => $transaction->category->type === 'income')
            ->sum('amount');

        $expense = $transactions
            ->filter(fn ($transaction) => $transaction->category->type === 'expense')
            ->sum('amount');

        return [
            'success' => true,
            'week' => 'Current Week',
            'income' => $income,
            'expense' => $expense,
            'savings' => max(0, $income - $expense),
            'deficit' => max(0, $expense - $income),
            'transactions' => $transactions,
        ];
    }

    public function yearlyReport(User $user, int $year): array
    {
        $transactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->whereYear('transaction_date', $year)
            ->get();

        $income = $transactions
            ->filter(fn ($transaction) => $transaction->category->type === 'income')
            ->sum('amount');

        $expense = $transactions
            ->filter(fn ($transaction) => $transaction->category->type === 'expense')
            ->sum('amount');

        return [
            'success' => true,
            'year' => $year,
            'income' => $income,
            'expense' => $expense,
            'savings' => max(0, $income - $expense),
            'deficit' => max(0, $expense - $income),
        ];
    }

    public function categoryReport(User $user, string $type): array
    {
        $categories = Transaction::selectRaw('category_id, SUM(amount) as total')
            ->with('category')
            ->where('user_id', $user->id)
            ->whereHas('category', function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->groupBy('category_id')
            ->get();

        $grandTotal = $categories->sum('total');

        $report = $categories->map(function ($item) use ($grandTotal) {
            return [
                'category' => $item->category->name,
                'icon' => $item->category->icon,
                'color' => $item->category->color,
                'amount' => (float) $item->total,
                'percentage' => $grandTotal > 0
                    ? round(($item->total / $grandTotal) * 100, 2)
                    : 0,
            ];
        });

        return [
            'success' => true,
            'type' => $type,
            'report' => $report,
        ];
    }

    private function recentTransactions(User $user)
    {
        return Transaction::with('category')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();
    }

    public function aiContext(User $user): array
    {
        return [

            'summary' => $this->analyticsService->summary($user),

            'monthly' => $this->analyticsService->monthlySummary($user),

            'recent_transactions' => $this->recentTransactions($user),

            'expense_by_category' => $this->analyticsService->expenseByCategory($user),

            'income_by_category' => $this->analyticsService->incomeByCategory($user),
        ];
    }
}
