<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Models\Category;
use App\Domain\Models\Reservation;
use App\Domain\Models\Tool;
use App\Domain\Models\User;
use App\Domain\States\ReservationState;
use App\Domain\States\ToolCondition;
use App\Domain\States\ToolReturnedCondition;
use App\Domain\States\ToolStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class AdminPanelApiTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException('AdminPanelApiTest requires isolated neighbourlend_test PostgreSQL.');
        }
        $this->refreshIsolatedDatabase();
    }

    private function category(): Category
    {
        return Category::create([
            'name' => 'Testing ' . uniqid(),
            'description' => 'Administrative test category',
        ]);
    }

    private function tool(User $owner, Category $category): Tool
    {
        return Tool::create([
            'title' => 'Power drill',
            'description' => 'Test item',
            'category_id' => $category->id,
            'owner_id' => $owner->id,
            'daily_rate' => '12.00',
            'condition' => ToolCondition::GOOD->value,
            'availability_status' => ToolStatus::MAINTENANCE->value,
        ]);
    }

    public function test_admin_endpoints_require_login_and_admin_role(): void
    {
        $this->getJson('/api/admin/categories')->assertUnauthorized();
        $this->postJson('/api/admin/categories', [])->assertUnauthorized();
        $this->getJson('/api/admin/disputes')->assertUnauthorized();

        $category = $this->category();
        Sanctum::actingAs(User::factory()->create(['is_admin' => false]));
        $this->getJson('/api/admin/categories')->assertForbidden();
        $this->postJson('/api/admin/categories', [])->assertForbidden();
        $this->getJson("/api/admin/categories/{$category->id}")->assertForbidden();
        $this->patchJson("/api/admin/categories/{$category->id}", [])->assertForbidden();
        $this->deleteJson("/api/admin/categories/{$category->id}")->assertForbidden();
        $this->getJson('/api/admin/disputes')->assertForbidden();
    }

    public function test_admin_can_create_read_update_and_delete_an_unused_category(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
        $id = $this->postJson('/api/admin/categories', [
            'name' => 'Garden equipment',
            'description' => 'Yard and garden tools',
        ])->assertCreated()->assertJsonPath('data.description', 'Yard and garden tools')->json('data.id');

        $this->getJson('/api/admin/categories')->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.tools_count', 0);
        $this->getJson("/api/admin/categories/{$id}")->assertOk()
            ->assertJsonPath('data.name', 'Garden equipment');
        $this->patchJson("/api/admin/categories/{$id}", [
            'name' => 'Landscaping equipment',
            'description' => 'Updated description',
        ])->assertOk()->assertJsonPath('data.name', 'Landscaping equipment');

        $this->deleteJson("/api/admin/categories/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('categories', ['id' => $id]);
        $this->getJson("/api/admin/categories/{$id}")->assertNotFound();
    }

    public function test_category_validation_rejects_invalid_data_and_duplicate_names(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
        $category = $this->category();

        $this->postJson('/api/admin/categories', [
            'name' => $category->name,
            'description' => 'Duplicate',
        ])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/api/admin/categories', [
            'name' => '',
            'description' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'description']);
        $this->patchJson("/api/admin/categories/{$category->id}", [
            'name' => str_repeat('x', 101),
        ])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->patchJson("/api/admin/categories/{$category->id}", [
            'description' => str_repeat('x', 256),
        ])->assertUnprocessable()->assertJsonValidationErrors('description');
    }

    public function test_categories_used_by_active_or_soft_deleted_tools_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
        $owner = User::factory()->create();
        $category = $this->category();
        $tool = $this->tool($owner, $category);

        $this->deleteJson("/api/admin/categories/{$category->id}")
            ->assertStatus(409);
        $tool->delete();
        $this->deleteJson("/api/admin/categories/{$category->id}")
            ->assertStatus(409);
        $this->getJson('/api/admin/categories')->assertOk()
            ->assertJsonPath('data.0.tools_count', 1);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_admin_disputes_are_global_searchable_filterable_and_include_inspection_notes(): void
    {
        $lender = User::factory()->create(['name' => 'Olivia Lender']);
        $borrower = User::factory()->create(['name' => 'Marcus Borrower']);
        $category = $this->category();
        $tool = $this->tool($lender, $category);
        $dispute = Reservation::create([
            'tool_id' => $tool->id,
            'borrower_id' => $borrower->id,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-03',
            'status' => ReservationState::DISPUTED->value,
            'returned_condition' => ToolReturnedCondition::DAMAGED->value,
            'inspection_notes' => 'Broken handle after return',
            'total_cost' => '36.00',
        ]);
        Reservation::create([
            'tool_id' => $tool->id,
            'borrower_id' => $borrower->id,
            'start_date' => '2026-12-05',
            'end_date' => '2026-12-06',
            'status' => ReservationState::CLOSED->value,
            'returned_condition' => ToolReturnedCondition::GOOD->value,
            'total_cost' => '24.00',
        ]);

        // Deleted listings must still be visible in historical disputes.
        $tool->delete();
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
        $this->getJson('/api/admin/disputes')->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $dispute->id)
            ->assertJsonPath('data.0.tool.title', 'Power drill')
            ->assertJsonPath('data.0.tool.owner.name', 'Olivia Lender')
            ->assertJsonPath('data.0.borrower.name', 'Marcus Borrower')
            ->assertJsonPath('data.0.inspection_notes', 'Broken handle after return');
        $this->getJson('/api/admin/disputes?q=Marcus&condition=DAMAGED&from=2026-12-01&to=2026-12-03')
            ->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/admin/disputes?q=Olivia')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/admin/disputes?q=999999')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/admin/disputes?to=2026-12-03')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/admin/disputes?condition=UNKNOWN')
            ->assertUnprocessable()->assertJsonValidationErrors('condition');
        $this->getJson('/api/admin/disputes?from=2026-12-05&to=2026-12-01')
            ->assertUnprocessable()->assertJsonValidationErrors('to');
    }
}
