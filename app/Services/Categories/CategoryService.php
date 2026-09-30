<?php

namespace App\Services\Categories;

use App\Exceptions\AppException;
use App\Models\Category;
use App\Services\Concerns\SyncsTranslations;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    use SyncsTranslations;

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return Category::query()
            ->with('translations')
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Category
    {
        $category = Category::create();

        $this->syncTranslations($category, $data['translations']);

        return $category->load('translations');
    }

    public function update(Category $category, array $data): Category
    {
        if (isset($data['translations'])) {
            $this->syncTranslations($category, $data['translations']);
        }

        return $category->load('translations');
    }

    public function show(Category $category): Category
    {
        return $category->load('translations');
    }

    public function delete(Category $category): void
    {
        if ($category->courses()->exists()) {
            throw AppException::fromKey('messages.categories.has_courses', 'CATEGORY_HAS_COURSES', 400);
        }

        $category->delete();
    }
}
