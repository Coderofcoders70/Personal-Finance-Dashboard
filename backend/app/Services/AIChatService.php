<?php

namespace App\Services;

use App\Ai\LLMProvider;
use App\Models\User;
use App\Services\FinanceService;

class AIChatService
{

    private LLMProvider $llmProvider;
    private FinanceService $financeService;

    public function __construct(FinanceService $financeService, LLMProvider $llmProvider)
    {
        $this->financeService = $financeService;
        $this->llmProvider = $llmProvider;
    }

    public function generateResponse(User $user, string $message): string
    {
        $financialContext = $this->buildFinancialContext($user);

        $insights = $this->detectInsights($financialContext);

        $prompt = $this->buildPrompt(
            $user,
            $financialContext,
            $insights,
            $message
        );

        $cachedResponse = $this->getCachedResponse(
            $user,
            $message,
            $prompt
        );

        if ($cachedResponse) {
            return $cachedResponse;
        }

        try {

            $response = $this->askGemini($prompt);

            $this->cacheResponse(
                $user,
                $message,
                $prompt,
                $response
            );

            return $response;
        } catch (\Throwable $e) {

            return $this->generateFallbackResponse(
                $user,
                $financialContext,
                $insights
            );
        }
    }

    private function buildFinancialContext(User $user): array
    {
        return $this->financeService->aiContext($user);
    }

    private function detectInsights(array $financialContext): array
    {
        $insights = [];

        // Rule 1 - Check deficit
        if ($financialContext['monthly']['deficit'] > 0) {

            $insights[] = [
                'type' => 'warning',
                'title' => 'Monthly Deficit',
                'message' => 'The user is currently operating with a monthly deficit.',
            ];
        }

        // Rule 2 - Check savings
        if ($financialContext['monthly']['savings'] > 0) {

            $insights[] = [
                'type' => 'positive',
                'title' => 'Monthly Savings',
                'message' => 'The user has successfully saved money this month.',
            ];
        }

        // Rule 3 - Check if no income
        if ($financialContext['summary']['total_income'] == 0) {

            $insights[] = [
                'type' => 'info',
                'title' => 'Total Income Summary',
                'message' => 'No income has been recorded yet.',
            ];
        }

        // Rule 4 - Check recent transactions
        if (count($financialContext['recent_transactions']) === 0) {

            $insights[] = [
                'type' => 'info',
                'title' => 'Recent Transactions',
                'message' => 'No transactions have been recorded yet.',
            ];
        }

        // Rule 5 - Check heighest expense category
        if (!empty($financialContext['expense_by_category'])) {

            $highestCategory = $financialContext['expense_by_category'][0];

            $insights[] = [
                'type' => 'warning',
                'title' => 'Expense By Category',
                'message' => "{$highestCategory['category']} is currently the highest spending category.",
            ];
        }

        // Rule 6 - Positive Balance
        if ($financialContext['summary']['current_balance'] > 0) {

            $insights[] = [
                'type' => 'positive',
                'title' => 'Positive Balance',
                'message' => 'The user currently has a positive account balance.',
            ];
        }

        // Rule 7 - No Expenses
        if ($financialContext['monthly']['expense'] == 0) {

            $insights[] = [
                'type' => 'positive',
                'title' => 'No Expenses',
                'message' => 'No expenses have been recorded this month.',
            ];
        }

        // Rule 8 - Healthy savings (20%)
        if (
            $financialContext['monthly']['income'] > 0 &&
            $financialContext['monthly']['savings'] >= ($financialContext['monthly']['income'] * 0.2)
        ) {

            $insights[] = [
                'type' => 'positive',
                'title' => 'Healthy Savings',
                'message' => 'The user has saved at least 20% of monthly income.',
            ];
        }

        // Rule 9 - Excellent savings (40%)
        if (
            $financialContext['monthly']['income'] > 0 &&
            $financialContext['monthly']['savings'] >= ($financialContext['monthly']['income'] * 0.4)
        ) {

            $insights[] = [
                'type' => 'positive',
                'title' => 'Excellent Savings',
                'message' => 'Excellent saving habits detected this month.',
            ];
        }

        // Rule 10 - Expense without Income
        if (
            $financialContext['summary']['total_income'] == 0 &&
            $financialContext['summary']['total_expense'] > 0
        ) {

            $insights[] = [
                'type' => 'warning',
                'title' => 'No Income',
                'message' => 'Expenses have been recorded without any income this month.',
            ];
        }

        // Rule 11 - Income without expense
        if (
            $financialContext['summary']['total_income'] > 0 &&
            $financialContext['summary']['total_expense'] == 0
        ) {

            $insights[] = [
                'type' => 'positive',
                'title' => 'Excellent Spending Control',
                'message' => 'Income has been recorded without any expenses this month.',
            ];
        }

        // Rule 12 - Few Transactions
        if (count($financialContext['recent_transactions']) <= 2) {

            $insights[] = [
                'type' => 'info',
                'title' => 'Limited Data',
                'message' => 'Only a few transactions have been recorded. More data will improve financial insights.',
            ];
        }

        // Rule 13 - Dominant Expense Category
        if (!empty($financialContext['expense_by_category'])) {

            $highest = $financialContext['expense_by_category'][0];

            $totalExpense = $financialContext['summary']['total_expense'];

            if (
                $totalExpense > 0 &&
                ($highest['amount'] / $totalExpense) >= 0.5
            ) {

                $insights[] = [
                    'type' => 'warning',
                    'title' => 'Dominant Spending',
                    'message' => "{$highest['category']} accounts for most of this month's expenses.",
                ];
            }
        }

        // Rule 14 - Balanced Month
        if (
            $financialContext['monthly']['savings'] == 0 &&
            $financialContext['monthly']['deficit'] == 0
        ) {

            $insights[] = [
                'type' => 'info',
                'title' => 'Balanced Month',
                'message' => 'Income and expenses are currently balanced.',
            ];
        }

        return $insights;
    }

