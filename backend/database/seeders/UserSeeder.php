<?php

namespace Database\Seeders;

use App\User\Application\CreateUser\CreateUser;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed users.
     */
    public function run(): void
    {
        $this->seedInitialAdmin();
    }

    private function seedInitialAdmin(): void
    {
        $email = (string) env('INITIAL_ADMIN_EMAIL', '');
        $password = (string) env('INITIAL_ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            return;
        }

        if (EloquentUser::query()->where('email', $email)->exists()) {
            return;
        }

        app(CreateUser::class)(
            role: 'admin',
            name: (string) env('INITIAL_ADMIN_NAME', 'Admin'),
            email: $email,
            plainPassword: $password,
        );
    }
}
