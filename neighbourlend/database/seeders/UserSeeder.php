<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('Password123!');

        // 1. Demo admin: never create a second admin or reset an existing admin's password.
        // Include soft-deleted records to preserve the one-admin seed invariant.
        $existingAdmin = User::withTrashed()->where('is_admin', true)->first();
        if ($existingAdmin) {
            $this->command?->info('An administrator already exists. Demo admin creation skipped.');
        } elseif (User::withTrashed()->where('email', 'admin@equipmentshare.local')->exists()) {
            // Do not automatically promote an existing regular account.
            $this->command?->warn('Demo admin email is already taken. No admin was created.');
        } else {
            User::create([
                'email'    => 'admin@equipmentshare.local',
                'name'     => 'System Administrator',
                'password' => $password,
                'is_admin' => true,
                'phone'    => '+15550001',
                'address'  => '100 Admin Plaza, Miami, FL',
            ]);
        }

        // 2. Lender 1
        User::updateOrCreate(
            ['email' => 'carlos@example.com'],
            [
                'name'      => 'Carlos Lender',
                'password'  => $password,
                'is_admin'  => false,
                'phone'     => '+15550002',
                'address'   => '742 Evergreen Terr, Hialeah, FL',
            ]
        );

        // 3. Lender 2
        User::updateOrCreate(
            ['email' => 'maria@example.com'],
            [
                'name'      => 'Maria Lender',
                'password'  => $password,
                'is_admin'  => false,
                'phone'     => '+15550003',
                'address'   => '123 Palm Ave, Miami, FL',
            ]
        );

        // 4. Borrower 1
        User::updateOrCreate(
            ['email' => 'david@example.com'],
            [
                'name'      => 'David Borrower',
                'password'  => $password,
                'is_admin'  => false,
                'phone'     => '+15550004',
                'address'   => '456 Coral Way, Coral Gables, FL',
            ]
        );

        // 5. Borrower 2
        User::updateOrCreate(
            ['email' => 'elena@example.com'],
            [
                'name'      => 'Elena Borrower',
                'password'  => $password,
                'is_admin'  => false,
                'phone'     => '+15550005',
                'address'   => '890 Brickell Ave, Miami, FL',
            ]
        );
    }
}
