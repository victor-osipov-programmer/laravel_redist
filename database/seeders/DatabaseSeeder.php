<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Vi',
            'email' => 'test@gmail.com',
            'password' => 'testtest',
            'balance' => 1000
        ]);
        User::factory()->create([
            'name' => 'Os',
            'email' => 'test2@gmail.com',
            'password' => 'testtest',
            'balance' => 900
        ]);
    }
}
