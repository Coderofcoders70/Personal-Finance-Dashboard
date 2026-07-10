<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\NotificationService;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class CategoryController extends Controller
{
    use AuthorizesRequests;

    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $categories = Category::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'categories' => CategoryResource::collection($categories),
        ]);
    }

    public function store(CategoryRequest $request)
    {
        $category = DB::transaction(function () use ($request) {

            $category = Category::create([
                'user_id' => $request->user()->id,
                'name' => $request->name,
                'type' => $request->type,
                'icon' => $request->icon,
                'color' => $request->color,
                'is_system' => false,
            ]);

            $this->notificationService->create(
                $request->user(),
                'category_created',
                'Category Created',
                "{$category->name} category created successfully.",
                'success'
            );

            return $category;
        });

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'category' => new CategoryResource($category),
        ], 201);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $this->authorize('update', $category);

        DB::transaction(function () use ($request, $category) {

            $category->update([
                'name' => $request->name,
                'type' => $request->type,
                'icon' => $request->icon,
                'color' => $request->color,
            ]);

            $this->notificationService->create(
                $request->user(),
                'category_updated',
                'Category Updated',
                "{$category->name} category updated successfully.",
                'info'
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'category' => new CategoryResource($category),
        ]);
    }

    public function destroy(Request $request, Category $category)
    {
        $this->authorize('delete', $category);

        $categoryName = $category->name;
        
        DB::transaction(function () use ($request, $category, $categoryName) {
            
            $category->delete();

            $this->notificationService->create(
                $request->user(),
                'category_deleted',
                'Category Deleted',
                "{$categoryName} category deleted successfully.",
                'warning'
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}
