<?php

namespace App\Http\Controllers;

use App\Services\ReservationService;
use App\Services\ToolService;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $reservation = $this->reservationService->requestTool($data);

        return response()->json([
            'message' => 'Reservation requested successfully.',
            'data' => $reservation
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $reservations = $this->reservationRepository->findByUserId($request->user()->id);

        return response()->json([
            'data' => $reservations
        ]);
    }

    public function received(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->reservationRepository->findReceivedByOwnerId(
                $request->user()->id
            ),
        ]);
    }

    public function show(
        Request $request,
        int $reservation
    ): JsonResponse {
        $item = $this->reservationService
            ->getReservarionById($reservation);

        $isBorrower = $item->borrower_id === $request->user()->id;
        $isLender = $item->tool?->owner_id === $request->user()->id;

        abort_unless(
            $isBorrower || $isLender,
            403,
            'You are not authorized to view this reservation.'
        );

        return response()->json([
            'data' => $item->load(['tool', 'borrower:id,name'])
        ]);
    }

    public function approve(Request $request, int $reservation): JsonResponse
    {
        try {
            $item = $this->reservationService->approveReservation(
                $reservation,
                $request->user()->id
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'message' => 'Reservation approved successfully.',
            'data' => $item->load(['tool', 'borrower:id,name']),
        ]);
    }

    public function cancel(Request $request, int $reservation): JsonResponse
    {
        $item = $this->reservationService->cancelReservation(
            $reservation,
            $request->user()->id
        );

        return response()->json([
            'message' => 'Reservation canceled successfully.',
            'data' => $item->load('tool'),
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
