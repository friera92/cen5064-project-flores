<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Models\Category;
use App\Domain\Models\Reservation;
use App\Domain\Models\Tool;
use App\Domain\Models\User;
use App\Domain\States\ReservationState;
use App\Domain\States\ToolCondition;
use App\Domain\States\ToolStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class InspectionNotesApiTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException('InspectionNotesApiTest requires isolated neighbourlend_test PostgreSQL.');
        }
        $this->refreshIsolatedDatabase();
    }

    /** @return array{0: User, 1: User, 2: Reservation} */
    private function setupReturnedReservation(): array
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $category = Category::create(['name' => 'Test notes', 'description' => 'Description']);
        $tool = Tool::create([
            'title' => 'Drill',
            'description' => 'Test',
            'category_id' => $category->id,
            'owner_id' => $lender->id,
            'daily_rate' => '10.00',
            'condition' => ToolCondition::GOOD->value,
            'availability_status' => ToolStatus::INSPECTION->value,
        ]);
        $reservation = Reservation::create([
            'tool_id' => $tool->id,
            'borrower_id' => $borrower->id,
            'start_date' => today()->subDays(2)->toDateString(),
            'end_date' => today()->subDay()->toDateString(),
            'status' => ReservationState::RETURNED->value,
            'total_cost' => '20.00',
        ]);
        return [$lender, $borrower, $reservation];
    }

    public function test_inspection_notes_are_saved_and_visible_to_both_parties_and_admin(): void
    {
        [$lender, $borrower, $reservation] = $this->setupReturnedReservation();
        $admin = User::factory()->create(['is_admin' => true]);
        $stranger = User::factory()->create();

        Sanctum::actingAs($lender);
        $this->patchJson("/api/loan/{$reservation->id}/inspect", [
            'returned_condition' => 'DAMAGED',
            'inspection_notes' => '  Scratches near the motor.  ',
        ])->assertOk()
            ->assertJsonPath('data.status', 'DISPUTED')
            ->assertJsonPath('data.inspection_notes', 'Scratches near the motor.');
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'inspection_notes' => 'Scratches near the motor.',
        ]);
        $this->getJson("/api/reservation/{$reservation->id}")->assertOk()
            ->assertJsonPath('data.inspection_notes', 'Scratches near the motor.');

        Sanctum::actingAs($borrower);
        $this->getJson("/api/reservation/{$reservation->id}")->assertOk()
            ->assertJsonPath('data.inspection_notes', 'Scratches near the motor.');
        $this->getJson('/api/reservation/all')->assertOk()
            ->assertJsonPath('data.0.inspection_notes', 'Scratches near the motor.');

        Sanctum::actingAs($stranger);
        $this->getJson("/api/reservation/{$reservation->id}")->assertForbidden();

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/disputes')->assertOk()
            ->assertJsonPath('data.0.inspection_notes', 'Scratches near the motor.');
    }

    public function test_inspection_notes_are_optional_and_checked_for_length(): void
    {
        [$lender, , $reservation] = $this->setupReturnedReservation();
        Sanctum::actingAs($lender);

        $this->patchJson("/api/loan/{$reservation->id}/inspect", [
            'returned_condition' => 'GOOD',
            'inspection_notes' => str_repeat('x', 2001),
        ])->assertUnprocessable()->assertJsonValidationErrors('inspection_notes');
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'returned_condition' => null,
            'inspection_notes' => null,
        ]);

        $this->patchJson("/api/loan/{$reservation->id}/inspect", [
            'returned_condition' => 'GOOD',
        ])->assertOk()
            ->assertJsonPath('data.status', 'RETURNED')
            ->assertJsonPath('data.inspection_notes', null);
    }
}
