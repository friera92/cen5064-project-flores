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

    public function test_lender_cannot_handoff_tool_before_approval(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $tool = $this->makeTool($lender);
        $reservationId = $this->createReservation($tool, $borrower);

        Sanctum::actingAs($lender);
        $this->patchJson("/api/loan/{$reservationId}/handoff")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Invalid reservation transition: REQUESTED -> HANDED_OFF');
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
            ->assertJsonPath('data.status', ReservationState::HANDED_OFF->value);
    }
}
