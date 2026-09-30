<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Categories\CategoryResource;
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
            $paginator->through(fn ($c) => (new CategoryResource($c))->toArray($request)),
            'messages.categories.list',
        );
    }
}
