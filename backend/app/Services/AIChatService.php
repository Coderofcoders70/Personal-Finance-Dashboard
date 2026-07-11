<?php

namespace App\Services;

use App\Models\User;
use App\Services\FinanceService;

class AIChatService
{

    private FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    public function generateResponse(User $user, string $message): string
    {
        $financialContext = $this->buildFinancialContext($user);

        $insights = $this->detectInsights($financialContext);

        $prompt = $this->buildPrompt(
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
        return [];
    }

    private function buildPrompt(array $financialContext, array $insights, string $message): string
    {
        $identitySection = <<<PROMPT

            You are FinMate AI, an intelligent personal finance coach.

            Your role is to analyze the user's financial data and provide practical, personalized, and encouraging financial advice.

            Rules:

            - Never invent financial information.
            - Only use the financial data provided below.
            - If information is missing, clearly say so.
            - If the user asks something unrelated to personal finance, politely answer that you specialize in finance while still trying to be helpful.
            - Be supportive and motivating.
            - Explain financial concepts in simple language.

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
            {$identitySection}

            {$financialContextSection}

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
        return "Gemini response";
    }

    private function generateFallbackResponse(array $financialContext, array $insights): string
    {
        return "Fallback response";
    }
}