    private function buildPrompt(User $user, array $financialContext, array $insights, string $message): string
    {

        $currentDate = now()->format('d F Y');
        $currentTime = now()->format('h:i A');
        $currentTimezone = config('app.timezone');

        $userContextSection = <<<PROMPT
            User Information

            Name: {$user->name}

            Current Date: {$currentDate}

            Current Time: {$currentTime}

            Current TimeZone: {$currentTimezone}
        PROMPT;

        $financialContextSection = <<<PROMPT
            Financial Summary

            Total Income: ₹{$financialContext['summary']['total_income']}
            Total Expense: ₹{$financialContext['summary']['total_expense']}
            Current Balance: ₹{$financialContext['summary']['current_balance']}

            Monthly Summary

            Income: ₹{$financialContext['monthly']['income']}
            Expense: ₹{$financialContext['monthly']['expense']}
            Savings: ₹{$financialContext['monthly']['savings']}
            Deficit: ₹{$financialContext['monthly']['deficit']}
            
        PROMPT;

        // Transactions
        $recentTransactions = "";

        foreach ($financialContext['recent_transactions'] as $transaction) {

            $recentTransactions .=
                "- {$transaction['title']} ({$transaction['category']['name']}) : ₹{$transaction['amount']}\n";
        }

        if (empty($recentTransactions)) {
            $recentTransactions = "- No transactions recorded yet.\n";
        }

        $financialContextSection .= "\n\nRecent Transactions:\n";
        $financialContextSection .= $recentTransactions;

        // Expense by category
        $expenseCategories = "";

        foreach ($financialContext['expense_by_category'] as $category) {

            $expenseCategories .=
                "- {$category['category']}: ₹{$category['amount']}\n";
        }

        if (empty($expenseCategories)) {
            $expenseCategories = "- No expense category recorded yet.\n";
        }

        $financialContextSection .= "\nExpense Categories:\n";
        $financialContextSection .= $expenseCategories;

        // Income by category
        $incomeCategories = "";

        foreach ($financialContext['income_by_category'] as $category) {

            $incomeCategories .=
                "- {$category['category']}: ₹{$category['amount']}\n";
        }

        if (empty($incomeCategories)) {
            $incomeCategories = "- No income category recorded yet.\n";
        }

        $financialContextSection .= "\nIncome Categories:\n";
        $financialContextSection .= $incomeCategories;


        $insightsSection = "\nDetected Financial Insights:\n";

        foreach ($insights as $insight) {

            $insightsSection .=
                "- {$insight['title']}: {$insight['message']}\n";
        }

        if (empty($insights)) {

            $insightsSection .=
                "- No significant financial insights detected.\n";
        }


        $instructionsSection = <<<PROMPT
            User Question:

            {$message}

            Instructions:

            - Answer only using the financial information provided.
            - If you detect important spending patterns, mention them even if the user didn't ask.
            - Congratulate positive financial habits.
            - Suggest practical improvements when necessary.
            - Never criticize the user.
            - Keep the response under 200 words.
            - Use bullet points when appropriate.
            - End with one short motivational sentence. If user have deficit.
            - If spending is high, suggest realistic improvements.
            - Never mention JSON or internal calculations.
            - Never say you don't have access to the data.

        PROMPT;

        return <<<PROMPT

            {$userContextSection}

            {$financialContextSection}

            {$insightsSection}

            {$instructionsSection}
            
        PROMPT;
    }

    private function getCachedResponse(User $user, string $message, string $prompt): ?string
    {
        return null;
    }

    private function cacheResponse(User $user, string $message, string $prompt, string $response): void
    {
        //
    }

    private function askGemini(string $prompt): string
    {
        return $this->llmProvider->generate($prompt);
    }

    private function generateFallbackResponse(User $user, array $financialContext, array $insights): string
    {
        $response = "FinMate Quick Analysis\n\n";

        $response .= "Hello {$user->name},\n\n";

        $response .= "The AI assistant is temporarily unavailable, but here's a summary based on your latest financial data.\n\n";

        $response .= "### Financial Summary\n";

        $response .= "- Income: ₹{$financialContext['summary']['total_income']}\n";
        $response .= "- Expense: ₹{$financialContext['summary']['total_expense']}\n";
        $response .= "- Current Balance: ₹{$financialContext['summary']['current_balance']}\n";
        $response .= "- Savings: ₹{$financialContext['monthly']['savings']}\n";
        $response .= "- Deficit: ₹{$financialContext['monthly']['deficit']}\n\n";

        $response .= "### Key Insights\n";

        foreach ($insights as $insight) {

            $response .= "- {$insight['message']}\n";
        }

        if (empty($insights)) {

            $response .= "- No significant financial insights available.\n";
        }

        $response .= "\nKeep recording your transactions consistently. Every entry helps FinMate provide smarter and more personalized financial guidance.\n";

        return $response;;
    }
}
