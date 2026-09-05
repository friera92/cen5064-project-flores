<?php

namespace App\Repositories\Interfaces;

use App\Domain\Models\Review;

interface ReviewRepositoryInterface
{
    public function create(array $data): ?Review;
    public function save(Review $review): bool;
    public function findById(int $reviewId): ?Review;
    public function findByToolId(int $toolId): array;
    public function findByReservationId(int $reservationId): array;
    public function findByReviewerId(int $reviewerId): array;
    public function findByRevieweeId(int $revieweeId): array;
}
