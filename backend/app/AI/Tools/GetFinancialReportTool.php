<?php

namespace App\Ai\Tools;

use App\Services\FinanceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetFinancialReportTool implements Tool
{
    public function __construct(
        protected FinanceService $financeService,
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Returns a financial report for the authenticated user. Supports weekly, monthly and yearly reports.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $user = Auth::user();

        if (! $user) {
            return json_encode([
                'error' => 'User is not authenticated.',
            ]);
        }

        $period = $request->string('period')->toString();

        $month = $request->has('month') 
                ? $request->integer('month') 
                : now()->month;

        $year = $request->has('year') 
                ? $request->integer('year') 
                : now()->year;

        $report = match ($period) {
            'weekly' => $this->financeService->weeklyReport($user),

            'monthly' => $this->financeService->monthlyReport(
                $user,
                $month,
                $year
            ),

            'yearly' => $this->financeService->yearlyReport(
                $user,
                $year
            ),
        };

        return json_encode($report);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()
                ->enum(['weekly', 'monthly', 'yearly'])
                ->description('Type of financial report to generate.')
                ->required(),

            'month' => $schema->integer()
                ->description('Month number (1-12). Required only for monthly reports.'),

            'year' => $schema->integer()
                ->description('Year of the report. Defaults to the current year.'),
        ];
    }
}
