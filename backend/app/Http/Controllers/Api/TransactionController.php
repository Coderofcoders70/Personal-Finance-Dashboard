<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Services\NotificationService;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\CacheInvalidationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    private NotificationService $notificationService;
    private CacheInvalidationService $cacheInvalidationService;

    public function __construct(
        NotificationService $notificationService,
        CacheInvalidationService $cacheInvalidationService
    ) {
        $this->notificationService = $notificationService;
        $this->cacheInvalidationService = $cacheInvalidationService;
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
        $category = Category::query()
            ->where('id', $request->category_id)
            ->where(function ($query) use ($request) {
                $query->default();

                $query->orWhere(function ($query) use ($request) {
                    $query->custom($request->user()->id);
                });
            })
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
                'title' => $request->title,
                'description' => $request->description,
                'amount' => $request->amount,
                'transaction_date' => $request->transaction_date,
            ]);

            $this->notificationService->create(
                $request->user(),
                'transaction_created',
                'Transaction Created',
                "{$transaction->title} of {$transaction->amount} added successfully.",
                'success'
            );

            return $transaction;
        });

        $this->cacheInvalidationService
            ->clearFinance($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully.',
            'transaction' => new TransactionResource($transaction->load('category')),
        ], 201);
    }

    public function update(TransactionRequest $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $category = Category::query()
            ->where('id', $request->category_id)
            ->where(function ($query) use ($request) {
                $query->default();

                $query->orWhere(function ($query) use ($request) {
                    $query->custom($request->user()->id);
                });
            })
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
                'title' => $request->title,
                'description' => $request->description,
                'amount' => $request->amount,
                'transaction_date' => $request->transaction_date,
            ]);

            $this->notificationService->create(
                $request->user(),
                'transaction_updated',
                'Transaction updated',
                "{$transaction->title} updated successfully.",
                'info'
            );
        });

        $this->cacheInvalidationService
            ->clearFinance($request->user());

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
        $amount = $transaction->amount;

        DB::transaction(function () use ($request, $transaction, $title, $amount) {

            $transaction->delete();

            $this->notificationService->create(
                $request->user(),
                'transaction_deleted',
                'Transaction deleted',
                "{$title} of {$amount} deleted successfully.",
                'warning'
            );
        });

        $this->cacheInvalidationService
            ->clearFinance($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully.',
        ]);
    }
}
