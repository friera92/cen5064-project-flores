<?php

namespace Database\Seeders;

use App\Domain\Models\Reservation;
use App\Domain\Models\Review;
use App\Domain\Models\User;
use App\Domain\States\ReservationState;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $david = User::where('email', 'david@example.com')->first();
        $carlos = User::where('email', 'carlos@example.com')->first();

        // Retrieve the closed reservation for the drill
        $closedReservation = Reservation::where('status', ReservationState::CLOSED->value)
            ->where('borrower_id', $david->id)
            ->first();

        if (!$closedReservation) {
            return;
        }

        // 1. Borrower reviewing the tool/experience (Active & Visible)
        Review::updateOrCreate(
            [
                'reservation_id' => $closedReservation->id,
                'reviewer_id' => $david->id,
                'reviewee_id' => $carlos->id,
                'tool_id' => $closedReservation->tool_id
            ],
            [
                'rating'   => 5,
                'comment'  => 'Great tool, batteries were fully charged and Carlos was very easy to communicate with!',
                'visible'  => true,
                'end_date' => now()->addMonths(6),
            ]
        );

        // 2. Lender reviewing the borrower's care of the tool (Reciprocal Review)
        Review::updateOrCreate(
            [
                'reservation_id' => $closedReservation->id,
                'reviewer_id' => $carlos->id,
                'reviewee_id' => $david->id,
                'tool_id' => $closedReservation->tool_id
            ],
            [
                'rating'   => 5,
                'comment'  => 'David returned the drill on time and in spotless condition. Would happily lend to him again.',
                'visible'  => true,
                'end_date' => now()->addMonths(6),
            ]
        );

        // 3. Historical/Expired Review (to verify your active() scope filters it out)
        Review::updateOrCreate(
            [
                'reservation_id' => $closedReservation->id,
                'reviewer_id' => $carlos->id,
                'reviewee_id' => $david->id,
                'comment' => 'Test archived review',
                'tool_id' => $closedReservation->tool_id
            ],
            [
                'rating'   => 4,
                'visible'  => true,
                'end_date' => now()->subDay(), // Expired yesterday
            ]
        );
    }
}
