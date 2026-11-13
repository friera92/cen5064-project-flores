<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Models\Tool;
use App\Domain\Models\User;
use App\Domain\States\ToolCondition;
use App\Domain\States\ToolStatus;
use App\Repositories\ToolRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * PostgreSQL integration tests; intentionally refuse to migrate the dev database.
 */
class ToolSearchBuilderTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException(
                'ToolSearchBuilderTest requires PostgreSQL database neighbourlend_test. '
                . 'Never run migration tests against the development database.'
            );
        }

        $this->refreshIsolatedDatabase();
    }

    private function category(string $name): int
    {
        // Category::fillable has an unrelated typo in the supplied snapshot.
        return (int) DB::table('categories')->insertGetId([
            'name' => $name,
            'description' => 'Test category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function tool(
        User $owner,
        int $categoryId,
        string $title,
        string $price,
        ToolCondition $condition = ToolCondition::GOOD,
        ToolStatus $status = ToolStatus::AVAILABLE
    ): Tool {
        return Tool::create([
            'title' => $title,
            'description' => 'A test tool',
            'category_id' => $categoryId,
            'owner_id' => $owner->id,
            'condition' => $condition->value,
            'availability_status' => $status->value,
            'daily_rate' => $price,
        ]);
    }

    public function test_search_excludes_soft_deleted_tools(): void
    {
        $owner = User::factory()->create();
        $categoryId = $this->category('Power tools');
        $visible = $this->tool($owner, $categoryId, 'Drill', '15.00');
        $deleted = $this->tool($owner, $categoryId, 'Old drill', '8.00');
        $deleted->delete();

        $results = (new ToolRepository())->search([]);

        $this->assertSame(1, $results->total());
        $this->assertSame([$visible->id], $results->getCollection()->modelKeys());
    }

    public function test_search_combines_owner_category_condition_availability_and_price_filters(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $powerTools = $this->category('Power tools');
        $gardenTools = $this->category('Garden tools');

        $match = $this->tool($owner, $powerTools, 'Cordless Drill', '20.00');
        $this->tool($owner, $powerTools, 'Cordless Saw', '35.00');
        $this->tool($owner, $gardenTools, 'Cordless Drill', '20.00');
        $this->tool($other, $powerTools, 'Cordless Drill', '20.00');
        $this->tool($owner, $powerTools, 'Cordless Drill', '20.00', ToolCondition::POOR);
        $this->tool($owner, $powerTools, 'Cordless Drill', '20.00', ToolCondition::GOOD, ToolStatus::IN_USE);

        $results = (new ToolRepository())->search([
            'owner_id' => $owner->id,
            'category_id' => $powerTools,
            'q' => '  CORDLESS dRiLL  ',
            'condition' => ToolCondition::GOOD->value,
            'availability_status' => ToolStatus::AVAILABLE->value,
            'min_rate' => '15.00',
            'max_rate' => '25.00',
        ]);

        $this->assertSame(1, $results->total());
        $this->assertSame($match->id, $results->items()[0]->id);
        $this->assertTrue($results->items()[0]->relationLoaded('category'));
    }

    public function test_rate_filter_is_inclusive_at_both_boundaries(): void
    {
        $owner = User::factory()->create();
        $categoryId = $this->category('Hand tools');
        $min = $this->tool($owner, $categoryId, 'Low', '10.00');
        $max = $this->tool($owner, $categoryId, 'High', '25.00');
        $this->tool($owner, $categoryId, 'Too expensive', '25.01');

        $results = (new ToolRepository())->search([
            'min_rate' => '10.00',
            'max_rate' => '25.00',
        ]);

        $this->assertEqualsCanonicalizing(
            [$min->id, $max->id],
            $results->getCollection()->modelKeys()
        );
    }

    public function test_search_paginates_and_orders_newest_tools_first(): void
    {
        $owner = User::factory()->create();
        $categoryId = $this->category('Safety equipment');
        $old = $this->tool($owner, $categoryId, 'Helmet 1', '3.00');
        $middle = $this->tool($owner, $categoryId, 'Helmet 2', '4.00');
        $new = $this->tool($owner, $categoryId, 'Helmet 3', '5.00');

        $results = (new ToolRepository())->search(['per_page' => 2]);

        $this->assertSame(3, $results->total());
        $this->assertSame(2, $results->perPage());
        $this->assertSame([$new->id, $middle->id], $results->getCollection()->modelKeys());
        $this->assertNotContains($old->id, $results->getCollection()->modelKeys());
    }

    public function test_blank_text_filter_does_not_exclude_valid_tools(): void
    {
        $owner = User::factory()->create();
        $categoryId = $this->category('Camping');
        $tool = $this->tool($owner, $categoryId, 'Tent', '12.00');

        $results = (new ToolRepository())->search(['q' => '  ']);

        $this->assertSame([$tool->id], $results->getCollection()->modelKeys());
    }
}
