<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL et ADMIN_PASSWORD ne sont pas definis. Aucun admin cree.');

            return;
        }

        User::updateOrCreate(['email' => $email], [
            'name' => env('ADMIN_NAME', 'Administrateur'),
            'email_verified_at' => now(),
            'account_type' => 'visitor',
            'account_type_selected' => true,
            'is_admin' => true,
            'password' => Hash::make($password),
        ]);
    }
}
