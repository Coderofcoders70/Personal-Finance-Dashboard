<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // summary
        $totalIncome = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->sum('amount');

        $totalExpense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->sum('amount');

        $currentBalance = $totalIncome - $totalExpense;

        // Monthly summary
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
        }else{
            $monthlySavings = 0;
            $monthlyDeficit = $monthlyExpense - $monthlyIncome;
        }

        // Total transactions
        $totalTransactions = Transaction::where('user_id', $user->id)->count();

        // Recent transactions
        $recentTransactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // Expense by category
        $expenseByCategory = Transaction::selectRaw('category_id, SUM(amount) as total')
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

        // Income by category
        $incomeByCategory = Transaction::selectRaw('category_id, SUM(amount) as total')
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

        return response()->json([
            'success' => true,

            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'current_balance' => $currentBalance,
                'total_transactions' => $totalTransactions,
            ],

            'monthly' => [
                'income' => $monthlyIncome,
                'expense' => $monthlyExpense,
                'savings' => $monthlySavings,
                'deficit' => $monthlyDeficit,
            ],

            'recent_transactions' => TransactionResource::collection($recentTransactions),

            'expense_by_category' => $expenseByCategory,

            'income_by_category' => $incomeByCategory,
        ]);
    }
}
