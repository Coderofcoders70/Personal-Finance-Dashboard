<?php

namespace App\Ai\Tools;

use App\Models\Transaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetTransactionsTool implements Tool
{
    public function description(): string|Stringable
    {
        return 'Returns the authenticated user transactions with optional filters.';
    }

    public function handle(Request $request): string|Stringable
    {
        $user = Auth::user();

        if (! $user) {
            return json_encode([
                'error' => 'User is not authenticated.',
            ]);
        }

        $query = Transaction::with('category')
            ->where('user_id', $user->id);

        if ($request->has('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where(
                    'name',
                    $request->string('category')->toString()
                );
            });
        }

        if ($request->has('month')) {
            $query->whereMonth(
                'transaction_date',
                $request->integer('month')
            );
        }

        if ($request->has('year')) {
            $query->whereYear(
                'transaction_date',
                $request->integer('year')
            );
        }

        $limit = $request->has('limit')
            ? $request->integer('limit')
            : 10;

        return json_encode(
            $query
                ->latest()
                ->limit($limit)
                ->get()
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->enum(['income', 'expense'])
                ->description('Transaction type.'),

            'category' => $schema->string()
                ->description('Category name.'),

            'month' => $schema->integer()
                ->description('Month number.'),

            'year' => $schema->integer()
                ->description('Year.'),

            'limit' => $schema->integer()
                ->description('Maximum number of transactions to return.')
                ->default(10),
        ];
    }
}