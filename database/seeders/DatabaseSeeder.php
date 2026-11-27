<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * I due admin (Roberto e Stefania) e, se indicata nel .env, il superadmin. La password iniziale è provvisoria (va cambiata al primo accesso)
     * e si imposta nel file .env con MANAGER_INITIAL_PASSWORD.
     */
    public function run(): void
    {
        $password = env('MANAGER_INITIAL_PASSWORD', 'Cambiami-2026');
        foreach ([
            ['roberto', 'Roberto', 'roberto@effepimaster.it'],
            ['stefania', 'Stefania', 'stefania@effepimaster.it'],
        ] as [$username, $name, $email]) {
            User::query()->firstOrCreate(['username' => $username], [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => User::ROLE_ADMIN,
                'must_change_password' => true,
                'active' => true,
            ]);
        }

        // Superadmin: si crea solo se SUPERADMIN_EMAIL è nel file .env.
        if ($email = env('SUPERADMIN_EMAIL')) {
            User::query()->firstOrCreate(['username' => env('SUPERADMIN_USERNAME', 'laura')], [
                'name' => env('SUPERADMIN_NAME', 'Laura'),
                'email' => $email,
                'password' => $password,
                'role' => User::ROLE_SUPERADMIN,
                'must_change_password' => true,
                'active' => true,
            ]);
        }
    }
}
