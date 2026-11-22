<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class CategorySeederSequenceTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException('CategorySeederSequenceTest requires isolated neighbourlend_test PostgreSQL.');
        }

        $this->refreshIsolatedDatabase();
    }

    public function test_explicit_seed_ids_do_not_block_category_creation(): void
    {
        $this->seed(CategorySeeder::class);

        $id = $this->insertCategory('New category');

        $this->assertGreaterThan(5, $id);
        $this->assertDatabaseHas('categories', [
            'id' => $id,
            'name' => 'New category',
        ]);
    }

    public function test_reseeding_does_not_rewind_sequence_after_admin_creates_category(): void
    {
        $this->seed(CategorySeeder::class);
        $firstId = $this->insertCategory('First custom category');

        $this->seed(CategorySeeder::class);
        $secondId = $this->insertCategory('Second custom category');

        $this->assertGreaterThan($firstId, $secondId);
    }

    private function insertCategory(string $name): int
    {
        return (int) DB::table('categories')->insertGetId([
            'name' => $name,
            'description' => 'Sequence regression test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
