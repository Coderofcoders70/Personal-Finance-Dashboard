<?php

namespace App\Services;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use App\Services\CacheInvalidationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CategoryService
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
        return Category::query()
            ->where(function (Builder $query) use ($user) {

                $query->default();

                $query->orWhere(function (Builder $query) use ($user) {
                    $query->custom($user->id);
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function store(CategoryRequest $categoryRequest): Category
    {
        DB::beginTransaction();

        try {

            $category = Category::create([
                'user_id' => $categoryRequest->user()->id,
                'name' => $categoryRequest->name,
                'type' => $categoryRequest->type,
                'icon' => $categoryRequest->icon,
                'color' => $categoryRequest->color,
            ]);

            $this->notificationService->create(
                $categoryRequest->user(),
                'category_created',
                'Category Created',
                "{$category->name} category created successfully.",
                'success'
            );

            DB::commit();

            $this->cacheInvalidationService
                ->clearFinance($categoryRequest->user());

            return $category;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(CategoryRequest $categoryRequest, Category $category): Category
    {
        DB::beginTransaction();

        try {
            $category->update([
                'name' => $categoryRequest->name,
                'type' => $categoryRequest->type,
                'icon' => $categoryRequest->icon,
                'color' => $categoryRequest->color,
            ]);

            $this->notificationService->create(
                $categoryRequest->user(),
                'category_updated',
                'Category Updated',
                "{$category->name} category updated successfully.",
                'info'
            );

            DB::commit();

            $this->cacheInvalidationService
                ->clearFinance($categoryRequest->user());

            return $category;

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(User $user, Category $category): void
    {
        DB::beginTransaction();

        try {
            $categoryName = $category->name;

            $category->delete();

                $this->notificationService->create(
                    $user,
                    'category_deleted',
                    'Category Deleted',
                    "{$categoryName} category deleted successfully.",
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
}
