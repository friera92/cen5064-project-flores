<?php

namespace App\Policies;

use App\Domain\Models\Reservation;
use App\Domain\Models\User;
use App\Domain\States\ReservationState;

class ReviewPolicy
{
    public function create(User $user, Reservation $reservation): bool
    {
        // 1. Check Participation: Must be either the borrower or the lender
        $isBorrower = $user->id === $reservation->borrower_id;
        $isLender = $user->id === $reservation->tool->owner_id;

        if (!$isBorrower && !$isLender) {
            return false;
        }

        // 2. Check State: Reviews are only permitted on completed reservations
        if ($reservation->status !== ReservationState::CLOSED) {
            return false;
        }

        // 3. Anti-Duplication: Has this user already reviewed this reservation?
        $alreadyReviewed = $reservation->reviews()
            ->where('reviewer_id', $user->id)
            ->exists();

        if ($alreadyReviewed) {
            return false;
        }

        return true;
    }
}
