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
class ReservationLenderApiTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException(
                'ReservationLenderApiTest requires PostgreSQL database neighbourlend_test. '
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

    public function test_guest_cannot_access_lender_endpoints(): void
    {
        $this->getJson('/api/reservation/received')->assertUnauthorized();
        $this->patchJson('/api/reservation/1/approve')->assertUnauthorized();
    }

    public function test_received_list_only_contains_reservations_for_owned_tools(): void
    {
        $lender = User::factory()->create();
        $otherLender = User::factory()->create();
        $borrower = User::factory()->create();

        $ownReservation = $this->createReservation($this->makeTool($lender), $borrower);
        $otherReservation = $this->createReservation($this->makeTool($otherLender), $borrower);

        Sanctum::actingAs($lender);
        $response = $this->getJson('/api/reservation/received')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownReservation)
            ->assertJsonPath('data.0.borrower.id', $borrower->id)
            ->assertJsonPath('data.0.borrower.name', $borrower->name);

        $this->assertNotContains(
            $otherReservation,
            array_column($response->json('data'), 'id')
        );
        $this->assertArrayNotHasKey('email', $response->json('data.0.borrower'));

        Sanctum::actingAs($borrower);
        $this->getJson('/api/reservation/received')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_lender_can_view_and_approve_received_request(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $tool = $this->makeTool($lender);
        $id = $this->createReservation($tool, $borrower);

        Sanctum::actingAs($lender);
        $this->getJson("/api/reservation/{$id}")
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.tool.id', $tool->id)
            ->assertJsonPath('data.borrower.name', $borrower->name);

        $this->patchJson("/api/reservation/{$id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', ReservationState::APPROVED->value);

        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::APPROVED->value,
        ]);

        Sanctum::actingAs($borrower);
        $this->getJson("/api/reservation/{$id}")
            ->assertOk()
            ->assertJsonPath('data.status', ReservationState::APPROVED->value);
    }

    public function test_borrower_and_unrelated_user_cannot_approve_or_view_as_lender(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $stranger = User::factory()->create();
        $id = $this->createReservation($this->makeTool($lender), $borrower);

        Sanctum::actingAs($borrower);
        $this->patchJson("/api/reservation/{$id}/approve")->assertForbidden();

        Sanctum::actingAs($stranger);
        $this->getJson("/api/reservation/{$id}")->assertForbidden();
        $this->patchJson("/api/reservation/{$id}/approve")->assertForbidden();
        $this->getJson('/api/reservation/received')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::REQUESTED->value,
        ]);
    }

    public function test_canceled_reservation_cannot_be_approved(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $id = $this->createReservation($this->makeTool($lender), $borrower);

        $this->patchJson("/api/reservation/{$id}/cancel")->assertOk();

        Sanctum::actingAs($lender);
        $this->patchJson("/api/reservation/{$id}/approve")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Invalid reservation transition: CANCELED -> APPROVED');

        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::CANCELED->value,
        ]);
    }

    public function test_approved_reservation_cannot_be_approved_twice(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $id = $this->createReservation($this->makeTool($lender), $borrower);

        Sanctum::actingAs($lender);
        $this->patchJson("/api/reservation/{$id}/approve")->assertOk();
        $this->patchJson("/api/reservation/{$id}/approve")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Invalid reservation transition: APPROVED -> APPROVED');

        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'status' => ReservationState::APPROVED->value,
        ]);
    }

    public function test_nonexistent_reservation_returns_not_found(): void
    {
        $lender = User::factory()->create();
        Sanctum::actingAs($lender);

        $this->getJson('/api/reservation/999999')->assertNotFound();
        $this->patchJson('/api/reservation/999999/approve')->assertNotFound();
    }
}
