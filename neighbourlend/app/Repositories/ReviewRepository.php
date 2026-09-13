<?php

namespace App\Repositories;

use App\Domain\Models\Review;
use App\Repositories\Interfaces\ReviewRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ReviewRepository implements ReviewRepositoryInterface
{
    public function findById(int $id): ?Review
    {
        return Review::find($id);
    }

    public function create(array $data): Review
    {
        return Review::create($data);
    }

    public function save(Review $review): bool
    {
        return $review->save();
    }

    public function findByReviewerId(int $reviewerId): array
    {
        return Review::where('reviewer_id', $reviewerId)->get()->toArray();
    }

    public function findByRevieweeId(int $revieweeId): array
    {
        return Review::where('reviewee_id', $revieweeId)->get()->toArray();
    }

    public function findByToolId(int $toolId): array
    {
        return Review::where('tool_id', $toolId)->get()->toArray();
    }

    public function findByReservationId(int $reservationId): array
    {
        return Review::where('reservation_id', $reservationId)->get()->toArray();
    }

    public function getReviewsForTool(int $toolId): Collection
    {
        return Review::query()
            ->active() // Applies visible = true AND (end_date IS NULL OR end_date > NOW())
            ->whereHas('reservation', function ($query) use ($toolId) {
                $query->where('tool_id', $toolId);
            })
            ->latest()
            ->get();
    }
}
