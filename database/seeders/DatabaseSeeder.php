<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo accounts are deliberately opt-in and require STEMI_DEMO_PASSWORD.
        if (config('stemi.demo_password')) {
            $this->call(StemiUserSeeder::class);
        }
    }
}
