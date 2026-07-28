<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CategoryService $categoryService
    ) {}

    public function index(Request $request)
    {
        $categories = $this->categoryService->index($request->user());

        return response()->json([
            'success' => true,
            'categories' => CategoryResource::collection($categories),
        ]);
    }

    public function store(CategoryRequest $request)
    {
        $category = $this->categoryService->store($request);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'category' => new CategoryResource($category),
        ], 201);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $this->authorize('update', $category);

        $category = $this->categoryService->update($request, $category);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'category' => new CategoryResource($category),
        ]);
    }

    public function destroy(Request $request, Category $category)
    {
        $this->authorize('delete', $category);

        $this->categoryService->destroy($request->user(), $category);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}
