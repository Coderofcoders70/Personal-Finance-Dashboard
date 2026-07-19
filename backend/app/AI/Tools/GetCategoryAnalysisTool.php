<?php

namespace App\Ai\Tools;

use App\Services\FinanceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetCategoryAnalysisTool implements Tool
{
    public function __construct(
        protected FinanceService $financeService,
    ) {}

    public function description(): string|Stringable
    {
        return 'Returns category-wise income or expense analysis.';
    }

    public function handle(Request $request): string|Stringable
    {
        $user = Auth::user();

        if (! $user) {
            return json_encode([
                'error' => 'User is not authenticated.',
            ]);
        }

        $type = $request->has('type')
            ? $request->string('type')->toString()
            : 'expense';

        return json_encode(
            $this->financeService->categoryReport(
                $user,
                $type
            )
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->enum(['income', 'expense'])
                ->description('Category analysis type.')
                ->default('expense'),
        ];
    }
}