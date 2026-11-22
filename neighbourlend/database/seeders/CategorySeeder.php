<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'name' => 'Power Tools', 'description' => 'Drills, saws, sanders, and rotary tools'],
            ['id' => 2, 'name' => 'Lawn & Garden', 'description' => 'Mowers, trimmers, blowers, and chainsaws'],
            ['id' => 3, 'name' => 'Hand Tools', 'description' => 'Wrench sets, socket sets, hammers, and clamps'],
            ['id' => 4, 'name' => 'Heavy Equipment', 'description' => 'Generators, pressure washers, and tillers'],
            ['id' => 5, 'name' => 'Plumbing Tools', 'description' => 'Pipe threaders, drain augers, and crimpers'],
        ];

        foreach ($categories as $cat) {
            DB::table('categories')->updateOrInsert(
                ['id' => $cat['id']],
                [
                    'name'        => $cat['name'],
                    'description' => $cat['description'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
        }

        // PostgreSQL does not advance the identity sequence for explicit IDs.
        // Keep generated IDs above all seeded and user-created categories.
        if (DB::getDriverName() === 'pgsql') {
            DB::selectOne(<<<'SQL'
                SELECT setval(
                    pg_get_serial_sequence('categories', 'id'),
                    COALESCE((SELECT MAX(id) FROM categories), 1),
                    EXISTS (SELECT 1 FROM categories)
                )
                SQL);
        }
    }
}
