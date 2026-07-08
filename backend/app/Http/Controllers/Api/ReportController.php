<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FinanceService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    public function monthly(Request $request)
    {
        $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        return response()->json(
            $this->financeService->monthlyReport(
                $request->user(),
                (int) $request->input('month', now()->month),
                (int) $request->input('year', now()->year)
            )
        );
    }

    public function weekly(Request $request)
    {
        return response()->json(
            $this->financeService->weeklyReport(
                $request->user()
            )
        );
    }

    public function yearly(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        return response()->json(
            $this->financeService->yearlyReport(
                $request->user(),
                (int) $request->input('year', now()->year)
            )
        );
    }

    public function category(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:income,expense',
        ]);

        return response()->json(
            $this->financeService->categoryReport(
                $request->user(),
                $request->input('type', 'expense')
            )
        );
    }
}
