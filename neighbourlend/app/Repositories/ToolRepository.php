<?php

namespace App\Repositories;

use App\Domain\Models\Tool;
use App\Repositories\Interfaces\ToolRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Builders\ToolSearchBuilder;

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
        return $tool ? (bool) $tool->delete() : false;
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

    public function search(array $filters): LengthAwarePaginator
    {
        // The Builder assembles optional filters; repository owns execution/pagination.
        $query = (new ToolSearchBuilder())
            ->forOwner(isset($filters['owner_id']) ? (int) $filters['owner_id'] : null)
            ->matchingText($filters['q'] ?? null)
            ->inCategory(isset($filters['category_id']) && $filters['category_id'] !== ''
                ? (int) $filters['category_id']
                : null)
            ->withCondition($filters['condition'] ?? null)
            ->withAvailability($filters['availability_status'] ?? null)
            ->minimumRate($filters['min_rate'] ?? null)
            ->maximumRate($filters['max_rate'] ?? null)
            ->build();

        return $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 12));
    }
}
