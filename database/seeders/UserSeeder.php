<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Test',
                'email' => 'test@indogreen.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password12124'),
                'remember_token' => Str::random(10)
            ],
            [
                'name' => 'Admo',
                'email' => 'admo@indogreen.com',
                'email_verified_at' => now(),
                'password' => Hash::make('Password12124'),
                'remember_token' => Str::random(10)
            ],
            [
                'name' => 'Ujang',
                'email' => 'ujang@indogreen.com',
                'email_verified_at' => now(),
                'password' => Hash::make('Password12124'),
                'remember_token' => Str::random(10)
            ],
            [
                'name' => 'Iwan',
                'email' => 'iwan@indogreen.com',
                'email_verified_at' => now(),
                'password' => Hash::make('Password12124'),
                'remember_token' => Str::random(10)
            ],
            [
                'name' => 'Riza',
                'email' => 'riza@indogreen.com',
                'email_verified_at' => now(),
                'password' => Hash::make('Password12124'),
                'remember_token' => Str::random(10)
            ],        
        ]);

        // \App\Models\User::factory(5)->create();
    }
} 