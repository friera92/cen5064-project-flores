<?php

namespace Database\Seeders;

use App\Domain\Models\Reservation;
use App\Domain\Models\Review;
use App\Domain\Models\Tool;
use App\Domain\Models\User;
use App\Domain\States\ReservationState;
use App\Domain\States\ToolReturnedCondition;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $david = User::where('email', 'david@example.com')->first();
        $elena = User::where('email', 'elena@example.com')->first();

        $drill = Tool::where('title', 'like', '%DeWalt%')->first();
        $chainsaw = Tool::where('title', 'like', '%Chainsaw%')->first();

        // 1. Completed reservation with a review
        Reservation::updateOrCreate(
            [
                'tool_id'     => $drill->id,
                'borrower_id' => $david->id,
                'start_date'  => now()->subDays(5)->toDateString(),
            ],
            [
                'end_date'           => now()->subDays(2)->toDateString(),
                'status'             => ReservationState::CLOSED->value,
                // 'total_cost'         => 45.00,
                'returned_condition' => ToolReturnedCondition::GOOD->value
            ]
        );

        // 2. Currently active rental
        Reservation::updateOrCreate(
            [
                'tool_id'     => $chainsaw->id,
                'borrower_id' => $elena->id,
                'start_date'  => now()->subDay()->toDateString(),
            ],
            [
                'end_date'           => now()->addDays(2)->toDateString(),
                'status'             => ReservationState::ACTIVE->value,
                // 'total_cost'         => 90.00,
                'returned_condition' => null
            ]
        );
    }
}
