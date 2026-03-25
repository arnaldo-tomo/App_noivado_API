<?php

namespace Database\Seeders;

use App\Models\User;
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
        User::create([
            'name' => 'Arnaldo',
            'email' => 'admin@undangan.local',
            'password' => bcrypt('password'),
            'access_key' => \Illuminate\Support\Str::random(64),
            'tz' => 'Africa/Maputo',
        ]);
    }
}
