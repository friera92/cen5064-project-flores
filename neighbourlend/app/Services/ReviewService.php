<?php

namespace App\Services;

use App\Domain\Models\Review;
use App\Repositories\Interfaces\ReviewRepositoryInterface;


class ReviewService
{
    private ReviewRepositoryInterface $reviewRepository;

    public function __construct(ReviewRepositoryInterface $reviewRepository)
    {
        $this->reviewRepository = $reviewRepository;
    }

    public function createReview(array $data): ?Review
    {
        return $this->reviewRepository->create($data);
    }

    public function getReviewById(int $id): ?Review
    {
        return $this->reviewRepository->findById($id);
    }

    public function updateReview(Review $review): bool
    {
        return $this->reviewRepository->save($review);
    }
}
