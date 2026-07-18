<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\Auth;
use Stringable;

class GetFinancialSummaryTool implements Tool
{
    public function __construct(
        protected AnalyticsService $analyticsService,
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Returns the authenticated user financial summary including total income, total expense and current balance.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $user = Auth::user();

        if (!$user) {
            return json_encode([
                'error' => 'User is not authenticated.'
            ]);
        }

        return json_encode(
            $this->analyticsService->summary($user)
        );
    }

    /**
     * Get the tool's schema definition. It defines the input LLM can provide
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
