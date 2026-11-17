<?php

namespace App\Http\Controllers;

use App\Services\LoanLifecycleService;
use App\Domain\States\ToolReturnedCondition;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DomainException;

class LoanLifecycleController extends Controller
{
    public function __construct(
        private LoanLifecycleService $loanLifecycleService
    ) {}

    public function handOff(Request $request, int $reservationId): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $reservation = $this->loanLifecycleService->handOffTool($reservationId, $userId);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
                'message' => 'Tool handed off successfully.',
                'data' => $reservation
            ]);
    }

    public function return(Request $request, int $reservationId): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $reservation = $this->loanLifecycleService->returnTool($reservationId, $userId);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'message' => 'Tool returned successfully.',
            'data' => $reservation
        ]);
    }

    public function inspect(Request $request, int $reservationId): JsonResponse
    {
        try {
            $data = $request->validate([
                'returned_condition' => [
                    'required',
                    Rule::enum(ToolReturnedCondition::class),
                ],
            ]);

            $userId = $request->user()->id;
            $reservation = $this->loanLifecycleService->inspectTool(
                $reservationId,
                $data['returned_condition'],
                $userId
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'message' => 'Tool inspected successfully.',
            'data' => $reservation
        ]);
    }

    public function close(Request $request, int $reservationId): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $reservation = $this->loanLifecycleService->closeReservation($reservationId, $userId);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'message' => 'Reservation closed successfully.',
            'data' => $reservation
        ]);
    }
}