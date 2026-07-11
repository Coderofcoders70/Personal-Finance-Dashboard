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
        $context = $this->financeService->aiContext($user);

        $prompt = $this->buildPrompt(
            $context,
            $message
        );

        // Gemini API logic here

        return $prompt;
    }

    private function buildPrompt(array $context, string $message): string
    {
        $identity = <<<PROMPT

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

        $financialContext = <<<PROMPT
            Financial Summary

            Total Income: ₹{$context['summary']['total_income']}
            Total Expense: ₹{$context['summary']['total_expense']}
            Current Balance: ₹{$context['summary']['current_balance']}

            Monthly Summary

            Income: ₹{$context['monthly']['income']}
            Expense: ₹{$context['monthly']['expense']}
            Savings: ₹{$context['monthly']['savings']}
            Deficit: ₹{$context['monthly']['deficit']}
            
        PROMPT;

        // Transactions
        $recentTransactions = "";

        foreach ($context['recent_transactions'] as $transaction) {

            $recentTransactions .=
                "- {$transaction['title']} ({$transaction['category']['name']}) : ₹{$transaction['amount']}\n";
        }

        $financialContext .= "\n\nRecent Transactions:\n";
        $financialContext .= $recentTransactions;

        // Expense by category
        $expenseCategories = "";

        foreach ($context['expense_by_category'] as $category) {

            $expenseCategories .=
                "- {$category['category']}: ₹{$category['amount']}\n";
        }

        $financialContext .= "\nExpense Categories:\n";
        $financialContext .= $expenseCategories;

        // Income by category
        $incomeCategories = "";

        foreach ($context['income_by_category'] as $category) {

            $incomeCategories .=
                "- {$category['category']}: ₹{$category['amount']}\n";
        }

        $financialContext .= "\nIncome Categories:\n";
        $financialContext .= $incomeCategories;

        $instructions = <<<PROMPT
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
            {$identity}

            {$financialContext}

            {$instructions}
            
        PROMPT;
    }
}
