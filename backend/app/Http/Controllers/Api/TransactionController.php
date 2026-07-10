<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Services\NotificationService;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class TransactionController extends Controller
{
    use AuthorizesRequests;

    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $transactions = Transaction::with('category')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'transactions' => TransactionResource::collection($transactions),
            // 'transactions' => $transactions,
        ]);
    }

    public function store(TransactionRequest $request)
    {
        $category = Category::where('id', $request->category_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid category selected.',
            ], 404);
        }

        $transaction = DB::transaction(function () use ($request) {
            $transaction = Transaction::create([
                'user_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'type' => $request->type,
                'title' => $request->title,
                'description' => $request->description,
                'amount' => $request->amount,
                'transaction_date' => $request->transaction_date,
            ]);

            $action = $transaction->type === 'income'
                ? 'Income Added'
                : 'Expense Added';

            $event = $transaction->type === 'income'
                ? 'income_created'
                : 'expense_created';

            $this->notificationService->create(
                $request->user(),
                $event,
                $action,
                "{$transaction->title} of ₹{$transaction->amount} added successfully.",
                'success'
            );

            return $transaction;
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully.',
            'transaction' => new TransactionResource($transaction->load('category')),
        ], 201);
    }

    public function update(TransactionRequest $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $category = Category::where('id', $request->category_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid category selected.',
            ], 404);
        }

        DB::transaction(function () use ($request, $transaction) {
            $transaction->update([
                'category_id' => $request->category_id,
                'type' => $request->type,
                'title' => $request->title,
                'description' => $request->description,
                'amount' => $request->amount,
                'transaction_date' => $request->transaction_date,
            ]);

            $action = $transaction->type === 'income'
                ? 'Income Updated'
                : 'Expense Updated';

            $event = $transaction->type === 'income'
                ? 'income_updated'
                : 'expense_updated';

            $this->notificationService->create(
                $request->user(),
                $event,
                $action,
                "{$transaction->title} updated successfully.",
                'info'
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully.',
            'transaction' => new TransactionResource($transaction->load('category')),
        ]);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        $this->authorize('delete', $transaction);

        $title = $transaction->title;
        $type = $transaction->type;
        $amount = $transaction->amount;

        DB::transaction(function () use ($request, $transaction, $title, $type, $amount) {
            
            $transaction->delete();

            $action = $type === 'income'
                ? 'Income Deleted'
                : 'Expense Deleted';

            $event = $type === 'income'
                ? 'income_deleted'
                : 'expense_deleted';

            $this->notificationService->create(
                $request->user(),
                $event,
                $action,
                "{$title} of ₹{$amount} deleted successfully.",
                'warning'
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully.',
        ]);
    }
}
