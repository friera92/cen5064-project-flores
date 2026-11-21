<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Models\Tool;
use App\Domain\Models\User;
use App\Domain\States\ReservationState;
use App\Domain\States\ToolCondition;
use App\Domain\States\ToolStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * HTTP tests for lender actions against the isolated PostgreSQL test database.
 */
class LoanLifecycleApiTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException(
                'LoanLifecycleApiTest requires PostgreSQL database neighbourlend_test. '
                . 'Do not run migration tests against a development database.'
            );
        }

        $this->refreshIsolatedDatabase();
    }

    private function makeTool(User $owner): Tool
    {
        $categoryId = (int) DB::table('categories')->insertGetId([
            'name' => 'Test Tools ' . Str::uuid(),
            'description' => 'Lender API test category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Tool::create([
            'title' => 'Lender Test Drill ' . Str::uuid(),
            'description' => 'Test equipment',
            'category_id' => $categoryId,
            'owner_id' => $owner->id,
            'daily_rate' => '15.00',
            'condition' => ToolCondition::GOOD->value,
            'availability_status' => ToolStatus::AVAILABLE->value,
        ]);
    }

    private function createReservation(Tool $tool, User $borrower): int
    {
        Sanctum::actingAs($borrower);

        return (int) $this->postJson('/api/reservation/new', [
            'tool_id' => $tool->id,
            'start_date' => today()->addDays(10)->toDateString(),
            'end_date' => today()->addDays(11)->toDateString(),
        ])->assertCreated()->json('data.id');
    }

    private function approveReservation(int $reservationId, User $lender): void
    {
        Sanctum::actingAs($lender);
        $this->patchJson("/api/reservation/{$reservationId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', ReservationState::APPROVED->value);
    }

    /** Move through request, approval, handoff, and return using only HTTP endpoints. */
    private function moveToReturned(int $reservationId, User $lender, User $borrower): void
    {
        $this->approveReservation($reservationId, $lender);

        Sanctum::actingAs($lender);
        $this->patchJson("/api/loan/{$reservationId}/handoff")
            ->assertOk()
            ->assertJsonPath('data.status', ReservationState::ACTIVE->value);

        Sanctum::actingAs($borrower);
        $this->patchJson("/api/loan/{$reservationId}/return")
            ->assertOk()
            ->assertJsonPath('data.status', ReservationState::RETURNED->value);
    }

    public function test_lender_cannot_handoff_tool_before_approval(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $tool = $this->makeTool($lender);
        $reservationId = $this->createReservation($tool, $borrower);

        Sanctum::actingAs($lender);
        $this->patchJson("/api/loan/{$reservationId}/handoff")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Invalid reservation transition: REQUESTED -> ACTIVE');
    }

        public function test_lender_can_handoff_tool_after_approval(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $tool = $this->makeTool($lender);
        $reservationId = $this->createReservation($tool, $borrower);

        // Approve the reservation first
        $this->approveReservation($reservationId, $lender);

        // Now hand off the tool
        Sanctum::actingAs($lender);
        $this->patchJson("/api/loan/{$reservationId}/handoff")
            ->assertOk()
            ->assertJsonPath('data.status', ReservationState::ACTIVE->value);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservationId,
            'status' => ReservationState::ACTIVE->value,
        ]);
        $this->assertSame(ToolStatus::IN_USE, $tool->fresh()->availability_status);

        // Handoff is not idempotent: it cannot transition ACTIVE -> ACTIVE.
        $this->patchJson("/api/loan/{$reservationId}/handoff")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Invalid reservation transition: ACTIVE -> ACTIVE');

        $this->assertSame(ToolStatus::IN_USE, $tool->fresh()->availability_status);
    }

    public function test_guest_cannot_access_any_lifecycle_endpoint(): void
    {
        $this->patchJson('/api/loan/999/handoff')->assertUnauthorized();
        $this->patchJson('/api/loan/999/return')->assertUnauthorized();
        $this->patchJson('/api/loan/999/inspect', ['returned_condition' => 'GOOD'])
            ->assertUnauthorized();
        $this->patchJson('/api/loan/999/close')->assertUnauthorized();
    }

    public function test_borrower_and_unrelated_user_cannot_handoff_an_approved_reservation(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $stranger = User::factory()->create();
        $tool = $this->makeTool($lender);
        $id = $this->createReservation($tool, $borrower);
        $this->approveReservation($id, $lender);

        Sanctum::actingAs($borrower);
        $this->patchJson("/api/loan/{$id}/handoff")->assertForbidden();
        Sanctum::actingAs($stranger);
        $this->patchJson("/api/loan/{$id}/handoff")->assertForbidden();

        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::APPROVED->value,
        ]);
        $this->assertSame(ToolStatus::AVAILABLE, $tool->fresh()->availability_status);
    }

    public function test_borrower_cannot_return_before_handoff(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $tool = $this->makeTool($lender);
        $id = $this->createReservation($tool, $borrower);
        $this->approveReservation($id, $lender);

        Sanctum::actingAs($borrower);
        $this->patchJson("/api/loan/{$id}/return")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Invalid reservation transition: APPROVED -> RETURNED');

        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::APPROVED->value,
        ]);
        $this->assertSame(ToolStatus::AVAILABLE, $tool->fresh()->availability_status);
    }

    public function test_only_borrower_can_return_an_active_tool_and_cannot_return_twice(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $stranger = User::factory()->create();
        $tool = $this->makeTool($lender);
        $id = $this->createReservation($tool, $borrower);
        $this->approveReservation($id, $lender);
        $this->patchJson("/api/loan/{$id}/handoff")
            ->assertOk()
            ->assertJsonPath('data.status', ReservationState::ACTIVE->value);

        $this->patchJson("/api/loan/{$id}/return")->assertForbidden(); // lender
        Sanctum::actingAs($stranger);
        $this->patchJson("/api/loan/{$id}/return")->assertForbidden();

        Sanctum::actingAs($borrower);
        $this->patchJson("/api/loan/{$id}/return")
            ->assertOk()
            ->assertJsonPath('data.status', ReservationState::RETURNED->value);
        $this->assertSame(ToolStatus::INSPECTION, $tool->fresh()->availability_status);

        $this->patchJson("/api/loan/{$id}/return")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Invalid reservation transition: RETURNED -> RETURNED');

        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::RETURNED->value,
        ]);
        $this->assertSame(ToolStatus::INSPECTION, $tool->fresh()->availability_status);
    }

    public function test_only_lender_can_inspect_a_returned_tool(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $stranger = User::factory()->create();
        $tool = $this->makeTool($lender);
        $id = $this->createReservation($tool, $borrower);
        $this->moveToReturned($id, $lender, $borrower);

        $this->patchJson("/api/loan/{$id}/inspect", ['returned_condition' => 'GOOD'])
            ->assertForbidden(); // borrower
        Sanctum::actingAs($stranger);
        $this->patchJson("/api/loan/{$id}/inspect", ['returned_condition' => 'GOOD'])
            ->assertForbidden();

        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::RETURNED->value,
            'returned_condition' => null,
        ]);
        $this->assertSame(ToolStatus::INSPECTION, $tool->fresh()->availability_status);
    }

    public function test_lender_cannot_inspect_before_return(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $tool = $this->makeTool($lender);
        $id = $this->createReservation($tool, $borrower);
        $this->approveReservation($id, $lender);

        $this->patchJson("/api/loan/{$id}/inspect", ['returned_condition' => 'GOOD'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Only returned reservations awaiting inspection can be inspected.');

        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::APPROVED->value,
            'returned_condition' => null,
        ]);
        $this->assertSame(ToolStatus::AVAILABLE, $tool->fresh()->availability_status);
    }

    public function test_inspection_requires_a_valid_returned_condition(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $tool = $this->makeTool($lender);
        $id = $this->createReservation($tool, $borrower);
        $this->moveToReturned($id, $lender, $borrower);
        Sanctum::actingAs($lender);

        $this->patchJson("/api/loan/{$id}/inspect", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('returned_condition');
        $this->patchJson("/api/loan/{$id}/inspect", ['returned_condition' => 'UNKNOWN'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('returned_condition');

        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::RETURNED->value,
            'returned_condition' => null,
        ]);
        $this->assertSame(ToolStatus::INSPECTION, $tool->fresh()->availability_status);
    }

    public function test_complete_http_lifecycle_with_good_inspection_closes_and_releases_tool(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $tool = $this->makeTool($lender);
        $id = $this->createReservation($tool, $borrower);
        $this->moveToReturned($id, $lender, $borrower);

        Sanctum::actingAs($lender);
        $this->patchJson("/api/loan/{$id}/close")->assertStatus(409);
        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::RETURNED->value,
            'returned_condition' => null,
        ]);

        $this->patchJson("/api/loan/{$id}/inspect", ['returned_condition' => 'GOOD'])
            ->assertOk()
            ->assertJsonPath('data.status', ReservationState::RETURNED->value)
            ->assertJsonPath('data.returned_condition', 'GOOD');
        $this->assertSame(ToolStatus::INSPECTION, $tool->fresh()->availability_status);

        $this->patchJson("/api/loan/{$id}/close")
            ->assertOk()
            ->assertJsonPath('data.status', ReservationState::CLOSED->value);
        $this->assertSame(ToolStatus::AVAILABLE, $tool->fresh()->availability_status);
        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::CLOSED->value,
            'returned_condition' => 'GOOD',
        ]);

        $this->patchJson("/api/loan/{$id}/close")->assertStatus(409);
        $this->assertSame(ToolStatus::AVAILABLE, $tool->fresh()->availability_status);
    }

    public function test_damaged_broken_and_missing_parts_inspections_create_disputes(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();

        foreach ([
            'DAMAGED' => ToolStatus::MAINTENANCE,
            'BROKEN' => ToolStatus::RETIRED,
            'MISSING_PARTS' => ToolStatus::RETIRED,
        ] as $condition => $expectedToolStatus) {
            $tool = $this->makeTool($lender);
            $id = $this->createReservation($tool, $borrower);
            $this->moveToReturned($id, $lender, $borrower);

            Sanctum::actingAs($lender);
            $this->patchJson("/api/loan/{$id}/inspect", [
                'returned_condition' => $condition,
            ])->assertOk()
              ->assertJsonPath('data.status', ReservationState::DISPUTED->value)
              ->assertJsonPath('data.returned_condition', $condition);

            $this->assertDatabaseHas('reservations', [
                'id' => $id,
                'status' => ReservationState::DISPUTED->value,
                'returned_condition' => $condition,
            ]);
            $this->assertSame($expectedToolStatus, $tool->fresh()->availability_status);

            $this->patchJson("/api/loan/{$id}/close")->assertStatus(409);
            $this->assertSame(ReservationState::DISPUTED, $tool->fresh()->reservations()->findOrFail($id)->status);
            $this->assertSame($expectedToolStatus, $tool->fresh()->availability_status);
        }
    }

    public function test_borrower_and_unrelated_user_cannot_close_after_good_inspection(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $stranger = User::factory()->create();
        $tool = $this->makeTool($lender);
        $id = $this->createReservation($tool, $borrower);
        $this->moveToReturned($id, $lender, $borrower);
        Sanctum::actingAs($lender);
        $this->patchJson("/api/loan/{$id}/inspect", ['returned_condition' => 'GOOD'])
            ->assertOk();

        Sanctum::actingAs($borrower);
        $this->patchJson("/api/loan/{$id}/close")->assertForbidden();
        Sanctum::actingAs($stranger);
        $this->patchJson("/api/loan/{$id}/close")->assertForbidden();

        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::RETURNED->value,
            'returned_condition' => 'GOOD',
        ]);
        $this->assertSame(ToolStatus::INSPECTION, $tool->fresh()->availability_status);
    }

    public function test_missing_reservations_return_not_found_for_all_lifecycle_actions(): void
    {
        $lender = User::factory()->create();
        Sanctum::actingAs($lender);

        $this->patchJson('/api/loan/999999/handoff')->assertNotFound();
        $this->patchJson('/api/loan/999999/return')->assertNotFound();
        $this->patchJson('/api/loan/999999/inspect', ['returned_condition' => 'GOOD'])
            ->assertNotFound();
        $this->patchJson('/api/loan/999999/close')->assertNotFound();
    }
}
