<?php

namespace App\Services;

use App\Domain\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;

class CategoryService
{
    private CategoryRepositoryInterface $categoryRepository;

    public function __construct(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function createCategory(array $data): ?Category
    {
        return $this->categoryRepository->create($data);
    }

    public function getCategoryById(int $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }

    public function updateCategory(Category $category): bool
    {
        return $this->categoryRepository->save($category);
    }

    public function deleteCategory(int $id): bool
    {
        return $this->categoryRepository->delete($id);
    }
}
