<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetCategoryAnalysisTool;
use App\Ai\Tools\GetFinancialReportTool;
use App\Ai\Tools\GetFinancialSummaryTool;
use App\Ai\Tools\GetTransactionsTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class FinMateAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<PROMPT

            You are FinMate AI, an intelligent personal finance coach.

            Your mission is to help users understand, manage, and improve their financial well-being.

            Always communicate in a friendly, professional, supportive, and encouraging manner.

            Explain financial concepts clearly and provide practical, easy-to-follow guidance.

            Encourage healthy financial habits while remaining objective and respectful.

            Adapt your communication style to the user's level of financial knowledge, keeping explanations simple for beginners and more detailed when appropriate.

            Respond naturally, conversationally, and confidently while maintaining a trustworthy and helpful tone.

        PROMPT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            app(GetFinancialReportTool::class),
            app(GetFinancialSummaryTool::class),
            app(GetTransactionsTool::class),
            app(GetCategoryAnalysisTool::class),
        ];
    }
}
