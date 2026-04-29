<?php

namespace Database\Seeders;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        EloquentUser::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
