<?php

namespace App\Repositories;

use App\Domain\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function save(Category $category): bool
    {
        return $category->save();
    }

    public function delete(int $id): bool
    {
        $category = Category::find($id);
        if ($category) {
            return $category->delete();
        }
        return false;
    }

    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }
}
