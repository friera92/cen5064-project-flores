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
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/** HTTP cancellation regression tests in the isolated PostgreSQL database. */
class ReservationCancellationApiTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException('This test requires the isolated neighbourlend_test PostgreSQL database.');
        }
        $this->refreshIsolatedDatabase();
    }

    private function requestReservation(User $lender, User $borrower): int
    {
        $categoryId = (int) DB::table('categories')->insertGetId([
            'name' => 'Cancellation test tools',
            'description' => 'Test category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tool = Tool::create([
            'title' => 'Cancellation test tool',
            'description' => 'Equipment for a cancellation scenario',
            'category_id' => $categoryId,
            'owner_id' => $lender->id,
            'daily_rate' => '15.00',
            'condition' => ToolCondition::GOOD->value,
            'availability_status' => ToolStatus::AVAILABLE->value,
        ]);
        Sanctum::actingAs($borrower);
        return (int) $this->postJson('/api/reservation/new', [
            'tool_id' => $tool->id,
            'start_date' => today()->addDays(10)->toDateString(),
            'end_date' => today()->addDays(12)->toDateString(),
        ])->assertCreated()->json('data.id');
    }

    public function test_lender_must_give_nonblank_reason_and_reservation_is_unchanged(): void
    {
        $lender = User::factory()->create();
        $id = $this->requestReservation($lender, User::factory()->create());
        Sanctum::actingAs($lender);
        foreach (['', '    '] as $reason) {
            $this->patchJson("/api/reservation/{$id}/cancel", ['cancellation_reason' => $reason])
                ->assertUnprocessable()->assertJsonValidationErrors('cancellation_reason');
        }
        $this->patchJson("/api/reservation/{$id}/cancel", [])
            ->assertUnprocessable()->assertJsonValidationErrors('cancellation_reason');
        $this->assertDatabaseHas('reservations', [
            'id' => $id, 'status' => ReservationState::REQUESTED->value,
            'cancellation_reason' => null, 'canceled_by_user_id' => null, 'canceled_at' => null,
        ]);
    }

    public function test_lender_reason_is_trimmed_saved_and_visible_to_borrower(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $id = $this->requestReservation($lender, $borrower);
        Sanctum::actingAs($lender);
        $this->patchJson("/api/reservation/{$id}/cancel", [
            'cancellation_reason' => '  Tool needs maintenance.  ',
        ])->assertOk()
          ->assertJsonPath('data.status', ReservationState::CANCELED->value)
          ->assertJsonPath('data.cancellation_reason', 'Tool needs maintenance.')
          ->assertJsonPath('data.canceled_by_user_id', $lender->id);
        $this->assertDatabaseHas('reservations', [
            'id' => $id, 'status' => ReservationState::CANCELED->value,
            'cancellation_reason' => 'Tool needs maintenance.',
            'canceled_by_user_id' => $lender->id,
        ]);
        $this->assertNotNull(DB::table('reservations')->where('id', $id)->value('canceled_at'));
        Sanctum::actingAs($borrower);
        $this->getJson("/api/reservation/{$id}")
            ->assertOk()
            ->assertJsonPath('data.cancellation_reason', 'Tool needs maintenance.');
        $this->getJson('/api/reservation/all')
            ->assertOk()
            ->assertJsonPath('data.0.canceled_by_user_id', $lender->id);
    }

    public function test_borrower_may_cancel_requested_reservation_without_reason(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $id = $this->requestReservation($lender, $borrower);
        // requestReservation authenticates as borrower.
        $this->patchJson("/api/reservation/{$id}/cancel", [])->assertOk()
            ->assertJsonPath('data.cancellation_reason', null)
            ->assertJsonPath('data.canceled_by_user_id', $borrower->id);
        $this->assertDatabaseHas('reservations', [
            'id' => $id, 'status' => ReservationState::CANCELED->value,
            'cancellation_reason' => null, 'canceled_by_user_id' => $borrower->id,
        ]);
    }

    public function test_borrower_may_cancel_approved_reservation_with_reason(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $id = $this->requestReservation($lender, $borrower);
        Sanctum::actingAs($lender);
        $this->patchJson("/api/reservation/{$id}/approve")->assertOk();
        Sanctum::actingAs($borrower);
        $this->patchJson("/api/reservation/{$id}/cancel", [
            'cancellation_reason' => 'Plans changed',
        ])->assertOk()
          ->assertJsonPath('data.cancellation_reason', 'Plans changed')
          ->assertJsonPath('data.status', ReservationState::CANCELED->value);
    }

    public function test_lender_cannot_cancel_approved_reservation_even_with_reason(): void
    {
        $lender = User::factory()->create();
        $id = $this->requestReservation($lender, User::factory()->create());
        Sanctum::actingAs($lender);
        $this->patchJson("/api/reservation/{$id}/approve")->assertOk();
        $this->patchJson("/api/reservation/{$id}/cancel", [
            'cancellation_reason' => 'No longer available',
        ])->assertForbidden();
        $this->assertDatabaseHas('reservations', [
            'id' => $id, 'status' => ReservationState::APPROVED->value, 'canceled_at' => null,
        ]);
    }

    public function test_cannot_cancel_active_or_already_canceled_reservation(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $id = $this->requestReservation($lender, $borrower);
        Sanctum::actingAs($lender);
        $this->patchJson("/api/reservation/{$id}/approve")->assertOk();
        $this->patchJson("/api/loan/{$id}/handoff")->assertOk();
        Sanctum::actingAs($borrower);
        $this->patchJson("/api/reservation/{$id}/cancel", ['cancellation_reason' => 'Changed mind'])
            ->assertStatus(409);
        $this->assertDatabaseHas('reservations', [
            'id' => $id, 'status' => ReservationState::ACTIVE->value, 'canceled_at' => null,
        ]);

        // Verify duplicate cancellation is rejected on a separate requested loan.
        $id2 = $this->requestReservation($lender, $borrower);
        $this->patchJson("/api/reservation/{$id2}/cancel")->assertOk();
        $this->patchJson("/api/reservation/{$id2}/cancel")->assertStatus(409);
    }

    public function test_unrelated_user_cannot_cancel_and_missing_reservation_returns_404(): void
    {
        $lender = User::factory()->create();
        $id = $this->requestReservation($lender, User::factory()->create());
        Sanctum::actingAs(User::factory()->create());
        $this->patchJson("/api/reservation/{$id}/cancel", [
            'cancellation_reason' => 'Unauthorized attempt',
        ])->assertForbidden();
        $this->patchJson('/api/reservation/999999/cancel', [
            'cancellation_reason' => 'Unknown',
        ])->assertNotFound();
        $this->assertDatabaseHas('reservations', [
            'id' => $id, 'status' => ReservationState::REQUESTED->value, 'canceled_at' => null,
        ]);
    }

    public function test_invalid_reason_length_is_rejected(): void
    {
        $lender = User::factory()->create();
        $id = $this->requestReservation($lender, User::factory()->create());
        Sanctum::actingAs($lender);
        $this->patchJson("/api/reservation/{$id}/cancel", [
            'cancellation_reason' => str_repeat('A', 501),
        ])->assertUnprocessable()->assertJsonValidationErrors('cancellation_reason');
        $this->assertDatabaseHas('reservations', [
            'id' => $id, 'status' => ReservationState::REQUESTED->value,
        ]);
    }
}
