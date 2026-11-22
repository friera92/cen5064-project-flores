<?php

namespace Database\Seeders;

use App\Domain\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

/**
 * Opt-in administrator provisioning for local development/demo environments.
 * Run only this seeder to avoid modifying regular demo accounts.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // A disabled (soft-deleted) admin also counts: never silently create a replacement.
        $existing = User::withTrashed()->where('is_admin', true)->first();

        if ($existing) {
            if ($existing->trashed()) {
                $this->command?->warn('An administrator exists but is deactivated. No new admin was created; restore the existing account if needed.');
            } else {
                $this->command?->info('Administrator already exists. Nothing was created or changed.');
            }

            return;
        }

        $email = strtolower(trim((string) (getenv('NEIGHBOURLEND_ADMIN_EMAIL')
            ?: $this->command?->ask('Administrator email', 'admin@equipmentshare.local')
            ?: 'admin@equipmentshare.local')));
        $name = trim((string) (getenv('NEIGHBOURLEND_ADMIN_NAME') ?: 'System Administrator'));
        $password = getenv('NEIGHBOURLEND_ADMIN_PASSWORD');
        if ($password === false || $password === '') {
            // The console hides the typed password; do not hard-code admin credentials.
            $password = $this->command?->secret('New admin password (12+ characters, uppercase, lowercase, number, symbol)');
        }

        $data = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()->symbols()],
        ])->validate();

        if (User::withTrashed()->where('email', $data['email'])->exists()) {
            throw new RuntimeException(
                'The selected email already belongs to another user. No permissions were changed; choose a different admin email.'
            );
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => true,
        ]);

        $this->command?->info('Administrator created. Log in through /login with the email and password you supplied.');
    }
}
