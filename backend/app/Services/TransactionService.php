<?php

namespace App\Services;

use App\Http\Requests\TransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;
use App\Services\CacheInvalidationService;
use Illuminate\Database\Eloquent\Collection;

class TransactionService
{
    private NotificationService $notificationService;
    private CacheInvalidationService $cacheInvalidationService;

    public function __construct(
        NotificationService $notificationService,
        CacheInvalidationService $cacheInvalidationService
    ) {
        $this->notificationService = $notificationService;
        $this->cacheInvalidationService = $cacheInvalidationService;
    }

    public function index(User $user): Collection
    {
        return Transaction::with('category')
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    public function store(TransactionRequest $transactionRequest): Transaction
    {
        DB::beginTransaction();

        try {

            $category = $this->findValidCategory($transactionRequest->category_id, $transactionRequest->user());

            if (!$category) {
                throw new \Exception('Invalid category selected.');
            }

            $transaction = Transaction::create([
                'user_id' => $transactionRequest->user()->id,
                'category_id' => $transactionRequest->category_id,
                'title' => $transactionRequest->title,
                'description' => $transactionRequest->description,
                'amount' => $transactionRequest->amount,
                'transaction_date' => $transactionRequest->transaction_date,
            ]);

            $this->notificationService->create(
                $transactionRequest->user(),
                'transaction_created',
                'Transaction Created',
                "{$transaction->title} of {$transaction->amount} added successfully.",
                'success'
            );

            DB::commit();

            $this->cacheInvalidationService
                ->clearFinance($transactionRequest->user());

            return $transaction->load('category');
        } catch (\Throwable $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function update(TransactionRequest $transactionRequest, Transaction $transaction): Transaction
    {
        DB::beginTransaction();

        try {
            $category = $this->findValidCategory($transactionRequest->category_id, $transactionRequest->user());

            if (!$category) {
                throw new \Exception('Invalid category selected.');
            }

            $transaction->update([
                'category_id' => $transactionRequest->category_id,
                'title' => $transactionRequest->title,
                'description' => $transactionRequest->description,
                'amount' => $transactionRequest->amount,
                'transaction_date' => $transactionRequest->transaction_date,
            ]);

            $this->notificationService->create(
                $transactionRequest->user(),
                'transaction_updated',
                'Transaction updated',
                "{$transaction->title} updated successfully.",
                'info'
            );

            DB::commit();

            $this->cacheInvalidationService
                ->clearFinance($transactionRequest->user());

            return $transaction->load('category');

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(User $user, Transaction $transaction): void
    {
        DB::beginTransaction();

        try {
            $title = $transaction->title;
            $amount = $transaction->amount;

            $transaction->delete();

                $this->notificationService->create(
                    $user,
                    'transaction_deleted',
                    'Transaction deleted',
                    "{$title} of {$amount} deleted successfully.",
                    'warning'
                );

            DB::commit();

            $this->cacheInvalidationService
                ->clearFinance($user);

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function findValidCategory(int $categoryId, User $user): ?Category
    {
        return Category::query()
            ->where('id', $categoryId)
            ->where(function ($query) use ($user) {
                $query->default();

                $query->orWhere(function ($query) use ($user) {
                    $query->custom($user->id);
                });
            })
            ->first();
    }
}
