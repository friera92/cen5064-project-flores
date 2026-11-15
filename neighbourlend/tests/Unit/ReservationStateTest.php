<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Models\Reservation;
use App\Domain\States\ReservationState;
use DomainException;
use PHPUnit\Framework\TestCase;

class ReservationStateTest extends TestCase
{
    public function test_requested_reservation_can_be_approved(): void
    {
        $this->assertTrue(
            ReservationState::REQUESTED->canTransitionTo(ReservationState::APPROVED)
        );
    }

    public function test_requested_reservation_can_be_canceled(): void
    {
        $this->assertTrue(
            ReservationState::REQUESTED->canTransitionTo(ReservationState::CANCELED)
        );
    }

    public function test_canceled_reservation_cannot_be_approved(): void
    {
        $this->assertFalse(
            ReservationState::CANCELED->canTransitionTo(ReservationState::APPROVED)
        );
    }

    public function test_requested_reservation_cannot_skip_directly_to_active(): void
    {
        $this->assertFalse(
            ReservationState::REQUESTED->canTransitionTo(ReservationState::ACTIVE)
        );
    }

    public function test_happy_path_transitions_are_allowed(): void
    {
        $transitions = [
            [ReservationState::REQUESTED, ReservationState::APPROVED],
            [ReservationState::APPROVED, ReservationState::ACTIVE],
            [ReservationState::ACTIVE, ReservationState::RETURNED],
            [ReservationState::RETURNED, ReservationState::CLOSED],
        ];

        foreach ($transitions as [$from, $to]) {
            $this->assertTrue(
                $from->canTransitionTo($to),
                "Expected {$from->value} -> {$to->value} to be allowed."
            );
        }
    }

    public function test_returned_reservation_can_be_disputed(): void
    {
        $this->assertTrue(
            ReservationState::RETURNED->canTransitionTo(ReservationState::DISPUTED)
        );
    }

    public function test_terminal_states_cannot_transition_to_any_state(): void
    {
        foreach ([ReservationState::CANCELED, ReservationState::CLOSED, ReservationState::DISPUTED] as $state) {
            foreach (ReservationState::cases() as $nextState) {
                $this->assertFalse(
                    $state->canTransitionTo($nextState),
                    "Expected {$state->value} -> {$nextState->value} to be rejected."
                );
            }
        }
    }

    public function test_only_returned_reservations_can_be_inspected(): void
    {
        foreach (ReservationState::cases() as $state) {
            $this->assertSame(
                $state === ReservationState::RETURNED,
                $state->canBeInspected()
            );
        }
    }

    public function test_reservation_model_rejects_invalid_transition(): void
    {
        $reservation = new Reservation(['status' => ReservationState::CANCELED]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid reservation transition: CANCELED -> APPROVED');

        $reservation->transitionTo(ReservationState::APPROVED);
    }
}
