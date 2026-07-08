<?php

namespace App\Services;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Carbon\Carbon;
use App\Models\User;

class FinanceService
{
    public function dashboard(User $user): array
    {
        return [
            'success' => true,

            'summary' => $this->calculateSummary($user),

            'monthly' => $this->calculateMonthlySummary($user),

            'recent_transactions' => TransactionResource::collection(
                $this->recentTransactions($user)
            ),

            'expense_by_category' => $this->expenseByCategory($user),

            'income_by_category' => $this->incomeByCategory($user),
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

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        return [
            'success' => true,
            'month' => Carbon::create()->month($month)->format('F'),
            'year' => $year,
            'income' => $income,
            'expense' => $expense,
            'savings' => max(0, $income - $expense),
            'deficit' => max(0, $expense - $income),
            'transactions' => TransactionResource::collection($transactions),
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

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        return [
            'success' => true,
            'week' => 'Current Week',
            'income' => $income,
            'expense' => $expense,
            'savings' => max(0, $income - $expense),
            'deficit' => max(0, $expense - $income),
            'transactions' => TransactionResource::collection($transactions),
        ];
    }

    public function yearlyReport(User $user, int $year): array
    {
        $transactions = Transaction::where('user_id', $user->id)
            ->whereYear('transaction_date', $year)
            ->get();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

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
            ->where('type', $type)
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

    private function calculateSummary(User $user): array
    {
        $totalIncome = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->sum('amount');

        $totalExpense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->sum('amount');

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'current_balance' => $totalIncome - $totalExpense,
            'total_transactions' => Transaction::where('user_id', $user->id)->count(),
        ];
    }

    private function calculateMonthlySummary(User $user): array
    {
        $monthlyIncome = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereMonth('transaction_date', Carbon::now()->month)
            ->whereYear('transaction_date', Carbon::now()->year)
            ->sum('amount');

        $monthlyExpense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', Carbon::now()->month)
            ->whereYear('transaction_date', Carbon::now()->year)
            ->sum('amount');

        if ($monthlyIncome >= $monthlyExpense) {
            $monthlySavings = $monthlyIncome - $monthlyExpense;
            $monthlyDeficit = 0;
        } else {
            $monthlySavings = 0;
            $monthlyDeficit = $monthlyExpense - $monthlyIncome;
        }

        return [
            'income' => $monthlyIncome,
            'expense' => $monthlyExpense,
            'savings' => $monthlySavings,
            'deficit' => $monthlyDeficit,
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

    private function expenseByCategory(User $user)
    {
        return Transaction::selectRaw('category_id, SUM(amount) as total')
            ->with('category')
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->groupBy('category_id')
            ->get()
            ->map(function ($transaction) {
                return [
                    'category' => $transaction->category->name,
                    'icon' => $transaction->category->icon,
                    'color' => $transaction->category->color,
                    'amount' => (float) $transaction->total,
                ];
            });
    }

    private function incomeByCategory(User $user)
    {
        return Transaction::selectRaw('category_id, SUM(amount) as total')
            ->with('category')
            ->where('user_id', $user->id)
            ->where('type', 'income')
            ->groupBy('category_id')
            ->get()
            ->map(function ($transaction) {
                return [
                    'category' => $transaction->category->name,
                    'icon' => $transaction->category->icon,
                    'color' => $transaction->category->color,
                    'amount' => (float) $transaction->total,
                ];
            });
    }
}
