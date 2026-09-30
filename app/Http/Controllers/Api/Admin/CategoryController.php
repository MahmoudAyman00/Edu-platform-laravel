<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Http\Resources\Categories\CategoryResource;
use App\Models\Category;
use App\Services\Categories\CategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(protected CategoryService $categories)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->categories->list((int) $request->integer('per_page', 15));

        return $this->paginated(
            $paginator->through(fn (Category $c) => (new CategoryResource($c))->toArray($request)),
            'messages.categories.list',
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        return $this->success(
            new CategoryResource($this->categories->create($request->validated())),
            'messages.categories.created',
            201
        );
    }

    public function show(Category $category): JsonResponse
    {
        return $this->success(
            new CategoryResource($this->categories->show($category)),
            'messages.categories.detail'
        );
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        return $this->success(
            new CategoryResource($this->categories->update($category, $request->validated())),
            'messages.categories.updated'
        );
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->categories->delete($category);

        return $this->success(null, 'messages.categories.deleted');
    }
}
