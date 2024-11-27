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
            'phone' => '+7 926 818 86 63',
            'password' => 'testtest',
            'balance' => 1000
        ]);
        User::factory()->create([
            'name' => 'Os',
            'email' => 'test2@gmail.com',
            'phone' => '+7 923 233 14 56',
            'password' => 'testtest',
            'balance' => 900
        ]);
    }
}
