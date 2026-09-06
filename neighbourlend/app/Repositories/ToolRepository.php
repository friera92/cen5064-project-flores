<?php

namespace App\Repositories;

use App\Domain\Models\Tool;
use App\Repositories\Interfaces\ToolRepositoryInterface;

class ToolRepository implements ToolRepositoryInterface
{
    public function getAllTools()
    {
        return Tool::all();
    }

    public function getToolById($id)
    {
        return Tool::find($id);
    }

    public function create(array $data): ?Tool
    {
        return Tool::create($data);
    }

    public function save(Tool $tool): bool
    {
        return $tool->save();
    }

    public function delete(int $id): bool
    {
        $tool = Tool::find($id);
        if ($tool) {
            return $tool->delete();
        }
        return false;
    }

    public function findById(int $id): ?Tool
    {
        return Tool::find($id);
    }

    public function findAll(): array
    {
        return Tool::all()->toArray();
    }

    public function findByCategoryId(int $categoryId): array
    {
        return Tool::where('category_id', $categoryId)->get()->toArray();
    }

    public function findByOwnerId(int $ownerId): array
    {
        return Tool::where('owner_id', $ownerId)->get()->toArray();
    }

    public function findByName(string $name): array
    {
        return Tool::where('title', 'like', '%' . $name . '%')->get()->toArray();
    }

    public function findByCondition(string $condition): array
    {
        return Tool::where('condition', $condition)->get()->toArray();
    }

    public function findByAvailabilityStatus(string $availabilityStatus): array
    {
        return Tool::where('availability_status', $availabilityStatus)->get()->toArray();
    }

    public function findByIdWithLock(int $id): ?Tool
    {
        return Tool::where('id', $id)->lockForUpdate()->first();
    }
}
