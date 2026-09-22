<?php

namespace App\Http\Controllers;

use App\Services\ReservationService;
use App\Services\ToolService;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Domain\States\ReservationState;
use App\Http\Requests\ReservationRequest;

class ReservationController extends Controller
{
    public function __construct(
        private ToolService $toolService,
        private ReservationService $reservationService,
        private ReservationRepositoryInterface $reservationRepository
    ) {}

    public function store(ReservationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['borrower_id'] = $request->user()->id;
        $data['status'] = ReservationState::REQUESTED;

        $reservation = $this->reservationService->requestTool($data);

        return response()->json([
            'message' => 'Reservation requested successfully.',
            'data' => $reservation
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $reservations = $this->reservationRepository
            ->findByUserId($request->user()->id);

        return response()->json([
            'data' => $reservations
        ]);
    }

    public function show(
        Request $request,
        int $reservation
    ): JsonResponse {
        $item = $this->reservationService
            ->getReservarionById($reservation);

        abort_unless(
            $item->borrower_id === $request->user()->id,
            403,
            'You are not authorized to view this reservation.'
        );

        return response()->json([
            'data' => $item->load('tool')
        ]);
    }

    public function quote(ReservationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $tool = $this->toolService->getToolById($data['tool_id']);
        $dailyRate = $tool->daily_rate;

        $quote = $this->reservationService->calculateCost($dailyRate, $data['start_date'], $data['end_date']);

        return response()->json([
            'data' => $quote
        ]);
    }
}
