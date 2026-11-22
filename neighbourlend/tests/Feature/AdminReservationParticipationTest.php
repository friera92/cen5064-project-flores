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

/** Regression coverage for the administrator's strictly non-participant role. */
class AdminReservationParticipationTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException(
                'AdminReservationParticipationTest requires PostgreSQL neighbourlend_test.'
            );
        }

        $this->refreshIsolatedDatabase();
    }

    private function makeTool(User $lender): Tool
    {
        $categoryId = (int) DB::table('categories')->insertGetId([
            'name' => 'Admin authorization test',
            'description' => 'Role separation regression',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Tool::create([
            'title' => 'Admin role test drill',
            'description' => 'A legitimate lender-owned tool',
            'category_id' => $categoryId,
            'owner_id' => $lender->id,
            'daily_rate' => '15.00',
            'condition' => ToolCondition::GOOD->value,
            'availability_status' => ToolStatus::AVAILABLE->value,
        ]);
    }

    private function requestPayload(Tool $tool): array
    {
        return [
            'tool_id' => $tool->id,
            'start_date' => today()->addDays(10)->toDateString(),
            'end_date' => today()->addDays(11)->toDateString(),
        ];
    }

    public function test_administrator_cannot_request_a_tool(): void
    {
        $lender = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $tool = $this->makeTool($lender);

        Sanctum::actingAs($admin);
        $this->postJson('/api/reservation/new', $this->requestPayload($tool))
            ->assertForbidden();

        $this->assertDatabaseMissing('reservations', [
            'tool_id' => $tool->id,
            'borrower_id' => $admin->id,
        ]);
        $this->assertSame(ToolStatus::AVAILABLE, $tool->fresh()->availability_status);
    }

    public function test_administrator_cannot_view_or_mutate_participant_reservation(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $tool = $this->makeTool($lender);

        Sanctum::actingAs($borrower);
        $id = (int) $this->postJson('/api/reservation/new', $this->requestPayload($tool))
            ->assertCreated()
            ->json('data.id');

        Sanctum::actingAs($admin);
        $this->getJson("/api/reservation/{$id}")->assertForbidden();
        $this->patchJson("/api/reservation/{$id}/approve")->assertForbidden();
        $this->patchJson("/api/reservation/{$id}/cancel")->assertForbidden();
        $this->patchJson("/api/loan/{$id}/handoff")->assertForbidden();
        $this->patchJson("/api/loan/{$id}/return")->assertForbidden();
        $this->patchJson("/api/loan/{$id}/inspect", ['returned_condition' => 'GOOD'])
            ->assertForbidden();
        $this->patchJson("/api/loan/{$id}/close")->assertForbidden();

        $this->assertDatabaseHas('reservations', [
            'id' => $id,
            'borrower_id' => $borrower->id,
            'status' => ReservationState::REQUESTED->value,
            'returned_condition' => null,
        ]);
        $this->assertSame(ToolStatus::AVAILABLE, $tool->fresh()->availability_status);
    }
}
