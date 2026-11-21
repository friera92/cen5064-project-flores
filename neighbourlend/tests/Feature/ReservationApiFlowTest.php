<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Models\Tool;
use App\Domain\Models\User;
use App\Domain\States\ReservationState;
use App\Domain\States\ToolCondition;
use App\Domain\States\ToolStatus;
use App\Services\LoanLifecycleService;
use App\Services\ReservationService;
use DomainException;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * HTTP feature tests backed by the isolated PostgreSQL neighbourlend_test DB.
 * No Nuxt app or running HTTP server is needed: Laravel dispatches requests in-process.
 */
class ReservationApiFlowTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException(
                'ReservationApiFlowTest requires PostgreSQL database neighbourlend_test. '
                . 'Never run migration tests against the development database.'
            );
        }

        $this->refreshIsolatedDatabase();
    }

    /** @return array{tool_id: int, start_date: string, end_date: string} */
    private function payload(Tool $tool): array
    {
        return [
            'tool_id' => $tool->id,
            'start_date' => today()->addDays(10)->toDateString(),
            'end_date' => today()->addDays(12)->toDateString(),
        ];
    }

    private function makeTool(User $owner): Tool
    {
        // The current Category model has a misspelled fillable field; use query builder.
        $categoryId = (int) DB::table('categories')->insertGetId([
            'name' => 'Test Power Tools',
            'description' => 'Category used by reservation tests',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Tool::create([
            'title' => 'Test Cordless Drill',
            'description' => 'Available for reservation test scenarios',
            'category_id' => $categoryId,
            'owner_id' => $owner->id,
            'daily_rate' => '12.50',
            'condition' => ToolCondition::GOOD->value,
            'availability_status' => ToolStatus::AVAILABLE->value,
        ]);
    }

    public function test_guest_cannot_access_reservation_api(): void
    {
        $this->postJson('/api/reservation/quote', [])->assertUnauthorized();
        $this->postJson('/api/reservation/new', [])->assertUnauthorized();
        $this->getJson('/api/reservation/all')->assertUnauthorized();
    }

    public function test_borrower_can_quote_request_view_list_and_cancel_via_api(): void
    {
        config()->set('reservations.fee_rate', 0.0);

        $owner = User::factory()->create();
        $borrower = User::factory()->create();
        $tool = $this->makeTool($owner);
        $payload = $this->payload($tool);

        Sanctum::actingAs($borrower);

        $quote = $this->postJson('/api/reservation/quote', $payload)
            ->assertOk()
            ->assertJsonPath('data.days', 3);

        $this->assertEquals(37.50, $quote->json('data.total_cost'));

        $created = $this->postJson('/api/reservation/new', $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', ReservationState::REQUESTED->value)
            ->assertJsonPath('data.borrower_id', $borrower->id)
            ->assertJsonPath('data.tool_id', $tool->id);

        $reservationId = (int) $created->json('data.id');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservationId,
            'status' => ReservationState::REQUESTED->value,
            'tool_id' => $tool->id,
            'borrower_id' => $borrower->id,
            'total_cost' => '37.50',
        ]);

        $this->getJson("/api/reservation/{$reservationId}")
            ->assertOk()
            ->assertJsonPath('data.id', $reservationId)
            ->assertJsonPath('data.tool.id', $tool->id);

        $this->getJson('/api/reservation/all')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reservationId);

        $this->patchJson("/api/reservation/{$reservationId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', ReservationState::CANCELED->value);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservationId,
            'status' => ReservationState::CANCELED->value,
        ]);
    }

    public function test_other_user_cannot_view_borrowers_reservation(): void
    {
        $owner = User::factory()->create();
        $borrower = User::factory()->create();
        $stranger = User::factory()->create();
        $tool = $this->makeTool($owner);

        Sanctum::actingAs($borrower);
        $created = $this->postJson('/api/reservation/new', $this->payload($tool))
            ->assertCreated();

        $reservationId = (int) $created->json('data.id');

        Sanctum::actingAs($stranger);
        $this->getJson("/api/reservation/{$reservationId}")->assertForbidden();
    }

    public function test_reservation_api_rejects_invalid_dates(): void
    {
        $owner = User::factory()->create();
        $borrower = User::factory()->create();
        $tool = $this->makeTool($owner);

        Sanctum::actingAs($borrower);

        $this->postJson('/api/reservation/new', [
            'tool_id' => $tool->id,
            'start_date' => today()->subDays(2)->toDateString(),
            'end_date' => today()->addDays(2)->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('start_date');

        $this->postJson('/api/reservation/new', [
            'tool_id' => $tool->id,
            'start_date' => today()->addDays(12)->toDateString(),
            'end_date' => today()->addDays(10)->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('end_date');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_overlapping_booking_is_blocked_without_creating_another_record(): void
    {
        $owner = User::factory()->create();
        $borrower = User::factory()->create();
        $otherBorrower = User::factory()->create();
        $tool = $this->makeTool($owner);
        $payload = $this->payload($tool);

        Sanctum::actingAs($borrower);
        $this->postJson('/api/reservation/new', $payload)->assertCreated();

        Sanctum::actingAs($otherBorrower);

        // The controller now translates domain booking conflicts to HTTP 409.
        $this->postJson('/api/reservation/new', $payload)
            ->assertStatus(409)
            ->assertJsonPath('message', 'The tool is already booked or requested for the selected dates.');

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_canceled_reservation_does_not_block_another_request_for_same_dates(): void
    {
        $owner = User::factory()->create();
        $borrower = User::factory()->create();
        $otherBorrower = User::factory()->create();
        $tool = $this->makeTool($owner);
        $payload = $this->payload($tool);

        Sanctum::actingAs($borrower);
        $created = $this->postJson('/api/reservation/new', $payload)->assertCreated();
        $reservationId = (int) $created->json('data.id');

        $this->patchJson("/api/reservation/{$reservationId}/cancel")->assertOk();

        Sanctum::actingAs($otherBorrower);
        $this->postJson('/api/reservation/new', $payload)
            ->assertCreated()
            ->assertJsonPath('data.borrower_id', $otherBorrower->id);

        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_full_happy_path_from_api_request_through_domain_services(): void
    {
        $owner = User::factory()->create();
        $borrower = User::factory()->create();
        $tool = $this->makeTool($owner);

        Sanctum::actingAs($borrower);
        $created = $this->postJson('/api/reservation/new', $this->payload($tool))
            ->assertCreated();
        $reservationId = (int) $created->json('data.id');

        // These five operations are currently implemented only in services,
        // not in routes/api.php. Do not pretend they were exercised via HTTP.
        $reservations = app(ReservationService::class);
        $lifecycle = app(LoanLifecycleService::class);

        $approved = $reservations->approveReservation($reservationId, $owner->id);
        $this->assertSame(ReservationState::APPROVED, $approved->status);

        $active = $lifecycle->handOffTool($reservationId, $owner->id);
        $this->assertSame(ReservationState::ACTIVE, $active->status);
        $this->assertSame(ToolStatus::IN_USE, $tool->fresh()->availability_status);

        $returned = $lifecycle->returnTool($reservationId, $borrower->id);
        $this->assertSame(ReservationState::RETURNED, $returned->status);
        $this->assertSame(ToolStatus::INSPECTION, $tool->fresh()->availability_status);

        $inspected = $lifecycle->inspectTool($reservationId, 'GOOD', $owner->id);
        $this->assertSame(ReservationState::RETURNED, $inspected->status);

        $closed = $lifecycle->closeReservation($reservationId, $owner->id);
        $this->assertSame(ReservationState::CLOSED, $closed->status);
        $this->assertSame(ToolStatus::AVAILABLE, $tool->fresh()->availability_status);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservationId,
            'status' => ReservationState::CLOSED->value,
            'returned_condition' => 'GOOD',
        ]);
    }

    public function test_canceled_reservation_cannot_be_approved_by_service(): void
    {
        $owner = User::factory()->create();
        $borrower = User::factory()->create();
        $tool = $this->makeTool($owner);

        Sanctum::actingAs($borrower);
        $created = $this->postJson('/api/reservation/new', $this->payload($tool))
            ->assertCreated();
        $reservationId = (int) $created->json('data.id');
        $this->patchJson("/api/reservation/{$reservationId}/cancel")->assertOk();

        try {
            app(ReservationService::class)->approveReservation($reservationId, $owner->id);
            $this->fail('The service allowed a canceled reservation to be approved.');
        } catch (DomainException $e) {
            $this->assertStringContainsString(
                'CANCELED -> APPROVED',
                $e->getMessage()
            );
        }

        $this->assertDatabaseHas('reservations', [
            'id' => $reservationId,
            'status' => ReservationState::CANCELED->value,
        ]);
    }

    public function test_owner_cannot_reserve_own_tool(): void
    {
        $owner = User::factory()->create();
        $tool = $this->makeTool($owner);

        Sanctum::actingAs($owner);

        $this->postJson(
            '/api/reservation/new',
            $this->payload($tool)
        )->assertForbidden();

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_owner_info_on_reservations(): void
    {
        $owner = User::factory()->create();
        $borrower = User::factory()->create();
        $stranger = User::factory()->create();
        $tool = $this->makeTool($owner);

        // Borrower can view lender contact information.
        Sanctum::actingAs($borrower);
        $reservation = $this->postJson('/api/reservation/new', $this->payload($tool))
            ->assertCreated();
        $reservationId = (int) $reservation->json('data.id');

        $this->getJson("/api/reservation/{$reservationId}")
            ->assertOk()
            ->assertJsonPath('data.tool.owner.name', $owner->name)
            ->assertJsonPath('data.tool.owner.phone', $owner->phone)
            ->assertJsonPath('data.tool.owner.address', $owner->address)
            ->assertJsonMissingPath('data.tool.owner.email');

        // Lender does not receive owner contact details.
        Sanctum::actingAs($owner);

        $this->getJson("/api/reservation/{$reservationId}")
            ->assertOk()
            ->assertJsonMissingPath('data.tool.owner');

        // An unrelated user cannot view the reservation.
        Sanctum::actingAs($stranger);

        $this->getJson("/api/reservation/{$reservationId}")
            ->assertForbidden();
    }
}
