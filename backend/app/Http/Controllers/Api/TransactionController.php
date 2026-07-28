<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TransactionService $transactionService
    ) {}

    public function index(Request $request)
    {
        $transactions = $this->transactionService->index($request->user());

        return response()->json([
            'success' => true,
            'transactions' => TransactionResource::collection($transactions),
            // 'transactions' => $transactions,
        ]);
    }

    public function store(TransactionRequest $request)
    {
        $transaction = $this->transactionService->store($request);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully.',
            'transaction' => new TransactionResource($transaction),
        ], 201);
    }

    public function update(TransactionRequest $request, Transaction $transaction)
    {

        $this->authorize('update', $transaction);

        $transaction = $this->transactionService->update($request, $transaction);

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully.',
            'transaction' => new TransactionResource($transaction),
        ]);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        $this->authorize('delete', $transaction);

        $this->transactionService->destroy($request->user(), $transaction);

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully.',
        ]);
    }
}
