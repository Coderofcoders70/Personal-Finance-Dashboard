<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use AuthorizesRequests;

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

        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'type' => $request->type,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
        ]);

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

        $transaction->update([
            'category_id' => $request->category_id,
            'type' => $request->type,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully.',
            'transaction' => new TransactionResource($transaction->load('category')),
        ]);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully.',
        ]);
    }
}
