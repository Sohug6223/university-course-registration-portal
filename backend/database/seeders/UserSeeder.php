<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Test Student
        User::updateOrCreate(
            ['user_id' => '0272320005101147'],
            [
                'name' => 'Sohug Student',
                'email' => 'sohugmia1234@gmail.com',
                'password' => Hash::make('12345678'),
                'role' => 'student',
            ]
        );

        // Test Admin
        User::updateOrCreate(
            ['user_id' => 'ADMIN001'],
            [
                'name' => 'System Admin',
                'email' => 'admin@cityuniversity.edu',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
            ]
        );
    }
}