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
        $this->seedUser('Admin', 'admin@example.com', 'admin');
        $this->seedUser('Rafael Magno', 'user1@example.com', 'user');
        $this->seedUser('Ana Souza', 'user2@example.com', 'user');
    }

    private function seedUser(string $name, string $email, string $role): void
    {
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => $role,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );
    }
}
