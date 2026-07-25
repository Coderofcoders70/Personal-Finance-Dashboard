<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Support\CacheKeys;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    private const SUMMARY_CACHE_TTL = 600;
    private const MONTHLY_SUMMARY_CACHE_TTL = 600;
    private const EXPENSE_CATEGORY_CACHE_TTL = 600;
    private const INCOME_CATEGORY_CACHE_TTL = 600;

    // Dashboard Summary
    public function summary(User $user): array
    {
        return Cache::remember(
            CacheKeys::summary($user),
            self::SUMMARY_CACHE_TTL,
            function () use ($user) {

                $totalIncome = Transaction::where('user_id', $user->id)
                    ->whereHas('category', function ($query) {
                        $query->where('type', 'income');
                    })
                    ->sum('amount');

                $totalExpense = Transaction::where('user_id', $user->id)
                    ->whereHas('category', function ($query) {
                        $query->where('type', 'expense');
                    })
                    ->sum('amount');

                return [
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense,
                    'current_balance' => $totalIncome - $totalExpense,
                    'total_transactions' => Transaction::where('user_id', $user->id)->count(),
                ];
            }
        );
    }

    // Monthly Summary
    public function monthlySummary(User $user): array
    {
        return Cache::remember(
            CacheKeys::monthlySummary($user),
            self::MONTHLY_SUMMARY_CACHE_TTL,
            function () use ($user) {

                $income = Transaction::where('user_id', $user->id)
                    ->whereHas('category', function ($query) {
                        $query->where('type', 'income');
                    })
                    ->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year)
                    ->sum('amount');

                $expense = Transaction::where('user_id', $user->id)
                    ->whereHas('category', function ($query) {
                        $query->where('type', 'expense');
                    })
                    ->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year)
                    ->sum('amount');

                return [
                    'income' => $income,
                    'expense' => $expense,
                    'savings' => max($income - $expense, 0),
                    'deficit' => max($expense - $income, 0),
                ];
            }
        );
    }

    // Expense Analytics
    public function expenseByCategory(User $user): array
    {
        return Cache::remember(
            CacheKeys::expenseCategory($user),
            self::EXPENSE_CATEGORY_CACHE_TTL,
            function () use ($user) {

                return Transaction::query()
                    ->selectRaw('category_id, SUM(amount) as amount')
                    ->with('category:id,name,icon,color')
                    ->where('user_id', $user->id)
                    ->whereHas('category', function ($query) {
                        $query->where('type', 'expense');
                    })
                    ->groupBy('category_id')
                    ->get()
                    ->map(function ($transaction) {
                        return [
                            'category' => $transaction->category?->name,
                            'icon' => $transaction->category?->icon,
                            'color' => $transaction->category?->color,
                            'amount' => (float) $transaction->amount,
                        ];
                    })
                    ->values()
                    ->toArray();
            }
        );
    }

    // Income Analytics
    public function incomeByCategory(User $user): array
    {
        return Cache::remember(
            CacheKeys::incomeCategory($user),
            self::INCOME_CATEGORY_CACHE_TTL,
            function () use ($user) {

                return Transaction::query()
                    ->selectRaw('category_id, SUM(amount) as amount')
                    ->with('category:id,name,icon,color')
                    ->where('user_id', $user->id)
                    ->whereHas('category', function ($query) {
                        $query->where('type', 'income');
                    })
                    ->groupBy('category_id')
                    ->get()
                    ->map(function ($transaction) {
                        return [
                            'category' => $transaction->category?->name,
                            'icon' => $transaction->category?->icon,
                            'color' => $transaction->category?->color,
                            'amount' => (float) $transaction->amount,
                        ];
                    })
                    ->values()
                    ->toArray();
            }
        );
    }
}
