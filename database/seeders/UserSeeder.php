<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Test',
            'email' => 'test@indogreen.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password12124'),
            'remember_token' => Str::random(10)
        ]);
    }
    // {
    //     \App\Models\User::factory(5)->create();
    // }
} 