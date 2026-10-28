<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class StemiUserSeeder extends Seeder
{
    public function run(): void
    {
        if (config('app.env') !== 'local') {
            throw new RuntimeException('Demo accounts may only be seeded in the local environment.');
        }
        $password = config('stemi.demo_password');
        if (!is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set STEMI_DEMO_PASSWORD to a unique password of at least 12 characters.');
        }
        foreach ([
            ['admin@stemi.local', 'Stemi Administrator', 'admin'],
            ['doctor@stemi.local', 'Dr. Sarah Lim', 'doctor'],
            ['consultant@stemi.local', 'Dr. Aisha Rahman', 'consultant'],
        ] as [$email, $name, $role]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name, 'password' => $password,
                'role' => $role, 'status' => 'active',
            ]);
        }
    }
}
