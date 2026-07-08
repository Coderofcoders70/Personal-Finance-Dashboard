<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function monthly(Request $request)
    {
        $user = $request->user();

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $transactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->latest()
            ->get();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        return response()->json([
            'success' => true,
            'month' => Carbon::create()->month($month)->format('F'),
            'year' => $year,
            'income' => $income,
            'expense' => $expense,
            'savings' => max(0, $income - $expense),
            'deficit' => max(0, $expense - $income),
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }

    public function weekly(Request $request)
    {
        $user = $request->user();

        $start = Carbon::now()->startOfWeek();
        $end = Carbon::now()->endOfWeek();

        $transactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->whereBetween('transaction_date', [$start, $end])
            ->latest()
            ->get();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        return response()->json([
            'success' => true,
            'week' => 'Current Week',
            'income' => $income,
            'expense' => $expense,
            'savings' => max(0, $income - $expense),
            'deficit' => max(0, $expense - $income),
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }

    public function yearly(Request $request)
    {
        $user = $request->user();

        $year = $request->input('year', now()->year);

        $transactions = Transaction::where('user_id', $user->id)
            ->whereYear('transaction_date', $year)
            ->get();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        return response()->json([
            'success' => true,
            'year' => $year,
            'income' => $income,
            'expense' => $expense,
            'savings' => max(0, $income - $expense),
            'deficit' => max(0, $expense - $income),
        ]);
    }

    public function category(Request $request)
    {
        $user = $request->user();

        $type = $request->input('type', 'expense');

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

        return response()->json([
            'success' => true,
            'type' => $type,
            'report' => $report,
        ]);
    }
}
