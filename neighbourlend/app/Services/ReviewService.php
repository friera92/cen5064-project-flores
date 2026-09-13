<?php

namespace App\Services;

use App\Domain\Models\Review;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use App\Repositories\Interfaces\ReviewRepositoryInterface;
use App\Domain\States\ReservationState;
use App\Repositories\Interfaces\ToolRepositoryInterface;
use Exception;


class ReviewService
{
    private ReviewRepositoryInterface $reviewRepository;
    private ReservationRepositoryInterface $reservationRepository;
    private ToolRepositoryInterface $toolRepository;

    public function __construct(
        ReviewRepositoryInterface $reviewRepository,
        ReservationRepositoryInterface $reservationRepository,
        ToolRepositoryInterface $toolRepository
    ) {
        $this->reviewRepository = $reviewRepository;
        $this->reservationRepository = $reservationRepository;
        $this->toolRepository = $toolRepository;
    }

    public function createReview(array $data): ?Review
    {
        if (!isset($data['reviewee_id'])) {
            throw new Exception('Reviewee ID is required to register the review.');
        }

        $data['visible'] = true;
        $data['end_date'] = now()->addMonths(6);
        $data['visible'] = true;
        return $this->reviewRepository->create($data);
    }

    public function getReviewById(int $id): ?Review
    {
        $review = $this->reviewRepository->findById($id);
        if (!$review) {
            throw new Exception('Review not found.');
        }
        return $review;
    }

    public function hideReview(int $id): bool
    {
        $review = $this->reviewRepository->findById($id);
        if (!$review) {
            throw new Exception('Review not found.');
        }
        return $this->reviewRepository->save($review);
    }

    public function reservationToReview(int $reservationId, int $userId)
    {
        $reservation = $this->reservationRepository->findById($reservationId);
        if (!$reservation) {
            throw new Exception('Reservation not found.');
        }

        if ($userId !== $reservation->userId) {
            throw new Exception('Only the user who reserved the tool can add a review.');
        }

        if ($reservation->status !== ReservationState::CLOSED) {
            throw new Exception('The reservation has to be CLOSED to add a review.');
        }

        return $reservation;
    }

    public function toolToReview(int $toolId)
    {
        $tool = $this->toolRepository->findById($toolId);
        if (!$tool) {
            throw new Exception("Tool not found.");
        }

        return $tool;
    }
}
