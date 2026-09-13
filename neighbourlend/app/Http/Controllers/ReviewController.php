<?php

namespace App\Http\Controllers;

use App\Domain\Models\Review;
use App\Services\ReviewService;
use App\Http\Requests\ReviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService
    ) {}

    public function add(ReviewRequest $request): JsonResponse
    {
        $data = $request->validated();
        $reservation = $this->reviewService->reservationToReview($request->reservation_id, $request->user()->id);

        $this->authorize('create', [Review::class, $reservation]);

        $data['reservation_id'] = $reservation->id;
        $data['reviewer_id'] = $request->user()->id;

        $tool = $this->reviewService->toolToReview($reservation->tool_id);
        $data['tool_id'] = $tool->id;
        $data['owner_id'] = $tool->owner_id;

        $review = $this->reviewService->createReview($data);

        return response()->json([
            "message" => "Review created successfully",
            "review" => $review
        ], Response::HTTP_OK);
    }
}
