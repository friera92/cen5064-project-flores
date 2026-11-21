<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Models\Tool;
use App\Domain\Models\User;
use App\Domain\States\ToolCondition;
use App\Domain\States\ToolStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class ToolOwnerContactApiTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException('ToolOwnerContactApiTest requires isolated neighbourlend_test PostgreSQL.');
        }
        $this->refreshIsolatedDatabase();
    }

    private function createTool(User $owner): Tool
    {
        $categoryId = (int) DB::table('categories')->insertGetId([
            'name' => 'Contact visibility tools',
            'description' => 'Lender contact information tests',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return Tool::create([
            'title' => 'Contact test tool',
            'description' => 'For contact information testing',
            'owner_id' => $owner->id,
            'category_id' => $categoryId,
            'daily_rate' => '10.00',
            'condition' => ToolCondition::GOOD->value,
            'availability_status' => ToolStatus::AVAILABLE->value,
        ]);
    }

    public function test_public_tool_detail_shows_lender_name_and_address_but_not_phone(): void
    {
        $owner = User::factory()->create([
            'name' => 'Sample Lender',
            'phone' => '305-555-0123',
            'address' => 'Miami, FL',
        ]);
        $tool = $this->createTool($owner);

        $this->getJson("/api/tools/{$tool->id}")
            ->assertOk()
            ->assertJsonPath('owner.name', 'Sample Lender')
            ->assertJsonPath('owner.address', 'Miami, FL')
            ->assertJsonMissingPath('owner.phone')
            ->assertJsonMissingPath('owner.email');
    }

    public function test_authenticated_borrower_can_see_lender_phone_without_email(): void
    {
        $owner = User::factory()->create([
            'phone' => '305-555-0123',
        ]);
        $borrower = User::factory()->create();
        $tool = $this->createTool($owner);
        Sanctum::actingAs($borrower);

        $this->getJson("/api/tools/{$tool->id}")
            ->assertOk()
            ->assertJsonPath('owner.name', $owner->name)
            ->assertJsonPath('owner.phone', '305-555-0123')
            ->assertJsonMissingPath('owner.email');
    }

    public function test_missing_lender_phone_is_returned_as_null(): void
    {
        $owner = User::factory()->create(['phone' => null]);
        $tool = $this->createTool($owner);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/tools/{$tool->id}")
            ->assertOk()
            ->assertJsonPath('owner.phone', null);
    }
}
