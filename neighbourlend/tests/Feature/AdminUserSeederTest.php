<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshIsolatedDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'neighbourlend_test') {
            throw new RuntimeException('AdminUserSeederTest requires isolated neighbourlend_test PostgreSQL.');
        }

        $this->refreshIsolatedDatabase();
    }

    /** Isolate the process environment from the test runner's environment. */
    private function withSeedCredentials(callable $callback): void
    {
        $values = [
            'NEIGHBOURLEND_ADMIN_EMAIL' => 'new-admin@example.test',
            'NEIGHBOURLEND_ADMIN_NAME' => 'Test Administrator',
            'NEIGHBOURLEND_ADMIN_PASSWORD' => 'SecurePassword123!',
        ];
        $previous = [];

        foreach ($values as $key => $value) {
            $previous[$key] = getenv($key);
            putenv("{$key}={$value}");
        }

        try {
            $callback();
        } finally {
            foreach ($previous as $key => $value) {
                putenv($value === false ? $key : "{$key}={$value}");
            }
        }
    }

    public function test_creates_only_one_admin_without_overwriting_it(): void
    {
        $this->withSeedCredentials(function (): void {
            (new AdminUserSeeder())->run();

            $admin = User::query()->where('is_admin', true)->sole();
            $this->assertSame('new-admin@example.test', $admin->email);
            $this->assertTrue(Hash::check('SecurePassword123!', $admin->password));
            $this->assertDatabaseCount('users', 1);

            $admin->update(['name' => 'Existing Admin']);
            putenv('NEIGHBOURLEND_ADMIN_PASSWORD=AnotherPassword123!');
            (new AdminUserSeeder())->run();

            $this->assertDatabaseCount('users', 1);
            $this->assertSame('Existing Admin', $admin->fresh()->name);
            $this->assertTrue(Hash::check('SecurePassword123!', $admin->fresh()->password));
        });
    }

    public function test_existing_admin_is_not_duplicated_even_if_soft_deleted(): void
    {
        $existing = User::factory()->create(['is_admin' => true]);
        $existing->delete();

        (new AdminUserSeeder())->run();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($existing->id, User::withTrashed()->where('is_admin', true)->sole()->id);
    }

    public function test_it_will_not_promote_a_regular_user_with_the_requested_email(): void
    {
        $this->withSeedCredentials(function (): void {
            $regular = User::factory()->create([
                'email' => 'new-admin@example.test',
                'is_admin' => false,
            ]);

            try {
                (new AdminUserSeeder())->run();
                $this->fail('Expected email collision to be rejected.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('already belongs to another user', $exception->getMessage());
            }

            $this->assertFalse((bool) $regular->fresh()->is_admin);
            $this->assertDatabaseCount('users', 1);
        });
    }
}
