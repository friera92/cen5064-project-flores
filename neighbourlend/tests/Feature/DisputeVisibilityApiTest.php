<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Models\Reservation;
use App\Domain\Models\Tool;
use App\Domain\Models\User;
use App\Domain\States\ReservationState;
use App\Domain\States\ToolCondition;
use App\Domain\States\ToolReturnedCondition;
use App\Domain\States\ToolStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class DisputeVisibilityApiTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException('DisputeVisibilityApiTest requires isolated neighbourlend_test PostgreSQL.');
        }
        $this->refreshIsolatedDatabase();
    }

    public function test_disputed_reservation_is_visible_to_each_participant_but_not_other_users(): void
    {
        $owner = User::factory()->create();
        $borrower = User::factory()->create();
        $stranger = User::factory()->create();
        $categoryId = (int) DB::table('categories')->insertGetId([
            'name' => 'Dispute testing',
            'description' => 'Dispute visibility test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tool = Tool::create([
            'title' => 'Disputed test equipment',
            'description' => 'Equipment with a reported issue',
            'category_id' => $categoryId,
            'owner_id' => $owner->id,
            'daily_rate' => '20.00',
            'condition' => ToolCondition::GOOD->value,
            'availability_status' => ToolStatus::MAINTENANCE->value,
        ]);
        $reservation = Reservation::create([
            'tool_id' => $tool->id,
            'borrower_id' => $borrower->id,
            'start_date' => today()->addDays(10)->toDateString(),
            'end_date' => today()->addDays(12)->toDateString(),
            'status' => ReservationState::DISPUTED->value,
            'returned_condition' => ToolReturnedCondition::DAMAGED->value,
            'total_cost' => '60.00',
        ]);

        Sanctum::actingAs($owner);
        $this->getJson('/api/reservation/received')->assertOk()
            ->assertJsonPath('data.0.id', $reservation->id)
            ->assertJsonPath('data.0.status', ReservationState::DISPUTED->value);
        $this->getJson('/api/reservation/all')->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($borrower);
        $this->getJson('/api/reservation/all')->assertOk()
            ->assertJsonPath('data.0.id', $reservation->id)
            ->assertJsonPath('data.0.returned_condition', ToolReturnedCondition::DAMAGED->value);
        $this->getJson('/api/reservation/received')->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($stranger);
        $this->getJson('/api/reservation/all')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/reservation/received')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/reservation/{$reservation->id}")->assertForbidden();
    }
}
