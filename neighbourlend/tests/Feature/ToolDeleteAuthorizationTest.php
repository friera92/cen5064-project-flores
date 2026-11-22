<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Models\Reservation;
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

/**
 * API authorization tests backed only by the isolated PostgreSQL test database.
 */
class ToolDeleteAuthorizationTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException(
                'ToolDeleteAuthorizationTest requires PostgreSQL database neighbourlend_test. '
                . 'Never run migration tests against the development database.'
            );
        }

        $this->refreshIsolatedDatabase();
    }

    private function makeTool(User $owner): Tool
    {
        // Use query builder because Category currently has a misspelled fillable field.
        $categoryId = (int) DB::table('categories')->insertGetId([
            'name' => 'Authorization test category',
            'description' => 'Tools for delete authorization tests',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Tool::create([
            'title' => 'Test Cordless Drill',
            'description' => 'Tool for authorization tests',
            'category_id' => $categoryId,
            'owner_id' => $owner->id,
            'daily_rate' => '15.00',
            'condition' => ToolCondition::GOOD->value,
            'availability_status' => ToolStatus::AVAILABLE->value,
        ]);
    }

    public function test_guest_cannot_delete_tool(): void
    {
        $owner = User::factory()->create();
        $tool = $this->makeTool($owner);

        $this->deleteJson("/api/tools/{$tool->id}")->assertUnauthorized();
        $this->assertNull($tool->fresh()->deleted_at);
    }

    public function test_owner_can_soft_delete_own_tool(): void
    {
        $owner = User::factory()->create();
        $tool = $this->makeTool($owner);

        Sanctum::actingAs($owner);
        $this->deleteJson("/api/tools/{$tool->id}")->assertNoContent();

        $this->assertSoftDeleted('tools', ['id' => $tool->id]);
        $this->assertDatabaseHas('tools', ['id' => $tool->id]);
    }

    public function test_unrelated_user_cannot_delete_tool(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $tool = $this->makeTool($owner);

        Sanctum::actingAs($otherUser);
        $this->deleteJson("/api/tools/{$tool->id}")->assertForbidden();

        $this->assertNull($tool->fresh()->deleted_at);
    }

    public function test_admin_cannot_delete_another_users_tool(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $tool = $this->makeTool($owner);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/tools/{$tool->id}")
            ->assertForbidden();

        // The tool must remain in the database.
        $this->assertDatabaseHas('tools', [
            'id' => $tool->id,
            'owner_id' => $owner->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_cannot_delete_tool_with_active_reservation(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $borrower = User::factory()->create();
        $tool = $this->makeTool($owner);

        $reservation = Reservation::create([
            'tool_id' => $tool->id,
            'borrower_id' => $borrower->id,
            'start_date' => today()->addDays(10)->toDateString(),
            'end_date' => today()->addDays(11)->toDateString(),
            'status' => ReservationState::ACTIVE->value,
            'total_cost' => '30.00',
        ]);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/tools/{$tool->id}")
            ->assertForbidden();

        // The tool must not be deleted.
        $this->assertNull($tool->fresh()->deleted_at);

        // The existing reservation must remain unchanged.
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'tool_id' => $tool->id,
            'status' => ReservationState::ACTIVE->value,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_cannot_update_another_users_tool(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $tool = $this->makeTool($owner);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/tools/{$tool->id}", [
            'title' => 'Unauthorized title change',
        ])->assertForbidden();

        $this->assertSame('Test Cordless Drill', $tool->fresh()->title);
    }
}
